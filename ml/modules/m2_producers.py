"""M2 — Producteurs & Usines : regrouper les producteurs en profils (apprentissage non supervisé).

K-Means (k = 4) sur le mode de culture, la capacité de production (échelle log), le nombre de
certifications et le nombre de cultures. Chaque groupe reçoit ensuite un nom lisible, déduit des
caractéristiques moyennes de ses membres (voir _name_cluster). Le modèle renvoie le profil d'un
producteur et sa distance au centre du groupe (plus elle est faible, plus il est typique).

Données : 4 types d'exploitations tunisiennes simulés (petite ferme agroécologique, exploitation bio
certifiée, exploitation raisonnée familiale, grande exploitation conventionnelle) avec du bruit.
Comme le type réel est connu dans les données simulées, on peut mesurer si K-Means le retrouve
(indice de Rand ajusté), en plus de la silhouette.
"""

from __future__ import annotations

import numpy as np
import pandas as pd
from sklearn.cluster import KMeans
from sklearn.compose import ColumnTransformer
from sklearn.metrics import adjusted_rand_score, silhouette_score
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import OneHotEncoder, StandardScaler

NAME = "m2_producers"
MODULE = "M2"
TITLE = "Profils de producteurs"
ALGORITHM = "K-Means (4 groupes) sur variables normalisées"
DESCRIPTION = "Classe chaque producteur dans un profil type à partir de ses pratiques, de sa taille et de ses certifications."

FARMING_METHODS = ["biologique", "permaculture", "raisonnée", "conventionnelle"]
FEATURES = ["farming_method", "log_capacity", "n_certifications", "n_crops"]

# (farming methods and their probabilities, capacity range in tonnes, certifications range, crops range)
ARCHETYPES = {
    "petite_agroecologique": (["permaculture", "biologique"], [0.6, 0.4], (5, 60), (0, 2), (3, 7)),
    "bio_certifiee": (["biologique"], [1.0], (50, 400), (1, 3), (1, 3)),
    "raisonnee_familiale": (["raisonnée"], [1.0], (20, 250), (0, 1), (1, 4)),
    "grande_conventionnelle": (["conventionnelle"], [1.0], (500, 6000), (0, 1), (1, 2)),
}


def make_dataset(n: int = 1200, seed: int = 42) -> pd.DataFrame:
    rng = np.random.default_rng(seed)
    rows = []
    for _ in range(n):
        archetype = rng.choice(list(ARCHETYPES))
        methods, p, (cap_lo, cap_hi), (cert_lo, cert_hi), (crop_lo, crop_hi) = ARCHETYPES[archetype]
        # 10 % of producers do not follow their archetype's usual method
        method = rng.choice(methods, p=p) if rng.random() > 0.10 else rng.choice(FARMING_METHODS)
        capacity = float(np.exp(rng.uniform(np.log(cap_lo), np.log(cap_hi))) * rng.lognormal(0, 0.2))
        rows.append({
            "archetype": archetype,
            "farming_method": method,
            "capacity_tonnes": round(capacity, 1),
            "log_capacity": round(float(np.log1p(capacity)), 4),
            "n_certifications": int(rng.integers(cert_lo, cert_hi + 1)),
            "n_crops": int(rng.integers(crop_lo, crop_hi + 1)),
        })
    return pd.DataFrame(rows)


def _name_cluster(members: pd.DataFrame) -> tuple[str, str]:
    """Readable name from the cluster's average profile."""
    capacity = float(np.expm1(members["log_capacity"].median()))
    method = members["farming_method"].mode().iloc[0]
    certs = members["n_certifications"].mean()
    crops = members["n_crops"].mean()

    if capacity >= 400:
        return "Grande exploitation", f"Production importante (≈ {capacity:.0f} t/an), surtout {method}, peu de cultures."
    if method in ("permaculture", "biologique") and crops >= 3:
        return "Petite ferme agroécologique", f"Petite production (≈ {capacity:.0f} t/an), cultures diversifiées, pratiques {method}."
    if method == "biologique" or certs >= 1.2:
        return "Exploitation bio certifiée", f"Production moyenne (≈ {capacity:.0f} t/an) avec {certs:.1f} certification(s) en moyenne."
    return "Exploitation raisonnée familiale", f"Production moyenne (≈ {capacity:.0f} t/an), agriculture {method}, peu certifiée."


def _pipeline(seed: int) -> Pipeline:
    return Pipeline([
        ("prep", ColumnTransformer([
            ("method", OneHotEncoder(categories=[FARMING_METHODS], handle_unknown="ignore"), ["farming_method"]),
            ("num", StandardScaler(), ["log_capacity", "n_certifications", "n_crops"]),
        ])),
        ("kmeans", KMeans(n_clusters=4, n_init=20, random_state=seed)),
    ])


def train(df: pd.DataFrame, seed: int = 42):
    model = _pipeline(seed).fit(df[FEATURES])
    labels = model.named_steps["kmeans"].labels_

    names = {}
    for cluster in range(4):
        title, description = _name_cluster(df[labels == cluster])
        names[cluster] = {"label": title, "description": description, "size": int((labels == cluster).sum())}

    features = model.named_steps["prep"].transform(df[FEATURES])
    metrics = {
        "silhouette": round(float(silhouette_score(features, labels)), 3),
        "adjusted_rand_index": round(float(adjusted_rand_score(df["archetype"], labels)), 3),
        "clusters": {v["label"]: v["size"] for v in names.values()},
    }
    return {"model": model, "names": names}, metrics


def predict(bundle, payload: dict) -> dict:
    model: Pipeline = bundle["model"]
    capacity = max(float(payload.get("capacity_tonnes") or 0), 0.0)
    row = pd.DataFrame([{
        "farming_method": payload.get("farming_method") or "conventionnelle",
        "log_capacity": float(np.log1p(capacity)),
        "n_certifications": int(payload.get("n_certifications") or 0),
        "n_crops": int(payload.get("n_crops") or 1),
    }])

    features = model.named_steps["prep"].transform(row)
    distances = model.named_steps["kmeans"].transform(features)[0]
    cluster = int(distances.argmin())
    # Typicality: 100 % at the centre of its group, lower when close to another group
    second = float(np.sort(distances)[1])
    typicality = round(100 * (1 - distances[cluster] / (distances[cluster] + second)) * 2 - 100, 0)

    return {
        "cluster": cluster,
        "label": bundle["names"][cluster]["label"],
        "description": bundle["names"][cluster]["description"],
        "typicality": max(0.0, min(100.0, float(typicality))),
    }
