"""Local ML service: serves the trained models to the Laravel application.

    uvicorn service:app --port 8001

Everything runs on this machine with the models trained by train_all.py: no external AI API.
Interactive documentation: http://127.0.0.1:8001/docs
"""

from __future__ import annotations

import warnings
from contextlib import asynccontextmanager
from typing import Any

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field

from common import load_info, load_model
from modules import ALL, m1_footprint, m2_producers, m3_delays, m4_reviews, m5_anomalies, m5_greenwashing, m6_recommendations

warnings.filterwarnings("ignore", category=UserWarning)

MODELS: dict[str, Any] = {}


@asynccontextmanager
async def lifespan(_app: FastAPI):
    # Load every trained model once, at startup
    for module in ALL:
        bundle = load_model(module.NAME)
        if bundle is not None:
            MODELS[module.NAME] = bundle
    yield


app = FastAPI(
    title="Trace Verte — service ML",
    description="Modèles scikit-learn des modules M1 à M6, entraînés localement (python train_all.py).",
    version="1.0.0",
    lifespan=lifespan,
)


def _run(module, payload: BaseModel) -> dict:
    bundle = MODELS.get(module.NAME)
    if bundle is None:
        raise HTTPException(503, f"Modèle {module.NAME} absent : lancez « python train_all.py ».")
    return module.predict(bundle, payload.model_dump())


# ---- Inputs ----------------------------------------------------------------

class FootprintIn(BaseModel):
    category: str | None = None
    farming_method: str | None = None
    transformation_type: str | None = None
    distance_km: float = 0
    organic_certified: bool = False


class ProducerIn(BaseModel):
    farming_method: str | None = None
    capacity_tonnes: float = 0
    n_certifications: int = 0
    n_crops: int = 1


class DelayIn(BaseModel):
    distance_km: float = 0
    n_steps: int = 1
    current_stage: str | None = None
    distributor_type: str | None = None
    category: str | None = None
    days_in_transit: float = 0
    month: int = Field(1, ge=1, le=12)


class TextIn(BaseModel):
    text: str = Field("", max_length=5000)


class FootprintValuesIn(BaseModel):
    category: str | None = None
    co2_emissions: float = 0
    water_usage: float = 0
    land_usage: float = 0


class RecommendationIn(BaseModel):
    preferences: list[str] = []
    allergies: list[str] = []
    ratings: dict[str, float] = {}


# ---- Routes ----------------------------------------------------------------

@app.get("/health")
def health() -> dict:
    return {"status": "ok", "models": {m.NAME: m.NAME in MODELS for m in ALL}}


@app.get("/models")
def models() -> list[dict]:
    """Description and evaluation metrics of every model (shown on the admin "Modèles ML" page)."""
    return [load_info(m.NAME) or {"name": m.NAME, "module": m.MODULE, "title": m.TITLE, "trained": False} for m in ALL]


@app.post("/m1/footprint")
def m1(payload: FootprintIn) -> dict:
    return _run(m1_footprint, payload)


@app.post("/m2/producer-profile")
def m2(payload: ProducerIn) -> dict:
    return _run(m2_producers, payload)


@app.post("/m3/delay-risk")
def m3(payload: DelayIn) -> dict:
    return _run(m3_delays, payload)


@app.post("/m4/review")
def m4(payload: TextIn) -> dict:
    return _run(m4_reviews, payload)


@app.post("/m5/greenwashing")
def m5_text(payload: TextIn) -> dict:
    return _run(m5_greenwashing, payload)


@app.post("/m5/footprint-anomaly")
def m5_anomaly(payload: FootprintValuesIn) -> dict:
    return _run(m5_anomalies, payload)


@app.post("/m6/recommendations")
def m6(payload: RecommendationIn) -> dict:
    return _run(m6_recommendations, payload)
