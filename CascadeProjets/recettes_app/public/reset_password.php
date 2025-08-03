<?php
// reset_password.php
session_start();
require_once '../config/db.php';
$pageTitle = 'Réinitialiser le mot de passe';
include_once 'templates/base.php';

$token = $_GET['token'] ?? '';
$error = '';
$email = '';

if (!$token) {
    $error = "Lien de réinitialisation invalide.";
} else {
    $db = new PDO(DB_DSN, DB_USER, DB_PASS);
    $stmt = $db->prepare('SELECT email, expires_at FROM password_resets WHERE token = ? LIMIT 1');
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if (!$row || strtotime($row['expires_at']) < time()) {
        $error = "Ce lien de réinitialisation a expiré ou est invalide.";
    } else {
        $email = $row['email'];
    }
}
?>
<div class="container" style="max-width:400px;margin:40px auto;background:#fff;border-radius:10px;box-shadow:0 2px 8px #0002;padding:32px 24px;">
    <h2 style="color:#1976d2;text-align:center;margin-bottom:18px;">Réinitialiser le mot de passe</h2>
    <?php if ($error): ?>
        <div style="background:#ffcdd2;color:#c62828;padding:10px 16px;border-radius:6px;margin-bottom:18px;text-align:center;">
            <?= htmlspecialchars($error) ?>
        </div>
        <div style="text-align:center;">
            <a href="forgot_password.php" style="color:#1976d2;text-decoration:underline;">Demander un nouveau lien</a>
        </div>
    <?php elseif ($email): ?>
        <form action="update_password.php" method="post" style="display:flex;flex-direction:column;gap:16px;">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <label for="password" style="font-weight:500;">Nouveau mot de passe</label>
            <input type="password" name="password" id="password" required minlength="8" style="padding:10px;border:1px solid #bdbdbd;border-radius:5px;font-size:1.1em;">
            <button type="submit" style="background:#1976d2;color:#fff;padding:10px 0;border:none;border-radius:6px;font-size:1.1em;font-weight:600;cursor:pointer;transition:background .2s;">Valider le nouveau mot de passe</button>
        </form>
    <?php endif; ?>
</div>
<?php include_once 'templates/footer.php'; ?>
