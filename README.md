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

Tâches planifiées (expiration des certifications, alertes automatiques) :

```bash
php artisan schedule:work          # en local
php artisan traceverte:scan-alerts # exécution manuelle
```

## Comptes de démonstration

Mot de passe : `password`

| Email | Rôle | Accès |
|---|---|---|
| admin@traceverte.test | admin | tout le back-office |
| producer@traceverte.test | producer | ses produits, leurs certifications et traces |
| transformer@traceverte.test | transformer | les produits qu'il transforme |
| distributor@traceverte.test | distributor | les traces logistiques qu'il assure |
| consumer@traceverte.test | consumer | site public, avis, recommandations |

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

## IA : détection du greenwashing

`app/Services/GreenwashingAnalyzer.php` envoie la déclaration et ses preuves (certifications, empreinte,
vérifications) à l'API Claude et reçoit une réponse JSON structurée (score de risque, crédibilité,
résumé, signaux d'alerte). Renseignez `ANTHROPIC_API_KEY` dans `.env` pour l'activer.

Sans clé (ou si l'API ne répond pas), une heuristique locale explicable prend le relais ; l'analyse est
alors marquée `is_demo` et le moteur affiché est `heuristique-locale-v1`.

## Tests

```bash
php artisan test
```
