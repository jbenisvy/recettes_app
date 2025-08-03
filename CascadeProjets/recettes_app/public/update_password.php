<?php
// update_password.php
session_start();
require_once '../config/db.php';

if (!isset($_POST['token'], $_POST['password'])) {
    $_SESSION['reset_message'] = "Requête invalide.";
    header('Location: forgot_password.php');
    exit;
}

$token = $_POST['token'];
$password = $_POST['password'];

if (strlen($password) < 8) {
    $_SESSION['reset_message'] = "Le mot de passe doit contenir au moins 8 caractères.";
    header("Location: reset_password.php?token=" . urlencode($token));
    exit;
}

$db = new PDO(DB_DSN, DB_USER, DB_PASS);
$stmt = $db->prepare('SELECT email, expires_at FROM password_resets WHERE token = ? LIMIT 1');
$stmt->execute([$token]);
$row = $stmt->fetch();

if (!$row || strtotime($row['expires_at']) < time()) {
    $_SESSION['reset_message'] = "Ce lien de réinitialisation a expiré ou est invalide.";
    header('Location: forgot_password.php');
    exit;
}

$email = $row['email'];
$hash = password_hash($password, PASSWORD_DEFAULT);

// Mettre à jour le mot de passe utilisateur
$stmt = $db->prepare('UPDATE users SET password = ? WHERE email = ?');
$stmt->execute([$hash, $email]);

// Supprimer tous les tokens pour cet email
$db->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$email]);

$_SESSION['reset_message'] = "Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.";
header('Location: login.php');
exit;
