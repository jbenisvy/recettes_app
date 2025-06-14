# Devbook du projet Recettes App

Ce fichier regroupe toutes les étapes du projet, il sera mis à jour au fur et à mesure de l’avancement.

> **Important : Toutes les futures évolutions (fonctionnalités, corrections, refontes, migrations, etc.) doivent obligatoirement être consignées dans ce document afin d’assurer une traçabilité complète du projet.**

## Étapes réalisées depuis le début

1. **Initialisation du projet**
   - Création de la structure de base du projet (dossiers : `public`, `config`, `database`, etc.)
   - Ajout d’un fichier README.md

2. **Configuration de la base de données**
   - Création du fichier `database.sql`
   - Mise en place de la connexion à la base de données dans `config/db.php`

3. **Gestion des utilisateurs**
   - Création des pages d’inscription (`register.php`) et de connexion (`login.php`)
   - Création de la page de profil utilisateur (`profile.php`)

4. **Mise en place du style**
   - Création de fichiers CSS dans `public/css/`

---

## Informations sur la base de données

### Tables principales

- **users** : stocke les utilisateurs (id, username, email, password, avatar, created_at)
- **categories** : catégories de recettes (id, name)
- **recipes** : recettes (id, user_id, title, description, ingredients, steps, category_id, prep_time, cook_time, difficulty, image, created_at)
- **favorites** : recettes favorites des utilisateurs (id, user_id, recipe_id, created_at)
- **comments** : commentaires sur les recettes (id, user_id, recipe_id, content, rating, created_at)
- **recipe_ingredients** : association recettes/ingrédients (recipe_id, ingredient_id, quantity, unit)
- **ingredients** : liste des ingrédients (id, name)

### Relations principales

- Une **recette** appartient à un **utilisateur** (`recipes.user_id` → `users.id`)
- Une **recette** appartient à une **catégorie** (`recipes.category_id` → `categories.id`)
- Un **favori** relie un utilisateur à une recette (`favorites.user_id` → `users.id`, `favorites.recipe_id` → `recipes.id`)
- Un **commentaire** relie un utilisateur à une recette (`comments.user_id` → `users.id`, `comments.recipe_id` → `recipes.id`)
- Les **ingrédients** d’une recette sont gérés par la table d’association `recipe_ingredients` (`recipe_ingredients.recipe_id` → `recipes.id`, `recipe_ingredients.ingredient_id` → `ingredients.id`)

### Notes complémentaires
- Les tables utilisent des clés étrangères pour garantir l’intégrité des données.
- La table `ingredients` contient une liste d’ingrédients courants insérée lors de l’initialisation.

---

## Fonctionnalité : Liste des courses

### Description
Permet à chaque utilisateur de générer et gérer une liste de courses à partir des ingrédients des recettes sélectionnées. La liste est persistée en base de données et peut être modifiée (quantités, suppression, coche d'ingrédients achetés).

### Structure SQL
```sql
CREATE TABLE shopping_lists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE shopping_list_items (
    list_id INT NOT NULL,
    ingredient_id INT NOT NULL,
    quantity VARCHAR(50),
    unit VARCHAR(30),
    checked BOOLEAN DEFAULT FALSE,
    PRIMARY KEY (list_id, ingredient_id),
    FOREIGN KEY (list_id) REFERENCES shopping_lists(id) ON DELETE CASCADE,
    FOREIGN KEY (ingredient_id) REFERENCES ingredients(id)
);
```

- Un utilisateur possède une seule liste de courses active.
- Les ingrédients sont liés à la table `ingredients`.
- La colonne `checked` permet de cocher un ingrédient comme acheté.

---

Les prochaines étapes seront ajoutées ici au fur et à mesure.

---

## Historique des ajouts
- 15/05/2025 : **Gestion avancée des unités et ingrédients, fiabilisation et modernisation UX**
    - Nettoyage et unicité stricte des tables `units` et `ingredients` (suppression des doublons, collation insensible à la casse, noms en majuscules sans accents).
    - Ajout ou correction des contraintes d'unicité SQL sur `units.name` et `ingredients.name`.
    - Alimentation massive de la table `units` avec toutes les unités courantes de cuisine (requête adaptée MySQL).
    - Correction du formulaire de création de recette : la liste déroulante des unités est à nouveau dynamique et alimentée via AJAX depuis la base.
    - Suppression des `<option>` HTML résiduels hors du `<select>` pour les unités.
    - Amélioration de la gestion des erreurs SQL lors de l'insertion des unités.
    - Mise en place d'une boîte de confirmation stylée (modal) après création, modification ou suppression d'une recette (remplace les alertes JS classiques).
    - Documentation et scripts SQL ajoutés pour migration/cleaning des données existantes.
    - Rappel de la procédure de déploiement via `deploy.sh` et bonnes pratiques pour la mise à jour distante.
    - Ajout et application du fichier `public/css/home.css` pour tous les styles spécifiques à l'accueil.
    - Correction des bugs d'affichage liés à la structure PHP (suppression des doublons, gestion correcte du buffer de sortie, injection via `$pageContent` dans le template `base.php`).
    - Amélioration du contraste et de la lisibilité du bandeau d'accueil (titre en blanc, overlay sombre sur le dégradé, ombre portée).
    - Sécurisation renforcée : toutes les entrées utilisateur sont échappées, requêtes SQL via statements préparés.
    - Tests manuels et corrections : validation de l'affichage sur desktop/mobile, correction des bugs de style et de structure.
- 24/04/2025 : Ajout de la structure SQL pour la liste des courses.
- 24/04/2025 : Création du devbook et ajout des étapes initiales.
- 24/04/2025 : Ajout de la section sur la structure de la base de données.

---

## Évolutions et actions après le 24/04/2025

### 28/04/2025 — Harmonisation du formulaire d’ajout de recette
- Le formulaire d’ajout de recette a été harmonisé avec celui de l’ancien site pour faciliter la transition des utilisateurs.
- Modifications de structure HTML/PHP, validation et ergonomie.

### 30/04/2025 — Migration SQL : gestion des réinitialisations de mot de passe
- Ajout d’une migration SQL (`2025_04_30_create_password_resets.sql`) pour permettre la réinitialisation sécurisée des mots de passe utilisateurs.

### Fin avril 2025 — Corrections et ajustements sur le déploiement
- Refonte et test du script de déploiement (`deploy.sh`).
- Harmonisation du chemin de déploiement vers `public_html/Recettes_Application`.
- Vérification et application de la règle Apache `RewriteRule ^$ /Recettes_Application/ [L]` pour la redirection de la racine du site.

### Début mai 2025 — Correction de bugs sur l’édition de recette
- Correction d’un bug PHP lors de l’édition de recette : "Array to string conversion" dans `edit_recipe.php` (ligne 139).
- Correction d’un problème d’envoi de headers après affichage prématuré ("headers already sent").
- Conseils appliqués pour corriger le code sur le serveur distant.

### Mai 2025 — Gestion du dépôt Git et synchronisation GitHub
- Vérification et réinitialisation de l’URL du dépôt distant : `git remote set-url origin https://github.com/jbenisvy/recettes_app.git`.
- Conseils pour l’utilisation de `git add`, `git push` et le clonage dans le bon dossier côté o2switch.

### Printemps 2025 — Documentation et migrations
- Utilisation de migrations Doctrine pour faire évoluer ou corriger la base de données.
- Ajustements mineurs sur la structure des tables et la logique de l’application.

---

**Synthèse chronologique**

| Date         | Action principale                                                                                   |
|--------------|----------------------------------------------------------------------------------------------------|
| 28/04/2025   | Harmonisation du formulaire d’ajout de recette avec l’ancien site                                  |
| 30/04/2025   | Ajout d’une migration SQL pour la gestion des réinitialisations de mot de passe                    |
| Fin avril    | Correction du script de déploiement, harmonisation des chemins, vérification des règles Apache      |
| Début mai    | Correction de bugs sur l’édition de recette (PHP : Array to string, headers already sent)          |
| Mai 2025     | Vérification et synchronisation du dépôt Git avec GitHub, consignes de déploiement                 |
| Printemps 25 | Ajustements sur la base de données via migrations Doctrine (évolutions ou corrections mineures)     |

---

Toutes ces évolutions sont issues de l’analyse des fichiers de restauration, migrations SQL, scripts et logs techniques retrouvés après la perte du dossier initial. Ce suivi permet d’assurer une traçabilité complète et actualisée du projet Recettes App.
