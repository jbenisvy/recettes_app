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

    <button class="navbar-toggle" id="navbar-toggle-btn" type="button" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="navbar-links" autocomplete="off" style="width:48px;height:48px;display:flex;align-items:center;justify-content:center;background:#1976d2;border:none;z-index:99999;pointer-events:auto;border-radius:8px;transition:background 0.2s;">
    <span style="display:block;width:28px;height:28px;position:relative;">
        <span style="position:absolute;left:0;top:4px;width:28px;height:4px;background:#fff;border-radius:2px;"></span>
        <span style="position:absolute;left:0;top:12px;width:28px;height:4px;background:#fff;border-radius:2px;"></span>
        <span style="position:absolute;left:0;top:20px;width:28px;height:4px;background:#fff;border-radius:2px;"></span>
    </span>
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
    const toggle = document.querySelector('.navbar-toggle');
    const links = document.getElementById('navbar-links');
    function toggleMenu(e) {
        e.preventDefault();
        const expanded = this.getAttribute('aria-expanded') === 'true';
        this.setAttribute('aria-expanded', !expanded);
        links.classList.toggle('navbar-links-open');
        return false;
    }
    if (toggle && links) {
        toggle.addEventListener('click', toggleMenu);
        toggle.addEventListener('touchstart', toggleMenu);
    }

});
</script>
