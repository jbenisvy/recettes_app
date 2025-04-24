<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../config/db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$db = require __DIR__ . '/../config/db.php';
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? $user['username']);
    $email = trim($_POST['email'] ?? $user['email']);
    $avatar = $user['avatar'];
    $errors = [];
    if (!$username || !$email) {
        $errors[] = 'Tous les champs sont obligatoires.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email invalide.';
    }
    // Gestion de l'upload d'avatar
    if (!empty($_FILES['avatar']['name'])) {
        $targetDir = 'uploads/';
        $fileName = basename($_FILES['avatar']['name']);
        $targetFile = $targetDir . uniqid() . '_' . $fileName;
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];
        if (in_array($fileType, $allowed) && $_FILES['avatar']['size'] < 2*1024*1024) {
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFile)) {
                $avatar = $targetFile;
            } else {
                $errors[] = "Erreur lors de l'upload de l'avatar.";
            }
        } else {
            $errors[] = 'Format ou taille de fichier non valide.';
        }
    }
    if (empty($errors)) {
        // Gestion admin (l'utilisateur peut modifier son propre statut admin)
        $is_admin = $user['is_admin'];
        if ($_SESSION['user_id'] == $user['id']) {
            $is_admin = isset($_POST['is_admin']) ? 1 : 0;
        }
        $stmt = $pdo->prepare('UPDATE users SET username=?, email=?, avatar=?, is_admin=? WHERE id=?');
        $stmt->execute([$username, $email, $avatar, $is_admin, $user_id]);
        $_SESSION['username'] = $username;
        $_SESSION['is_admin'] = $is_admin;
        header('Location: profile.php?updated=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Profil</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="css/profile.css">
</head>
<body>
    <a class="btn" href="index.php" style="margin:16px 0 8px 0;display:inline-block;">&larr; Retour à l'accueil</a>
    <div class="profile-container">
        <h1>Profil de <?php echo htmlspecialchars($user['username']); ?></h1>
        <?php if (!empty($errors)) : ?>
            <div class="error">
                <ul>
                    <?php foreach ($errors as $e) echo "<li>$e</li>"; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['updated'])) : ?>
            <div class="success">Profil mis à jour !</div>
        <?php endif; ?>
        <?php if ($user['avatar']) : ?>
            <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar" class="profile-avatar">
        <?php else : ?>
            <img src="images/default-avatar.png" alt="Avatar" class="profile-avatar">
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data" class="profile-form">
            <label for="username">Nom d'utilisateur</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            <label for="avatar">Avatar</label>
            <input type="file" id="avatar" name="avatar" accept="image/*">
            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user['id']): ?>
                <label class="admin-checkbox"><input type="checkbox" name="is_admin" value="1" <?php if (isset($user['is_admin']) && $user['is_admin']) echo 'checked'; ?>> Administrateur</label>
            <?php endif; ?>
            <button type="submit" class="btn">Mettre à jour</button>
        </form>
    </div>
    <p><a href="logout.php">Se déconnecter</a></p>
</body>
</html>
