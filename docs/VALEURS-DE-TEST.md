# Valeurs de test pour les CRUD

À utiliser après `php artisan migrate:fresh --seed`, connecté avec **admin@traceverte.test** / `password`
(sauf mention contraire). Pour chaque module : des valeurs **valides** pour créer, des valeurs pour
**modifier**, et des valeurs **invalides** qui doivent afficher un message d'erreur.

Un champ non mentionné peut rester vide.

---

## 1. Utilisateurs — Administration › Utilisateurs › Ajouter

**Créer**

| Champ | Valeur |
|---|---|
| Nom | `Ines Producteur` |
| Email | `ines@traceverte.test` |
| Rôle | `producer` |
| Mot de passe | `Test12345!` |
| Confirmation | `Test12345!` |

Résultat : compte créé, email de vérification dans `storage/logs/laravel.log`.

**Modifier** : Rôle → `distributor`, Nom → `Ines Distribution`.

**Autres actions** : « Suspendre » puis « Réactiver » ; sur `nouveau@traceverte.test`, « Renvoyer l’email de vérification ».

**Erreurs attendues**

| Saisie | Message |
|---|---|
| Email `admin@traceverte.test` | la valeur du champ adresse email est déjà utilisée |
| Confirmation `Autre123!` | la confirmation ne correspond pas |
| Mot de passe `abc` | le champ mot de passe doit contenir au moins 8 caractères |
| Votre propre compte admin → rôle `consumer` | vous ne pouvez pas retirer votre propre rôle |

---

## 2. Produits — Produits › Ajouter

**Créer**

| Champ | Valeur |
|---|---|
| Nom | `Figues séchées de Djerba` |
| Code-barres | `6191234000103` |
| SKU | `TV-FIG-010` |
| Catégorie | `fruits secs` |
| Origine | `Djerba, Tunisie` |
| Statut | `draft` |
| Producteur | `Oasis de Tozeur` |
| Transformateur | — |
| Ingrédients | `Figues séchées` |
| Image | n’importe quelle photo JPG/PNG < 2 Mo |

**Modifier** : Catégorie → `épicerie`, Ingrédients → `Figues séchées, farine de riz`.

**Erreurs attendues**

| Saisie | Message |
|---|---|
| Nom vide | le champ nom est obligatoire |
| Code-barres `6191234000011` (huile) | la valeur du champ code-barres est déjà utilisée |
| SKU `TV-OLV-001` | déjà utilisé |
| Statut `published` à la création | impossible de publier : il manque une empreinte et une certification |
| Image : un fichier PDF (choisir « Tous les fichiers » dans la fenêtre) | doit être une image |

**Publication pas à pas** (règle métier) :
1. Ouvrez **Tomates séchées à l’huile** → Modifier → Statut `published` → refusé.
2. Ajoutez une empreinte (section 3) puis une certification (section 4) aux Tomates.
3. Statut `published` → accepté, le produit apparaît sur le site public avec son QR code.

---

## 3. Empreintes — Empreintes › Ajouter

**Créer**

| Champ | Valeur |
|---|---|
| Produit | `Tomates séchées à l’huile` (seul produit sans empreinte) |
| Émissions CO₂ | `2.5` |
| Consommation d’eau | `60` |
| Occupation des sols | `1.5` |

Résultat : **score calculé = 87/100** (excellent).

**Modifier** : CO₂ `12`, eau `300`, sols `6` → **score = 40/100** (moderate).

**Erreurs attendues**

| Saisie | Message |
|---|---|
| CO₂ `-5` | la valeur du champ émissions CO₂ doit être au moins 0 |

---

## 4. Certifications — Certifications › Ajouter

**Créer**

| Champ | Valeur |
|---|---|
| Produit | `Tomates séchées à l’huile` |
| Type | `Bio Tunisie` |
| Émetteur | `CNCB` |
| Numéro | `CNCB-BIO-9001` |
| Date d’émission | `01/10/2026` |
| Date d’expiration | `01/10/2027` |
| Statut | `verified` |
| Certificat | un PDF quelconque < 5 Mo |

**Modifier** : Date d’expiration → dans 10 jours (ex. `08/11/2026`) → badge **« Expire dans 10 j »** dans la liste.

**Erreurs attendues**

| Saisie | Message |
|---|---|
| Expiration `01/09/2026` (avant l’émission) | doit être postérieure ou égale à la date d’émission |
| Émetteur `Ecocert` + Numéro `ECO-TN-2025-0187` | ce numéro existe déjà pour cet émetteur |
| Émetteur `Bureau Veritas` + Numéro `ECO-TN-2025-0187` | accepté (autre émetteur) |
| Document : un fichier `.docx` (« Tous les fichiers » dans la fenêtre) | doit être de type : pdf, jpg, jpeg, png |

**Avec producer@traceverte.test** : la liste des produits ne propose que l’huile et les amandes, et le
statut est forcé à « en attente » (seul l’admin valide).

---

## 5. Déclarations environnementales — Déclarations › Ajouter

**Créer une déclaration sérieuse**

| Champ | Valeur |
|---|---|
| Produit | `Jus d’orange maltaise` |
| Type | `Produit local` |
| Titre | `Oranges récoltées à moins de 20 km` |
| Statut | `pending` |
| Référence du document | `attestation-origine-2026.pdf` |
| Description | `Oranges maltaises de Menzel Bouzelfa, pressées à Nabeul (18 km).` |

Résultat attendu : risque de greenwashing **faible à moyen**.

**Créer une déclaration douteuse** (pour voir l’IA réagir)

| Champ | Valeur |
|---|---|
| Produit | `Harissa artisanale du Cap Bon` |
| Type | `100% écologique` |
| Titre | `Harissa 100% naturelle et éco-responsable` |
| Statut | `pending` |
| Description | `Un produit vert, durable et respectueux de la planète.` |

Puis ouvrez la déclaration → **Relancer l’analyse IA** → risque **élevé**, crédibilité **faible**,
liste des signaux d’alerte (termes vagues, pas de document, pas de certification vérifiée).

**Erreurs attendues** : Titre effacé (le navigateur le signale) ; Justificatif : un fichier `.txt` → doit être de type : pdf, jpg, jpeg, png.

---

## 6. Vérifications de conformité — Conformité › Ajouter

**Créer une vérification qui échoue** (déclenche une alerte)

| Champ | Valeur |
|---|---|
| Déclaration | `Harissa 100% naturelle et éco-responsable` (créée en 5) |
| Type | `document_review` |
| Statut | `failed` |
| Date | `28/10/2026` |
| Résultat | `Aucun justificatif fourni pour l’allégation « 100% naturelle ».` |

Résultat : message « Une alerte a été créée automatiquement et la déclaration a été rejetée ».
Vérifiez dans **Alertes** (nouvelle alerte « high ») et dans la déclaration (statut **rejected**).

**Créer une vérification qui réussit**

| Champ | Valeur |
|---|---|
| Déclaration | `Oranges récoltées à moins de 20 km` |
| Type | `certificate_check` |
| Statut | `passed` |
| Résultat | `Attestation d’origine conforme.` |

Résultat : la déclaration passe en **verified**.

**Erreurs attendues**

| Saisie | Message |
|---|---|
| Statut `failed` et Résultat vide | le champ résultat est obligatoire |
| Date `01/01/2027` (future) | la date de vérification doit être antérieure ou égale à aujourd’hui |

---

## 7. Producteurs / Transformateurs / Distributeurs

**Producteur — Producteurs › Ajouter**

| Champ | Valeur |
|---|---|
| Raison sociale | `Ferme Bio de Zaghouan` |
| Compte lié | — (tous les comptes producteurs ont déjà une entreprise) |
| Localisation | `Zaghouan, Tunisie` |
| Latitude / Longitude | `36.4029` / `10.1429` |
| Méthode | `biologique` |
| Cultures | `figues, grenades, thym` |
| Capacité | `30 tonnes/an` |
| Certifications | `Bio Tunisie, GlobalGAP` |

**Transformateur — Transformateurs › Ajouter**

| Champ | Valeur |
|---|---|
| Raison sociale | `Séchoir de Djerba` |
| Localisation | `Djerba, Tunisie` |
| Latitude / Longitude | `33.8076` / `10.8451` |
| Type | `séchage solaire` |
| Description du procédé | `Séchage au soleil pendant 5 jours, tri manuel.` |
| Capacité | `50 tonnes/an` |
| Certifications | `HACCP` |

**Distributeur — Distributeurs › Ajouter**

| Champ | Valeur |
|---|---|
| Raison sociale | `Sud Express` |
| Type | `transport routier` |
| Localisation | `Gabès, Tunisie` |
| Latitude / Longitude | `33.8815` / `10.0982` |
| Zone couverte | `Sud tunisien` |

**Erreurs attendues (les trois modules)**

| Saisie | Message |
|---|---|
| Raison sociale vide | obligatoire |
| Latitude `95` | doit être comprise entre -90 et 90 |
| Longitude `200` | doit être comprise entre -180 et 180 |

Le menu « Compte lié » ne propose que les comptes du bon rôle **sans** entreprise : un compte ne peut pas
être rattaché à deux entreprises (contrôlé aussi côté serveur).

---

## 8. Traces logistiques — Traces logistiques › Ajouter

| Champ | Valeur |
|---|---|
| Produit | `Harissa artisanale du Cap Bon` |
| Distributeur | `Distri Nord` |
| Statut | `in_transit` |
| Distance totale | **vide** (calcul automatique) |

Cliquez **+ Ajouter une étape** trois fois :

| Étape | Lieu | Date | Lat. | Lon. |
|---|---|---|---|---|
| `production` | `Nabeul` | `25/10/2026 08:00` | `36.4561` | `10.7376` |
| `transport` | `Tunis` | `27/10/2026 14:00` | `36.8065` | `10.1815` |
| `distribution` | `Bizerte` | `28/10/2026 09:00` | `37.2744` | `9.8739` |

Résultat : distance ≈ **121,85 km**, étape actuelle **distribution**, position = Bizerte, timeline dans la
fiche et sur la page publique de traçabilité.

**Modifier** : Statut → `delayed`, puis lancez `php artisan traceverte:scan-alerts` → alerte « Livraison en retard ».

**Erreurs attendues**

| Saisie | Message |
|---|---|
| Une étape avec Lat. `36.8` mais Lon. vide | le champ longitude de l’étape est obligatoire quand latitude de l’étape est renseigné |
| Une étape sans nom | chaque étape doit avoir un nom |

**Avec distributor@traceverte.test** : le distributeur est imposé (Distri Nord) ; les traces de Sahel
Logistique n’apparaissent pas.

---

## 9. Alertes — Alertes › Ajouter

**Créer**

| Champ | Valeur |
|---|---|
| Produit | `Leben (lait fermenté)` |
| Vérification liée | la vérification `site_audit` du Leben |
| Type | `rappel_qualite` |
| Titre | `Rupture de la chaîne du froid` |
| Sévérité | `critical` |
| Statut | `open` |
| Description | `Température relevée à 12 °C à la livraison.` |

Résultat : alerte créée. Sévérité high/critical → **email aux admins**, envoyé par la file d’attente :
lancez `php artisan queue:work` dans un second terminal, puis cherchez « Alerte critical » dans `storage/logs/laravel.log`.

**Modifier pour clôturer**

| Saisie | Résultat |
|---|---|
| Statut `resolved`, Résolution vide | **erreur** : décrivez la résolution |
| Statut `resolved`, Résolution `Lot retiré de la vente et détruit.` | accepté |

---

## 10. Consommateurs — Consommateurs › Profils › Ajouter

| Champ | Valeur |
|---|---|
| Compte consommateur | `Compte Suspendu (bloque@traceverte.test)` (seul compte sans profil) |
| Niveau de durabilité | `intermédiaire` |
| Budget | `éco` |
| Préférences | cocher `Local` et `Faible CO₂` |
| Restrictions alimentaires | `sans lactose` |
| Allergies | `lait` |

Résultat : profil créé et recommandations calculées ; dans la fiche, le **Leben** a un score de 0
(contient du lait).

Le menu « Compte consommateur » ne propose que les comptes consommateurs sans profil (un profil par compte).

---

## 11. Avis — Qualité › Modération des avis

Pas de création côté admin (les avis viennent du site public).

| Action | Résultat |
|---|---|
| Filtre Statut `pending` | 2 avis : « La meilleure harissa » (Karim), « Correct » (Sara) |
| **Approuver** l’avis de Karim | visible sur la fiche Harissa |
| **Refuser** l’avis de Sara | reste invisible |
| Ouvrir un avis → cocher « Achat vérifié » → Enregistrer la décision | badge achat vérifié |

**Depuis le site** (connecté `consumer@traceverte.test`) : fiche **Harissa** → Note `4 / 5`, Titre
`Parfaite avec le couscous`, Avis `Bien relevée, je recommande.` → « en attente de modération ».

---

## 12. Analyses IA et recommandations

| Action | Où | Résultat |
|---|---|---|
| Analyser (IA) | fiche produit **Leben** (admin) | nouvelle analyse, risque élevé |
| Relancer l’analyse IA | fiche d’une déclaration | analyse mise à jour, score de confiance recalculé |
| Recalculer toutes les recommandations | Consommateurs › Recommandations | « Recommandations recalculées pour 4 consommateur(s) » (5 si vous avez créé le profil de la section 10) |
| Assistant produit | site public, fiche **Couscous** (connecté) | question `Contient-il du gluten ?` → avec clé API : réponse rédigée par l’IA ; sans clé : résumé incluant les ingrédients (« gluten ») |

Sans `ANTHROPIC_API_KEY`, ces analyses utilisent l’heuristique locale (moteur `heuristique-locale-v1`).
