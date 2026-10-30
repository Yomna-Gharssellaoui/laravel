"""One file per project module (M1 … M6), each owned by one student.

Every module exposes the same interface:
    NAME, TITLE, ALGORITHM, DESCRIPTION
    make_dataset(n: int, seed: int) -> pandas.DataFrame
    train(df) -> (bundle: dict, metrics: dict)
    predict(bundle, payload: dict) -> dict
"""

from . import m1_footprint, m2_producers, m3_delays, m4_reviews, m5_greenwashing, m5_anomalies, m6_recommendations

ALL = [m1_footprint, m2_producers, m3_delays, m4_reviews, m5_greenwashing, m5_anomalies, m6_recommendations]
