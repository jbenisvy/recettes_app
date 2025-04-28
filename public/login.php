<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../config/db.php';
session_start();
// Gestion de la connexion utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $errors = [];

    if (!$email || !$password) {
        $errors[] = 'Tous les champs sont obligatoires.';
    } else {
        $db = require __DIR__ . '/../config/db.php';
        $pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->execute([$email, $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            $errors[] = 'Utilisateur non trouvé.';
        } elseif (!password_verify($password, $user['password'])) {
            $errors[] = 'Mot de passe incorrect.';
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['is_admin'] = !empty($user['admin']) ? (int)$user['admin'] : 0;
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Connexion</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<a class="btn" href="index.php" style="margin:16px 0 8px 0;display:inline-block;">&larr; Retour à l'accueil</a>
    <h1>Connexion</h1>
    <?php if (!empty($errors)) : ?>
        <div class="error">
            <ul>
                <?php foreach ($errors as $e) echo "<li>$e</li>"; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['registered'])) : ?>
        <div class="success">Inscription réussie, vous pouvez vous connecter.</div>
    <?php endif; ?>
    <form method="post">
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Mot de passe" required><br>
        <button type="submit">Se connecter</button>
    </form>
    <p>Pas encore de compte ? <a href="register.php">Inscription</a></p>
</body>
</html>
