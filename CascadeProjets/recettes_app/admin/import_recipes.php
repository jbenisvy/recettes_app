<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config/db.php';
$db = require __DIR__ . '/../config/db.php';

// Connexion PDO
try {
    $pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Erreur de connexion à la base de données : ' . $e->getMessage());
}

$success = null;
$error = null;

if (!empty($_FILES['csv']['tmp_name'])) {
    $file = fopen($_FILES['csv']['tmp_name'], 'r');
    $header = fgetcsv($file, 1000, ';');
    $expected = ['title','description','ingredients','steps','category','prep_time','cook_time','difficulty','tags'];
    if ($header === $expected) {
        $count = 0;
        while (($row = fgetcsv($file, 10000, ';')) !== false) {
            list($title,$description,$ingredients,$steps,$category,$prep_time,$cook_time,$difficulty,$tags) = $row;
            // Insertion recette
            $stmt = $pdo->prepare("INSERT INTO recipes (user_id, title, description, steps, category_id, prep_time, cook_time, difficulty) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $cat = $pdo->prepare('SELECT id FROM categories WHERE name = ?');
            $cat->execute([$category]);
            $cat_id = $cat->fetchColumn();
            if (!$cat_id) $cat_id = null;
            $stmt->execute([
                $_SESSION['user_id'], $title, $description, $steps, $cat_id, intval($prep_time), intval($cook_time), $difficulty
            ]);
            $recipe_id = $pdo->lastInsertId();
            // Ingrédients (séparés par |, chaque ingrédient : nom:quantité:unité)
            foreach (explode('|', $ingredients) as $ing) {
                $ing = trim($ing);
                if (!$ing) continue;
                $parts = explode(':', $ing);
                $name = $parts[0] ?? '';
                $qty = $parts[1] ?? '';
                $unit = $parts[2] ?? '';
                // Gère l'unité (id ou texte)
                $stmtu = $pdo->prepare('SELECT id FROM units WHERE name = ?');
                $stmtu->execute([$unit]);
                $unit_id = $stmtu->fetchColumn();
                if (!$unit_id && $unit !== '') {
                    $stmtu = $pdo->prepare('INSERT INTO units (name) VALUES (?)');
                    $stmtu->execute([$unit]);
                    $unit_id = $pdo->lastInsertId();
                }
                // Ingrédient
                $stmti = $pdo->prepare('SELECT id FROM ingredients WHERE name = ?');
                $stmti->execute([$name]);
                $ingredient_id = $stmti->fetchColumn();
                if (!$ingredient_id && $name !== '') {
                    $stmti = $pdo->prepare('INSERT INTO ingredients (name) VALUES (?)');
                    $stmti->execute([$name]);
                    $ingredient_id = $pdo->lastInsertId();
                }
                $stmtlink = $pdo->prepare('INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity, unit_id) VALUES (?, ?, ?, ?)');
                $stmtlink->execute([$recipe_id, $ingredient_id, $qty, $unit_id]);
            }
            // Tags (séparés par virgule)
            foreach (explode(',', $tags) as $tag) {
                $tag = trim($tag);
                if (!$tag) continue;
                $stmtt = $pdo->prepare('SELECT id FROM tags WHERE name = ?');
                $stmtt->execute([$tag]);
                $tag_id = $stmtt->fetchColumn();
                if (!$tag_id) {
                    $stmtt = $pdo->prepare('INSERT INTO tags (name) VALUES (?)');
                    $stmtt->execute([$tag]);
                    $tag_id = $pdo->lastInsertId();
                }
                $stmtlink = $pdo->prepare('INSERT INTO recipe_tags (recipe_id, tag_id) VALUES (?, ?)');
                $stmtlink->execute([$recipe_id, $tag_id]);
            }
            $count++;
        }
        $success = "$count recette(s) importée(s) avec succès.";
    } else {
        $error = "Format de fichier incorrect. Utilisez le modèle fourni.";
    }
    fclose($file);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Import recettes (CSV)</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2em; }
        .container { max-width: 700px; margin: auto; }
        .alert { padding: 1em; margin-bottom: 1em; border-radius: 6px; }
        .alert-success { background: #e9fbe9; color: #225c22; }
        .alert-danger { background: #fbe9e9; color: #a52222; }
    </style>
</head>
<body>
<div class="container">
    <h1>Import de recettes par fichier CSV</h1>
    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <label>Fichier CSV à importer :</label>
        <input type="file" name="csv" accept=".csv" required>
        <button type="submit">Importer</button>
    </form>
    <hr>
    <h2>Modèle CSV à utiliser</h2>
    <pre style="background:#f4f4f4;padding:1em;overflow:auto;">
title;description;ingredients;steps;category;prep_time;cook_time;difficulty;tags
Crêpes faciles sans repos;Recette facile et rapide de crêpes, sans temps de repos.;Farine:250 g:|Oeuf:4:|Lait:0.5 l:|Sel:1 pincée:|Sucre:2 c à s:|Beurre fondu:50 g:;1. Mélanger...|2. Cuire...;Dessert;10;15;Facile;crêpes,dessert,rapide
</pre>
    <p>• <b>ingredients</b> : sépare chaque ingrédient par <b>|</b>, chaque ingrédient sous la forme <b>Nom:Quantité:Unité</b> (laisser vide si pas d’unité).<br>• <b>tags</b> : séparés par virgule.<br>• Les catégories doivent exister dans la base (ou seront vides).</p>
</div>
</body>
</html>
