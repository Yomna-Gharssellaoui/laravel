"""Shared helpers: where models live, how they are saved and loaded."""

from __future__ import annotations

import json
import time
from pathlib import Path
from typing import Any

import joblib

ROOT = Path(__file__).resolve().parent
MODELS_DIR = ROOT / "models"
DATA_DIR = ROOT / "data"

# Every dataset and model uses this seed: training twice gives the same models
SEED = 42


def save_model(name: str, bundle: dict[str, Any], info: dict[str, Any]) -> None:
    """Save the trained objects (joblib) and their description + metrics (json)."""
    MODELS_DIR.mkdir(exist_ok=True)
    joblib.dump(bundle, MODELS_DIR / f"{name}.joblib")
    info = {**info, "trained_at": time.strftime("%Y-%m-%d %H:%M:%S")}
    (MODELS_DIR / f"{name}.json").write_text(json.dumps(info, ensure_ascii=False, indent=2), encoding="utf-8")


def load_model(name: str) -> dict[str, Any] | None:
    path = MODELS_DIR / f"{name}.joblib"
    return joblib.load(path) if path.exists() else None


def load_info(name: str) -> dict[str, Any] | None:
    path = MODELS_DIR / f"{name}.json"
    return json.loads(path.read_text(encoding="utf-8")) if path.exists() else None


def save_dataset(name: str, df) -> None:
    """Keep a CSV copy of each generated dataset so it can be inspected (Excel, pandas…)."""
    DATA_DIR.mkdir(exist_ok=True)
    df.to_csv(DATA_DIR / f"{name}.csv", index=False, encoding="utf-8")


def r(x: float, digits: int = 3) -> float:
    return round(float(x), digits)
