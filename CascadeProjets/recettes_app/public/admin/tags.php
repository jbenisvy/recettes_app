<?php
session_start();
if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../../config/db.php';
$db = require __DIR__ . '/../../config/db.php';
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);

// Ajout d'un tag
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_tag'])) {
    $tag = trim($_POST['new_tag']);
    if ($tag) {
        try {
            $stmt = $pdo->prepare('INSERT INTO tags (name) VALUES (?)');
            $stmt->execute([$tag]);
        } catch (PDOException $e) {
            $error = 'Ce tag existe déjà ou est invalide.';
        }
    }
}
// Suppression d'un tag
$errorMessage = null;
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $pdo->prepare('DELETE FROM tags WHERE id = ?')->execute([$_GET['delete']]);
        header('Location: tags.php'); exit;
    } catch (PDOException $e) {
        if ($e->getCode() == '23000' && strpos($e->getMessage(), 'a foreign key constraint fails') !== false) {
            $errorMessage = "Impossible de supprimer ce tag car il est utilisé dans une ou plusieurs recettes.\nVeuillez d’abord modifier ou supprimer les recettes qui utilisent ce tag.";
        } else {
            $errorMessage = "Erreur lors de la suppression : " . htmlspecialchars($e->getMessage());
        }
    }
}
// Récupérer les tags et le nombre de recettes associées
$tags = $pdo->query('SELECT t.*, (SELECT COUNT(*) FROM recipe_tags rt WHERE rt.tag_id = t.id) AS recipe_count FROM tags t ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

// Modification d'un tag
if (isset($_POST['edit_tag_id'], $_POST['edit_tag_name'])) {
    $edit_id = (int)$_POST['edit_tag_id'];
    $edit_name = trim($_POST['edit_tag_name']);
    if ($edit_id && $edit_name) {
        try {
            $stmt = $pdo->prepare('UPDATE tags SET name = ? WHERE id = ?');
            $stmt->execute([$edit_name, $edit_id]);
            header('Location: tags.php'); exit;
        } catch (PDOException $e) {
            $error = 'Ce tag existe déjà ou est invalide.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Tags</title>
    <link rel="stylesheet" href="../css/home.css">
    <style>
        .admin-header { background: #2c7c7b; color: #fff; padding: 16px 0 12px 0; border-radius: 0 0 24px 24px; box-shadow: 0 4px 18px rgba(44,124,123,0.13); display: flex; align-items: center; gap: 18px; }
        .admin-header .icon { font-size: 2.2em; margin-left: 28px; }
        .admin-header .btn-home { background: #fff; color: #2c7c7b; border-radius: 18px; padding: 7px 20px; font-weight: 700; font-size: 1em; margin-left: 18px; box-shadow: 0 2px 8px rgba(44,124,123,0.10); text-decoration: none; transition: background 0.2s, color 0.2s; }
        .admin-header .btn-home:hover { background: #e0f7fa; color: #1d5352; }
        .admin-header .header-title { font-family: 'Montserrat', 'Poppins', sans-serif; font-size: 1.3em; font-weight: 600; margin-left: 34px; letter-spacing: 0.5px; }
        body { background: #f4f8fb; font-family: 'Poppins', sans-serif; }
        .admin-content { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(44,124,123,0.11); padding: 32px 28px; }
        h1 { text-align: center; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th, td { padding: 10px 8px; border-bottom: 1px solid #e5e5e5; text-align: left; }
        tr:last-child td { border-bottom: none; }
        .actions { display:flex; gap:8px; }
        .btn-delete { background: #b22; color:#fff; border-radius:8px; padding:4px 12px; text-decoration:none; }
        .btn-delete:hover { background:#d33; }
        .add-form { display: flex; gap: 8px; margin-bottom: 18px; }
        .add-form input[type=text] { flex: 1; padding: 8px; border-radius: 6px; border: 1px solid #c9e2e2; }
        .add-form button { padding: 8px 18px; border-radius: 8px; background: #2c7c7b; color: #fff; border: none; font-weight: 600; }
        .error { color: #b22; margin-bottom: 10px; }
    </style>
</head>
<body>
<div class="admin-header">
    <span class="icon">🏷️</span>
    <a href="../index.php" class="btn-home">Accueil du site</a>
    <span class="header-title">Gestion des tags</span>
</div>
<div class="admin-content">
    <h1>Gestion des tags</h1>
    <?php if ($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
    <form class="add-form" method="post">
        <input type="text" name="new_tag" placeholder="Ajouter un tag (ex: végétarien, sans gluten...)" required maxlength="64">
        <button type="submit">Ajouter</button>
    </form>
    <input type="text" id="tagSearch" placeholder="Rechercher un tag..." style="width:100%;padding:8px 14px;border-radius:7px;border:1px solid #c9e2e2;margin-bottom:12px;font-size:1em;">
    <table id="tagsTable">
        <tr><th>Nom du tag</th><th>Recettes associées</th><th style="width:140px;">Actions</th></tr>
        <?php foreach ($tags as $tag): ?>
            <tr>
                <td>
                    <form method="post" style="display:inline;" class="edit-tag-form">
                        <input type="hidden" name="edit_tag_id" value="<?php echo $tag['id']; ?>">
                        <input type="text" name="edit_tag_name" value="<?php echo htmlspecialchars($tag['name']); ?>" maxlength="64" style="border:1px solid #c9e2e2;border-radius:6px;padding:4px 10px;font-size:1em;width:160px;">
                        <button type="submit" style="background:#2c7c7b;color:#fff;border:none;border-radius:6px;padding:4px 12px;margin-left:4px;">Enregistrer</button>
                    </form>
                </td>
                <td style="text-align:center;">
                    <?php echo (int)$tag['recipe_count']; ?>
                </td>
                <td class="actions">
                    <a href="tags.php?delete=<?php echo $tag['id']; ?>" class="btn-delete" onclick="return confirm('Supprimer ce tag ?');">Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <script>
    // Filtre simple sur les tags
    document.getElementById('tagSearch').addEventListener('input', function(e) {
        const val = e.target.value.toLowerCase();
        document.querySelectorAll('#tagsTable tr').forEach(function(row, idx) {
            if (idx === 0) return; // header
            const tag = row.querySelector('input[name="edit_tag_name"]').value.toLowerCase();
            row.style.display = tag.includes(val) ? '' : 'none';
        });
    });
    </script>
    <a href="dashboard.php" class="btn">&larr; Retour au tableau de bord</a>
</div>
<?php if (!empty($errorMessage)): ?>
<script>
    window.onload = function() {
        alert(<?php echo json_encode($errorMessage); ?>);
    };
</script>
<?php endif; ?>
</body>
</html>
