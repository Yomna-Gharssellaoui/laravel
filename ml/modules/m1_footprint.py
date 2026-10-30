"""M1 — Produits & Empreinte : estimer l'empreinte d'un produit qui n'a pas encore été mesuré.

Régression multi-sorties (Random Forest) : à partir de la catégorie, du mode de culture du
producteur, du type de transformation, de la distance parcourue et de la certification bio,
le modèle prédit les émissions de CO₂ (kg CO₂e/kg), la consommation d'eau (L/kg) et
l'occupation des sols (m²/kg). L'écart entre les arbres de la forêt donne une marge d'incertitude.

Données : jeu synthétique construit à partir d'ordres de grandeur réalistes par catégorie
(voir CATEGORY_BASE), modulés par le mode de culture, la transformation et le transport,
avec un bruit log-normal. Le jeu est sauvegardé dans data/m1_footprint.csv.
"""

from __future__ import annotations

import numpy as np
import pandas as pd
from sklearn.compose import ColumnTransformer
from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_absolute_error, r2_score
from sklearn.model_selection import train_test_split
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import OneHotEncoder

NAME = "m1_footprint"
MODULE = "M1"
TITLE = "Estimation de l'empreinte environnementale"
ALGORITHM = "Random Forest (régression multi-sorties, 200 arbres)"
DESCRIPTION = "Prédit CO₂, eau et sols d'un produit à partir de sa catégorie, de son mode de culture, de sa transformation et de son transport."

TARGETS = ["co2_emissions", "water_usage", "land_usage"]
CATEGORICAL = ["category", "farming_method", "transformation_type"]
NUMERIC = ["distance_km", "organic_certified"]

# Typical footprint per kg of product: (kg CO2e, L of water, m² of land)
CATEGORY_BASE = {
    "huiles": (3.0, 250, 4.0),
    "fruits secs": (1.2, 300, 2.5),
    "fruits": (0.6, 120, 0.8),
    "légumes": (0.5, 80, 0.5),
    "condiments": (1.8, 150, 1.0),
    "céréales": (1.1, 200, 2.0),
    "épicerie sucrée": (1.0, 100, 1.0),
    "épicerie": (1.5, 120, 1.2),
    "produits laitiers": (3.2, 400, 4.5),
    "boissons": (0.7, 160, 0.6),
    "conserves": (1.6, 140, 0.9),
}

# Multipliers (co2, water, land) by farming method
FARMING = {
    "biologique": (0.85, 0.90, 1.25),
    "permaculture": (0.75, 0.80, 1.10),
    "raisonnée": (0.95, 0.95, 1.05),
    "conventionnelle": (1.00, 1.00, 1.00),
}

# Added by the processing step (co2, water)
TRANSFORMATION = {
    "aucune": (0.0, 0),
    "pressage à froid": (0.4, 20),
    "conserverie": (0.8, 40),
    "meunerie": (0.3, 10),
    "séchage solaire": (0.1, 5),
    "laiterie": (0.9, 60),
    "autre": (0.5, 25),
}


def make_dataset(n: int = 3000, seed: int = 42) -> pd.DataFrame:
    rng = np.random.default_rng(seed)
    categories = list(CATEGORY_BASE)
    rows = []
    for _ in range(n):
        category = rng.choice(categories)
        farming = rng.choice(list(FARMING), p=[0.25, 0.10, 0.30, 0.35])
        transformation = rng.choice(list(TRANSFORMATION))
        distance = float(rng.gamma(2.0, 150.0))  # mostly < 600 km, a few long imports
        organic = int(rng.random() < (0.8 if farming in ("biologique", "permaculture") else 0.05))

        co2, water, land = CATEGORY_BASE[category]
        m_co2, m_water, m_land = FARMING[farming]
        t_co2, t_water = TRANSFORMATION[transformation]

        co2 = co2 * m_co2 + t_co2 + distance * 0.00015
        water = water * m_water + t_water
        land = land * m_land

        noise = rng.lognormal(0, 0.15, size=3)
        rows.append({
            "category": category,
            "farming_method": farming,
            "transformation_type": transformation,
            "distance_km": round(distance, 1),
            "organic_certified": organic,
            "co2_emissions": round(co2 * noise[0], 3),
            "water_usage": round(water * noise[1], 1),
            "land_usage": round(land * noise[2], 3),
        })
    return pd.DataFrame(rows)


def _pipeline(seed: int) -> Pipeline:
    return Pipeline([
        ("prep", ColumnTransformer([
            ("cat", OneHotEncoder(handle_unknown="ignore"), CATEGORICAL),
            ("num", "passthrough", NUMERIC),
        ])),
        ("forest", RandomForestRegressor(n_estimators=200, min_samples_leaf=3, random_state=seed, n_jobs=-1)),
    ])


def train(df: pd.DataFrame, seed: int = 42):
    X, y = df[CATEGORICAL + NUMERIC], np.log1p(df[TARGETS])
    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=seed)

    model = _pipeline(seed).fit(X_train, y_train)
    pred = np.expm1(model.predict(X_test))
    truth = np.expm1(y_test.to_numpy())

    metrics = {}
    for i, target in enumerate(TARGETS):
        metrics[f"r2_{target}"] = round(float(r2_score(truth[:, i], pred[:, i])), 3)
        metrics[f"mae_{target}"] = round(float(mean_absolute_error(truth[:, i], pred[:, i])), 3)

    # Final model trained on all the data
    final = _pipeline(seed).fit(X, y)
    return {"model": final}, metrics


def predict(bundle, payload: dict) -> dict:
    model: Pipeline = bundle["model"]
    row = pd.DataFrame([{
        "category": payload.get("category") or "épicerie",
        "farming_method": payload.get("farming_method") or "conventionnelle",
        "transformation_type": payload.get("transformation_type") or "aucune",
        "distance_km": float(payload.get("distance_km") or 0),
        "organic_certified": int(bool(payload.get("organic_certified"))),
    }])

    # Each tree gives its own estimate: their spread is the uncertainty
    features = model.named_steps["prep"].transform(row)
    per_tree = np.expm1(np.array([tree.predict(features)[0] for tree in model.named_steps["forest"].estimators_]))
    mean, low, high = per_tree.mean(axis=0), np.percentile(per_tree, 10, axis=0), np.percentile(per_tree, 90, axis=0)

    return {
        target: {"value": round(float(mean[i]), 2), "low": round(float(low[i]), 2), "high": round(float(high[i]), 2)}
        for i, target in enumerate(TARGETS)
    }
