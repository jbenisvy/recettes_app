# Suivi de développement — Version PHP pur

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
