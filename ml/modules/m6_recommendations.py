"""M6 — Consommateurs : recommandation par filtrage collaboratif.

Filtrage collaboratif « utilisateur-utilisateur » (k plus proches voisins, similarité cosinus).
Chaque consommateur est décrit par ses préférences (bio, local, faible CO₂, faible eau), ses
allergies et les notes qu'il a déjà données. Pour un consommateur, on cherche les 25 profils les
plus proches et on prédit sa note pour chaque produit qu'il n'a pas encore noté par la moyenne
pondérée des notes de ces voisins.

Données : 2 000 consommateurs simulés notant le catalogue réel de Trace Verte (CATALOGUE, mêmes
SKU que le jeu de démonstration Laravel). Leurs notes suivent des goûts cachés (préférences,
allergies, qualité propre de chaque produit) + du hasard. Évaluation : on cache 20 % des notes
de consommateurs de test et on compare l'erreur (RMSE) du modèle à celle d'une base naïve
(moyenne du produit).
"""

from __future__ import annotations

import numpy as np
import pandas as pd
from sklearn.neighbors import NearestNeighbors

NAME = "m6_recommendations"
MODULE = "M6"
TITLE = "Recommandation par filtrage collaboratif"
ALGORITHM = "k plus proches voisins (k = 25, cosinus) sur profils et notes"
DESCRIPTION = "Prédit la note qu'un consommateur donnerait à chaque produit, d'après les consommateurs qui lui ressemblent."

PREFERENCES = ["bio", "local", "faible_co2", "faible_eau"]
ALLERGIES = ["gluten", "lait", "fruits à coque"]

# Same SKUs as database/seeders/TraceVerteDatasetSeeder.php
CATALOGUE = {
    "TV-OLV-001": {"name": "Huile d'olive bio", "quality": 4.4, "bio": 1, "local": 1, "co2": 3.1, "water": 240, "allergens": []},
    "TV-DAT-002": {"name": "Dattes Deglet Nour", "quality": 4.2, "bio": 1, "local": 0, "co2": 1.2, "water": 290, "allergens": []},
    "TV-HAR-003": {"name": "Harissa du Cap Bon", "quality": 4.0, "bio": 0, "local": 1, "co2": 2.2, "water": 160, "allergens": []},
    "TV-COU-004": {"name": "Couscous complet", "quality": 3.9, "bio": 0, "local": 1, "co2": 1.4, "water": 210, "allergens": ["gluten"]},
    "TV-MIE-005": {"name": "Miel de thym", "quality": 4.3, "bio": 0, "local": 1, "co2": 0.8, "water": 80, "allergens": []},
    "TV-LEB-006": {"name": "Leben", "quality": 3.4, "bio": 0, "local": 1, "co2": 18.5, "water": 620, "allergens": ["lait"]},
    "TV-JUS-007": {"name": "Jus d'orange maltaise", "quality": 3.8, "bio": 0, "local": 1, "co2": 0.6, "water": 130, "allergens": []},
    "TV-TOM-008": {"name": "Tomates séchées", "quality": 3.7, "bio": 0, "local": 1, "co2": 2.0, "water": 150, "allergens": []},
    "TV-AMA-009": {"name": "Amandes de Sfax", "quality": 4.1, "bio": 1, "local": 1, "co2": 1.0, "water": 270, "allergens": ["fruits à coque"]},
}
SKUS = list(CATALOGUE)
PROFILE_WEIGHT = 3.0  # weight of preferences/allergies against ratings (tuned: 1.0, 1.5, 3.0)


def _true_rating(prefs: set, allergies: set, sku: str, taste: float, rng: np.random.Generator) -> float:
    p = CATALOGUE[sku]
    rating = p["quality"] + taste
    if "bio" in prefs:
        rating += 0.6 if p["bio"] else -0.4
    if "local" in prefs:
        rating += 0.4 if p["local"] else -0.5
    if "faible_co2" in prefs:
        rating += 0.5 if p["co2"] < 2 else (-1.0 if p["co2"] > 10 else 0)
    if "faible_eau" in prefs:
        rating += 0.4 if p["water"] < 100 else (-0.6 if p["water"] > 300 else 0)
    if allergies & set(p["allergens"]):
        rating = 1.0
    return float(np.clip(rating + rng.normal(0, 0.45), 1, 5))


def make_dataset(n: int = 2000, seed: int = 42) -> pd.DataFrame:
    """One row per (consumer, product) rating."""
    rng = np.random.default_rng(seed)
    rows = []
    for user in range(n):
        prefs = {p for p in PREFERENCES if rng.random() < 0.4}
        allergies = {a for a in ALLERGIES if rng.random() < 0.08}
        taste = rng.normal(0, 0.3)
        for sku in rng.choice(SKUS, size=int(rng.integers(3, 8)), replace=False):
            rows.append({
                "user": user, "preferences": ",".join(sorted(prefs)), "allergies": ",".join(sorted(allergies)),
                "sku": sku, "rating": round(_true_rating(prefs, allergies, sku, taste, rng), 1),
            })
    return pd.DataFrame(rows)


def _profile_vector(preferences, allergies, ratings: dict, item_means: np.ndarray) -> np.ndarray:
    prefs = [PROFILE_WEIGHT * (p in preferences) for p in PREFERENCES]
    allergy = [PROFILE_WEIGHT * (a in allergies) for a in ALLERGIES]
    centered = [ratings[s] - item_means[i] if s in ratings else 0.0 for i, s in enumerate(SKUS)]
    return np.array(prefs + allergy + centered, dtype=float)


def _users(df: pd.DataFrame) -> pd.DataFrame:
    def split(v):
        return set(v.split(",")) if isinstance(v, str) and v else set()

    grouped = df.groupby("user")
    return pd.DataFrame({
        "preferences": grouped["preferences"].first().map(split),
        "allergies": grouped["allergies"].first().map(split),
        "ratings": grouped.apply(lambda g: dict(zip(g["sku"], g["rating"])), include_groups=False),
    })


def _fit(users: pd.DataFrame, item_means: np.ndarray, k: int = 25) -> dict:
    matrix = np.vstack([_profile_vector(u.preferences, u.allergies, u.ratings, item_means) for u in users.itertuples()])
    ratings = np.full((len(users), len(SKUS)), np.nan)
    for row, u in enumerate(users.itertuples()):
        for sku, value in u.ratings.items():
            ratings[row, SKUS.index(sku)] = value
    knn = NearestNeighbors(n_neighbors=min(k, len(users)), metric="cosine").fit(matrix + 1e-9)
    return {"knn": knn, "ratings": ratings, "item_means": item_means}


def _predict_all(bundle: dict, preferences, allergies, ratings: dict) -> dict:
    vector = _profile_vector(preferences, allergies, ratings, bundle["item_means"]) + 1e-9
    distances, idx = bundle["knn"].kneighbors([vector])
    weights = np.clip(1 - distances[0], 0.01, None)
    neighbour_ratings = bundle["ratings"][idx[0]]

    out = {}
    for j, sku in enumerate(SKUS):
        rated = ~np.isnan(neighbour_ratings[:, j])
        if rated.sum() >= 2:
            value = float(np.average(neighbour_ratings[rated, j], weights=weights[rated]))
            out[sku] = {"rating": round(value, 2), "neighbours": int(rated.sum())}
        else:
            out[sku] = {"rating": round(float(bundle["item_means"][j]), 2), "neighbours": 0}
    return out


def train(df: pd.DataFrame, seed: int = 42):
    rng = np.random.default_rng(seed)
    users = _users(df)
    test_ids = rng.choice(users.index, size=int(len(users) * 0.2), replace=False)
    train_users = users.drop(test_ids)

    train_rows = df[df["user"].isin(train_users.index)]
    item_means = np.array([train_rows.loc[train_rows["sku"] == s, "rating"].mean() for s in SKUS])
    bundle = _fit(train_users, item_means)

    errors_model, errors_baseline = [], []
    for uid in test_ids:
        u = users.loc[uid]
        items = list(u.ratings.items())
        hidden = [items[i] for i in rng.choice(len(items), size=max(1, len(items) // 5), replace=False)]
        visible = {s: v for s, v in items if (s, v) not in hidden}
        predictions = _predict_all(bundle, u.preferences, u.allergies, visible)
        for sku, truth in hidden:
            errors_model.append(predictions[sku]["rating"] - truth)
            errors_baseline.append(item_means[SKUS.index(sku)] - truth)

    rmse_model = float(np.sqrt(np.mean(np.square(errors_model))))
    rmse_baseline = float(np.sqrt(np.mean(np.square(errors_baseline))))
    metrics = {
        "rmse": round(rmse_model, 3),
        "rmse_baseline_item_mean": round(rmse_baseline, 3),
        "improvement_vs_baseline": f"{round(100 * (1 - rmse_model / rmse_baseline))} %",
        "n_users": int(len(users)),
        "n_ratings": int(len(df)),
    }

    all_means = np.array([df.loc[df["sku"] == s, "rating"].mean() for s in SKUS])
    return _fit(users, all_means), metrics


def predict(bundle, payload: dict) -> dict:
    preferences = set(payload.get("preferences") or [])
    allergies = {a.lower() for a in (payload.get("allergies") or [])}
    ratings = {s: float(v) for s, v in (payload.get("ratings") or {}).items() if s in CATALOGUE}

    predictions = _predict_all(bundle, preferences, allergies, ratings)
    # Already-rated products keep their real rating
    for sku, value in ratings.items():
        predictions[sku] = {"rating": value, "neighbours": 0, "rated": True}
    return {"predictions": predictions}
