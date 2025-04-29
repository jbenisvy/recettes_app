<?php
session_start();
if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    echo '<div style="background:#fff;color:#b22;padding:24px;text-align:center;font-size:1.2em;">Accès refusé : vous devez être administrateur pour accéder à ce tableau de bord.<br><a href="/login.php">Se connecter</a></div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Tableau de bord</title>
    <link rel="stylesheet" href="/css/home.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;400&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(120deg, #e0f7fa 0%, #f4f8fb 100%);
            font-family: 'Roboto', 'Poppins', sans-serif;
            margin: 0;
        }
        .admin-header {
            background: #2c7c7b;
            color: #fff;
            padding: 32px 0 24px 0;
            border-radius: 0 0 32px 32px;
            box-shadow: 0 6px 24px rgba(44,124,123,0.13);
            text-align: center;
            position: relative;
        }
        .admin-header .icon {
            font-size: 3.5em;
            margin-bottom: 6px;
            display: block;
        }
        .admin-header h1 {
            font-family: 'Montserrat', 'Poppins', sans-serif;
            font-size: 2.1em;
            margin: 0 0 8px 0;
            letter-spacing: 1px;
        }
        .btn-site {
            position: absolute;
            right: 38px;
            top: 38px;
            background: #fff;
            color: #2c7c7b;
            border-radius: 24px;
            padding: 10px 28px;
            font-weight: 700;
            font-size: 1em;
            box-shadow: 0 2px 8px rgba(44,124,123,0.13);
            text-decoration: none;
            transition: background 0.2s, color 0.2s;
        }
        .btn-site:hover { background: #e0f7fa; color: #1d5352; }
        .admin-dashboard {
            max-width: 900px;
            margin: -38px auto 60px auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(44,124,123,0.11);
            padding: 48px 32px 38px 32px;
            position: relative;
        }
        .welcome {
            text-align: center;
            margin-bottom: 38px;
            color: #2c7c7b;
            font-size: 1.18em;
            font-family: 'Montserrat', 'Poppins', sans-serif;
        }
        .admin-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 28px;
            margin-bottom: 40px;
        }
        .admin-card {
            background: linear-gradient(120deg, #e0f7fa 0%, #f4f8fb 100%);
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(44,124,123,0.09);
            padding: 38px 18px 28px 18px;
            text-align: center;
            text-decoration: none;
            color: #2c7c7b;
            font-size: 1.15em;
            font-family: 'Montserrat', 'Poppins', sans-serif;
            font-weight: 600;
            position: relative;
            transition: box-shadow 0.2s, background 0.2s, transform 0.1s;
        }
        .admin-card:hover {
            background: #b2ebf2;
            box-shadow: 0 8px 28px rgba(44,124,123,0.17);
            transform: translateY(-2px) scale(1.03);
        }
        .admin-card .icon {
            font-size: 2.1em;
            margin-bottom: 10px;
            display: block;
        }
        .logout {
            display: block;
            text-align: center;
            margin-top: 28px;
            color: #b22;
            text-decoration: underline;
            font-weight: 500;
            font-size: 1.08em;
        }
        @media (max-width: 700px) {
            .admin-dashboard { padding: 18px 4vw 24px 4vw; }
            .admin-header .btn-site { right: 12px; top: 18px; padding: 8px 16px; font-size: 0.98em; }
            .admin-links { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="admin-header">
    <span class="icon">&#128295;</span>
    <h1>Espace Administration</h1>
    <a href="index.php" class="btn-site">&larr; Retour au site</a>
</div>
<div class="admin-dashboard">
    <div class="welcome">Bienvenue dans le tableau de bord administrateur.<br>Gérez toutes les fonctionnalités de votre site depuis cet espace sécurisé.</div>
    <div class="admin-links">
        <a href="users.php" class="admin-card"><span class="icon">&#128100;</span>Gérer les utilisateurs</a>
        <a href="recipes.php" class="admin-card"><span class="icon">&#127859;</span>Gérer les recettes</a>
        <a href="comments.php" class="admin-card"><span class="icon">&#128172;</span>Gérer les commentaires</a>
        <a href="ingredients.php" class="admin-card"><span class="icon">&#129367;</span>Gérer les ingrédients</a>
        <a href="units.php" class="admin-card"><span class="icon">&#128202;</span>Gérer les unités</a>
        <a href="categories.php" class="admin-card"><span class="icon">&#128193;</span>Gérer les catégories</a>
        <a href="tags.php" class="admin-card"><span class="icon">&#128278;</span>Gérer les tags</a>
    </div>
    <a href="logout.php" class="logout">Se déconnecter</a>
</div>
</body>
</html>
