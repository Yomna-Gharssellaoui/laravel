# Guide de test — Trace Verte

Jeu de données : `database/seeders/TraceVerteDatasetSeeder.php` (produits tunisiens).
Pour repartir de zéro à tout moment :

```bash
php artisan migrate:fresh --seed
```

Mot de passe de **tous** les comptes : `password`

## Comptes

| Email | Rôle | Particularité |
|---|---|---|
| admin@traceverte.test | admin | Accès à tout le back-office |
| producer@traceverte.test | producteur | Lié à **Domaine Zitouna** (huile, amandes) |
| producer2@traceverte.test | producteur | Lié à **Oasis de Tozeur** (dattes) |
| transformer@traceverte.test | transformateur | Lié à **Huilerie du Sahel** (huile) |
| distributor@traceverte.test | distributeur | Lié à **Distri Nord** (4 traces) |
| consumer@traceverte.test | consommateur | Préférences bio + faible CO₂, a déjà donné 2 avis |
| sara@traceverte.test | consommatrice | Préférence local, **allergique au gluten** |
| karim@traceverte.test | consommateur | Préférences faible eau + bio, 1 avis en attente |
| nouveau@traceverte.test | consommateur | **Email non vérifié** |
| bloque@traceverte.test | consommateur | **Compte suspendu** |

## Données et cas prévus

| Produit | Statut | Ce qu'il permet de tester |
|---|---|---|
| Huile d’olive extra vierge bio | publié | Produit « parfait » : certifié, déclaration vérifiée, trace complète Sfax → Tunis |
| Dattes Deglet Nour | publié | Certification qui **expire dans 20 jours**, déclaration « neutre en carbone » **rejetée**, alerte ouverte |
| Harissa artisanale | publié | Certification **en attente**, déclaration à vérifier, avis à modérer |
| Couscous complet | publié | Contient du **gluten** → écarté des recommandations de Sara |
| Miel de thym | publié | Certification **dont la date est dépassée** (passe en « expiré » au scan) |
| Leben | publié | Empreinte **très élevée**, certification rejetée, déclaration vague (greenwashing 100/100), avis spam refusé |
| Jus d’orange maltaise | publié | Livraison **en retard** |
| Tomates séchées | brouillon | **Impossible à publier** (ni empreinte ni certification) |
| Amandes de Sfax | archivé | N'apparaît pas sur le site public |

---

## 1. Tester en tant qu'utilisateur normal (consommateur)

Connectez-vous avec **consumer@traceverte.test**.

**Navigation et profil**
- [ ] Après connexion, vous arrivez sur **Mon espace** (pas sur le back-office).
- [ ] Le menu affiche Mon espace, Mon compte (avec vos initiales) et **Déconnexion**.
- [ ] Mon espace → **Modifier mes préférences** : cochez « Local », ajoutez l’allergie `gluten`, enregistrez.
- [ ] **Voir mes recommandations** : les scores sont recalculés, le Couscous apparaît dans « Produits écartés ».

**Catalogue et fiche produit**
- [ ] Produits : seuls les 7 produits publiés apparaissent (pas les tomates ni les amandes).
- [ ] Recherchez `harissa` dans la barre de recherche.
- [ ] Ouvrez **Huile d’olive** : empreinte, certifications, déclaration vérifiée, QR code.
- [ ] **Voir la traçabilité** : parcours Sfax → Sousse → Tunis avec les dates.
- [ ] Assistant : cliquez « D’où vient ce produit ? » → une réponse s’affiche.
- [ ] Le QR code se télécharge (« Télécharger »).

**Avis**
- [ ] Sur **Harissa**, donnez un avis 4/5 → message « en attente de modération », l’avis n’est pas encore public.
- [ ] Sur **Huile d’olive**, le formulaire affiche « Modifier mon avis » (avis existant), modifiez-le → il repasse en attente.

**Compte**
- [ ] Mon compte → ajoutez une **photo de profil** → elle apparaît dans le menu.
- [ ] Changez le mot de passe (ancien : `password`).
- [ ] Activez la **2FA** : mot de passe, scannez le QR code avec Google Authenticator, saisissez le code.
  Notez les codes de récupération, déconnectez-vous, reconnectez-vous : le code est demandé.
  Testez aussi la connexion avec un **code de récupération**.

**Sécurité (ce qu'un utilisateur normal ne doit PAS pouvoir faire)**
- [ ] Ouvrez http://127.0.0.1:8000/admin/dashboard → **403 Accès non autorisé**.
- [ ] Ouvrez http://127.0.0.1:8000/admin/users → **403**.
- [ ] Ouvrez http://127.0.0.1:8000/produits/8 (brouillon) → **404**.

## 2. Inscription, email et comptes particuliers

- [ ] **Inscription** d’un nouveau compte → page « Vérifiez votre email ».
- [ ] Ouvrez `storage/logs/laravel.log`, copiez le lien « Vérifier l’adresse email » dans le navigateur → compte activé.
- [ ] **Mot de passe oublié** avec `karim@traceverte.test` → lien dans `laravel.log` → choisissez un nouveau mot de passe.
- [ ] Connexion avec **nouveau@traceverte.test** → redirigé vers « Vérifiez votre email ».
- [ ] Connexion avec **bloque@traceverte.test** → « Votre compte a été suspendu ».
- [ ] Connexion avec un mauvais mot de passe → message en français.

## 3. Back-office administrateur

Connectez-vous avec **admin@traceverte.test**.

| Module | À tester |
|---|---|
| Tableau de bord | Chiffres et graphiques ; 1 alerte ouverte |
| Utilisateurs | Filtrer par rôle ; passer Karim en distributeur ; **réactiver** bloque@ ; renvoyer la vérification à nouveau@ |
| Produits | Créer un produit en brouillon ; essayer de publier **Tomates séchées** → refus expliqué ; ajouter une image ; « Analyser (IA) » sur Leben |
| Empreintes | Ajouter une empreinte aux Tomates : le **score est calculé** automatiquement |
| Certifications | Badge « Expire dans 20 j » sur les dattes ; ajouter un certificat PDF ; même numéro + même émetteur → refusé |
| Déclarations | Ouvrir « Leben 100% naturel » : analyse greenwashing 100/100 et signaux d’alerte ; « Relancer l’analyse IA » |
| Conformité | Créer une vérification **failed** sur la déclaration Harissa → une **alerte est créée** et la déclaration passe en « rejeté » |
| Alertes | Passer une alerte en « résolue » **sans** résolution → refusé ; avec résolution → accepté |
| Traces | Créer une trace avec 2 étapes (lat/lon) : distance calculée, étape actuelle = la dernière par date |
| Avis | 2 avis **en attente** en tête de liste : approuver celui de Karim, refuser celui de Sara |
| Analyses IA | 4 analyses générées par l’heuristique (ou par Claude si `ANTHROPIC_API_KEY` est renseignée) |
| Recommandations | « Recalculer toutes les recommandations » ; Sara : Couscous à 0 (gluten) |

**Alertes automatiques**

```bash
php artisan traceverte:scan-alerts
```

- [ ] Résultat : **4 alertes créées** (miel expiré, dattes expirant bientôt, jus en retard, leben empreinte élevée),
  **5 avec le service ML démarré** (+ « Empreinte atypique (détection ML) » sur le leben).
- [ ] La certification du miel passe en **expiré**.
- [ ] Relancez la commande → **0 alerte** (pas de doublon).

## 4. Rôles métier (propriété des données)

| Compte | Doit voir / pouvoir | Ne doit PAS pouvoir |
|---|---|---|
| producer@ | Ses 2 produits (huile, amandes), en créer un nouveau (rattaché automatiquement à Domaine Zitouna), leurs certifications | Voir les dattes ; ouvrir `/admin/products/2` → 403 ; ouvrir `/admin/users` → 403 ; valider sa propre certification (statut forcé à « en attente ») |
| producer2@ | Les dattes uniquement | Modifier l’huile |
| transformer@ | L’huile (qu’il transforme) | Créer un produit, changer le producteur |
| distributor@ | Les 4 traces de Distri Nord, en créer une | Voir les traces de Sahel Logistique (dattes, harissa) |

## 5. Machine learning (un modèle par module)

Démarrer le service (voir `ml/README.md`), mettre `ML_SERVICE_URL=http://127.0.0.1:8001` dans `.env`, puis :

```bash
php artisan config:clear
php artisan traceverte:ml-check
```

- [ ] La commande affiche **7/7 modèles chargés** et leurs scores.
- [ ] Back-office → **Modèles ML** : une carte par modèle (algorithme, taille des données, scores).

| Module | Où | Résultat attendu sur le jeu de démonstration |
|---|---|---|
| M1 | Produits → Huile d’olive → Voir | Carte « Empreinte estimée par le modèle » : CO₂ ≈ 3,1 kg (mesuré 3,10), score estimé ≈ mesuré |
| M1 | Produit sans empreinte → « Créer l’empreinte avec ces valeurs » | Formulaire pré-rempli avec l’estimation |
| M2 | Producteurs | Domaine Zitouna → *Exploitation bio certifiée* ; Ferme du Cap Bon → *Petite ferme agroécologique* ; Coopérative de Béja → *Grande exploitation* |
| M3 | Traces logistiques | Dattes (en transit) → risque ≈ 32 % *moyen* ; Harissa → ≈ 11 % *faible* ; traces livrées → « — » |
| M4 | Modération des avis | Avis « PROMO !!! cliquez ici » → **Spam** ; les autres → positif / neutre |
| M4 | Côté consommateur : poster l’avis « Gagnez un iPhone sur http://promo.example » | Avis enregistré directement **rejeté** |
| M5 | Déclarations → Analyser | Moteur `ml-m5-greenwashing (scikit-learn)` ; « 100 % naturel, respectueux de la planète » → score élevé avec les expressions en cause |
| M5 | Empreintes | Leben → **Atypique** (score ≈ 86) ; les autres → *Cohérente* |
| M6 | Recommandations → Recalculer | Raison « des consommateurs au profil proche le notent X/5 » sur certains produits |

**Côté consommateur**

| Où | Action | Résultat attendu |
|---|---|---|
| Menu **Outils IA** | Coller « Notre huile 100 % naturelle, respectueuse de la planète et neutre en carbone. » → Analyser | **Greenwashing probable** (≈ 97 %), mots surlignés en rouge |
| Outils IA | Cliquer l’exemple « Huile certifiée bio par Ecocert… » | **Allégation plutôt vérifiable**, éléments en vert |
| Outils IA → Simulateur | Huiles, conventionnelle, pressage à froid, 900 km → Estimer | CO₂ ≈ 3,8 kg ; scénarios : bio ≈ −22 %, circuit court ≈ −14 % ; comparaison avec l’huile du catalogue |
| Fiche Huile d’olive (connecté consumer@) | Taper dans l’avis « Huile rance, goût horrible, très déçu » avec 5/5 | L’assistant affiche **ton négatif** et signale la **note contradictoire** |
| Fiche Huile d’olive | Panneau violet « Ce qu’en disent nos modèles IA » | Empreinte cohérente, estimation 3,07 (déclaré 3,10), ton des avis |
| Fiche Leben | Même panneau | **Empreinte inhabituelle** |
| Fiche produit → Déclarations | Sous chaque déclaration | « Analyse du texte (IA) » avec le % de risque |
| Dattes → Voir la traçabilité | Trace en transit | Badge **Risque de retard (IA) : moyen · 32 %** |

- [ ] Arrêter le service (Ctrl+C) puis recharger ces pages : elles s’affichent normalement avec
  « Service ML arrêté » à la place des prédictions.

## 6. Tests automatiques

```bash
php artisan test
```

103 tests : contrôle d’accès, règles métier, 2FA, photo de profil, IA, machine learning (service simulé),
recommandations, QR code, ouverture de toutes les pages et vérification des scénarios de ce guide.

```bash
cd ml
python -m pytest -q
```

Tests Python : seuil de qualité minimal de chaque modèle + tests de l’API.
