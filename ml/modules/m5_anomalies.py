"""M5 — Alertes & IA (2/2) : repérer les empreintes environnementales atypiques.

Détection d'anomalies (Isolation Forest). Chaque empreinte est comparée aux valeurs habituelles
de sa catégorie : on travaille sur le logarithme du rapport entre la valeur déclarée et la médiane
de la catégorie (CO₂, eau, sols). Une empreinte très éloignée de ses voisines est « isolée »
rapidement par les arbres et reçoit un score d'anomalie élevé → alerte automatique.

Données : empreintes « normales » générées avec le même modèle physique que M1, sur lesquelles on
injecte, pour l'évaluation, des anomalies connues (valeurs multipliées ou divisées par 3 à 8), afin
de mesurer la précision et le rappel de la détection.
"""

from __future__ import annotations

import numpy as np
import pandas as pd
from sklearn.ensemble import IsolationForest
from sklearn.metrics import precision_score, recall_score

from . import m1_footprint

NAME = "m5_anomalies"
MODULE = "M5"
TITLE = "Détection d'empreintes atypiques"
ALGORITHM = "Isolation Forest sur écarts à la médiane de la catégorie"
DESCRIPTION = "Signale les empreintes dont les valeurs sont anormales pour leur catégorie (erreur de saisie ou déclaration douteuse)."

INDICATORS = ["co2_emissions", "water_usage", "land_usage"]


def make_dataset(n: int = 3000, seed: int = 42) -> pd.DataFrame:
    return m1_footprint.make_dataset(n, seed)[["category"] + INDICATORS]


def _features(df: pd.DataFrame, medians: dict) -> np.ndarray:
    default = np.median(np.array(list(medians.values())), axis=0)
    ref = np.array([medians.get(c, default) for c in df["category"]])
    values = df[INDICATORS].to_numpy(dtype=float).clip(min=1e-3)
    return np.log(values / ref)


def _inject_anomalies(df: pd.DataFrame, rng: np.random.Generator, share: float = 0.1) -> tuple[pd.DataFrame, np.ndarray]:
    df = df.copy()
    labels = np.zeros(len(df), dtype=int)
    idx = rng.choice(len(df), size=int(len(df) * share), replace=False)
    for i in idx:
        column = rng.choice(INDICATORS)
        factor = rng.uniform(3, 8)
        df.iloc[i, df.columns.get_loc(column)] *= factor if rng.random() < 0.7 else 1 / factor
        labels[i] = 1
    return df, labels


def train(df: pd.DataFrame, seed: int = 42):
    rng = np.random.default_rng(seed)
    medians = {c: g[INDICATORS].median().to_numpy() for c, g in df.groupby("category")}

    train_df = df.sample(frac=0.8, random_state=seed)
    test_df, truth = _inject_anomalies(df.drop(train_df.index), rng)

    forest = IsolationForest(n_estimators=300, contamination=0.05, random_state=seed).fit(_features(train_df, medians))
    pred = (forest.predict(_features(test_df, medians)) == -1).astype(int)

    metrics = {
        "precision_injected": round(float(precision_score(truth, pred)), 3),
        "recall_injected": round(float(recall_score(truth, pred)), 3),
        "false_alarm_rate": round(float(pred[truth == 0].mean()), 3),
    }
    final = IsolationForest(n_estimators=300, contamination=0.05, random_state=seed).fit(_features(df, medians))
    return {"forest": final, "medians": medians}, metrics


def predict(bundle, payload: dict) -> dict:
    row = pd.DataFrame([{
        "category": payload.get("category") or "épicerie",
        "co2_emissions": float(payload.get("co2_emissions") or 0),
        "water_usage": float(payload.get("water_usage") or 0),
        "land_usage": float(payload.get("land_usage") or 0),
    }])
    features = _features(row, bundle["medians"])
    forest: IsolationForest = bundle["forest"]

    # decision_function < 0 means anomaly; mapped to a 0-100 score
    decision = float(forest.decision_function(features)[0])
    score = round(float(np.clip(50 - decision * 250, 0, 100)), 1)

    deviations = {name: round(float(np.exp(features[0][i])), 2) for i, name in enumerate(INDICATORS)}
    worst = max(deviations, key=lambda k: abs(np.log(deviations[k])))

    return {
        "is_anomaly": bool(forest.predict(features)[0] == -1),
        "score": score,
        "ratio_to_category_median": deviations,
        "main_indicator": worst,
    }
