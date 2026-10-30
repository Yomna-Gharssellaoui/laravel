# Trace Verte — service de machine learning

Un modèle **scikit-learn** par module, entraîné et exécuté **sur la machine** (aucune API externe).
Laravel interroge ce service en HTTP (`app/Services/Ml/MlService.php`) ; s'il est arrêté, l'application
continue de fonctionner et masque simplement les prédictions.

| Module | Modèle | Algorithme | Où le voir dans l'application |
|---|---|---|---|
| M1 Produits & empreinte | Estimation de l'empreinte (CO₂, eau, sols) avec intervalle | Random Forest (régression multi-sorties) | Fiche produit du back-office ; pré-remplit le formulaire d'empreinte |
| M2 Producteurs & usines | Profil type du producteur | K-Means (4 groupes) | Producteurs : colonne « Profil ML » + fiche |
| M3 Logistique | Risque de retard d'une livraison en cours | Gradient Boosting | Traces logistiques ; alerte si risque ≥ 60 % |
| M4 Certifications & avis | Sentiment + détection de spam | TF-IDF + régression logistique | Modération des avis ; spam quasi certain rejeté automatiquement |
| M5 Alertes & IA | Greenwashing (texte) | TF-IDF + régression logistique explicable | Analyse des déclarations (expressions en cause) |
| M5 Alertes & IA | Empreintes atypiques | Isolation Forest | Empreintes : « Cohérente / Atypique » ; alerte automatique |
| M6 Consommateurs | Note prédite par les profils proches | Filtrage collaboratif (k plus proches voisins) | Recommandations personnalisées |

Chaque fichier `modules/*.py` décrit en tête ses données, sa méthode et son évaluation.
Les scores sont visibles dans le back-office : **Qualité → Modèles ML**.

## Données

Les jeux d'entraînement sont **synthétiques et reproductibles** (graine 42), générés par
`make_dataset()` dans chaque module à partir de règles réalistes (facteurs d'émission par catégorie,
profils de producteurs, logistique, phrases d'avis et de déclarations…), avec du bruit.
Les modèles texte sont aussi évalués sur des exemples **écrits à la main** (`HANDWRITTEN`).
Une copie CSV de chaque jeu est écrite dans `data/` à l'entraînement.

## Installation sous Windows (PowerShell)

Prérequis : Python 3.11 ou plus récent (https://www.python.org/downloads/, cocher « Add python.exe to PATH »).

```powershell
cd C:\Users\<vous>\...\trace-verte-complet-v3\ml
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
python train_all.py
uvicorn service:app --port 8001
```

Si PowerShell refuse `activate` : `Set-ExecutionPolicy -Scope CurrentUser RemoteSigned`, puis relancer.

Laisser ce terminal ouvert. Dans `.env` de Laravel :

```
ML_SERVICE_URL=http://127.0.0.1:8001
```

puis, dans un autre terminal à la racine du projet :

```powershell
php artisan config:clear
php artisan traceverte:ml-check
```

Les fois suivantes, seules deux commandes suffisent : `ml\.venv\Scripts\activate` puis
`uvicorn service:app --port 8001` (depuis `ml\`).

## Linux / macOS

```bash
cd ml
python3 -m venv .venv && source .venv/bin/activate
pip install -r requirements.txt
python train_all.py
uvicorn service:app --port 8001
```

## Avec Docker

`docker compose up -d --build` construit aussi le service `ml` (modèles entraînés pendant le build)
et configure `ML_SERVICE_URL=http://ml:8001` pour l'application.

## Commandes utiles

| Commande | Rôle |
|---|---|
| `python train_all.py` | Entraîne et évalue les 7 modèles, écrit `models/*.joblib` et leurs scores |
| `python train_all.py m4_reviews` | Réentraîne un seul modèle |
| `python -m pytest -q` | Seuils de qualité de chaque modèle + tests de l'API |
| http://127.0.0.1:8001/docs | Documentation interactive de l'API (tester chaque modèle) |

## API

| Route | Entrée | Sortie |
|---|---|---|
| `GET /health` | — | modèles chargés |
| `GET /models` | — | description et scores de chaque modèle |
| `POST /m1/footprint` | catégorie, mode de culture, transformation, distance, bio | CO₂ / eau / sols avec intervalle p10–p90 |
| `POST /m2/producer-profile` | mode de culture, capacité (t), nb certifications, nb cultures | groupe, libellé, typicité |
| `POST /m3/delay-risk` | distance, étapes, étape actuelle, distributeur, catégorie, jours en transit, mois | risque %, niveau |
| `POST /m4/review` | texte | sentiment, confiance, probabilité de spam |
| `POST /m5/greenwashing` | texte | probabilité, expressions à risque / rassurantes |
| `POST /m5/footprint-anomaly` | catégorie, CO₂, eau, sols | anomalie, score, écart à la médiane |
| `POST /m6/recommendations` | préférences, allergies, notes déjà données | note prédite par produit |
