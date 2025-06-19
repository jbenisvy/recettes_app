<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: search.php');
    exit;
}

$db = require __DIR__ . '/../config/db.php';
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);
$id = intval($_GET['id']);

// Récupérer la recette
$stmt = $pdo->prepare('SELECT recipes.*, categories.name AS category, users.username, users.id AS author_id FROM recipes LEFT JOIN categories ON recipes.category_id = categories.id LEFT JOIN users ON recipes.user_id = users.id WHERE recipes.id = ?');
$stmt->execute([$id]);
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$recipe) {
    $pageTitle = "Recette introuvable";
    $pageContent = '<h2>Recette introuvable.</h2>';
    require 'templates/base.php';
    exit;
}

// Récupération des médias
$media_stmt = $pdo->prepare("SELECT * FROM recipe_media WHERE recipe_id = ? ORDER BY created_at");
$media_stmt->execute([$id]);
$media = $media_stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les commentaires
$comments_stmt = $pdo->prepare('SELECT comments.*, users.username FROM comments LEFT JOIN users ON comments.user_id = users.id WHERE recipe_id = ? ORDER BY created_at DESC');
$comments_stmt->execute([$id]);
$comments = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);

// Ajout de commentaire
$commentMsg = '';
if (isset($_SESSION['user_id']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'], $_POST['rating'])) {
    $content = trim($_POST['content']);
    $rating = intval($_POST['rating']);
    if ($content && $rating >= 1 && $rating <= 5) {
        $stmt = $pdo->prepare('INSERT INTO comments (user_id, recipe_id, content, rating) VALUES (?, ?, ?, ?)');
        $stmt->execute([$_SESSION['user_id'], $id, $content, $rating]);

        // Notifier par mail
        $to = 'johny.benisvy@gmail.com';
        $subject = 'Nouveau commentaire sur la recette : ' . $recipe['title'];
        $author = isset($_SESSION['username']) ? $_SESSION['username'] : 'Utilisateur';
        $message = "Un nouveau commentaire a été posté par $author :\n\n";
        $message .= "Note : $rating/5\n";
        $message .= "Commentaire :\n$content\n";
        $message .= "\nVoir la recette : http://localhost/recettes-app/public/recipe.php?id=$id";
        $headers = 'From: noreply@recettes-app.local' . "\r\n" .
                   'Content-Type: text/plain; charset=utf-8';
        mail($to, $subject, $message, $headers);

        $_SESSION['success_message'] = "Commentaire ajouté !";
        header("Location: recipe.php?id=$id");
        exit;
    } else {
        $commentMsg = "Remplis le commentaire et la note (1-5).";
    }
}

// Gestion des favoris
$is_favorite = false;
if (isset($_SESSION['user_id'])) {
    $fav = $pdo->prepare('SELECT id FROM favorites WHERE user_id = ? AND recipe_id = ?');
    $fav->execute([$_SESSION['user_id'], $id]);
    $is_favorite = $fav->fetch() ? true : false;
    if (isset($_GET['fav'])) {
        if ($_GET['fav'] === 'add' && !$is_favorite) {
            $pdo->prepare('INSERT INTO favorites (user_id, recipe_id) VALUES (?, ?)')->execute([$_SESSION['user_id'], $id]);
            header("Location: recipe.php?id=$id"); exit;
        } elseif ($_GET['fav'] === 'del' && $is_favorite) {
            $pdo->prepare('DELETE FROM favorites WHERE user_id = ? AND recipe_id = ?')->execute([$_SESSION['user_id'], $id]);
            header("Location: recipe.php?id=$id"); exit;
        }
    }
}

$pageTitle = htmlspecialchars($recipe['title']);
ob_start();
?>
<body>
<?php if ((isset($_SESSION['user_id']) && isset($recipe['user_id']) && $_SESSION['user_id'] == $recipe['user_id']) || (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'])): ?>
    <a href="delete_recipe.php?id=<?php echo $recipe['id']; ?>" onclick="return confirm('Supprimer cette recette ?');" class="btn btn-delete" style="background:#c00;color:#fff;float:right;margin:20px 0 0 20px;">🗑️ Supprimer</a>
<?php endif; ?>
<?php if (!empty($_SESSION['success_message'])): ?>
    <script>
        setTimeout(function() { alert(<?php echo json_encode($_SESSION['success_message']); ?>); }, 100);
    </script>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>
<style>
.recipe-page-container {
    max-width: 760px;
    margin: 32px auto 40px auto;
    background: #f9fafb;
    border-radius: 14px;
    box-shadow: 0 2px 16px #0001;
    padding: 30px 18px 32px 18px;
    font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
}
.recipe-header {
    border-bottom: 2px solid #e0e4e8;
    padding-bottom: 16px;
    margin-bottom: 18px;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}
.recipe-header h1 {
    font-size: 2.2em;
    color: #236665;
    margin-bottom: 8px;
    font-weight: 700;
    letter-spacing: 1px;
}
.recipe-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    font-size: 1.05em;
    color: #444;
    margin-bottom: 4px;
}
.recipe-meta span {
    background: #e6f3f1;
    border-radius: 12px;
    padding: 4px 12px;
    margin-right: 4px;
}
.recipe-description, .recipe-ingredients, .recipe-steps {
    margin-bottom: 26px;
    padding: 18px 18px 14px 18px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 1px 6px #0001;
}
.recipe-description h3, .recipe-ingredients h3, .recipe-steps h3 {
    color: #236665;
    margin-bottom: 10px;
    font-size: 1.2em;
    font-weight: 600;
}
.recipe-ingredients ul, .recipe-steps ol {
    margin-left: 16px;
    font-size: 1.08em;
}
.recipe-footer {
    margin-top: 18px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
@media (max-width: 600px) {
    .recipe-page-container {
        padding: 8px 2vw 16px 2vw;
    }
    .recipe-header h1 {
        font-size: 1.3em;
    }
    .recipe-meta {
        gap: 7px;
        font-size: 0.98em;
    }
    .recipe-description, .recipe-ingredients, .recipe-steps {
        padding: 10px 5px 8px 8px;
    }
}
</style>
<div class="recipe-page-container">
    <a class="btn" href="index.php">&larr; Retour à l'accueil</a>
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    <div class="recipe-card">
        <div class="recipe-header">
            <h1><?php echo htmlspecialchars($recipe['title']); ?></h1>
            <div class="recipe-meta">
                <span class="category"><strong>Catégorie :</strong> <?php echo htmlspecialchars($recipe['category']); ?></span>
                <?php
                $tags_stmt = $pdo->prepare('SELECT t.name FROM tags t JOIN recipe_tags rt ON t.id = rt.tag_id WHERE rt.recipe_id = ? ORDER BY t.name');
                $tags_stmt->execute([$id]);
                $tags = $tags_stmt->fetchAll(PDO::FETCH_COLUMN);
                if ($tags): ?>
                    <span class="tags" style="margin-left:18px;"><strong>Tags :</strong> <?php echo htmlspecialchars(implode(', ', $tags)); ?></span>
                <?php endif; ?>
                <span class="difficulty"><strong>Difficulté :</strong> <?php echo htmlspecialchars($recipe['difficulty']); ?></span>
                <span class="prep-time"><strong>Préparation :</strong> <?php echo htmlspecialchars($recipe['prep_time']); ?> min</span>
                <span class="cook-time"><strong>Cuisson :</strong> <?php echo htmlspecialchars($recipe['cook_time']); ?> min</span>
                <span class="author"><strong>Par :</strong> <?php echo htmlspecialchars($recipe['username']); ?></span>
            </div>
            <?php if ((isset($_SESSION['user_id']) && $_SESSION['user_id'] == $recipe['author_id']) || (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'])): ?>
                <a href="edit_recipe.php?id=<?php echo $id; ?>" class="btn btn-edit">✏️ Modifier la recette</a>
            <?php endif; ?>
        </div>
        <?php if ($media): ?>
        <div class="recipe-media">
            <?php foreach ($media as $item): ?>
                <?php if ($item['media_type'] === 'image'): ?>
                    <img src="<?php echo htmlspecialchars($item['file_path']); ?>" alt="Image de la recette" class="recipe-image">
                <?php elseif ($item['media_type'] === 'video'): ?>
                    <video controls class="recipe-video">
                        <source src="<?php echo htmlspecialchars($item['file_path']); ?>">
                        Votre navigateur ne supporte pas la vidéo.
                    </video>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="recipe-description">
            <h3>Description</h3>
            <p><?php echo nl2br(htmlspecialchars($recipe['description'])); ?></p>
        </div>
        <div class="recipe-ingredients">
            <h3>Ingrédients</h3>
            <ul>
                <?php
                $ing_stmt = $pdo->prepare('SELECT i.name, ri.quantity, u.name as unit_name FROM recipe_ingredients ri JOIN ingredients i ON ri.ingredient_id = i.id LEFT JOIN units u ON ri.unit_id = u.id WHERE ri.recipe_id = ?');
                $ing_stmt->execute([$id]);
                foreach ($ing_stmt as $ing): ?>
                    <li><?php echo htmlspecialchars($ing['name']); ?><?php if ($ing['quantity']) echo ' : ' . htmlspecialchars($ing['quantity']); ?><?php if ($ing['unit_name']) echo ' ' . htmlspecialchars($ing['unit_name']); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="recipe-steps">
            <h3>Étapes</h3>
            <ol>
                <?php
                // Essaye de lire les étapes depuis la table recipe_steps
                $step_stmt = $pdo->prepare('SELECT step_number, description FROM recipe_steps WHERE recipe_id = ? ORDER BY step_number');
                $step_stmt->execute([$id]);
                $steps = $step_stmt->fetchAll(PDO::FETCH_ASSOC);
                if (count($steps) > 0)<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db.php';

// Connexion à la base de données
$db = require __DIR__ . '/../config/db.php';
try {
    $pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Erreur de connexion à la base de données : ' . $e->getMessage());
}

// Compteur de vues par page
$page = basename($_SERVER['PHP_SELF']);
$stmt = $pdo->prepare('INSERT INTO page_views (page, views) VALUES (?, 1) ON DUPLICATE KEY UPDATE views = views + 1');
$stmt->execute([$page]);
$stmt = $pdo->prepare('SELECT views FROM page_views WHERE page = ?');
$stmt->execute([$page]);
$views = $stmt->fetchColumn();


// Récupérer les dernières recettes
$stmt = $pdo->query("SELECT r.*, u.username, c.name as category_name 
                     FROM recipes r 
                     LEFT JOIN users u ON r.user_id = u.id 
                     LEFT JOIN categories c ON r.category_id = c.id 
                     ORDER BY r.created_at DESC 
                     LIMIT 6");
$latest_recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Préparer le contenu de la page
$pageTitle = "Accueil - Gestion des Recettes";
$additionalCss = ["css/home.css"];
ob_start(); ?>



<?php session_start(); ?>
<body>
<?php if (!empty($_SESSION['success_message'])): ?>
    <script>
        setTimeout(function() { alert(<?php echo json_encode($_SESSION['success_message']); ?>); }, 100);
    </script>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<div class="welcome-section">
    <h1>Bienvenue sur l'application de gestion de recettes !</h1>
    <p class="welcome-text">Découvrez, partagez et organisez vos recettes préférées.</p>
    <p class="welcome-text"><strong>Nombre de visites pour cette page : <?php echo (int)$views; ?></strong></p>
    <a href="add_recipe.php" class="hero-btn">+ Ajouter une recette</a>
    <a href="admin/dashboard.php" class="hero-btn" style="background:#3e4a5e;margin-left:10px;">Espace Admin</a>
</div>

<section class="latest-recipes">
    <h2>Dernières Recettes</h2>
    <div class="recipes-grid">
        <?php foreach ($latest_recipes as $recipe): ?>
            <?php
            $stmt = $pdo->prepare("SELECT file_path FROM recipe_media WHERE recipe_id = ? AND media_type = 'image' LIMIT 1");
            $stmt->execute([$recipe['id']]);
            $image = $stmt->fetch(PDO::FETCH_COLUMN);
            ?>
            <div class="recipe-card">
                <div class="recipe-image">
                    <?php if ($image): ?>
                        <img src="<?php echo htmlspecialchars($image); ?>" alt="Image de la recette" class="recipe-img-fixed">
                    <?php else: ?>
                        <img src="https://img.icons8.com/fluency/180/000000/meal.png" alt="Image par défaut" class="recipe-img-fixed" style="opacity:0.6;">
                    <?php endif; ?>
                </div>
                <div class="recipe-content">
                    <h3><?php echo htmlspecialchars($recipe['title']); ?></h3>
                    <div class="recipe-meta">
                        <span class="badge-category"><?php echo htmlspecialchars($recipe['category_name']); ?></span>
                        <span class="author">par <?php echo htmlspecialchars($recipe['username']); ?></span>
                    </div>
                    <p class="recipe-description"><?php echo substr(htmlspecialchars($recipe['description']), 0, 100) . '...'; ?></p>
                    <a href="recipe.php?id=<?php echo $recipe['id']; ?>" class="view-recipe">Voir la recette</a>
                    <?php if ((isset($_SESSION['user_id']) && $_SESSION['user_id'] == $recipe['user_id']) || (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'])): ?>
                        <a href="edit_recipe.php?id=<?php echo $recipe['id']; ?>" class="btn btn-edit" style="margin-bottom:8px;">✏️ Modifier</a>
                        <a href="delete_recipe.php?id=<?php echo $recipe['id']; ?>" onclick="return confirm('Supprimer cette recette ?');" class="btn btn-delete" style="background:#c00;color:#fff;margin-left:8px;">🗑️ Supprimer</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="features">
    <h2>Fonctionnalités</h2>
    <div class="features-grid">
        <div class="feature-card">
            <img src="https://img.icons8.com/fluency/48/000000/add-file.png" alt="Ajouter">
            <h3>Ajoutez vos recettes</h3>
            <p>Partagez vos meilleures recettes avec la communauté.</p>
        </div>
        <div class="feature-card">
            <img src="https://img.icons8.com/fluency/48/000000/search.png" alt="Rechercher">
            <h3>Recherchez des recettes</h3>
            <p>Trouvez l'inspiration parmi nos recettes.</p>
        </div>
        <div class="feature-card">
            <img src="https://img.icons8.com/fluency/48/000000/shopping-cart.png" alt="Liste de courses">
            <h3>Liste de courses</h3>
            <p>Générez automatiquement votre liste de courses.</p>
        </div>
    </div>
</section>
<?php
$pageContent = ob_get_clean();
require 'templates/base.php';
?>
 {
                    // Affiche les étapes depuis la table recipe_steps
                    foreach ($steps as $step) {
                        echo '<li>' . htmlspecialchars($step['description']) . '</li>';
                    }
                } else {
                    // Sinon, affiche les étapes depuis la colonne steps de recipes
                    $raw_steps = $recipe['steps'];
                    $steps_array = preg_split('/\r\n|\r|\n/', $raw_steps);
                    foreach ($steps_array as $step) {
                        if (trim($step) !== '') {
                            echo '<li>' . htmlspecialchars($step) . '</li>';
                        }
                    }
                }
                ?>
            </ol>
        </div>
        <div class="recipe-footer">
            <a href="print_recipe.php?id=<?php echo $id; ?>" target="_blank" class="btn btn-secondary">🖨️ Imprimer</a>
            <a href="shopping_list.php?id=<?php echo $id; ?>" target="_blank" class="btn btn-secondary">🛒 Liste de courses</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="shopping_list.php?id=<?php echo $id; ?>&add=1" class="btn btn-primary">➕ Ajouter à ma liste de courses</a>
                <?php if ($is_favorite): ?>
                    <a href="recipe.php?id=<?php echo $id; ?>&fav=del" class="btn btn-secondary">★ Retirer des favoris</a>
                <?php else: ?>
                    <a href="recipe.php?id=<?php echo $id; ?>&fav=add" class="btn btn-secondary">☆ Ajouter aux favoris</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="recipe-comments">
        <h3>Commentaires</h3>
        <?php foreach ($comments as $comment): ?>
            <div class="comment">
                <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                <span class="comment-rating">Note : <?php echo intval($comment['rating']); ?>/5</span>
                <p><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
            </div>
        <?php endforeach; ?>
        <?php if (isset($_SESSION['user_id'])): ?>
            <h4>Laisser un commentaire</h4>
            <?php if ($commentMsg) echo '<div class="error">'.$commentMsg.'</div>'; ?>
            <form method="post">
                <textarea name="content" placeholder="Votre commentaire" required></textarea><br>
                <label>Note : <input type="number" name="rating" min="1" max="5" required></label><br>
                <button type="submit" class="btn">Envoyer</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php
$pageContent = ob_get_clean();
$additionalCss = ["css/style.css", "css/home.css"];
require 'templates/base.php';
