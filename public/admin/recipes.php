<?php
session_start();
if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../../config/db.php';
$db = require __DIR__ . '/../../config/db.php';
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);

// Suppression
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $pdo->prepare('DELETE FROM recipes WHERE id = ?');
    $stmt->execute([$_GET['delete']]);
    header('Location: recipes.php'); exit;
}
// Liste des recettes
$stmt = $pdo->query('SELECT r.*, u.username, c.name as category FROM recipes r LEFT JOIN users u ON r.user_id = u.id LEFT JOIN categories c ON r.category_id = c.id ORDER BY r.created_at DESC LIMIT 100');
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Recettes</title>
    <link rel="stylesheet" href="../css/home.css">
    <style>
        body { background: #f4f8fb; font-family: 'Poppins', sans-serif; }
        .admin-content { max-width: 900px; margin: 50px auto; background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(44,124,123,0.11); padding: 32px 28px; }
        h1 { text-align: center; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th, td { padding: 10px 8px; border-bottom: 1px solid #e5e5e5; text-align: left; }
        tr:last-child td { border-bottom: none; }
        .actions { display:flex; gap:8px; }
        .btn-delete { background: #b22; }
    </style>
</head>
<body>
<div class="admin-header">
    <span class="icon">&#127968;</span>
    <a href="../index.php" class="btn-home">Accueil du site</a>
    <span class="header-title">Gestion des recettes</span>
</div>
<div class="admin-content">
    <h1>Gestion des recettes</h1>
    <div style="max-width:340px;margin-bottom:18px;">
        <input type="text" id="recipeSearch" placeholder="Rechercher une recette..." style="width:100%;padding:10px 34px 10px 36px;border-radius:8px;border:1px solid #c9e2e2;font-size:1em;background:#f7fafc;outline:none;box-shadow:0 1px 4px rgba(44,124,123,0.06);">
        <span style="position:relative;left:-312px;top:-32px;color:#2c7c7b;font-size:1.2em;pointer-events:none;">&#128269;</span>
    </div>
    <table id="recipesTable">
        <tr><th>Titre</th><th>Catégorie</th><th>Auteur</th><th>Date</th><th>Tags</th><th style="width:90px;">Actions</th></tr>
        <?php
        // Précharger tous les tags associés (pour éviter les requêtes dans la boucle)
        $tags_stmt = $pdo->query('SELECT rt.recipe_id, t.name FROM recipe_tags rt JOIN tags t ON t.id = rt.tag_id');
        $recipe_tags_map = [];
        foreach ($tags_stmt as $row) {
            $recipe_tags_map[$row['recipe_id']][] = $row['name'];
        }
        ?>
        <?php foreach ($recipes as $rec): ?>
            <tr>
                <td><?php echo htmlspecialchars($rec['title']); ?></td>
                <td><?php echo htmlspecialchars($rec['category']); ?></td>
                <td><?php echo htmlspecialchars($rec['username']); ?></td>
                <td><?php echo htmlspecialchars($rec['created_at']); ?></td>
                <td>
                    <?php if (!empty($recipe_tags_map[$rec['id']])):
                        foreach ($recipe_tags_map[$rec['id']] as $tag): ?>
                            <span style="display:inline-block;background:#2c7c7b;color:#fff;border-radius:12px;padding:2px 12px;margin:2px 2px 2px 0;font-size:0.96em;line-height:1.6;">
                                <?php echo htmlspecialchars($tag); ?>
                            </span>
                        <?php endforeach;
                    endif; ?>
                </td>
                <td class="actions">
                    <a href="../recipe.php?id=<?php echo $rec['id']; ?>" class="btn">Voir</a>
                    <a href="recipes.php?delete=<?php echo $rec['id']; ?>" class="btn btn-delete" onclick="return confirm('Supprimer cette recette ?');">🗑️</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <a href="dashboard.php" class="btn">&larr; Retour au tableau de bord</a>
</div>
<script>
// Recherche dynamique recettes
const rinput = document.getElementById('recipeSearch');
const rtable = document.getElementById('recipesTable');
rinput.addEventListener('keyup', function() {
    const filter = rinput.value.toLowerCase();
    for (let i = 1; i < rtable.rows.length; i++) {
        const row = rtable.rows[i];
        let txt = row.cells[0].textContent + ' ' + row.cells[1].textContent + ' ' + row.cells[2].textContent;
        row.style.display = txt.toLowerCase().includes(filter) ? '' : 'none';
    }
});
</script>
</body>
</html>
