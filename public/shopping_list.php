<?php
require_once __DIR__ . '/../config/db.php';
session_start();
// Permet d'imprimer la liste des courses pour une ou plusieurs recettes
// Usage : shopping_list.php?id=1,2,3
// Gestion de la liste de courses persistante
// Compteur de vues par page
$page = basename($_SERVER['PHP_SELF']);
$db = require __DIR__ . '/../config/db.php';
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);
$stmt = $pdo->prepare('INSERT INTO page_views (page, views) VALUES (?, 1) ON DUPLICATE KEY UPDATE views = views + 1');
$stmt->execute([$page]);
$stmt = $pdo->prepare('SELECT views FROM page_views WHERE page = ?');
$stmt->execute([$page]);
$views = $stmt->fetchColumn();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); exit;
}
$user_id = $_SESSION['user_id'];

// Récupérer ou créer la liste de courses de l'utilisateur
$list_stmt = $pdo->prepare('SELECT id FROM shopping_lists WHERE user_id = ?');
$list_stmt->execute([$user_id]);
$list_id = $list_stmt->fetchColumn();
if (!$list_id) {
    $pdo->prepare('INSERT INTO shopping_lists (user_id) VALUES (?)')->execute([$user_id]);
    $list_id = $pdo->lastInsertId();
}

// Si ?add=1 et ?id=xx, ajouter les ingrédients de la recette à la liste de courses
if (isset($_GET['add'], $_GET['id']) && is_numeric($_GET['id'])) {
    $recipe_id = intval($_GET['id']);
    // Récupérer les ingrédients de la recette
    $ing_stmt = $pdo->prepare('SELECT ingredient_id, quantity, unit FROM recipe_ingredients WHERE recipe_id = ?');
    $ing_stmt->execute([$recipe_id]);
    foreach ($ing_stmt as $ing) {
        // Vérifier si déjà présent dans la liste
        $item_stmt = $pdo->prepare('SELECT quantity FROM shopping_list_items WHERE list_id = ? AND ingredient_id = ? AND unit = ?');
        $item_stmt->execute([$list_id, $ing['ingredient_id'], $ing['unit']]);
        $existing_qty = $item_stmt->fetchColumn();
        // Fusion des quantités si possible (si numérique)
        $new_qty = $ing['quantity'];
        if ($existing_qty !== false && is_numeric($existing_qty) && is_numeric($ing['quantity'])) {
            $new_qty = $existing_qty + $ing['quantity'];
        }
        if ($existing_qty !== false) {
            $pdo->prepare('UPDATE shopping_list_items SET quantity = ?, unit = ? WHERE list_id = ? AND ingredient_id = ? AND unit = ?')
                ->execute([$new_qty, $ing['unit'], $list_id, $ing['ingredient_id'], $ing['unit']]);
        } else {
            $pdo->prepare('INSERT INTO shopping_list_items (list_id, ingredient_id, quantity, unit) VALUES (?, ?, ?, ?)')
                ->execute([$list_id, $ing['ingredient_id'], $ing['quantity'], $ing['unit']]);
        }
    }
    $_SESSION['success_message'] = "Ingrédients ajoutés à votre liste de courses !";
    header('Location: shopping_list.php'); exit;
}

// Suppression d'un ingrédient
if (isset($_GET['del']) && is_numeric($_GET['del'])) {
    $del_id = intval($_GET['del']);
    $pdo->prepare('DELETE FROM shopping_list_items WHERE list_id = ? AND ingredient_id = ?')->execute([$list_id, $del_id]);
    $_SESSION['success_message'] = "Ingrédient supprimé de la liste.";
    header('Location: shopping_list.php'); exit;
}
// Vider la liste
if (isset($_GET['clear']) && $_GET['clear'] == 1) {
    $pdo->prepare('DELETE FROM shopping_list_items WHERE list_id = ?')->execute([$list_id]);
    $_SESSION['success_message'] = "Liste de courses vidée.";
    header('Location: shopping_list.php'); exit;
}
// Gestion des cases cochées (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checked'])) {
    // Réinitialiser toutes les cases à décoché
    $pdo->prepare('UPDATE shopping_list_items SET checked = 0 WHERE list_id = ?')->execute([$list_id]);
    // Cocher celles envoyées
    foreach ($_POST['checked'] as $ingredient_id) {
        $pdo->prepare('UPDATE shopping_list_items SET checked = 1 WHERE list_id = ? AND ingredient_id = ?')->execute([$list_id, $ingredient_id]);
    }
    $_SESSION['success_message'] = "Liste de courses mise à jour !";
    header('Location: shopping_list.php'); exit;
}

?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Liste des courses</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .shopping-icon {
            width: 44px; height: 44px; display:block; margin: 0 auto 8px auto;
        }
        @media print { .print-btn, .no-print { display: none !important; } }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="welcome-section">
    <a href="index.php" class="btn" style="margin-bottom:18px;">&larr; Retour à l'accueil</a>
    <img src="https://img.icons8.com/fluency/48/000000/shopping-basket.png" alt="Panier" class="shopping-icon">
    <h1>Ma liste de courses</h1>
    <p class="welcome-text"><strong>Nombre de visites pour cette page : <?php echo (int)$views; ?></strong></p>
    <p class="welcome-text">Voici la liste groupée de tous les ingrédients nécessaires pour vos recettes sélectionnées.</p>
</div>
<?php
// Correction : initialiser $ids AVANT le HTML pour garantir l'affichage correct
$ids = [];
if (isset($_GET['id'])) {
    $raw = $_GET['id'];
    if (is_array($raw)) {
        foreach ($raw as $rid) if (is_numeric($rid)) $ids[] = intval($rid);
    } else {
        foreach (explode(',', (string)$raw) as $rid) if (is_numeric($rid)) $ids[] = intval($rid);
    }
}
?>
<section class="latest-recipes">
    <?php if (empty($ids)) : ?>
        <div class="error" style="margin:36px auto 36px auto; font-size:1.18em; max-width:400px; text-align:center;">Pas de recette sélectionnée.</div>
    <?php else : ?>
    <?php
    // Afficher la liste de courses de l'utilisateur
    // Si une sélection temporaire est demandée (?id=1,2,3)
    if (isset($_GET['id']) && !isset($_GET['add'])) {
        $ids = [];
        $raw = $_GET['id'];
        if (is_array($raw)) {
            foreach ($raw as $rid) if (is_numeric($rid)) $ids[] = intval($rid);
        } else {
            foreach (explode(',', (string)$raw) as $rid) if (is_numeric($rid)) $ids[] = intval($rid);
        }
        if (count($ids) > 0) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "SELECT i.id as ingredient_id, i.name, SUM(CASE WHEN ri.quantity REGEXP '^[0-9.]+$' THEN ri.quantity ELSE 0 END) as quantity, ri.unit FROM recipe_ingredients ri JOIN ingredients i ON ri.ingredient_id = i.id WHERE ri.recipe_id IN ($placeholders) GROUP BY i.id, i.name, ri.unit ORDER BY i.name";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($ids);
            $ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Pour la liste temporaire, pas de case cochée ni suppression/vidage
            foreach ($ingredients as &$ing) { $ing['checked'] = 0; }
        } else {
            $ingredients = [];
        }
        $is_temp_list = true;
    } else {
        $stmt = $pdo->prepare('SELECT i.id as ingredient_id, i.name, sli.quantity, sli.unit, sli.checked FROM shopping_list_items sli JOIN ingredients i ON sli.ingredient_id = i.id WHERE sli.list_id = ? ORDER BY i.name');
        $stmt->execute([$list_id]);
        $ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $is_temp_list = false;
    }
    ?>
    <div style="display:flex; gap:10px; margin-bottom:18px;">
        <button class="btn print-btn no-print" onclick="window.print()">🖨️ Imprimer la liste</button>
        <a href="<?php echo isset($is_temp_list) && $is_temp_list ? 'export_pdf.php?id='.htmlspecialchars($_GET['id']) : 'export_pdf.php'; ?>" class="btn btn-secondary no-print" target="_blank">📄 Exporter en PDF</a>
        <?php if (empty($is_temp_list)) : ?>
        <a href="shopping_list.php?clear=1" class="btn btn-danger no-print" onclick="return confirm('Vider toute la liste de courses ?');">🗑️ Vider la liste</a>
        <?php endif; ?>
    </div>
    <?php if (empty($ingredients)) : ?>
        <div class="error" style="margin:24px 0;">Aucun ingrédient à afficher pour les recettes sélectionnées.</div>
    <?php else : ?>
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    <form method="post" action="shopping_list.php">
    <ul style="font-size:1.12em; list-style:none; padding-left:0;">
        <?php foreach ($ingredients as $ing): ?>
            <li style="margin-bottom:0.7em; display:flex; align-items:center; gap:10px;">
                <label style="cursor:pointer; flex:1;">
                    <?php if (empty($is_temp_list)) : ?>
                        <input type="checkbox" name="checked[]" value="<?php echo $ing['ingredient_id']; ?>" <?php if ($ing['checked']) echo 'checked'; ?>>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($ing['name']); ?>
                    <?php if ($ing['quantity'] > 0): ?>
                        : <strong><?php echo htmlspecialchars($ing['quantity']) . ' ' . htmlspecialchars($ing['unit']); ?></strong>
                    <?php endif; ?>
                </label>
                <?php if (empty($is_temp_list)) : ?>
                <a href="shopping_list.php?del=<?php echo $ing['ingredient_id']; ?>" class="btn btn-danger btn-sm no-print" style="padding:2px 8px; font-size:0.9em;" onclick="return confirm('Supprimer cet ingrédient de la liste ?');">✖</a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <button type="submit" class="btn btn-fixed-bottom">Sauvegarder les cases cochées</button>
    </form>
    <?php endif; ?>
    <div style="margin-top:2em; color:#888; font-size:0.9em;">
        Imprimé depuis l'application de gestion de recettes.
    </div>
<?php endif; ?>
</section>
</body>
</html>
