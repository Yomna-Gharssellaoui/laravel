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
        health = c.get("/health").json()
        assert health["status"] == "ok"
        assert all(health["models"].values())

        models_list = c.get("/models").json()
        assert len(models_list) == len(ALL)
        for m in models_list:
            assert "name" in m
            assert "title" in m
            assert "metrics" in m or m.get("trained") is False


def test_every_endpoint():
    with client() as c:
        assert "co2_emissions" in c.post("/m1/footprint", json={"category": "huiles"}).json()
        assert "label" in c.post("/m2/producer-profile", json={"capacity_tonnes": 100}).json()
        assert 0 <= c.post("/m3/delay-risk", json={"distance_km": 300, "month": 7}).json()["risk"] <= 100
        assert c.post("/m4/review", json={"text": "Excellent produit"}).json()["sentiment"] == "positif"
        assert "probability" in c.post("/m5/greenwashing", json={"text": "100% naturel"}).json()
        assert "is_anomaly" in c.post("/m5/footprint-anomaly", json={"category": "huiles", "co2_emissions": 3, "water_usage": 240, "land_usage": 4.5}).json()
        assert "TV-OLV-001" in c.post("/m6/recommendations", json={"preferences": ["bio"]}).json()["predictions"]


def test_validation_errors():
    with client() as c:
        # Month < 1 or > 12
        assert c.post("/m3/delay-risk", json={"month": 0}).status_code == 422
        assert c.post("/m3/delay-risk", json={"month": 13}).status_code == 422

        # Text length exceeding max_length (5000)
        assert c.post("/m4/review", json={"text": "x" * 5001}).status_code == 422
        assert c.post("/m5/greenwashing", json={"text": "y" * 5001}).status_code == 422

        # Non-numeric distance
        assert c.post("/m1/footprint", json={"distance_km": "non-numeric"}).status_code == 422


def test_default_payloads():
    with client() as c:
        # Calling with default/empty payload should succeed
        res_m1 = c.post("/m1/footprint", json={})
        assert res_m1.status_code == 200
        assert "co2_emissions" in res_m1.json()

        res_m2 = c.post("/m2/producer-profile", json={})
        assert res_m2.status_code == 200
        assert "cluster" in res_m2.json()

        res_m3 = c.post("/m3/delay-risk", json={})
        assert res_m3.status_code == 200
        assert "risk" in res_m3.json()

        res_m4 = c.post("/m4/review", json={})
        assert res_m4.status_code == 200

        res_m5_text = c.post("/m5/greenwashing", json={})
        assert res_m5_text.status_code == 200

        res_m5_ano = c.post("/m5/footprint-anomaly", json={})
        assert res_m5_ano.status_code == 200

        res_m6 = c.post("/m6/recommendations", json={})
        assert res_m6.status_code == 200
