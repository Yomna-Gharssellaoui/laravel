"""Quality gates: each model must reach a minimum score, and behave sensibly on known cases."""

import pytest

from common import SEED
from modules import (
    m1_footprint, m2_producers, m3_delays, m4_reviews, m5_anomalies, m5_greenwashing, m6_recommendations,
)


@pytest.fixture(scope="module")
def trained():
    cache = {}

    def get(module, n=None):
        if module.NAME not in cache:
            df = module.make_dataset(n, SEED) if n else module.make_dataset(seed=SEED)
            cache[module.NAME] = module.train(df, seed=SEED)
        return cache[module.NAME]

    return get


def test_m1_footprint(trained):
    bundle, metrics = trained(m1_footprint)
    assert metrics["r2_co2_emissions"] > 0.8
    dairy = m1_footprint.predict(bundle, {"category": "produits laitiers", "farming_method": "conventionnelle", "transformation_type": "laiterie"})
    honey = m1_footprint.predict(bundle, {"category": "épicerie sucrée", "farming_method": "permaculture"})
    assert dairy["co2_emissions"]["value"] > honey["co2_emissions"]["value"]
    assert dairy["co2_emissions"]["low"] <= dairy["co2_emissions"]["value"] <= dairy["co2_emissions"]["high"]


def test_m2_producers(trained):
    bundle, metrics = trained(m2_producers)
    assert metrics["adjusted_rand_index"] > 0.6
    big = m2_producers.predict(bundle, {"farming_method": "conventionnelle", "capacity_tonnes": 2000, "n_certifications": 0, "n_crops": 2})
    small = m2_producers.predict(bundle, {"farming_method": "permaculture", "capacity_tonnes": 30, "n_certifications": 1, "n_crops": 5})
    assert big["label"] == "Grande exploitation"
    assert small["label"] == "Petite ferme agroécologique"


def test_m3_delays(trained):
    bundle, metrics = trained(m3_delays)
    assert metrics["roc_auc"] > 0.7
    risky = m3_delays.predict(bundle, {"distance_km": 900, "n_steps": 1, "current_stage": "transport", "distributor_type": "grossiste", "category": "produits laitiers", "days_in_transit": 8, "month": 8})
    safe = m3_delays.predict(bundle, {"distance_km": 20, "n_steps": 5, "current_stage": "production", "distributor_type": "transport frigorifique", "category": "huiles", "days_in_transit": 0.5, "month": 3})
    assert risky["risk"] > safe["risk"]


def test_m4_reviews(trained):
    bundle, metrics = trained(m4_reviews)
    assert metrics["sentiment_accuracy_handwritten"] >= 0.75
    assert m4_reviews.predict(bundle, {"text": "Délicieux, je recommande à toute la famille"})["sentiment"] == "positif"
    assert m4_reviews.predict(bundle, {"text": "Produit arrivé périmé, très déçu"})["sentiment"] == "négatif"
    assert m4_reviews.predict(bundle, {"text": "Achetez des followers sur www.exemple-spam.test"})["is_spam"]


def test_m5_greenwashing(trained):
    bundle, metrics = trained(m5_greenwashing)
    assert metrics["roc_auc"] > 0.9
    vague = m5_greenwashing.predict(bundle, {"text": "Leben 100% naturel et écologique, vert et durable"})
    precise = m5_greenwashing.predict(bundle, {"text": "Certifié bio Ecocert, certificat ECO-TN-2025-0187, contrôle annuel"})
    assert vague["is_greenwashing"] and not precise["is_greenwashing"]
    assert vague["risky_terms"]


def test_m5_anomalies(trained):
    bundle, metrics = trained(m5_anomalies)
    assert metrics["recall_injected"] > 0.7
    assert m5_anomalies.predict(bundle, {"category": "produits laitiers", "co2_emissions": 18.5, "water_usage": 620, "land_usage": 8.4})["is_anomaly"]
    assert not m5_anomalies.predict(bundle, {"category": "huiles", "co2_emissions": 3.1, "water_usage": 240, "land_usage": 4.8})["is_anomaly"]


def test_m6_recommendations(trained):
    bundle, metrics = trained(m6_recommendations)
    assert metrics["rmse"] < metrics["rmse_baseline_item_mean"]
    gluten = m6_recommendations.predict(bundle, {"preferences": ["local"], "allergies": ["gluten"], "ratings": {}})["predictions"]
    assert gluten["TV-COU-004"]["rating"] < 2.5  # couscous contains gluten
    green = m6_recommendations.predict(bundle, {"preferences": ["faible_co2"], "allergies": [], "ratings": {}})["predictions"]
    assert green["TV-MIE-005"]["rating"] > green["TV-LEB-006"]["rating"]
