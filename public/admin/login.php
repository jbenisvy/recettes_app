<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../../config/db.php';
$db = require __DIR__ . '/../../config/db.php';
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);

$error = '';
if (isset($_POST['username'], $_POST['password'])) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$_POST['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($_POST['password'], $user['password']) && !empty($user['is_admin'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['is_admin'] = true;
        header('Location: dashboard.php'); exit;
    } else {
        $error = "Identifiants incorrects ou accès non autorisé.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Connexion</title>
    <link rel="stylesheet" href="/css/home.css">
    <style>
        body { background: #f4f8fb; font-family: 'Poppins', sans-serif; }
        .admin-login { max-width: 340px; margin: 80px auto; background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(44,124,123,0.11); padding: 32px 24px; }
        .admin-login h2 { text-align: center; margin-bottom: 18px; }
        .admin-login input { width: 100%; margin-bottom: 16px; padding: 10px; border-radius: 8px; border: 1px solid #d2dbe6; }
        .admin-login button { width: 100%; background: #2c7c7b; color: #fff; border: none; border-radius: 8px; padding: 12px; font-weight: 600; cursor: pointer; }
        .admin-login .error { color: #b22; margin-bottom: 10px; text-align: center; }
    </style>
</head>
<body>
<div class="admin-login">
    <h2>Admin – Connexion</h2>
    <?php if ($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
    <form method="post">
        <input type="text" name="username" placeholder="Nom d'utilisateur" required autofocus>
        <input type="password" name="password" placeholder="Mot de passe" required>
        <button type="submit">Connexion</button>
    </form>
</div>
</body>
</html>
