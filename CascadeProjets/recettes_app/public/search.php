<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connexion à la base de données
$db = require __DIR__ . '/../config/db.php';

try {
    $dsn = "mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}";
    $pdo = new PDO($dsn, $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Erreur de connexion à la base de données : ' . $e->getMessage();
    $pdo = null;
}

// Compteur de vues par page
if ($pdo) {
    $page = basename($_SERVER['PHP_SELF']);
    $stmt = $pdo->prepare('INSERT INTO page_views (page, views) VALUES (?, 1) ON DUPLICATE KEY UPDATE views = views + 1');
    $stmt->execute([$page]);
    $stmt = $pdo->prepare('SELECT views FROM page_views WHERE page = ?');
    $stmt->execute([$page]);
    $views = $stmt->fetchColumn();
} else {
    $views = 0;
}


// Récupérer les catégories et ingrédients pour le filtre
$categories = [];
$ingredients = [];
if ($pdo) {
    try {
        $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$ingredients = $pdo->query("SELECT name FROM ingredients ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
$all_tags = $pdo->query("SELECT * FROM tags ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Erreur lors de la récupération des catégories ou ingrédients : " . $e->getMessage();
    }
}

// Récupérer les filtres
$search = $_GET['q'] ?? '';
$category = $_GET['category'] ?? '';
$difficulty = $_GET['difficulty'] ?? '';
$selected_ingredients = isset($_GET['ingredients']) ? (array)$_GET['ingredients'] : [];
$selected_tags = isset($_GET['tags']) ? (array)$_GET['tags'] : [];

// Construire la requête de recherche
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(r.title LIKE ? OR r.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category)) {
    $where[] = "r.category_id = ?";
    $params[] = $category;
}

if (!empty($difficulty)) {
    $where[] = "r.difficulty = ?";
    $params[] = $difficulty;
}

// Recherche multi-ingrédients : la recette doit contenir TOUS les ingrédients sélectionnés
if (!empty($selected_ingredients)) {
    foreach ($selected_ingredients as $ing) {
        $where[] = "EXISTS (
            SELECT 1 FROM recipe_ingredients ri
            JOIN ingredients i ON ri.ingredient_id = i.id
            WHERE ri.recipe_id = r.id AND i.name = ?
        )";
        $params[] = $ing;
    }
}
// Recherche multi-tags : la recette doit contenir TOUS les tags sélectionnés
if (!empty($selected_tags)) {
    foreach ($selected_tags as $tag_id) {
        $where[] = "EXISTS (
            SELECT 1 FROM recipe_tags rt
            WHERE rt.recipe_id = r.id AND rt.tag_id = ?
        )";
        $params[] = $tag_id;
    }
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$recipes = [];
if ($pdo) {
    $query = "SELECT DISTINCT r.*, u.username, c.name as category_name 
              FROM recipes r 
              LEFT JOIN users u ON r.user_id = u.id 
              LEFT JOIN categories c ON r.category_id = c.id 
              $whereClause 
              ORDER BY r.created_at DESC";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Erreur lors de la recherche : " . $e->getMessage();
    }
}

$pageTitle = "Recherche de recettes";
$additionalCss = ["css/search.css"];
ob_start(); 
?>
<div class="search-section">
    <h1>Recherche de recettes</h1>
<p class="welcome-text"><strong>Nombre de visites pour cette page : <?php echo (int)$views; ?></strong></p>
    <form method="GET" class="search-form" autocomplete="off">
        <div class="search-inputs">
            <input type="text" name="q" placeholder="Rechercher une recette..." value="<?php echo htmlspecialchars($search); ?>" class="search-input">
            <div class="ingredients-select-group" style="min-width:220px;">
                <label for="ingredients-select">Ingrédients</label>
                <select name="ingredients[]" id="ingredients-select" multiple size="8" class="multi-ingredient-select">
                    <?php foreach ($ingredients as $ing): ?>
                        <option value="<?php echo htmlspecialchars($ing); ?>" <?php echo in_array($ing, $selected_ingredients) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ing); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button id="toggle-ingredients-btn" type="button" class="toggle-ingredients-btn">Afficher uniquement la sélection</button>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggle-ingredients-btn');
    const select = document.getElementById('ingredients-select');
    if (toggleBtn && select) {
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const selected = Array.from(select.selectedOptions).map(option => option.value);
            select.querySelectorAll('option').forEach(option => {
                if (selected.includes(option.value)) {
                    option.selected = true;
                } else {
                    option.selected = false;
                }
            });
        });
    }
});
</script>

<!-- Sélecteur de tags (Choices.js) -->
<div class="tags-select-group" style="min-width:240px;">
    <label for="tags-select">Tags</label>
    <select name="tags[]" id="tags-select" class="tags-select" multiple>
        <?php foreach ($all_tags as $tag): ?>
            <option value="<?php echo $tag['id']; ?>" <?php if (!empty($_GET['tags']) && in_array($tag['id'], (array)$_GET['tags'])) echo 'selected'; ?>><?php echo htmlspecialchars($tag['name']); ?></option>
        <?php endforeach; ?>
    </select>
</div>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>document.addEventListener('DOMContentLoaded',function(){
    if(window.Choices){
        new Choices(document.getElementById('tags-select'),{
            removeItemButton:true,
            placeholder:true,
            placeholderValue:'Filtrer par tags...',
            searchPlaceholderValue:'Rechercher un tag...',
            noResultsText:'Aucun tag trouvé',
            itemSelectText:'',
            shouldSort:false
        });
    }
});</script>
            <select name="category" class="category-select">
                <option value="">Toutes les catégories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo ($category == $cat['id'] ? 'selected' : ''); ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="difficulty" class="difficulty-select">
                <option value="">Toutes les difficultés</option>
                <option value="Facile" <?php echo ($difficulty === 'Facile' ? 'selected' : ''); ?>>Facile</option>
                <option value="Moyenne" <?php echo ($difficulty === 'Moyenne' ? 'selected' : ''); ?>>Moyenne</option>
                <option value="Difficile" <?php echo ($difficulty === 'Difficile' ? 'selected' : ''); ?>>Difficile</option>
            </select>
            <button type="submit" class="search-button">Rechercher</button>
        </div>
        <div class="multi-ingredient-help">(Maintenez Ctrl ou Cmd pour sélectionner plusieurs ingrédients)</div>
    </form>
    <script src="js/select-ingredients-toggle.js"></script>
</div>

<div class="search-results">
    <?php if (!empty($search) || !empty($category) || !empty($difficulty) || !empty($selected_ingredients)): ?>
        <h2><?php echo count($recipes); ?> résultat(s) trouvé(s)</h2>
    <?php endif; ?>
    <form id="shopping-list-form" method="get" action="shopping_list.php" onsubmit="return handleShoppingListSubmit();">
    <div class="recipes-grid">
        <?php foreach ($recipes as $recipe): ?>
            <div class="recipe-card">
                <label class="select-recipe-checkbox" style="position:absolute;z-index:2;margin:10px;">
                    <input type="checkbox" name="id[]" value="<?php echo $recipe['id']; ?>">
                </label>
                <?php
                try {
                    $stmt = $pdo->prepare("SELECT file_path FROM recipe_media WHERE recipe_id = ? AND media_type = 'image' LIMIT 1");
                    $stmt->execute([$recipe['id']]);
                    $image = $stmt->fetch(PDO::FETCH_COLUMN);
                } catch (PDOException $e) {
                    $image = false;
                }
                ?>
                <div class="recipe-image">
                    <?php if ($image): ?>
                        <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($recipe['title']); ?>">
                    <?php else: ?>
                        <img src="images/default-recipe.jpg" alt="Image par défaut">
                    <?php endif; ?>
                </div>
                <div class="recipe-content">
                    <h3><?php echo htmlspecialchars($recipe['title']); ?></h3>
                     <div class="recipe-meta">
                        <span class="category"><?php echo htmlspecialchars($recipe['category_name']); ?></span>
                        <span class="difficulty"><?php echo htmlspecialchars($recipe['difficulty']); ?></span>
                        <?php
                        // Affichage des tags associés à la recette
                        $tags_stmt = $pdo->prepare('SELECT t.name FROM tags t JOIN recipe_tags rt ON t.id = rt.tag_id WHERE rt.recipe_id = ? ORDER BY t.name');
                        $tags_stmt->execute([$recipe['id']]);
                        $tags = $tags_stmt->fetchAll(PDO::FETCH_COLUMN);
                        if ($tags): ?>
                            <span class="tags" style="margin-left:12px;">
                                <?php foreach ($tags as $tag): ?>
                                    <span style="display:inline-block;background:#2c7c7b;color:#fff;border-radius:12px;padding:2px 12px;margin:1px 2px;font-size:0.93em;line-height:1.5;">
                                        <?php echo htmlspecialchars($tag); ?>
                                    </span>
                                <?php endforeach; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <p class="recipe-description"><?php echo substr(htmlspecialchars($recipe['description']), 0, 100) . '...'; ?></p>
                    <div class="recipe-footer">
                        <span class="author">par <?php echo htmlspecialchars($recipe['username']); ?></span>
                        <a href="recipe.php?id=<?php echo $recipe['id']; ?>" class="view-recipe">Voir la recette</a>
<?php if ((isset($_SESSION['user_id']) && $_SESSION['user_id'] == $recipe['user_id']) || (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'])): ?>
    <a href="edit_recipe.php?id=<?php echo $recipe['id']; ?>" class="btn btn-edit" style="margin-bottom:8px;">✏️ Modifier</a>
    <a href="delete_recipe.php?id=<?php echo $recipe['id']; ?>" onclick="return confirm('Supprimer cette recette ?');" class="btn btn-delete" style="background:#c00;color:#fff;margin-left:8px;">🗑️ Supprimer</a>
<?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="submit" class="btn" style="margin:28px auto 0 auto;display:block;">Générer la liste de courses</button>
</form>
<script>
// Persistance des cases à cocher via localStorage
const CHECKBOX_KEY = 'recette_selectionnees';
const checkboxes = document.querySelectorAll('input[name="id[]"]');

// Restaurer la sélection au chargement
window.addEventListener('DOMContentLoaded', function() {
    const saved = localStorage.getItem(CHECKBOX_KEY);
    if (saved) {
        try {
            const ids = JSON.parse(saved);
            checkboxes.forEach(cb => {
                if (ids.includes(cb.value)) cb.checked = true;
            });
        } catch(e) {}
    }
});
// Sauvegarder la sélection à chaque changement
checkboxes.forEach(cb => {
    cb.addEventListener('change', function() {
        const selected = Array.from(checkboxes).filter(c => c.checked).map(c => c.value);
        localStorage.setItem(CHECKBOX_KEY, JSON.stringify(selected));
    });
});

function handleShoppingListSubmit() {
    const checked = Array.from(checkboxes).filter(cb => cb.checked);
    if (checked.length === 0) {
        alert('Veuillez sélectionner au moins une recette pour générer la liste de courses.');
        return false;
    }
    const ids = checked.map(cb => cb.value).join(',');
    window.location.href = 'shopping_list.php?id=' + ids;
    return false;
}
</script>
</div>
<?php
$pageContent = ob_get_clean();
require 'templates/base.php';
