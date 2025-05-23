<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
$db = require __DIR__ . '/../config/db.php';

try {
    $pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Erreur de connexion à la base de données : ' . $e->getMessage());
}

// Initialiser les variables
$errors = [];
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$steps = trim($_POST['steps'] ?? '');
$category_id = $_POST['category_id'] ?? null;
$prep_time = intval($_POST['prep_time'] ?? 0);
$cook_time = intval($_POST['cook_time'] ?? 0);
$difficulty = $_POST['difficulty'] ?? 'Facile';
$ingredients_data = $_POST['ingredients_data'] ?? '';
$media_files = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validation
    if (empty($title)) $errors[] = 'Le titre est obligatoire';
    if (empty($ingredients_data)) $errors[] = 'Au moins un ingrédient est obligatoire';
    if (empty($steps)) $errors[] = 'Les étapes de préparation sont obligatoires';
    if (empty($category_id)) $errors[] = 'La catégorie est obligatoire';

    // Traitement des images
    if (!empty($_FILES['images']['name'][0])) {
        $targetDir = 'images/';
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $fileName = basename($_FILES['images']['name'][$key]);
            $targetFile = $targetDir . uniqid() . '_' . $fileName;
            $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            
            if (in_array($fileType, ['jpg', 'jpeg', 'png', 'gif']) && $_FILES['images']['size'][$key] < 4*1024*1024) {
                if (move_uploaded_file($tmp_name, $targetFile)) {
                    // Redimensionnement et conversion JPEG (max 800x800px)
                    list($width, $height, $type) = getimagesize($targetFile);
                    $maxDim = 800;
                    $ratio = min($maxDim / $width, $maxDim / $height, 1);
                    $new_width = (int)($width * $ratio);
                    $new_height = (int)($height * $ratio);
                    switch ($type) {
                        case IMAGETYPE_JPEG: $src_img = imagecreatefromjpeg($targetFile); break;
                        case IMAGETYPE_PNG:  $src_img = imagecreatefrompng($targetFile); break;
                        case IMAGETYPE_GIF:  $src_img = imagecreatefromgif($targetFile); break;
                        default: $src_img = null;
                    }
                    if ($src_img) {
                        $dst_img = imagecreatetruecolor($new_width, $new_height);
                        imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                        imagejpeg($dst_img, $targetFile, 85); // Ecrase le fichier par la version optimisée
                        imagedestroy($src_img);
                        imagedestroy($dst_img);
                    }
                    $media_files[] = ['type' => 'image', 'path' => $targetFile];
                } else {
                    $errors[] = "Erreur lors de l'upload de l'image " . $_FILES['images']['name'][$key];
                }
            } else {
                $errors[] = 'Format ou taille de fichier non valide pour ' . $_FILES['images']['name'][$key];
            }
        }
    }

    // Traitement des vidéos
    if (!empty($_FILES['videos']['name'][0])) {
        $targetDir = 'videos/';
        foreach ($_FILES['videos']['tmp_name'] as $key => $tmp_name) {
            $fileName = basename($_FILES['videos']['name'][$key]);
            $targetFile = $targetDir . uniqid() . '_' . $fileName;
            $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            
            if (in_array($fileType, ['mp4', 'mov', 'avi']) && $_FILES['videos']['size'][$key] < 100*1024*1024) {
                if (move_uploaded_file($tmp_name, $targetFile)) {
                    $media_files[] = ['type' => 'video', 'path' => $targetFile];
                } else {
                    $errors[] = "Erreur lors de l'upload de la vidéo " . $_FILES['videos']['name'][$key];
                }
            } else {
                $errors[] = 'Format ou taille de fichier non valide pour ' . $_FILES['videos']['name'][$key];
            }
        }
    }

    // Enregistrement si pas d'erreurs
    if (empty($errors)) {
        try {
            // Vérification de l'unicité du titre pour cet utilisateur
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM recipes WHERE user_id = ? AND title = ?");
            $stmt->execute([$_SESSION['user_id'], $title]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'Vous avez déjà une recette avec ce titre. Veuillez en choisir un autre.';
                throw new Exception('Doublon de titre');
            }

            $pdo->beginTransaction();
            // Insertion de la recette
            $stmt = $pdo->prepare("INSERT INTO recipes (user_id, title, description, ingredients, steps, category_id, prep_time, cook_time, difficulty) VALUES (?, ?, ?, '', ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'],
                $title,
                $description,
                $steps,
                $category_id,
                $prep_time,
                $cook_time,
                $difficulty
            ]);
            
            $recipe_id = $pdo->lastInsertId();
            // Astuce : pour une sécurité maximale, ajouter une contrainte UNIQUE(user_id, title) dans la base de données.

            // Insertion des médias
            if (!empty($media_files)) {
                $stmt = $pdo->prepare("INSERT INTO recipe_media (recipe_id, media_type, file_path) VALUES (?, ?, ?)");
                foreach ($media_files as $media) {
                    $stmt->execute([$recipe_id, $media['type'], $media['path']]);
                }
            }

            // Traitement des ingrédients
            $ingredients = json_decode($ingredients_data, true);
            if (is_array($ingredients)) {
                foreach ($ingredients as $ing) {
                    $name = trim($ing['name']);
                    $quantity = trim($ing['quantity']);
                    $unit = trim($ing['unit']);
                    
                    // Recherche ou création de l'ingrédient
                    $stmt = $pdo->prepare('SELECT id FROM ingredients WHERE name = ?');
                    $stmt->execute([$name]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($row) {
                        $ingredient_id = $row['id'];
                    } else {
                        $stmt = $pdo->prepare('INSERT INTO ingredients (name) VALUES (?)');
                        $stmt->execute([$name]);
                        $ingredient_id = $pdo->lastInsertId();
                    }
                    
                    // Association recette-ingrédient
                    $stmt = $pdo->prepare('INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity, unit) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$recipe_id, $ingredient_id, $quantity, $unit]);
                }
            }

            // Association des tags à la recette
            if (!empty($_POST['tags'])) {
                $tag_ids = array_filter(array_map('intval', explode(',', $_POST['tags'])));
                foreach ($tag_ids as $tag_id) {
                    $stmt = $pdo->prepare('INSERT INTO recipe_tags (recipe_id, tag_id) VALUES (?, ?)');
                    $stmt->execute([$recipe_id, $tag_id]);
                }
            }

            $pdo->commit();
            $_SESSION['success_message'] = 'Recette ajoutée avec succès!';
            header('Location: recipe.php?id=' . $recipe_id);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Erreur lors de l\'enregistrement : ' . $e->getMessage();
        }
    }
}

// Récupérer les catégories
$categories = $pdo->query('SELECT * FROM categories')->fetchAll(PDO::FETCH_ASSOC);
// Récupérer les tags
// Affichage d'une alerte JS sur succès
if (!empty($_SESSION['success_message'])) {
    echo '<div class="modal-success-bg" id="modal-success-bg">
      <div class="modal-success">
        <span class="modal-icon">✅</span>
        <h2>Succès</h2>
        <div>' . htmlspecialchars($_SESSION['success_message']) . '</div>
        <button class="modal-btn" onclick="document.getElementById(\'modal-success-bg\').style.display=\'none\';">Fermer</button>
      </div>
    </div>';
    echo '<script>setTimeout(function(){ document.getElementById(\'modal-success-bg\').style.display=\'none\'; }, 3500);</script>';
    unset($_SESSION['success_message']);
}

$all_tags = $pdo->query('SELECT * FROM tags ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une recette</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .ingredient-tag { display: inline-block; background: #2d7c7b; color: #fff; border-radius: 2em; padding: 0.2em 1em; margin: 0.2em; font-size: 0.95em; }
        .ingredient-tag .remove-tag { margin-left: 0.5em; cursor: pointer; color: #fff; font-weight: bold; }
        #ingredients-select-group { position: relative; margin-bottom: 1em; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <link rel="stylesheet" href="assets/css/recipe-highlight.css">
<link rel="stylesheet" href="assets/css/modal-success.css">
    <div class="container">
        <h1 class="recipe-title-highlight" id="dynamic-recipe-title">Créer une nouvelle recette</h1>
        <a href="import_recipe.php" class="btn btn-secondary" style="float:right;margin-bottom:10px;">Importer une recette depuis une URL</a>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <label for="tags-select">Tags (autocomplétion, plusieurs choix) :</label><br>
            <select id="tags-select" multiple>
                <?php foreach ($all_tags as $tag): ?>
                    <option value="<?php echo $tag['id']; ?>"><?php echo htmlspecialchars($tag['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="tags" id="tags-hidden">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
            <link rel="stylesheet" href="css/tags-autocomplete.css">
            <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const tagsSelect = document.getElementById('tags-select');
                const tagsHidden = document.getElementById('tags-hidden');
                const choices = new Choices(tagsSelect, {
                    removeItemButton: true,
                    placeholder: true,
                    placeholderValue: 'Choisir un ou plusieurs tags...',
                    searchPlaceholderValue: 'Rechercher un tag...',
                    noResultsText: 'Aucun tag trouvé',
                    itemSelectText: '',
                    shouldSort: false,
                    classNames: { containerOuter: 'choices tags-autocomplete' }
                });
                function updateTagsHidden() {
                    const selected = Array.from(tagsSelect.selectedOptions).map(opt => opt.value).join(',');
                    tagsHidden.value = selected;
                }
                tagsSelect.addEventListener('change', updateTagsHidden);
                updateTagsHidden();
            });
            </script>
            <br><br><br>
            <label for="title">Titre :</label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required oninput="updateRecipeTitle()"><br><br>
<script>
function updateRecipeTitle() {
    const input = document.getElementById('title');
    const display = document.getElementById('dynamic-recipe-title');
    if (input.value.trim()) {
        display.textContent = 'Nouvelle recette : ' + input.value.trim();
    } else {
        display.textContent = 'Créer une nouvelle recette';
    }
}
document.addEventListener('DOMContentLoaded', updateRecipeTitle);
</script>
            <textarea name="description" placeholder="Description"><?php echo htmlspecialchars($description); ?></textarea><br>
            
            <div id="ingredients-select-group">
                <select id="ingredients-select">
                    <option value="">Choisir un ingrédient</option>
                    <option value="__autre__">Autre...</option>
                    <?php
                    $all_ingredients = $pdo->query('SELECT name FROM ingredients ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($all_ingredients as $ing) {
                        echo '<option value="' . htmlspecialchars($ing) . '">' . htmlspecialchars($ing) . '</option>';
                    }
                    ?>
                </select>
                <input type="text" id="ingredient-other" placeholder="Nom de l'ingrédient" style="width:160px;display:none;">
                <input type="text" id="ingredient-quantity" placeholder="Quantité" style="width:90px;">
                <select id="ingredient-unit" style="width:180px;"></select>
                <script src="vendor/select2.min.js"></script>
                <script>
                $(document).ready(function() {
                    // Charger dynamiquement les unités depuis units.php
                    function fetchUnits(callback) {
                        $.getJSON('units.php', function(units) {
                            const select = $('#ingredient-unit');
                            select.empty();
                            select.append('<option value="">Unité</option>');
                            units.forEach(function(u) {
                                select.append('<option value="'+u.id+'">'+u.name+'</option>');
                            });
                            if (callback) callback();
                        });
                    }
                    fetchUnits(function() {
                        $('#ingredient-unit').select2({
                            placeholder: "Choisir une unité",
                            allowClear: true,
                            tags: true,
                            language: {
                                noResults: function(params) {
                                    return 'Aucune unité trouvée. Appuyez sur Entrée pour ajouter.';
                                }
                            }
                        });
                    });

                    // Ajout dynamique d'une unité
                    $('#ingredient-unit').on('select2:select', function(e) {
                        const data = e.params.data;
                        if (data.id && data._resultId && data._resultId.startsWith('select2-ingredient-unit-result-new-')) {
                            // Nouvelle unité à ajouter
                            $.post('add_unit.php', { name: data.text }, function(resp) {
                                if (resp && resp.id) {
                                    // Ajoute et sélectionne l'unité
                                    const newOption = new Option(resp.name, resp.id, true, true);
                                    $('#ingredient-unit').append(newOption).trigger('change');
                                }
                            }, 'json');
                        }
                    });
                });
                </script>
                    <option value="càc">cuillère à café</option>
                    <option value="pincée">pincée</option>
                    <option value="__autre__">autre...</option>
                </select>
                <input type="text" id="ingredient-unit-other" style="display:none;" placeholder="Autre unité...">
                <button type="button" id="add-ingredient">Ajouter</button>
                <div id="selected-ingredients"></div>
                <input type="hidden" name="ingredients_data" id="ingredients-hidden">
            </div>

            <textarea name="steps" placeholder="Étapes de préparation" required><?php echo htmlspecialchars($steps); ?></textarea><br>
            
            <select name="category_id" required>
                <option value="">Choisir une catégorie</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo ($category['id'] == $category_id ? 'selected' : ''); ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select><br>

            <label for="prep_time"><b>Temps de préparation (minutes)&nbsp;:</b></label>
            <input type="number" name="prep_time" id="prep_time" placeholder="en minutes" value="<?php echo $prep_time; ?>" min="0" step="1"><br>
            <label for="cook_time"><b>Temps de cuisson (minutes)&nbsp;:</b></label>
            <input type="number" name="cook_time" id="cook_time" placeholder="en minutes" value="<?php echo $cook_time; ?>" min="0" step="1"><br>
            
            <select name="difficulty">
                <option value="Facile" <?php echo ($difficulty === 'Facile' ? 'selected' : ''); ?>>Facile</option>
                <option value="Moyenne" <?php echo ($difficulty === 'Moyenne' ? 'selected' : ''); ?>>Moyenne</option>
                <option value="Difficile" <?php echo ($difficulty === 'Difficile' ? 'selected' : ''); ?>>Difficile</option>
            </select><br>

            <label>Images :</label><br>
            <input type="file" name="images[]" accept="image/*" multiple><br>
            
            <label>Vidéos :</label><br>
            <input type="file" name="videos[]" accept="video/*" multiple><br>
            <small>Formats acceptés : MP4, MOV, AVI (max 100Mo)</small><br>

            <button type="submit">Ajouter la recette</button>
        </form>
    </div>

    <script>
    const select = document.getElementById('ingredients-select');
    const other = document.getElementById('ingredient-other');
    const qty = document.getElementById('ingredient-quantity');
    const unit = document.getElementById('ingredient-unit');
    const unitOther = document.getElementById('ingredient-unit-other');
    const addBtn = document.getElementById('add-ingredient');
    const selectedDiv = document.getElementById('selected-ingredients');
    const hidden = document.getElementById('ingredients-hidden');
    let selected = [];

    select.addEventListener('change', function() {
        if (select.value === '__autre__') {
            other.style.display = '';
            other.focus();
        } else {
            other.style.display = 'none';
        }
    });

    unit.addEventListener('change', function() {
        if (unit.value === '__autre__') {
            unitOther.style.display = 'inline-block';
            unitOther.focus();
        } else {
            unitOther.style.display = 'none';
        }
    });

    addBtn.onclick = function() {
        let name = select.value === '__autre__' ? other.value.trim() : select.value;
        if (!name) return;
        selected.push({
            name: name,
            quantity: qty.value,
            unit: unit.value === '__autre__' ? unitOther.value : unit.value
        });
        renderTags();
        qty.value = '';
        unit.value = '';
        unitOther.value = '';
        unitOther.style.display = 'none';
        if (select.value === '__autre__') {
            other.value = '';
            other.style.display = 'none';
        }
        select.value = '';
    };

    function renderTags() {
        selectedDiv.innerHTML = '';
        selected.forEach((ing, idx) => {
            const tag = document.createElement('span');
            tag.className = 'ingredient-tag';
            tag.textContent = `${ing.name} (${ing.quantity||''} ${ing.unit||''})`;
            
            const rm = document.createElement('span');
            rm.className = 'remove-tag';
            rm.textContent = '×';
            rm.onclick = () => {
                selected.splice(idx, 1);
                renderTags();
            };
            
            tag.appendChild(rm);
            selectedDiv.appendChild(tag);
        });
        updateHidden();
    }

    function updateHidden() {
        hidden.value = JSON.stringify(selected);
    }

    // Navigation clavier
    select.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') qty.focus();
    });
    
    qty.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') unit.focus();
    });
    
    unit.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            if (unit.value === '__autre__') {
                unitOther.focus();
            } else {
                addBtn.click();
            }
        }
    });
    
    unitOther.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') addBtn.click();
    });

    // Initialisation
    const prevData = hidden.value;
    if (prevData) {
        try {
            selected = JSON.parse(prevData);
            renderTags();
        } catch(e) {
            selected = [];
        }
    }
    // Correction : toujours mettre à jour le champ caché avant soumission
    document.querySelector('form').addEventListener('submit', function() {
        updateHidden();
    });
    </script>
</body>
</html>
