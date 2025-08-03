<?php
// forgot_password.php
session_start();
$pageTitle = 'Mot de passe oublié';
include_once 'templates/base.php'; // Inclut le header, navbar, etc.
?>

<div class="container" style="max-width:400px;margin:40px auto;background:#fff;border-radius:10px;box-shadow:0 2px 8px #0002;padding:32px 24px;">
    <h2 style="color:#1976d2;text-align:center;margin-bottom:18px;">Mot de passe oublié ?</h2>
    <?php if (isset($_SESSION['reset_message'])): ?>
        <div style="background:#e3f2fd;color:#1976d2;padding:10px 16px;border-radius:6px;margin-bottom:18px;text-align:center;">
            <?= htmlspecialchars($_SESSION['reset_message']) ?>
        </div>
        <?php unset($_SESSION['reset_message']); ?>
    <?php endif; ?>
    <form action="send_reset_link.php" method="post" style="display:flex;flex-direction:column;gap:16px;">
        <label for="email" style="font-weight:500;">Adresse e-mail</label>
        <input type="email" name="email" id="email" required autofocus style="padding:10px;border:1px solid #bdbdbd;border-radius:5px;font-size:1.1em;">
        <button type="submit" style="background:#1976d2;color:#fff;padding:10px 0;border:none;border-radius:6px;font-size:1.1em;font-weight:600;cursor:pointer;transition:background .2s;">Recevoir le lien de réinitialisation</button>
        <a href="login.php" style="color:#1976d2;text-align:center;text-decoration:underline;font-size:0.97em;">Retour à la connexion</a>
    </form>
</div>

<?php include_once 'templates/footer.php'; ?>
