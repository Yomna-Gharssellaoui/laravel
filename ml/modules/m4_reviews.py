"""M4 — Certifications & Avis : analyser les avis des consommateurs (traitement du langage).

Deux classifieurs de texte, TF-IDF (mots + morceaux de mots) puis régression logistique :
  * sentiment : positif / neutre / négatif ;
  * spam : publicité, liens, texte hors sujet → aide à la modération.

Données d'entraînement : avis en français générés en combinant des phrases variées (goût, prix,
emballage, livraison, traçabilité…) avec des fautes et des formulations mixtes. Pour une mesure
honnête, les modèles sont aussi évalués sur des avis rédigés à la main (HANDWRITTEN), jamais vus
pendant l'entraînement.
"""

from __future__ import annotations

import random
import re

import pandas as pd
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import accuracy_score, f1_score
from sklearn.model_selection import train_test_split
from sklearn.pipeline import FeatureUnion, Pipeline

NAME = "m4_reviews"
MODULE = "M4"
TITLE = "Analyse des avis (sentiment et spam)"
ALGORITHM = "TF-IDF (mots + caractères) + régression logistique"
DESCRIPTION = "Classe chaque avis en positif, neutre ou négatif, et repère les avis publicitaires ou frauduleux."

PRODUCTS = ["l'huile", "les dattes", "la harissa", "le couscous", "le miel", "le leben", "le jus", "ce produit", "les amandes", "la confiture"]

POSITIVE = [
    "excellent", "très bon", "délicieux", "parfait", "un vrai régal", "goût authentique", "je recommande vivement",
    "qualité au rendez-vous", "super produit", "on sent la qualité", "très frais", "meilleur que en grande surface",
    "rapport qualité prix imbattable", "emballage soigné", "livraison rapide", "traçabilité rassurante",
    "j'adore", "rien à redire", "conforme à la description", "je rachèterai sans hésiter", "toute la famille a aimé",
    "saveur incroyable", "bien relevé comme il faut", "texture parfaite", "top", "franchement bluffant",
]
NEGATIVE = [
    "décevant", "pas bon du tout", "goût bizarre", "trop cher pour ce que c'est", "arrivé abîmé", "périmé à la réception",
    "je ne recommande pas", "aucun goût", "rance", "trop salé", "emballage cassé", "livraison très en retard",
    "rien à voir avec la photo", "qualité médiocre", "je suis déçue", "plus jamais", "immangeable", "mauvaise surprise",
    "le pot fuyait", "arnaque sur la quantité", "service client injoignable", "amer et désagréable", "nul",
    "étiquette mensongère", "produit avarié", "trop sucré et artificiel",
]
NEUTRAL = [
    "correct sans plus", "bon mais un peu cher", "goût correct, emballage moyen", "ni bon ni mauvais", "conforme, rien d'exceptionnel",
    "pas mal mais j'attendais mieux", "moyen", "ça dépanne", "bon produit mais livraison longue", "assez bon",
    "le goût est bien mais le pot est petit", "pas d'avis tranché", "standard", "honnête pour le prix", "à voir sur la durée",
    "bon goût mais trop salé à mon goût", "satisfaisant", "je pense que c'est correct", "dans la moyenne",
]
SPAM = [
    "offre exceptionnelle réservée aux premiers inscrits sur {url}", "recevez un bon d'achat de 200 dt en remplissant le formulaire {url}",
    "crédit immédiat sans dossier, réponse en 24h au {phone}", "devenez riche avec notre méthode secrète, détails sur {url}",
    "abonnez vous à ma chaîne pour gagner des cadeaux", "écrivez moi en message privé pour une collaboration rémunérée",
    "vente de médicaments sans ordonnance {url}", "paris sportifs gagnants à coup sûr, rejoignez le groupe {url}",
    "voyance sérieuse et rapide, appelez maintenant le {phone}", "tirage au sort smartphone dernier modèle {url}",
    "je vends mon compte, prix intéressant, contact {phone}", "argent rapide en ligne sans effort {url}",
    "PROMO !!! cliquez ici {url}", "gagnez de l'argent facilement depuis chez vous {url}", "achetez des followers pas cher sur {url}",
    "meilleur casino en ligne bonus 500% {url}", "contactez moi sur whatsapp +216 {phone} pour des prix de gros",
    "crypto investissement garanti x10 rejoignez {url}", "vente de comptes premium {url}", "rencontres célibataires {url}",
    "code promo -90% sur tout le site {url}", "travail à domicile 3000 dt par mois appelez {phone}",
    "visitez ma boutique {url} livraison gratuite", "suivez moi sur instagram @{handle} pour des cadeaux",
]
URLS = ["www.promo-deal.test", "bit.ly/xx{n}", "http://gagner-vite.test", "www.super-offre.test/{n}", "tinyurl.com/a{n}"]
CONNECTORS = ["", " ", ", ", ". ", " ! ", " et ", " vraiment ", " franchement "]

HANDWRITTEN = [
    ("Huile au goût fruité, on sent que c'est de la vraie olive. Merci !", "positif"),
    ("Les dattes sont moelleuses et sucrées, mes enfants les adorent.", "positif"),
    ("Harissa parfaite avec le couscous du vendredi, je recommande.", "positif"),
    ("Très satisfaite, colis bien protégé et produit délicieux.", "positif"),
    ("Miel crémeux et parfumé, exactement ce que je cherchais.", "positif"),
    ("Super qualité, je commande à nouveau le mois prochain.", "positif"),
    ("Le leben était tourné à l'arrivée, impossible à boire.", "négatif"),
    ("Couscous plein de grumeaux, très déçu de cet achat.", "négatif"),
    ("Bouteille arrivée cassée et personne ne répond aux mails.", "négatif"),
    ("Beaucoup trop cher pour une si petite quantité, je ne reprendrai pas.", "négatif"),
    ("Goût de rance dès l'ouverture, produit à éviter.", "négatif"),
    ("Le jus n'a aucun goût d'orange, on dirait de l'eau sucrée.", "négatif"),
    ("Correct, mais j'ai trouvé mieux au marché pour moins cher.", "neutre"),
    ("Bon goût mais l'emballage pourrait être amélioré.", "neutre"),
    ("Produit conforme, livraison un peu longue.", "neutre"),
    ("Ça va, rien de spécial à signaler.", "neutre"),
    ("Les amandes sont bonnes mais certaines étaient abîmées.", "neutre"),
    ("Assez bien dans l'ensemble, prix moyen.", "neutre"),
]
HANDWRITTEN_SPAM = [
    ("Gagnez un iPhone gratuit en vous inscrivant sur www.cadeau-gratuit.test", 1),
    ("Prêt rapide sans justificatif, appelez le 98 765 432", 1),
    ("Découvrez mes vidéos sur mon profil, lien en bio", 1),
    ("Formation trading rentable, places limitées, écrivez-moi en privé", 1),
    ("Le miel est délicieux, je recommande à tous.", 0),
    ("Couscous très décevant, beaucoup trop de grumeaux.", 0),
    ("Livraison rapide et produit conforme.", 0),
    ("Harissa correcte mais un peu trop forte pour moi.", 0),
]


URL_RE = re.compile(r"(https?://\S+|www\.\S+|\b\S+\.(?:com|net|org|test|ly|tn)\S*)", re.IGNORECASE)
PHONE_RE = re.compile(r"(\+?\d[\d\s.-]{6,}\d)")
HANDLE_RE = re.compile(r"@\w+")


def normalize(text: str) -> str:
    """Text cleaning shared by training and prediction: links, phone numbers and @handles
    become tokens, so the model learns "contains a link" rather than one particular address."""
    text = URL_RE.sub(" urltoken ", text)
    text = PHONE_RE.sub(" teltoken ", text)
    text = HANDLE_RE.sub(" handletoken ", text)
    return text


def _typo(text: str, rng: random.Random) -> str:
    """A few realistic slips: missing accents, upper case, doubled letters."""
    if rng.random() < 0.2:
        text = text.replace("é", "e").replace("è", "e").replace("à", "a")
    if rng.random() < 0.1:
        text = text.upper()
    if rng.random() < 0.1 and len(text) > 5:
        i = rng.randrange(len(text))
        text = text[:i] + text[i] + text[i:]
    return text


def _review(phrases: list[str], rng: random.Random) -> str:
    parts = rng.sample(phrases, k=rng.randint(1, 3))
    text = rng.choice(PRODUCTS).capitalize() + " : " if rng.random() < 0.5 else ""
    for part in parts:
        text += part + rng.choice(CONNECTORS)
    return text.strip()


def make_dataset(n: int = 6000, seed: int = 42) -> pd.DataFrame:
    rng = random.Random(seed)
    rows = []
    for _ in range(n):
        kind = rng.choices(["positif", "négatif", "neutre", "spam"], weights=[0.35, 0.25, 0.25, 0.15])[0]
        if kind == "spam":
            text = rng.choice(SPAM).format(
                url=rng.choice(URLS).format(n=rng.randint(1, 999)), phone=rng.randint(20_000_000, 99_999_999),
                handle="deal" + str(rng.randint(1, 99)))
            # Spammers sometimes start with a fake compliment
            if rng.random() < 0.3:
                text = rng.choice(POSITIVE) + " ! " + text
            rows.append({"text": _typo(text, rng), "sentiment": None, "spam": 1})
            continue
        pool = {"positif": POSITIVE, "négatif": NEGATIVE, "neutre": NEUTRAL}[kind]
        # Mixed reviews: a positive or negative remark inside a neutral one
        phrases = pool + (rng.sample(POSITIVE + NEGATIVE, 3) if kind == "neutre" and rng.random() < 0.3 else [])
        rows.append({"text": _typo(_review(phrases, rng), rng), "sentiment": kind, "spam": 0})
    return pd.DataFrame(rows)


def _prep(text: str) -> str:
    return normalize(text).lower()


def _text_pipeline(seed: int, multiclass: bool) -> Pipeline:
    return Pipeline([
        ("features", FeatureUnion([
            ("words", TfidfVectorizer(ngram_range=(1, 2), min_df=2, sublinear_tf=True, strip_accents="unicode", lowercase=True, preprocessor=_prep)),
            ("chars", TfidfVectorizer(analyzer="char_wb", ngram_range=(3, 5), min_df=3, sublinear_tf=True, strip_accents="unicode", preprocessor=_prep)),
        ])),
        ("clf", LogisticRegression(max_iter=2000, C=4.0, class_weight="balanced", random_state=seed)),
    ])


def train(df: pd.DataFrame, seed: int = 42):
    opinions = df[df["spam"] == 0]

    X_train, X_test, y_train, y_test = train_test_split(opinions["text"], opinions["sentiment"], test_size=0.2, random_state=seed, stratify=opinions["sentiment"])
    sentiment = _text_pipeline(seed, True).fit(X_train, y_train)
    hw_text, hw_label = zip(*HANDWRITTEN)
    hw_pred = sentiment.predict(list(hw_text))

    S_train, S_test, s_train, s_test = train_test_split(df["text"], df["spam"], test_size=0.2, random_state=seed, stratify=df["spam"])
    spam = _text_pipeline(seed, False).fit(S_train, s_train)
    hs_text, hs_label = zip(*HANDWRITTEN_SPAM)

    metrics = {
        "sentiment_accuracy": round(float(accuracy_score(y_test, sentiment.predict(X_test))), 3),
        "sentiment_macro_f1": round(float(f1_score(y_test, sentiment.predict(X_test), average="macro")), 3),
        "sentiment_accuracy_handwritten": round(float(accuracy_score(hw_label, hw_pred)), 3),
        "spam_f1": round(float(f1_score(s_test, spam.predict(S_test))), 3),
        "spam_accuracy_handwritten": round(float(accuracy_score(hs_label, spam.predict(list(hs_text)))), 3),
    }

    final = {
        "sentiment": _text_pipeline(seed, True).fit(opinions["text"], opinions["sentiment"]),
        "spam": _text_pipeline(seed, False).fit(df["text"], df["spam"]),
    }
    return final, metrics


def predict(bundle, payload: dict) -> dict:
    text = (payload.get("text") or "").strip()
    sentiment_model, spam_model = bundle["sentiment"], bundle["spam"]

    spam_proba = float(spam_model.predict_proba([text])[0, 1])
    proba = dict(zip(sentiment_model.classes_, sentiment_model.predict_proba([text])[0]))
    label = max(proba, key=proba.get)

    return {
        "sentiment": label,
        "confidence": round(float(proba[label]) * 100, 1),
        "probabilities": {k: round(float(v) * 100, 1) for k, v in proba.items()},
        "spam_probability": round(spam_proba * 100, 1),
        "is_spam": spam_proba >= 0.5,
    }
