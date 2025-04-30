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
    <div style="position:relative;display:inline-block;">
    <button class="navbar-toggle" id="navbar-toggle-btn" type="button" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="navbar-links" autocomplete="off" style="width:48px;height:48px;display:flex;align-items:center;justify-content:center;background:none;border:none;z-index:2;position:relative;">
        <span class="navbar-toggle-icon" style="display:block;width:28px;height:28px;"></span>
    </button>
    <!-- Zone rouge de debug superposée -->
    <div id="debug-hotspot" style="position:absolute;top:0;left:0;width:48px;height:48px;background:rgba(255,0,0,0.25);border-radius:8px;z-index:3;"></div>
</div>
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
    // Ajout d'un log visible pour debug
    var logDiv = document.createElement('div');
    logDiv.id = 'navbarLog';
    logDiv.style = 'background:#2a9d8f;color:white;padding:4px;text-align:center;z-index:9999;font-size:14px;';
    document.body.insertBefore(logDiv, document.body.firstChild);

    function toggleMenu(e) {
        e.preventDefault();
        const expanded = this.getAttribute('aria-expanded') === 'true';
        this.setAttribute('aria-expanded', !expanded);
        links.classList.toggle('navbar-links-open');
        logDiv.innerText = 'JS OK : menu hamburger cliqué (' + e.type + ')';
        setTimeout(function(){ logDiv.innerText = ''; }, 2000);
        return false;
    }
    if (toggle && links) {
        toggle.addEventListener('click', toggleMenu);
        toggle.addEventListener('touchstart', toggleMenu);
    }
    // Zone rouge superposée pour debug mobile
    var hotspot = document.getElementById('debug-hotspot');
    if (hotspot && links && toggle) {
        hotspot.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var expanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', !expanded);
            links.classList.toggle('navbar-links-open');
            logDiv.innerText = 'HOTSPOT : menu hamburger cliqué';
            setTimeout(function(){ logDiv.innerText = ''; }, 1500);
            return false;
        });
        hotspot.addEventListener('touchstart', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var expanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', !expanded);
            links.classList.toggle('navbar-links-open');
            logDiv.innerText = 'HOTSPOT : menu hamburger touch';
            setTimeout(function(){ logDiv.innerText = ''; }, 1500);
            return false;
        });
    }

});
</script>
