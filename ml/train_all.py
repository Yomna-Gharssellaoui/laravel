"""Generate the datasets and train every module's model.

    python train_all.py            # all modules
    python train_all.py m3_delays  # one module only

Outputs: models/<name>.joblib (trained model), models/<name>.json (description + metrics),
data/<name>.csv (training data, for inspection).
"""

from __future__ import annotations

import sys
import time

from common import SEED, save_dataset, save_model
from modules import ALL


def train_module(module) -> dict:
    started = time.time()
    df = module.make_dataset(seed=SEED)
    save_dataset(module.NAME, df)
    bundle, metrics = module.train(df, seed=SEED)
    info = {
        "name": module.NAME,
        "module": module.MODULE,
        "title": module.TITLE,
        "algorithm": module.ALGORITHM,
        "description": module.DESCRIPTION,
        "dataset_rows": int(len(df)),
        "metrics": metrics,
        "training_seconds": round(time.time() - started, 1),
    }
    save_model(module.NAME, bundle, info)
    return info


def main(selected: list[str]) -> None:
    modules = [m for m in ALL if not selected or m.NAME in selected]
    if not modules:
        sys.exit(f"Module inconnu. Choix : {', '.join(m.NAME for m in ALL)}")

    for module in modules:
        info = train_module(module)
        print(f"[{info['module']}] {info['title']} — {info['dataset_rows']} lignes, {info['training_seconds']} s")
        for key, value in info["metrics"].items():
            print(f"      {key}: {value}")


if __name__ == "__main__":
    main(sys.argv[1:])
