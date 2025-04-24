<?php
session_start();
if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../../config/db.php';
$db = require __DIR__ . '/../../config/db.php';
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);

// Suppression d'un commentaire
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $pdo->prepare('DELETE FROM comments WHERE id = ?');
    $stmt->execute([$_GET['delete']]);
    header('Location: comments.php'); exit;
}
// Liste des commentaires
$stmt = $pdo->query('SELECT c.*, u.username, r.title as recipe_title FROM comments c LEFT JOIN users u ON c.user_id = u.id LEFT JOIN recipes r ON c.recipe_id = r.id ORDER BY c.created_at DESC LIMIT 100');
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Commentaires</title>
    <link rel="stylesheet" href="/css/home.css">
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
    <a href="/index.php" class="btn-home">Accueil du site</a>
    <span class="header-title">Gestion des commentaires</span>
</div>
<div class="admin-content">
    <h1>Gestion des commentaires</h1>
    <table>
        <tr><th>Recette</th><th>Auteur</th><th>Commentaire</th><th>Date</th><th style="width:90px;">Actions</th></tr>
        <?php foreach ($comments as $com): ?>
            <tr>
                <td><?php echo htmlspecialchars($com['recipe_title']); ?></td>
                <td><?php echo htmlspecialchars($com['username']); ?></td>
                <td><?php echo htmlspecialchars($com['content']); ?></td>
                <td><?php echo htmlspecialchars($com['created_at']); ?></td>
                <td class="actions">
                    <a href="comments.php?delete=<?php echo $com['id']; ?>" class="btn btn-delete" onclick="return confirm('Supprimer ce commentaire ?');">🗑️</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <a href="dashboard.php" class="btn">&larr; Retour au tableau de bord</a>
</div>
</body>
</html>
