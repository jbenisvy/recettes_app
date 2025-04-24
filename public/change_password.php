<?php
session_start();
require_once __DIR__ . '/../config/db.php';
$db = require __DIR__ . '/../config/db.php';
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);

if (empty($_SESSION['user_id'])) {
    header('Location: login.php'); exit;
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST['old'] ?? '';
    $new = $_POST['new'] ?? '';
    $conf = $_POST['conf'] ?? '';
    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $hash = $stmt->fetchColumn();
    if (!$hash || !password_verify($old, $hash)) {
        $error = "Ancien mot de passe incorrect.";
    } elseif (strlen($new) < 8) {
        $error = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
    } elseif ($new !== $conf) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        $newhash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([$newhash, $_SESSION['user_id']]);
        $success = "Mot de passe modifié avec succès.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Changer mon mot de passe</title>
    <link rel="stylesheet" href="/css/home.css">
    <style>body { background:#f4f8fb;font-family:'Poppins',sans-serif; } .container{max-width:400px;margin:60px auto;background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(44,124,123,0.11);padding:32px 28px;} label{display:block;margin-bottom:6px;} input[type=password]{width:100%;padding:8px;margin-bottom:16px;} .btn{width:100%;} .error{color:#b22;background:#ffd3d3;padding:10px 16px;border-radius:8px;} .success{color:#227c22;background:#d3ffd3;padding:10px 16px;border-radius:8px;} </style>
</head>
<body>
<div class="container">
    <h2>Changer mon mot de passe</h2>
    <?php if ($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success"><?php echo $success; ?></div><?php endif; ?>
    <form method="post">
        <label for="old">Ancien mot de passe :</label>
        <input type="password" name="old" id="old" required>
        <label for="new">Nouveau mot de passe :</label>
        <input type="password" name="new" id="new" required minlength="8">
        <label for="conf">Confirmer le nouveau mot de passe :</label>
        <input type="password" name="conf" id="conf" required minlength="8">
        <button type="submit" class="btn">Changer</button>
    </form>
    <a href="index.php" class="btn" style="margin-top:18px;">&larr; Retour</a>
</div>
</body>
</html>
