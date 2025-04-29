<?php
session_start();
if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../../config/db.php';
$db = require __DIR__ . '/../../config/db.php';
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);

// Ajout d'une catégorie
if (isset($_POST['add_name']) && trim($_POST['add_name'])) {
    $stmt = $pdo->prepare('INSERT INTO categories (name) VALUES (?)');
    $stmt->execute([trim($_POST['add_name'])]);
    header('Location: categories.php'); exit;
}
// Suppression
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
    $stmt->execute([$_GET['delete']]);
    header('Location: categories.php'); exit;
}
// Modification
if (isset($_POST['edit_id'], $_POST['edit_name']) && is_numeric($_POST['edit_id'])) {
    $stmt = $pdo->prepare('UPDATE categories SET name = ? WHERE id = ?');
    $stmt->execute([trim($_POST['edit_name']), $_POST['edit_id']]);
    header('Location: categories.php'); exit;
}
// Liste des catégories
$stmt = $pdo->query('SELECT * FROM categories ORDER BY name');
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Catégories</title>
    <link rel="stylesheet" href="../css/home.css">
    <style>
        body { background: #f4f8fb; font-family: 'Poppins', sans-serif; }
        .admin-content { max-width: 600px; margin: 50px auto; background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(44,124,123,0.11); padding: 32px 28px; }
        h1 { text-align: center; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th, td { padding: 10px 8px; border-bottom: 1px solid #e5e5e5; text-align: left; }
        tr:last-child td { border-bottom: none; }
        form.inline { display:inline; margin:0; }
        .actions { display:flex; gap:8px; }
        input[type=text] { padding: 7px 10px; border-radius: 8px; border: 1px solid #d2dbe6; }
        button, .btn { background: #2c7c7b; color: #fff; border: none; border-radius: 8px; padding: 7px 18px; font-weight: 600; cursor: pointer; }
        .btn-delete { background: #b22; }
        .add-form { display: flex; gap: 12px; margin-bottom: 16px; }
    </style>
</head>
<body>
<div class="admin-header">
    <span class="icon">&#127968;</span>
    <a href="../index.php" class="btn-home">Accueil du site</a>
    <span class="header-title">Gestion des catégories</span>
</div>
<div class="admin-content">
    <h1>Gestion des catégories</h1>
    <form class="add-form" method="post">
        <input type="text" name="add_name" placeholder="Nouvelle catégorie" required>
        <button type="submit">Ajouter</button>
    </form>
    <table>
        <tr><th>Nom</th><th style="width:120px;">Actions</th></tr>
        <?php foreach ($categories as $cat): ?>
            <tr>
                <td>
                    <form class="inline" method="post" style="display:inline;">
                        <input type="hidden" name="edit_id" value="<?php echo $cat['id']; ?>">
                        <input type="text" name="edit_name" value="<?php echo htmlspecialchars($cat['name']); ?>" required style="width: 80%">
                        <button type="submit">💾</button>
                    </form>
                </td>
                <td class="actions">
                    <form class="inline" method="get" onsubmit="return confirm('Supprimer cette catégorie ?');">
                        <input type="hidden" name="delete" value="<?php echo $cat['id']; ?>">
                        <button type="submit" class="btn btn-delete">🗑️</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <a href="dashboard.php" class="btn">&larr; Retour au tableau de bord</a>
</div>
</body>
</html>
