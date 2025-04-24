# Gestionnaire de Recettes de Cuisine

Ce projet est une application web complète de gestion de recettes, conçue pour être facilement déployée sur un hébergement mutualisé o2switch (PHP/MySQL).

## Fonctionnalités principales
- Inscription, connexion, gestion de profil utilisateur
- Ajout, modification, suppression de recettes avec photos
- Recherche et filtrage (ingrédients, catégories, temps, difficulté)
- Génération automatique d'une liste de courses
- Favoris, commentaires, notes, partage de recettes

## Structure des dossiers

```
recettes-app/
├── public/
│   ├── index.php
│   ├── login.php
│   ├── register.php
│   ├── profile.php
│   ├── recipe.php
│   ├── add_recipe.php
│   ├── edit_recipe.php
│   ├── search.php
│   ├── favorites.php
│   ├── shopping_list.php
│   ├── assets/
│   │   ├── css/
│   │   └── js/
│   └── uploads/
├── src/
│   ├── controllers/
│   ├── models/
│   └── services/
├── templates/
├── config/
│   └── db.php
├── vendor/
├── README.md
└── database.sql
```

## Installation sur o2switch

1. **Créer une base de données MySQL via cPanel**
   - Note le nom, l'utilisateur et le mot de passe de la base.

2. **Configurer la connexion à la base**
   - Modifie `config/db.php` avec tes identifiants.

3. **Uploader les fichiers**
   - Transfère tout le dossier `recettes-app/` via FTP dans le dossier `www/` de ton hébergement.
   - Assure-toi que le dossier `public/uploads/` est accessible en écriture (CHMOD 755 ou 775).

4. **Importer la structure de la base**
   - Depuis phpMyAdmin, importe le fichier `database.sql`.

5. **Configurer les variables d'environnement (optionnel)**
   - Si besoin, crée un fichier `.env` ou adapte `config/db.php`.

6. **Accéder à l'application**
   - Va sur `https://tondomaine.com/index.php`.

## Sécurité & Optimisation
- Les mots de passe sont hashés (password_hash)
- Protection CSRF et XSS sur les formulaires
- Limite de taille sur les uploads de photos
- Requêtes SQL préparées
- Code léger, sans framework lourd

## Compatibilité
- PHP >= 7.4
- MySQL
- Hébergement mutualisé o2switch (testé)

---

N'hésite pas à consulter chaque fichier pour plus de détails sur l'implémentation.
