"""The HTTP API answers with the expected shapes (models are trained first if needed)."""

from fastapi.testclient import TestClient

import train_all
from common import MODELS_DIR
from modules import ALL


def setup_module():
    for module in ALL:
        if not (MODELS_DIR / f"{module.NAME}.joblib").exists():
            train_all.train_module(module)


def client():
    from service import app

    return TestClient(app)


def test_health_and_models():
    with client() as c:
        assert all(c.get("/health").json()["models"].values())
        assert len(c.get("/models").json()) == len(ALL)


def test_every_endpoint():
    with client() as c:
        assert "co2_emissions" in c.post("/m1/footprint", json={"category": "huiles"}).json()
        assert "label" in c.post("/m2/producer-profile", json={"capacity_tonnes": 100}).json()
        assert 0 <= c.post("/m3/delay-risk", json={"distance_km": 300, "month": 7}).json()["risk"] <= 100
        assert c.post("/m4/review", json={"text": "Excellent produit"}).json()["sentiment"] == "positif"
        assert "probability" in c.post("/m5/greenwashing", json={"text": "100% naturel"}).json()
        assert "is_anomaly" in c.post("/m5/footprint-anomaly", json={"category": "huiles", "co2_emissions": 3, "water_usage": 240, "land_usage": 4.5}).json()
        assert "TV-OLV-001" in c.post("/m6/recommendations", json={"preferences": ["bio"]}).json()["predictions"]
        assert c.post("/m3/delay-risk", json={"month": 13}).status_code == 422
