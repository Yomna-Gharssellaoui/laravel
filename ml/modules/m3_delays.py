"""M3 — Logistique & Distributeurs : prédire le risque de retard d'une livraison en cours.

Classification binaire (Gradient Boosting) : à partir de la distance, du nombre d'étapes, de
l'étape actuelle, du type de distributeur, de la catégorie du produit (périssable ou non), du
nombre de jours déjà passés en transit et du mois, le modèle donne la probabilité que la
livraison soit en retard.

Données : livraisons simulées dont le retard suit une loi logistique connue (longues distances,
produits frais transportés sans froid, été, livraisons qui traînent… augmentent le risque), avec du
hasard : le modèle doit retrouver ces tendances sans les connaître.
"""

from __future__ import annotations

import numpy as np
import pandas as pd
from sklearn.compose import ColumnTransformer
from sklearn.ensemble import GradientBoostingClassifier
from sklearn.metrics import accuracy_score, f1_score, roc_auc_score
from sklearn.model_selection import train_test_split
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import OneHotEncoder

NAME = "m3_delays"
MODULE = "M3"
TITLE = "Risque de retard des livraisons"
ALGORITHM = "Gradient Boosting (classification binaire)"
DESCRIPTION = "Estime la probabilité qu'une livraison en cours arrive en retard."

CATEGORICAL = ["current_stage", "distributor_type", "category"]
NUMERIC = ["distance_km", "n_steps", "days_in_transit", "month"]

STAGES = ["production", "transformation", "stockage", "transport", "distribution", "vente"]
DISTRIBUTOR_TYPES = ["grossiste", "transport frigorifique", "transport routier", "détaillant", "autre"]
PERISHABLE = {"produits laitiers", "fruits", "légumes", "boissons"}
CATEGORIES = ["huiles", "fruits secs", "fruits", "légumes", "condiments", "céréales", "épicerie sucrée",
              "épicerie", "produits laitiers", "boissons", "conserves"]
STAGE_EFFECT = {"production": -0.4, "transformation": -0.2, "stockage": 0.3, "transport": 0.6, "distribution": 0.1, "vente": -1.0}


def make_dataset(n: int = 4000, seed: int = 42) -> pd.DataFrame:
    rng = np.random.default_rng(seed)
    rows = []
    for _ in range(n):
        category = rng.choice(CATEGORIES)
        distributor = rng.choice(DISTRIBUTOR_TYPES, p=[0.35, 0.2, 0.25, 0.1, 0.1])
        stage = rng.choice(STAGES, p=[0.1, 0.1, 0.2, 0.35, 0.2, 0.05])
        distance = float(rng.gamma(2.0, 180.0))
        n_steps = int(rng.integers(1, 7))
        days = float(rng.gamma(2.0, 1.5))
        month = int(rng.integers(1, 13))

        logit = -3.0
        logit += 0.0016 * distance
        logit += 0.32 * days
        logit -= 0.12 * n_steps  # well-documented journeys are better followed
        logit += STAGE_EFFECT[stage]
        if category in PERISHABLE and distributor != "transport frigorifique":
            logit += 1.3
        if distributor == "transport frigorifique":
            logit -= 0.4
        if month in (7, 8):
            logit += 0.5  # summer heat and holidays
        delayed = int(rng.random() < 1 / (1 + np.exp(-logit)))

        rows.append({
            "distance_km": round(distance, 1), "n_steps": n_steps, "current_stage": stage,
            "distributor_type": distributor, "category": category, "days_in_transit": round(days, 1),
            "month": month, "delayed": delayed,
        })
    return pd.DataFrame(rows)


def _pipeline(seed: int) -> Pipeline:
    return Pipeline([
        ("prep", ColumnTransformer([
            ("cat", OneHotEncoder(handle_unknown="ignore"), CATEGORICAL),
            ("num", "passthrough", NUMERIC),
        ])),
        ("gb", GradientBoostingClassifier(n_estimators=200, max_depth=3, learning_rate=0.05, random_state=seed)),
    ])


def train(df: pd.DataFrame, seed: int = 42):
    X, y = df[CATEGORICAL + NUMERIC], df["delayed"]
    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=seed, stratify=y)

    model = _pipeline(seed).fit(X_train, y_train)
    proba = model.predict_proba(X_test)[:, 1]
    pred = (proba >= 0.5).astype(int)
    metrics = {
        "roc_auc": round(float(roc_auc_score(y_test, proba)), 3),
        "accuracy": round(float(accuracy_score(y_test, pred)), 3),
        "f1": round(float(f1_score(y_test, pred)), 3),
        "delay_rate": round(float(y.mean()), 3),
    }
    return {"model": _pipeline(seed).fit(X, y)}, metrics


def predict(bundle, payload: dict) -> dict:
    row = pd.DataFrame([{
        "current_stage": payload.get("current_stage") or "transport",
        "distributor_type": payload.get("distributor_type") or "autre",
        "category": payload.get("category") or "épicerie",
        "distance_km": float(payload.get("distance_km") or 0),
        "n_steps": int(payload.get("n_steps") or 1),
        "days_in_transit": float(payload.get("days_in_transit") or 0),
        "month": int(payload.get("month") or 1),
    }])
    risk = float(bundle["model"].predict_proba(row)[0, 1])
    level = "élevé" if risk >= 0.6 else ("moyen" if risk >= 0.3 else "faible")
    return {"risk": round(risk * 100, 1), "level": level}
