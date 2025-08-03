<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); exit;
}
$db = require __DIR__ . '/../config/db.php';
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);

// Compteur de vues par page
$page = basename($_SERVER['PHP_SELF']);
$stmt = $pdo->prepare('INSERT INTO page_views (page, views) VALUES (?, 1) ON DUPLICATE KEY UPDATE views = views + 1');
$stmt->execute([$page]);
$stmt = $pdo->prepare('SELECT views FROM page_views WHERE page = ?');
$stmt->execute([$page]);
$views = $stmt->fetchColumn();

// On suppose qu'il existe une table favorites (user_id, recipe_id)
$stmt = $pdo->prepare('SELECT r.*, u.username, c.name as category_name FROM recipes r INNER JOIN favorites f ON r.id = f.recipe_id LEFT JOIN users u ON r.user_id = u.id LEFT JOIN categories c ON r.category_id = c.id WHERE f.user_id = ? ORDER BY r.created_at DESC');
$stmt->execute([$_SESSION['user_id']]);
$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Mes recettes favorites";
ob_start();
?>
<link rel="stylesheet" href="css/home.css">
<?php
?>
<a class="btn" href="index.php" style="margin-bottom:18px;display:inline-block;">&larr; Retour à l'accueil</a>
<h1>Mes recettes favorites</h1>
<p class="welcome-text"><strong>Nombre de visites pour cette page : <?php echo (int)$views; ?></strong></p>
<?php if (empty($favorites)) : ?>
    <p>Vous n'avez pas encore ajouté de recettes en favoris.</p>
<?php else : ?>
    <div class="recipes-grid">
    <?php foreach ($favorites as $recipe): ?>
        <?php
        $img_stmt = $pdo->prepare("SELECT file_path FROM recipe_media WHERE recipe_id = ? AND media_type = 'image' LIMIT 1");
        $img_stmt->execute([$recipe['id']]);
        $image = $img_stmt->fetch(PDO::FETCH_COLUMN);
        ?>
        <div class="recipe-card">
            <div class="recipe-image">
                <?php if ($image): ?>
                    <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($recipe['title']); ?>" class="recipe-img-fixed">
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
                <?php if (!empty($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                    <a href="edit_recipe.php?id=<?php echo $recipe['id']; ?>" class="btn btn-edit" style="margin-bottom:8px;">✏️ Modifier</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php
$pageContent = ob_get_clean();
$additionalCss = ["css/style.css", "css/home.css"];
require 'templates/base.php';
