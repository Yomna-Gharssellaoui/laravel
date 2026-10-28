# Contribuer à Trace Verte

## Branches

- `main` : version stable, protégée (pas de push direct).
- `develop` : intégration des fonctionnalités terminées.
- Une branche par fonctionnalité, créée depuis `develop` :
  `feature/<module>-<description>` (ex. `feature/certificates-upload`), `fix/<description>`.

## Commits

Messages courts au présent, préfixés par le type :

```
feat(products): ajout du QR code de traçabilité
fix(auth): bouton de déconnexion manquant
test(alerts): alerte créée après une vérification échouée
docs: installation Docker
```

## Pull requests

1. Poussez votre branche et ouvrez une PR vers `develop`.
2. La CI GitHub Actions doit être verte (tests, Pint, build front, image Docker).
3. Au moins une relecture par un autre membre du groupe avant la fusion.
4. Fusion de `develop` vers `main` à chaque version de démonstration.

## Avant de pousser

```bash
vendor/bin/pint
php artisan test
```
