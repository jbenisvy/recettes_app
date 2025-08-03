<?php
session_start();
if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../../config/db.php';
$db = require __DIR__ . '/../../config/db.php';
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);

$error = '';
$success = '';
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: users.php'); exit;
}
$user_id = intval($_GET['id']);
$stmt = $pdo->prepare('SELECT username, email FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    $error = "Utilisateur introuvable.";
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $newpass = $_POST['newpass'] ?? '';
    $conf = $_POST['conf'] ?? '';
    if (strlen($newpass) < 8) {
        $error = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif ($newpass !== $conf) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        $hash = password_hash($newpass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([$hash, $user_id]);
        $success = "Le mot de passe a été réinitialisé avec succès.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réinitialiser le mot de passe</title>
    <link rel="stylesheet" href="/css/home.css">
    <style>body { background:#f4f8fb;font-family:'Poppins',sans-serif; } .container{max-width:400px;margin:60px auto;background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(44,124,123,0.11);padding:32px 28px;} label{display:block;margin-bottom:6px;} input[type=password]{width:100%;padding:8px;margin-bottom:16px;} .btn{width:100%;} .error{color:#b22;background:#ffd3d3;padding:10px 16px;border-radius:8px;} .success{color:#227c22;background:#d3ffd3;padding:10px 16px;border-radius:8px;} </style>
</head>
<body>
<div class="container">
    <h2>Réinitialiser le mot de passe</h2>
    <?php if ($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success"><?php echo $success; ?></div><?php endif; ?>
    <?php if (!$success && $user): ?>
    <form method="post">
        <label>Utilisateur :</label>
        <div style="margin-bottom:10px;"><strong><?php echo htmlspecialchars($user['username']); ?></strong> (<?php echo htmlspecialchars($user['email']); ?>)</div>
        <label for="newpass">Nouveau mot de passe :</label>
        <input type="password" name="newpass" id="newpass" required minlength="8">
        <label for="conf">Confirmer le mot de passe :</label>
        <input type="password" name="conf" id="conf" required minlength="8">
        <button type="submit" class="btn">Réinitialiser</button>
    </form>
    <?php endif; ?>
    <a href="users.php" class="btn" style="margin-top:18px;">&larr; Retour à la liste</a>
</div>
</body>
</html>
