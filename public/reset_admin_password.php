<?php
// Script temporaire pour réinitialiser le mot de passe de johny.benisvy@gmail.com
// À supprimer après usage !

$config = require __DIR__ . '/../config/db.php';
$host = $config['host'];
$db   = $config['dbname'];
$user = $config['user'];
$pass = $config['pass'];
$charset = $config['charset'];

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    exit('Erreur de connexion à la base de données : ' . $e->getMessage());
}

$email = 'johny.benisvy@gmail.com';
$newpass = 'SuperAdmin2025?';
$codeSecret = 'AdminReset2025!';

$step = 1;
$codeOk = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['code']) && !isset($_POST['confirm_reset'])) {
        $code = $_POST['code'] ?? '';
        if ($code === $codeSecret) {
            $codeOk = true;
            $step = 2;
            echo "<div style='color:green;font-weight:bold;'>Code correct. Cliquez sur le bouton ci-dessous pour réinitialiser le mot de passe.</div>";
        } else {
            echo "<div style='color:red;font-weight:bold;'>Code secret incorrect.</div>";
        }
    } elseif (isset($_POST['confirm_reset']) && isset($_POST['code_validated']) && $_POST['code_validated'] === '1') {
        // L'utilisateur a validé le code et confirmé la réinitialisation
        $hash = password_hash($newpass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE email = ?');
        $stmt->execute([$hash, $email]);
        if ($stmt->rowCount() > 0) {
            echo "<div style='color:green;font-weight:bold;'>Mot de passe réinitialisé avec succès pour $email.</div>";
        } else {
            echo "<div style='color:orange;font-weight:bold;'>Aucun utilisateur mis à jour. Vérifiez l'email.</div>";
        }
        $step = 3;
    }
}
if ($step === 1) {
    // Étape 1 : saisie du code
    echo '<form method="post" style="max-width:340px;margin:40px auto;background:#f7f7f7;padding:24px;border-radius:10px;box-shadow:0 2px 10px #0001;">';
    echo '<h2 style="font-size:1.3em;margin-bottom:18px;">Réinitialisation admin sécurisée</h2>';
    echo '<label for="code">Code secret :</label>';
    echo '<div style="position:relative;">';
echo '<input type="password" name="code" id="code" required style="width:100%;padding:8px 38px 8px 8px;margin-bottom:16px;">';
echo '<span id="toggle-code" style="position:absolute;top:8px;right:10px;cursor:pointer;font-size:1.2em;color:#888;user-select:none;" title="Afficher/Masquer"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M2.05 12A9.94 9.94 0 0 1 12 4.05a9.94 9.94 0 0 1 9.95 7.95 9.94 9.94 0 0 1-9.95 7.95A9.94 9.94 0 0 1 2.05 12z"/></svg></span>';
echo '</div>';
echo '<script>
const codeInput = document.getElementById("code");
const toggle = document.getElementById("toggle-code");
toggle.addEventListener("click", function(e) {
  if (codeInput.type === "password") {
    codeInput.type = "text";
    toggle.style.color = "#1976d2";
  } else {
    codeInput.type = "password";
    toggle.style.color = "#888";
  }
});
</script>';
    echo '<button type="submit" style="width:100%;padding:10px 0;background:#1976d2;color:#fff;border:none;border-radius:5px;font-size:1em;">Vérifier le code</button>';
    echo '</form>';
} elseif ($step === 2 && $codeOk) {
    // Étape 2 : code correct, proposer la réinitialisation
    echo '<form method="post" style="max-width:340px;margin:40px auto;background:#f7f7f7;padding:24px;border-radius:10px;box-shadow:0 2px 10px #0001;">';
    echo '<input type="hidden" name="code_validated" value="1">';
    echo '<button type="submit" name="confirm_reset" style="width:100%;padding:10px 0;background:#1976d2;color:#fff;border:none;border-radius:5px;font-size:1em;">Réinitialiser le mot de passe</button>';
    echo '</form>';
}

