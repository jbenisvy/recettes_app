<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db.php';

// Connexion à la base de données
$db = require __DIR__ . '/../config/db.php';
try {
    $pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Erreur de connexion à la base de données : ' . $e->getMessage());
}

// Compteur de vues par page
$page = basename($_SERVER['PHP_SELF']);
$stmt = $pdo->prepare('INSERT INTO page_views (page, views) VALUES (?, 1) ON DUPLICATE KEY UPDATE views = views + 1');
$stmt->execute([$page]);
$stmt = $pdo->prepare('SELECT views FROM page_views WHERE page = ?');
$stmt->execute([$page]);
$views = $stmt->fetchColumn();


// Récupérer les dernières recettes
$stmt = $pdo->query("SELECT r.*, u.username, c.name as category_name 
                     FROM recipes r 
                     LEFT JOIN users u ON r.user_id = u.id 
                     LEFT JOIN categories c ON r.category_id = c.id 
                     ORDER BY r.created_at DESC 
                     LIMIT 6");
$latest_recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Préparer le contenu de la page
$pageTitle = "Accueil - Gestion des Recettes";
$additionalCss = ["css/home.css"];
ob_start(); ?>



<?php session_start(); ?>
<body>
<?php if (!empty($_SESSION['success_message'])): ?>
    <script>
        setTimeout(function() { alert(<?php echo json_encode($_SESSION['success_message']); ?>); }, 100);
    </script>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<div class="welcome-section">
    <h1>Bienvenue sur l'application de gestion de recettes !</h1>
    <p class="welcome-text">Découvrez, partagez et organisez vos recettes préférées.</p>
    <p class="welcome-text"><strong>Nombre de visites pour cette page : <?php echo (int)$views; ?></strong></p>
    <a href="add_recipe.php" class="hero-btn">+ Ajouter une recette</a>
    <a href="admin/dashboard.php" class="hero-btn" style="background:#3e4a5e;margin-left:10px;">Espace Admin</a>
</div>

<section class="latest-recipes">
    <h2>Dernières Recettes</h2>
    <div class="recipes-grid">
        <?php foreach ($latest_recipes as $recipe): ?>
            <?php
            $stmt = $pdo->prepare("SELECT file_path FROM recipe_media WHERE recipe_id = ? AND media_type = 'image' LIMIT 1");
            $stmt->execute([$recipe['id']]);
            $image = $stmt->fetch(PDO::FETCH_COLUMN);
            ?>
            <div class="recipe-card">
                <div class="recipe-image">
                    <?php if ($image): ?>
                        <img src="<?php echo htmlspecialchars($image); ?>" alt="Image de la recette" class="recipe-img-fixed">
                    <?php else: ?>
                        <img src="https://img.icons8.com/fluency/180/000000/meal.png" alt="Image par défaut" class="recipe-img-fixed" style="opacity:0.6;">
                    <?php endif; ?>
                </div>
                <div class="recipe-content">
                    <h3><?php echo htmlspecialchars($recipe['title']); ?></h3>
                    <div class="recipe-meta">
                        <span class="badge-category"><?php echo htmlspecialchars($recipe['category_name']); ?></span>
                        <span class="author">par <?php echo htmlspecialchars($recipe['username']); ?></span>
                    </div>
                    <p class="recipe-description"><?php echo substr(htmlspecialchars($recipe['description']), 0, 100) . '...'; ?></p>
                    <a href="recipe.php?id=<?php echo $recipe['id']; ?>" class="view-recipe">Voir la recette</a>
                    <?php if ((isset($_SESSION['user_id']) && $_SESSION['user_id'] == $recipe['user_id']) || (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'])): ?>
                        <a href="edit_recipe.php?id=<?php echo $recipe['id']; ?>" class="btn btn-edit" style="margin-bottom:8px;">✏️ Modifier</a>
                        <a href="delete_recipe.php?id=<?php echo $recipe['id']; ?>" onclick="return confirm('Supprimer cette recette ?');" class="btn btn-delete" style="background:#c00;color:#fff;margin-left:8px;">🗑️ Supprimer</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="features">
    <h2>Fonctionnalités</h2>
    <div class="features-grid">
        <div class="feature-card">
            <img src="https://img.icons8.com/fluency/48/000000/add-file.png" alt="Ajouter">
            <h3>Ajoutez vos recettes</h3>
            <p>Partagez vos meilleures recettes avec la communauté.</p>
        </div>
        <div class="feature-card">
            <img src="https://img.icons8.com/fluency/48/000000/search.png" alt="Rechercher">
            <h3>Recherchez des recettes</h3>
            <p>Trouvez l'inspiration parmi nos recettes.</p>
        </div>
        <div class="feature-card">
            <img src="https://img.icons8.com/fluency/48/000000/shopping-cart.png" alt="Liste de courses">
            <h3>Liste de courses</h3>
            <p>Générez automatiquement votre liste de courses.</p>
        </div>
    </div>
</section>
<?php
$pageContent = ob_get_clean();
require 'templates/base.php';
?>
