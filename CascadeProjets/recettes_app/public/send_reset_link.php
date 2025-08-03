<?php
// send_reset_link.php
session_start();
require_once '../config/db.php'; // adapter le chemin selon ton projet

function randomToken($length = 48) {
    return bin2hex(random_bytes($length/2));
}

if (!isset($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $_SESSION['reset_message'] = "Adresse e-mail invalide.";
    header('Location: forgot_password.php');
    exit;
}

$email = trim($_POST['email']);

// Vérifier si l'utilisateur existe
$db = new PDO(DB_DSN, DB_USER, DB_PASS);
$stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    // Pour la sécurité, ne pas révéler si l'email existe ou non
    $_SESSION['reset_message'] = "Si cette adresse existe, un mail de réinitialisation a été envoyé.";
    header('Location: forgot_password.php');
    exit;
}

// Générer un token sécurisé et une date d'expiration (1h)
$token = randomToken(48);
$expires = date('Y-m-d H:i:s', time() + 3600);

// Supprimer les anciens tokens pour cet email
$db->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$email]);
// Enregistrer le nouveau token
$db->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)')->execute([$email, $token, $expires]);

// Préparer le mail
$reset_url = sprintf(
    '%s://%s%s/reset_password.php?token=%s',
    isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http',
    $_SERVER['HTTP_HOST'],
    dirname($_SERVER['PHP_SELF']),
    $token
);
$subject = "Réinitialisation de votre mot de passe";
$message = "Bonjour,\n\nPour réinitialiser votre mot de passe, cliquez sur le lien ci-dessous ou copiez-le dans votre navigateur :\n\n$reset_url\n\nCe lien est valable 1 heure. Si vous n'êtes pas à l'origine de cette demande, ignorez ce message.\n\nCordialement,\nL'équipe Recettes";
$headers = "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\nContent-Type: text/plain; charset=utf-8";

// Envoyer le mail
mail($email, $subject, $message, $headers);

$_SESSION['reset_message'] = "Si cette adresse existe, un mail de réinitialisation a été envoyé.";
header('Location: forgot_password.php');
exit;
