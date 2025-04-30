# Suivi de développement — Version PHP pur

## Journal de bord du 29/04/2025

### Déploiement, corrections et améliorations majeures
- **Automatisation du déploiement** :
  - Création et amélioration du script `deploy.sh` pour valider, commiter, pousser sur GitHub puis déployer automatiquement sur o2switch via SSH/rsync.
  - Ajout d'explications détaillées pour la configuration du script et la gestion des chemins distants.
- **Corrections de liens relatifs/absolus** :
  - Correction de tous les liens `/index.php` et `/css/home.css` dans l'espace admin pour garantir le bon fonctionnement en sous-dossier sur o2switch.
  - Correction du lien "Espace Admin" sur la page d'accueil pour pointer vers `admin/dashboard.php` (chemin relatif).
  - Correction du bouton "Retour à l'accueil" dans le dashboard admin et tous les fichiers admin.
- **Responsive/mobile** :
  - Amélioration du CSS responsive pour la navigation mobile (menu hamburger, overlay, accessibilité sur petit écran).
  - Vérification du fonctionnement du menu sur mobile et debug JS/CSS pour garantir l'accès à toutes les pages.
- **Debug et fiabilité liste de courses** :
  - Ajout de logs/debug pour tracer l'ajout des ingrédients dans la liste de courses et l'affichage côté utilisateur.
  - Vérification de la cohérence entre les tables `recipe_ingredients`, `shopping_list_items` et `ingredients`.
- **Sécurité et base de données** :
  - Migration Doctrine pour forcer la valeur par défaut `[]` sur la colonne `roles` de la table `users` (jamais NULL).
- **Documentation et workflow** :
  - Ajout de l'inclusion de la barre de navigation (`navbar.php`) en haut de `index.php`.
  - Résout le bug où le menu n'était pas accessible sur mobile (seule la page d'accueil s'affichait sans navigation).
  - La navigation est désormais cohérente sur toutes les pages, y compris sur mobile.
  - Ajout d'instructions précises pour l'utilisation de SSH, la création de dossiers distants et la gestion du cache.
  - Conseils pour la gestion des fichiers uploadés (exclusion possible dans rsync).

---

## [2025-04-30] Correction menu mobile sur la page d'accueil

- Ajout de l'inclusion de la barre de navigation (`navbar.php`) en haut de `index.php` (étape précédente).
- Correction : suppression de l'inclusion directe de `navbar.php` dans `index.php` (ligne 39).
- L'inclusion se fait désormais uniquement via le template commun `base.php`, évitant la double barre de navigation et le bug d'affichage/interaction du menu sur mobile.
- Résout le bug où le menu n'était pas accessible ou cliquable sur mobile (présence de deux icônes auparavant).
- Ajout de `type="button"` au bouton hamburger pour éviter les comportements inattendus sur mobile.
- Sécurisation du JavaScript du menu : encapsulation dans `DOMContentLoaded` pour garantir l'exécution après chargement du DOM.
- Ajout de logs JS pour faciliter le debug sur mobile.
- Prévention du comportement par défaut au clic (évite l'ouverture du menu contextuel du navigateur).
- La navigation est désormais cohérente et fonctionnelle sur toutes les pages, y compris sur mobile.

## Objectif global
Créer une application web complète de gestion de recettes de cuisine avec :
- Inscription, connexion et gestion de profil utilisateur
- Ajout, modification, suppression de recettes avec photos
- Recherche et filtrage de recettes (ingrédients, catégories, temps, difficulté)
- Génération automatique d’une liste de courses
- Favoris, commentaires, notes, partage de recettes
- Compatibilité totale hébergement mutualisé o2switch (PHP/MySQL)
- Responsive (mobile/tablette)

## État d’avancement

- [x] Page d’accueil, dernières recettes, compteur de vues (`public/index.php`)
- [x] Inscription, connexion, gestion de profil utilisateur (`register.php`, `profile.php`, `change_password.php`)
- [x] Ajout de recette avec photo (`add_recipe.php`, `add_recipe_photo.php`)
- [x] Modification/Suppression de recette (`edit_recipe.php`)
- [x] Affichage d’une recette (`recipe.php`)
- [x] Recherche et filtrage de recettes (`search.php`)
- [x] Génération automatique d’une liste de courses (`shopping_list.php`)
- [x] Favoris (`favorites.php`)
- [~] Commentaires, notes (à confirmer)
- [x] Espace admin (gestion catégories, tags, ingrédients, utilisateurs, recettes) (`admin/`)
- [x] Export PDF (`export_pdf.php`)
- [x] Responsive design (`css/home.css`, `css/profile.css`, etc.)
- [ ] Instructions d’installation o2switch

## Inventaire technique détaillé

### Tables principales (MySQL)
- users
- recipes
- ingredients
- categories
- tags
- recipe_tags
- recipe_ingredients
- favorites
- comments
- shopping_list
- shopping_list_items
- units

### Fichiers principaux (routes)
- Accueil : `public/index.php`
- Inscription : `public/register.php`
- Connexion : `public/login.php`, `public/logout.php`
- Profil utilisateur : `public/profile.php`, `public/change_password.php`
- Ajout recette : `public/add_recipe.php`, `public/add_recipe_photo.php`
- Mes recettes : `public/my_recipes.php`
- Recettes (affichage, modification, suppression) : `public/recipe.php`, `public/edit_recipe.php`, `public/delete_recipe.php`
- Recherche : `public/search.php`
- Favoris : `public/favorites.php`
- Liste de courses : `public/shopping_list.php`
- Espace admin : `public/admin/`
- Export PDF : `public/export_pdf.php`

### Pages clés
- `public/index.php` (accueil)
- `public/register.php` (inscription)
- `public/login.php` (connexion)
- `public/profile.php` (profil)
- `public/add_recipe.php` (ajout recette)
- `public/my_recipes.php` (mes recettes)
- `public/recipe.php`, `public/edit_recipe.php` (recettes)
- `public/search.php` (recherche)
- `public/favorites.php` (favoris)
- `public/shopping_list.php` (liste de courses)
- `public/admin/dashboard.php` (admin)
- `public/export_pdf.php` (export)

## Choix techniques
- PHP natif, organisation MVC simplifiée
- Base de données MySQL (`gestion_des_recettes`)
- Structure des dossiers :
  - `public/` : fichiers accessibles (index, recettes, profils, etc.)
  - `admin/` : interface d’administration (gestion utilisateurs, catégories, tags...)
  - `config/` : fichiers de configuration (ex : unités)
  - `database/` : scripts SQL
  - `assets/`, `css/`, `js/` : ressources front
- Utilisation de scripts shell pour le dev (`start-dev.sh`, `stop-dev.sh`)

## Problèmes rencontrés / Solutions

## Spécificités o2switch
- À compléter lors de la préparation à la mise en ligne

## Prochaines étapes
- Faire l’inventaire précis des fonctionnalités déjà codées (voir les fichiers PHP dans `public/` et `admin/`)
- Documenter les choix de structure et de sécurité
- Ajouter les instructions d’installation et de configuration pour o2switch
- S’assurer du responsive sur mobile/tablette

## Tableau de suivi des fonctionnalités (Version PHP pur)

| Fonctionnalité                        | Statut         | Fichier(s) principal(aux)           | Remarques / Points à traiter            |
|---------------------------------------|---------------|-------------------------------------|-----------------------------------------|
| Inscription utilisateur               | ✅ Terminé     |                                      | Conversion automatique en minuscules pour username/email. Mot de passe affichable en clair (bouton œil). |
| Connexion/Déconnexion                 | ✅ Terminé     |                                      | Mot de passe affichable en clair (bouton œil). |
| Gestion du profil                     | ✅ Terminé     |                                      | Conversion automatique en minuscules pour username/email. Mot de passe modifiable avec bouton œil. |
| Ajout de recette                      | ⬜ À faire     | add_recipe.php, add_recipe_photo.php| Upload photo/vidéo                      |
| Modification/Suppression de recette   | ⬜ À faire     | edit_recipe.php, delete_recipe.php  |                                         |
| Affichage d’une recette               | ⬜ À faire     | recipe.php                          |                                         |
| Recherche/filtrage de recettes        | ⬜ À faire     | search.php                          | Autocomplétion, filtres avancés         |
| Favoris                               | ⬜ À faire     | favorites.php                       |                                         |
| Import automatique de recette depuis une URL | ✅ Terminé     | import_recipe.php, add_recipe.php    | Extraction d'ingrédients, étapes, images, tags depuis des sites externes. Permet la création et modification comme si la recette avait été saisie manuellement. |
| Commentaires/Notes                    | ⬜ À faire     | (à préciser)                        | Vérifier dans la BDD et les fichiers    |
| Liste de courses                      | ⬜ À faire     | shopping_list.php                   |                                         |
| Espace admin                          | ⬜ À faire     | admin/                              | Gestion catégories, tags, utilisateurs  |
| Export PDF                            | ⬜ À faire     | export_pdf.php                      |                                         |
| Responsive design                     | ⬜ À faire     | (global, CSS)                       | Tester sur mobile/tablette              |
| Sécurité (validation, etc.)           | ⬜ À faire     | (global)                            |                                         |
| Documentation installation o2switch   | ⬜ À faire     | (README, bookdev)                   | Variables d’environnement, FTP, etc.    |

Légende Statut :
- ⬜ À faire  
- 🟡 En cours  
- ✅ Terminé  
