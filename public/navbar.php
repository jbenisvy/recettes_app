<?php
// Barre de navigation réutilisable
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<div style="text-align:right; padding:10px;">
    <?php if (isset($_SESSION['username'])): ?>
        Connecté en tant que <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
        <?php if (!empty($_SESSION['avatar'])): ?>
            <img src="<?php echo htmlspecialchars($_SESSION['avatar']); ?>" alt="Avatar" style="width:28px;height:28px;border-radius:50%;object-fit:cover;vertical-align:middle;margin-left:6px;">
        <?php endif; ?>
    <?php endif; ?>
</div>
<nav class="navbar">
    <div class="navbar-logo">
        <a href="index.php" aria-label="Accueil">
            <img src="https://img.icons8.com/fluency/40/000000/chef-hat.png" alt="Logo Chef" style="vertical-align:middle;">
        </a>
    </div>
    <button class="navbar-toggle" type="button" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="navbar-links">
        <span class="navbar-toggle-icon"></span>
    </button>
    <ul class="navbar-links" id="navbar-links">
        <li><a href="index.php">Accueil</a></li>
        <li><a href="search.php">Recherche</a></li>
        <li><a href="add_recipe.php">Ajouter</a></li>
        <li><a href="favorites.php">Favoris</a></li>
        <li><a href="shopping_list.php">Courses</a></li>
        <?php if (isset($_SESSION['user_id'])): ?>
            <li><a href="my_recipes.php">Mes recettes</a></li>
            <li><a href="profile.php">Profil</a></li>
            <li><a href="logout.php">Déconnexion</a></li>
        <?php else: ?>
            <li><a href="login.php">Connexion</a></li>
            <li><a href="register.php">Inscription</a></li>
        <?php endif; ?>
    </ul>
</nav>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Affiche un message visible pour vérifier exécution JS sur mobile
    var debugDiv = document.createElement('div');
    debugDiv.innerHTML = '<div style="background:#e63946;color:white;padding:8px;text-align:center;z-index:9999;">JS NAVBAR OK</div>';
    document.body.insertBefore(debugDiv, document.body.firstChild);

    const toggle = document.querySelector('.navbar-toggle');
    const links = document.getElementById('navbar-links');
    // TEST : Forcer l'ouverture du menu au chargement
    if (links) {
        links.classList.add('navbar-links-open');
    }
    if (toggle && links) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const expanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', !expanded);
            links.classList.toggle('navbar-links-open');
        });
    }
    // À retirer après diagnostic
});
</script>
