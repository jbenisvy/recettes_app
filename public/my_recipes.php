<?php
session_start();
require_once __DIR__ . '/../config/db.php';
$db = require __DIR__ . '/../config/db.php';
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); exit;
}
$stmt = $pdo->prepare('SELECT recipes.*, categories.name AS category FROM recipes LEFT JOIN categories ON recipes.category_id = categories.id WHERE recipes.user_id = ? ORDER BY created_at DESC');
$stmt->execute([$_SESSION['user_id']]);
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Mes Recettes</title>
    <link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="css/home.css">
</head>
<body>
<link rel="stylesheet" href="assets/css/modal-success.css">
<?php if (!empty($_SESSION['success_message'])): ?>
<div class="modal-success-bg" id="modal-success-bg">
  <div class="modal-success">
    <span class="modal-icon">✅</span>
    <h2>Succès</h2>
    <div><?php echo htmlspecialchars($_SESSION['success_message']); ?></div>
    <button class="modal-btn" onclick="document.getElementById('modal-success-bg').style.display='none';">Fermer</button>
  </div>
</div>
<script>setTimeout(function(){ document.getElementById('modal-success-bg').style.display='none'; }, 3500);</script>
<?php unset($_SESSION['success_message']); endif; ?>
<a class="btn" href="index.php" style="margin:16px 0 8px 0;display:inline-block;">&larr; Retour à l'accueil</a>
<?php include 'navbar.php'; ?>
<div class="welcome-section">
    <h1>Mes Recettes</h1>
    <p class="welcome-text"><strong>Nombre de visites pour cette page : <?php echo (int)$views; ?></strong></p>
    <p class="welcome-text">Retrouvez ici toutes vos créations culinaires personnelles.</p>
</div>
<section class="latest-recipes">
    <a href="add_recipe.php" class="btn" style="margin-bottom:18px;">Ajouter une recette</a>
    <div class="recipes-grid">
    <?php foreach ($recipes as $r): ?>
        <?php
        $stmt = $pdo->prepare("SELECT file_path FROM recipe_media WHERE recipe_id = ? AND media_type = 'image' LIMIT 1");
        $stmt->execute([$r['id']]);
        $image = $stmt->fetch(PDO::FETCH_COLUMN);
        ?>
        <div class="recipe-card">
            <div class="recipe-image">
                <?php if ($image): ?>
                    <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($r['title']); ?>" class="recipe-img-fixed">
                <?php else: ?>
                    <img src="https://img.icons8.com/fluency/180/000000/meal.png" alt="Image par défaut" class="recipe-img-fixed" style="opacity:0.6;">
                <?php endif; ?>
            </div>
            <div class="recipe-content">
                <h3><?php echo htmlspecialchars($r['title']); ?></h3>
                <div class="recipe-meta">
                    <span class="badge-category"><?php echo htmlspecialchars($r['category']); ?></span>
                </div>
                <p class="recipe-description"><?php echo isset($r['description']) ? substr(htmlspecialchars($r['description']), 0, 100) . '...' : ''; ?></p>
                <a href="recipe.php?id=<?php echo $r['id']; ?>" class="view-recipe">Voir la recette</a>
                <div style="display:flex;gap:8px;margin-top:10px;">
                    <a href="edit_recipe.php?id=<?php echo $r['id']; ?>" class="btn btn-edit">✏️ Modifier</a>
                    <a href="delete_recipe.php?id=<?php echo $r['id']; ?>" class="btn btn-delete" onclick="return confirm('Supprimer cette recette ?');">🗑️ Supprimer</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
</section>
</body>
</html>
