<?php
session_start();
if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../../config/db.php';
$db = require __DIR__ . '/../../config/db.php';
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);

// Suppression
$error = '';
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$_GET['delete']]);
        header('Location: users.php'); exit;
    } catch (PDOException $e) {
        // Vérifie si c'est une erreur de contrainte étrangère
        if ($e->getCode() == '23000') {
            $error = "Impossible de supprimer cet utilisateur : il a déjà créé des recettes ou d'autres données dépendent de lui.";
        } else {
            $error = "Erreur lors de la suppression : " . $e->getMessage();
        }
    }
}
// Promotion/déclassement admin
if (isset($_GET['toggle_admin']) && is_numeric($_GET['toggle_admin'])) {
    $stmt = $pdo->prepare('UPDATE users SET is_admin = 1 - is_admin WHERE id = ?');
    $stmt->execute([$_GET['toggle_admin']]);
    header('Location: users.php'); exit;
}
// Liste des utilisateurs
$stmt = $pdo->query('SELECT * FROM users ORDER BY username');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Utilisateurs</title>
    <link rel="stylesheet" href="/css/home.css">
    <style>
        .admin-header {
            background: #2c7c7b;
            color: #fff;
            padding: 16px 0 12px 0;
            border-radius: 0 0 24px 24px;
            box-shadow: 0 4px 18px rgba(44,124,123,0.13);
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 18px;
            position: relative;
        }
        .admin-header .icon {
            font-size: 2.2em;
            margin-left: 28px;
        }
        .admin-header .btn-home {
            background: #fff;
            color: #2c7c7b;
            border-radius: 18px;
            padding: 7px 20px;
            font-weight: 700;
            font-size: 1em;
            margin-left: 18px;
            box-shadow: 0 2px 8px rgba(44,124,123,0.10);
            text-decoration: none;
            transition: background 0.2s, color 0.2s;
        }
        .admin-header .btn-home:hover { background: #e0f7fa; color: #1d5352; }
        .admin-header .header-title {
            font-family: 'Montserrat', 'Poppins', sans-serif;
            font-size: 1.3em;
            font-weight: 600;
            margin-left: 34px;
            letter-spacing: 0.5px;
        }

        body { background: #f4f8fb; font-family: 'Poppins', sans-serif; }
        .admin-content { max-width: 800px; margin: 50px auto; background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(44,124,123,0.11); padding: 32px 28px; }
        h1 { text-align: center; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th, td { padding: 10px 8px; border-bottom: 1px solid #e5e5e5; text-align: left; }
        tr:last-child td { border-bottom: none; }
        .actions { display:flex; gap:8px; }
        .btn-delete { background: #b22; }
        .btn-admin { background: #2c7c7b; }
    </style>
</head>
<body>
<div class="admin-header">
    <span class="icon">&#127968;</span>
    <a href="/index.php" class="btn-home">Accueil du site</a>
    <span class="header-title">Gestion des utilisateurs</span>
</div>
<div class="admin-content">
    <h1>Gestion des utilisateurs</h1>
    <?php if (!empty($error)) : ?>
        <div class="error" style="background:#ffd3d3;color:#b22;padding:10px 18px;border-radius:8px;margin-bottom:18px;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    <div style="max-width:340px;margin-bottom:18px;">
        <input type="text" id="userSearch" placeholder="Rechercher un utilisateur..." style="width:100%;padding:10px 34px 10px 36px;border-radius:8px;border:1px solid #c9e2e2;font-size:1em;background:#f7fafc;outline:none;box-shadow:0 1px 4px rgba(44,124,123,0.06);">
        <span style="position:relative;left:-312px;top:-32px;color:#2c7c7b;font-size:1.2em;pointer-events:none;">&#128269;</span>
    </div>
    <table id="usersTable">
        <tr><th>Nom d'utilisateur</th><th>Email</th><th>Admin</th><th style="width:120px;">Actions</th></tr>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo $user['is_admin'] ? 'Oui' : 'Non'; ?></td>
                <td class="actions">
                    <a href="users.php?toggle_admin=<?php echo $user['id']; ?>" class="btn btn-admin" onclick="return confirm('Changer le statut admin ?');">Admin</a>
                    <a href="reset_password.php?id=<?php echo $user['id']; ?>" class="btn" style="background:#227c22;">🔑 Réinitialiser</a>
                    <a href="users.php?delete=<?php echo $user['id']; ?>" class="btn btn-delete" onclick="return confirm('Supprimer cet utilisateur ?');">🗑️</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <a href="dashboard.php" class="btn">&larr; Retour au tableau de bord</a>
</div>
<script>
// Recherche dynamique utilisateurs
const input = document.getElementById('userSearch');
const table = document.getElementById('usersTable');
input.addEventListener('keyup', function() {
    const filter = input.value.toLowerCase();
    for (let i = 1; i < table.rows.length; i++) {
        const row = table.rows[i];
        let txt = row.cells[0].textContent + ' ' + row.cells[1].textContent;
        row.style.display = txt.toLowerCase().includes(filter) ? '' : 'none';
    }
});
</script>
</body>
</html>
