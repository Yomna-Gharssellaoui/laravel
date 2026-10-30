"""M5 — Alertes & IA (1/2) : détecter le greenwashing dans les déclarations environnementales.

Classifieur de texte, TF-IDF (mots + morceaux de mots) puis régression logistique. Il apprend à
distinguer :
  * les allégations vagues ou invérifiables (« 100 % naturel », « respectueux de la planète »,
    « neutre en carbone » sans preuve…) → greenwashing probable ;
  * les allégations précises et vérifiables (chiffres, unités, organisme certificateur, numéro de
    certificat, période mesurée…) → crédibles.

Le modèle est explicable : pour chaque texte, il renvoie les expressions qui ont le plus poussé
le score vers le greenwashing (coefficient du mot × son poids TF-IDF dans le texte).

Données : déclarations générées à partir de modèles de phrases réalistes (inspirés des critères
de la directive européenne « Green Claims »), avec des cas mixtes. Évaluation complémentaire sur
des déclarations rédigées à la main (HANDWRITTEN).
"""

from __future__ import annotations

import random
import re
import unicodedata

import numpy as np
import pandas as pd
from scipy.sparse import hstack
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import accuracy_score, f1_score, roc_auc_score
from sklearn.model_selection import train_test_split

NAME = "m5_greenwashing"
MODULE = "M5"
TITLE = "Détection du greenwashing"
ALGORITHM = "TF-IDF (mots + caractères) + régression logistique, explicable"
DESCRIPTION = "Estime la probabilité qu'une déclaration environnementale soit vague ou trompeuse, et cite les expressions en cause."

PRODUCTS = ["notre huile", "ces dattes", "cette harissa", "notre couscous", "ce miel", "notre leben", "ce jus", "ce produit", "nos amandes", "cette gamme"]

VAGUE = [
    "100% naturel", "totalement écologique", "respectueux de la planète", "éco-responsable", "neutre en carbone",
    "zéro impact sur l'environnement", "bon pour la planète", "vert et durable", "fabriqué de façon responsable",
    "un choix écologique", "le plus écologique du marché", "sans aucun impact", "issu d'une démarche durable",
    "ami de la nature", "pensé pour la planète", "100% propre", "écologique par nature", "respectueux de l'environnement",
    "sans produits chimiques", "climatiquement neutre", "engagé pour le climat", "naturellement durable",
]
VAGUE_TAILS = ["", ".", " !", " et responsable.", ", pour une consommation plus verte.", " depuis toujours.", ", c'est notre promesse.", " pour toute la famille."]

PRECISE = [
    "certifié Agriculture Biologique par Ecocert, certificat n° {cert} valable jusqu'en {year}",
    "émissions réduites de {pct} % entre {y1} et {y2}, bilan carbone vérifié par Bureau Veritas",
    "emballage composé à {pct} % de carton recyclé, certification FSC Recycled n° {cert}",
    "récolté à {km} km du site de transformation, distance mesurée sur la trace logistique",
    "irrigation goutte-à-goutte : {water} litres d'eau par kilo, mesure annuelle du compteur",
    "{pct} % d'énergie solaire sur le site, production mesurée de {kwh} kWh en {year}",
    "label Produit du Terroir attribué par l'APIA, dossier n° {cert}",
    "analyse en laboratoire ({lab}) : aucun résidu de pesticide détecté sur le lot {cert}",
    "compensation de {t} tonnes de CO2 via le programme Gold Standard GS-{cert}, attestations publiques",
    "empreinte de {co2} kg CO2e par kilo selon la méthode ACV PEF, rapport disponible",
]
LABS = ["Institut Pasteur de Tunis", "SGS", "Eurofins", "CNCB"]

# Hard cases: numbers without any proof (still vague) …
VAGUE_WITH_NUMBERS = [
    "{pct} % plus vert que les autres", "jusqu'à {pct} % d'impact en moins", "{pct} % naturel et responsable",
    "réduit l'empreinte de {pct} % (estimation interne)", "{pct} % d'engagement pour la planète",
]
# … and precise claims written in a marketing tone (still verifiable)
PRECISE_PLAIN = [
    "fièrement récolté à {km} km de notre moulin, comme l'indique la trace logistique publique",
    "bon pour vous : certifié bio par Ecocert (certificat {cert}), vérifiable en ligne",
    "notre engagement : {pct} % de réduction d'eau depuis {y1}, relevés de compteur publiés chaque année",
]

HANDWRITTEN = [
    ("Une huile qui respecte la nature et la planète.", 1),
    ("Le choix vert pour toute la famille !", 1),
    ("Produit éthique, durable et 100 % naturel.", 1),
    ("Nos dattes ont un impact carbone nul.", 1),
    ("Fabriqué avec amour et dans le respect de l'environnement.", 1),
    ("Le leben le plus écologique de Tunisie.", 1),
    ("Huile certifiée bio Ecocert, certificat ECO-TN-2025-0187 consultable en ligne.", 0),
    ("Piments cultivés à Korba puis transformés à Nabeul, à 12 km.", 0),
    ("Bocal en verre consigné, taux de retour de 64 % mesuré en 2025.", 0),
    ("Consommation d'eau réduite de 30 % depuis 2023 grâce au goutte-à-goutte, relevés disponibles.", 0),
    ("Analyse SGS du lot 2291 : aucun résidu de pesticide détecté.", 0),
    ("Empreinte de 1,1 kg CO2e par kilo calculée selon la méthode ACV PEF.", 0),
    ("Jusqu'à 80 % d'impact en moins sur la planète !", 1),
    ("Une démarche verte reconnue par nos clients.", 1),
    ("Emballage 100 % responsable.", 1),
    ("Nos oliviers sont irrigués au goutte-à-goutte : 35 litres par kilo, relevé 2025 publié.", 0),
    ("Fièrement produit à 8 km de notre huilerie, trace logistique consultable.", 0),
    ("Compensation de 120 tonnes de CO2 via Gold Standard, attestations publiques.", 0),
]


def _fill(template: str, rng: random.Random) -> str:
    y1 = rng.randint(2018, 2023)
    return template.format(
        cert=f"{rng.choice(['ECO', 'TN', 'FSC', 'APIA', 'LOT'])}-{rng.randint(1000, 99999)}", year=rng.randint(2025, 2029),
        pct=rng.randint(12, 95), y1=y1, y2=y1 + rng.randint(1, 4), km=rng.randint(2, 60), water=rng.randint(20, 400),
        kwh=rng.randint(5_000, 900_000), lab=rng.choice(LABS), t=rng.randint(5, 900), co2=round(rng.uniform(0.4, 9.0), 1),
    )


def _cap(text: str) -> str:
    return text[:1].upper() + text[1:]


def make_dataset(n: int = 5000, seed: int = 42) -> pd.DataFrame:
    rng = random.Random(seed)
    rows = []
    for _ in range(n):
        product = rng.choice(PRODUCTS)
        r = rng.random()
        if r < 0.38:  # vague claim
            terms = rng.sample(VAGUE, k=rng.randint(1, 3))
            text = f"{_cap(product)} : {', '.join(terms)}{rng.choice(VAGUE_TAILS)}"
            label = 1
        elif r < 0.46:  # vague claim dressed up with numbers
            text = f"{_cap(product)} : {_fill(rng.choice(VAGUE_WITH_NUMBERS), rng)}{rng.choice(VAGUE_TAILS)}"
            label = 1
        elif r < 0.78:  # precise, verifiable claim
            text = f"{_cap(product)} : {_fill(rng.choice(PRECISE), rng)}."
            label = 0
        elif r < 0.85:  # precise claim in a marketing tone
            text = f"{_cap(product)}, {_fill(rng.choice(PRECISE_PLAIN), rng)} !"
            label = 0
        else:  # mixed: a vague slogan, sometimes backed by evidence
            backed = rng.random() < 0.5
            text = f"{_cap(product)} {rng.choice(VAGUE)}"
            if backed:
                text += f" : {_fill(rng.choice(PRECISE), rng)}."
            label = 0 if backed else 1
        # ~3 % of labels flipped: human annotators disagree on borderline claims
        if rng.random() < 0.03:
            label = 1 - label
        rows.append({"text": text, "greenwashing": label})
    return pd.DataFrame(rows)


# Words that carry no meaning on their own (French stop words + product nouns)
STOP_WORDS = [
    "le", "la", "les", "l", "un", "une", "des", "de", "du", "d", "et", "ou", "en", "a", "au", "aux", "ce", "ces", "cet", "cette",
    "notre", "nos", "votre", "vos", "pour", "par", "sur", "dans", "avec", "est", "sont", "qui", "que", "nous", "vous", "son", "sa", "ses",
    "huile", "dattes", "harissa", "couscous", "miel", "leben", "jus", "produit", "produits", "amandes", "gamme", "piments", "tomates",
]


def _vectorizers():
    words = TfidfVectorizer(ngram_range=(1, 3), min_df=2, sublinear_tf=True, strip_accents="unicode", stop_words=STOP_WORDS)
    chars = TfidfVectorizer(analyzer="char_wb", ngram_range=(3, 5), min_df=3, sublinear_tf=True, strip_accents="unicode")
    return words, chars


def _fit(texts, labels, seed: int) -> dict:
    words, chars = _vectorizers()
    X = hstack([words.fit_transform(texts), chars.fit_transform(texts)]).tocsr()
    clf = LogisticRegression(max_iter=3000, C=3.0, class_weight="balanced", random_state=seed).fit(X, labels)
    return {"words": words, "chars": chars, "clf": clf}


def _proba(bundle: dict, texts) -> np.ndarray:
    X = hstack([bundle["words"].transform(texts), bundle["chars"].transform(texts)]).tocsr()
    return bundle["clf"].predict_proba(X)[:, 1]


def train(df: pd.DataFrame, seed: int = 42):
    X_train, X_test, y_train, y_test = train_test_split(df["text"], df["greenwashing"], test_size=0.2, random_state=seed, stratify=df["greenwashing"])
    bundle = _fit(X_train, y_train, seed)
    proba = _proba(bundle, X_test)
    hw_text, hw_label = zip(*HANDWRITTEN)
    hw_proba = _proba(bundle, list(hw_text))

    metrics = {
        "roc_auc": round(float(roc_auc_score(y_test, proba)), 3),
        "accuracy": round(float(accuracy_score(y_test, proba >= 0.5)), 3),
        "f1": round(float(f1_score(y_test, proba >= 0.5)), 3),
        "accuracy_handwritten": round(float(accuracy_score(hw_label, hw_proba >= 0.5)), 3),
    }
    return _fit(df["text"], df["greenwashing"], seed), metrics


def _strip(word: str) -> str:
    return "".join(c for c in unicodedata.normalize("NFKD", word.lower()) if not unicodedata.combining(c))


def _original(term: str, text: str) -> str:
    """The model works on accent-free words: show the expression as written in the text."""
    words = re.findall(r"\w+", text)
    target = term.split()
    for i in range(len(words) - len(target) + 1):
        if [_strip(w) for w in words[i:i + len(target)]] == target:
            return " ".join(words[i:i + len(target)])
    return term


def predict(bundle, payload: dict) -> dict:
    text = (payload.get("text") or "").strip()
    probability = float(_proba(bundle, [text])[0])

    # Explanation: word n-grams of this text weighted by their coefficient
    words: TfidfVectorizer = bundle["words"]
    vec = words.transform([text])
    coefs = bundle["clf"].coef_[0][: len(words.vocabulary_)]
    vocab = words.get_feature_names_out()
    contributions = [(vocab[i], float(vec[0, i] * coefs[i])) for i in vec.nonzero()[1]]
    risky = [term for term, c in sorted(contributions, key=lambda t: -t[1]) if c > 0.05][:4]
    reassuring = [term for term, c in sorted(contributions, key=lambda t: t[1]) if c < -0.05][:3]

    return {
        "probability": round(probability * 100, 1),
        "is_greenwashing": probability >= 0.5,
        "risky_terms": [_original(t, text) for t in risky],
        "reassuring_terms": [_original(t, text) for t in reassuring],
    }
