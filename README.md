# Trace Verte

Plateforme de traçabilité alimentaire : chaque produit est suivi du producteur au distributeur,
avec son empreinte environnementale, ses certifications, ses déclarations environnementales et
une détection du greenwashing par IA.

Laravel 12 · Breeze (Blade) · Tailwind (back-office) · Bootstrap (site public) · SQLite par défaut.

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
npm install && npm run build
composer dev   # serveur + worker de file d'attente + logs + Vite
```

### Avec Docker (app + file d'attente + planificateur + MySQL + Mailpit)

```bash
cp .env.example .env        # puis renseigner APP_KEY (php artisan key:generate --show)
docker compose up -d --build
```

- Application : http://localhost:8000
- Emails envoyés (vérification, mot de passe oublié, alertes) : http://localhost:8025

### Recevoir les emails en local sans Docker

Par défaut (`MAIL_MAILER=log`) les emails sont écrits dans `storage/logs/laravel.log`.
Pour les recevoir réellement, utilisez un compte gratuit [Mailtrap](https://mailtrap.io) (Email Testing)
et copiez ses identifiants SMTP dans `.env` :

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=...
MAIL_PASSWORD=...
```

Tâches planifiées (expiration des certifications, alertes automatiques) :

```bash
php artisan schedule:work          # en local
php artisan traceverte:scan-alerts # exécution manuelle
```

## Jeu de données et guide de test

`php artisan migrate:fresh --seed` charge un jeu de données cohérent (produits tunisiens) avec tous les
cas à démontrer. Les scénarios de test, module par module, sont dans [docs/GUIDE-DE-TEST.md](docs/GUIDE-DE-TEST.md).

## Comptes de démonstration

Mot de passe : `password`

| Email | Rôle | Accès |
|---|---|---|
| admin@traceverte.test | admin | tout le back-office |
| producer@traceverte.test | producer | ses produits, leurs certifications et traces |
| transformer@traceverte.test | transformer | les produits qu'il transforme |
| distributor@traceverte.test | distributor | les traces logistiques qu'il assure |
| producer2@traceverte.test | producer | un autre producteur (dattes) |
| consumer@traceverte.test | consumer | site public, avis, recommandations |
| sara@traceverte.test | consumer | allergique au gluten |
| nouveau@traceverte.test | consumer | email non vérifié |
| bloque@traceverte.test | consumer | compte suspendu |

## Rôles et permissions

- Rôles stockés dans `users.role` ; le rôle n'est **pas** assignable en masse : seul un admin le change.
- Routes protégées par les middlewares `auth`, `verified` (MustVerifyEmail), `active` (comptes suspendus) et `role:...`.
- Produits, certifications et traces sont partagés avec les acteurs de la chaîne : l'accès est contrôlé
  par les policies `ProductPolicy`, `CertificatePolicy` et `SupplyChainTracePolicy`, et les listes sont
  filtrées par `Product::visibleTo($user)`.
- Un producteur ne peut pas valider ses propres certifications (statut forcé à `pending`).

## Modules du back-office

| Module | Fonctionnalités |
|---|---|
| Utilisateurs | CRUD, changement de rôle, suspension, renvoi de l'email de vérification |
| Produits | CRUD, image, filtres, publication bloquée sans empreinte ni certification, analyse IA |
| Empreintes | CO₂ / eau / sols ; score 0-100 **calculé** (CO₂ 50 %, eau 30 %, sols 20 %) |
| Déclarations | CRUD, justificatif PDF/image, analyse de greenwashing lancée automatiquement (file d'attente) |
| Conformité | Produit repris de la déclaration ; `failed` ⇒ alerte + déclaration rejetée, `passed` ⇒ validée |
| Producteurs / Transformateurs / Distributeurs | CRUD, compte lié unique et du bon rôle |
| Traces logistiques | Étapes datées avec GPS, position actuelle et distance (Haversine) calculées |
| Certifications | CRUD, document, numéro unique par émetteur, badge d'expiration, expiration automatique |
| Alertes | CRUD, lien vers la vérification, résolution obligatoire à la clôture, email aux admins (high/critical), alertes automatiques |
| Avis | Rédigés par les consommateurs sur le site, modérés (approuver / refuser) dans le back-office |
| Analyses IA | Générées par `GreenwashingAnalyzer`, jamais saisies à la main |
| Consommateurs / Recommandations | Préférences fixes, allergies ; scores calculés par `RecommendationEngine` |

## Gestion des utilisateurs

| Exigence | Implémentation |
|---|---|
| Inscription, connexion, déconnexion | Laravel Breeze, routes protégées par `auth` |
| Mot de passe oublié | Lien par email, jeton valable 60 minutes |
| Vérification d'email | `MustVerifyEmail` + middleware `verified` |
| Rôles | admin, producer, transformer, distributor, consumer ; middleware `role`, policies, contrôle serveur |
| 2FA | TOTP via Laravel **Fortify** : QR code, confirmation par un premier code, 8 codes de récupération, activation et désactivation par l'utilisateur, étape de code à la connexion |
| Profil | Nom, email, mot de passe, **photo de profil**, suppression du compte |
| Administration | CRUD des comptes, changement de rôle, suspension, renvoi de l'email de vérification |

Messages de validation et emails en français (`lang/fr`).

## IA : détection du greenwashing

`app/Services/GreenwashingAnalyzer.php` envoie la déclaration et ses preuves (certifications, empreinte,
vérifications) au modèle d'IA et reçoit une réponse JSON structurée (score de risque, crédibilité,
résumé, signaux d'alerte).

Deux fournisseurs sont pris en charge (`app/Services/Ai`), au choix dans `.env` :

| Fournisseur | Variables | Clé |
|---|---|---|
| Google Gemini | `GEMINI_API_KEY`, `GEMINI_MODEL` (défaut `gemini-2.5-flash`) | gratuite sur https://aistudio.google.com/apikey |
| Anthropic Claude | `ANTHROPIC_API_KEY`, `ANTHROPIC_MODEL` (défaut `claude-opus-5-5`) | https://console.anthropic.com |

`AI_PROVIDER=auto` utilise le premier fournisseur qui a une clé ; `gemini` ou `anthropic` en impose un.

Sans clé (ou si l'API ne répond pas), une heuristique locale explicable prend le relais ; l'analyse est
alors marquée `is_demo` et le moteur affiché est `heuristique-locale-v1`.

## IA : assistant produit

Sur chaque fiche produit, un consommateur connecté pose une question en langage naturel
(« Ce produit est-il vraiment bio ? »). `app/Services/ProductAssistant.php` envoie à Claude la fiche
de traçabilité du produit (producteur, parcours, certifications, empreinte, déclarations, alertes, avis)
et l'assistant répond uniquement à partir de ces données. Limité à 10 questions par minute.

## Autres fonctionnalités avancées

- QR code de traçabilité par produit (à imprimer sur l'emballage), téléchargeable en SVG
- Files d'attente (analyse IA des déclarations), tâches planifiées, notifications email
- Recommandations personnalisées calculées, téléversement de fichiers, tableau de bord avec graphiques

## DevOps

- `Dockerfile` multi-étapes (dépendances Composer, build Vite, PHP 8.3 + Apache) et `docker-compose.yml`
- CI GitHub Actions (`.github/workflows/ci.yml`) : tests sur PHP 8.2 et 8.3, Pint, audit de sécurité,
  build front, image Docker publiée sur GHCR à chaque push sur `main`
- `Jenkinsfile` et `sonar-project.properties` pour une chaîne Jenkins + SonarQube
- Workflow Git : voir [CONTRIBUTING.md](CONTRIBUTING.md)

## Tests

```bash
php artisan test
```
