<?php
require_once __DIR__ . '/../config/db.php';
session_start();
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: search.php'); exit;
}
$db = require __DIR__ . '/../config/db.php';
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);
$id = intval($_GET['id']);
$stmt = $pdo->prepare('SELECT recipes.*, categories.name AS category, users.username FROM recipes LEFT JOIN categories ON recipes.category_id = categories.id LEFT JOIN users ON recipes.user_id = users.id WHERE recipes.id = ?');
$stmt->execute([$id]);
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$recipe) { echo '<h2>Recette introuvable.</h2>'; exit; }
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Impression - <?php echo htmlspecialchars($recipe['title']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #fff; color: #222; }
        h1, h2 { color: #2d7c7b; }
        .ingredients, .steps { margin-bottom: 2em; }
        .print-btn { display: none; }
        @media print {
            .print-btn, .no-print { display: none !important; }
        }
    </style>
</head>
<body>
<a class="btn" href="index.php" style="margin:16px 0 8px 0;display:inline-block;">&larr; Retour à l'accueil</a>
    <button class="print-btn no-print" onclick="window.print()">Imprimer cette recette</button>
    <h1><?php echo htmlspecialchars($recipe['title']); ?></h1>
    <div>
        <strong>Catégorie :</strong> <?php echo htmlspecialchars($recipe['category']); ?> |
        <strong>Difficulté :</strong> <?php echo htmlspecialchars($recipe['difficulty']); ?> |
        <strong>Préparation :</strong> <?php echo htmlspecialchars($recipe['prep_time']); ?> min |
        <strong>Cuisson :</strong> <?php echo htmlspecialchars($recipe['cook_time']); ?> min |
        <strong>Par :</strong> <?php echo htmlspecialchars($recipe['username']); ?>
    </div>
    <?php if ($recipe['image']): ?>
        <img src="<?php echo htmlspecialchars($recipe['image']); ?>" alt="Photo" style="max-width:300px; margin:1em 0;">
    <?php endif; ?>
    <div class="ingredients">
        <h2>Ingrédients</h2>
        <ul>
        <?php
        $ings = $pdo->prepare('SELECT ri.quantity, ri.unit, i.name FROM recipe_ingredients ri JOIN ingredients i ON ri.ingredient_id = i.id WHERE ri.recipe_id = ?');
        $ings->execute([$id]);
        foreach ($ings as $ing) {
            echo '<li>' . htmlspecialchars($ing['name']);
            if ($ing['quantity'] || $ing['unit']) {
                echo ' : <strong>' . htmlspecialchars($ing['quantity']) . ' ' . htmlspecialchars($ing['unit']) . '</strong>';
            }
            echo '</li>';
        }
        ?>
        </ul>
    </div>
    <div class="steps">
        <h2>Étapes</h2>
        <ol><?php foreach (explode("\n", $recipe['steps']) as $step) echo '<li>' . htmlspecialchars($step) . '</li>'; ?></ol>
    </div>
    <div style="margin-top:2em; color:#888; font-size:0.9em;">
        Imprimé depuis l'application de gestion de recettes.
    </div>
</body>
</html>
