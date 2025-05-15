<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/../config/db.php';
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: my_recipes.php'); exit;
}
$db = require __DIR__ . '/../config/db.php';
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Affiche les erreurs SQL
$id = intval($_GET['id']);
$stmt = $pdo->prepare('SELECT * FROM recipes WHERE id = ?');
$stmt->execute([$id]);
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$recipe) { echo '<h2>Recette introuvable.</h2>'; exit; }
if (!isset($_SESSION['user_id']) || ($_SESSION['user_id'] != $recipe['user_id'] && (empty($_SESSION['is_admin']) || !$_SESSION['is_admin']))) {
    echo '<h2>Accès refusé.</h2>';
    exit;
}
$errors = [];

// Récupérer les médias existants
// Suppression d'un média si demandé
if (isset($_GET['delete_media']) && is_numeric($_GET['delete_media'])) {
    $media_id = intval($_GET['delete_media']);
    $del_stmt = $pdo->prepare('SELECT file_path FROM recipe_media WHERE id = ? AND recipe_id = ?');
    $del_stmt->execute([$media_id, $id]);
    $file = $del_stmt->fetchColumn();
    if ($file) {
        @unlink($file); // Supprime le fichier du disque
        $pdo->prepare('DELETE FROM recipe_media WHERE id = ? AND recipe_id = ?')->execute([$media_id, $id]);
    }
    header('Location: edit_recipe.php?id=' . $id);
    exit;
}
$media_stmt = $pdo->prepare('SELECT * FROM recipe_media WHERE recipe_id = ? ORDER BY created_at');
$media_stmt->execute([$id]);
$media = $media_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // DEBUG : log du POST pour analyse
    file_put_contents('/tmp/debug_edit_recipe.txt', print_r($_POST, true));
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $ingredients_json = $_POST['ingredients'] ?? '[]';
$ingredients = json_decode($ingredients_json, true);
if (!is_array($ingredients)) {
    // Compatibilité ancien format : split lines
    $ingredients = array_map(function($l) {
        $parts = explode(':', $l);
        return [
            'id' => '',
            'name' => isset($parts[0]) ? trim($parts[0]) : '',
            'quantity' => isset($parts[1]) ? trim(explode(' ', $parts[1])[1] ?? '') : '',
            'unit' => isset($parts[1]) ? trim(explode(' ', $parts[1])[2] ?? '') : ''
        ];
    }, preg_split('/\r?\n/', trim($_POST['ingredients'])));
}
    $steps = trim($_POST['steps'] ?? '');
    $category_id = $_POST['category_id'] ?? null;
    $prep_time = intval($_POST['prep_time'] ?? 0);
    $cook_time = intval($_POST['cook_time'] ?? 0);
    $difficulty = $_POST['difficulty'] ?? 'Facile';

    if (!$title || !$ingredients || !$steps) {
        $errors[] = 'Titre, ingrédients et étapes sont obligatoires.';
    }

    // Upload images
    if (!empty($_FILES['images']['name'][0])) {
        $allowed = ['jpg','jpeg','png','gif'];
        $targetDir = 'images/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        foreach ($_FILES['images']['name'] as $idx => $name) {
            if (!$_FILES['images']['error'][$idx]) {
                $fileType = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (in_array($fileType, $allowed) && $_FILES['images']['size'][$idx] < 4*1024*1024) {
                    $fileName = uniqid() . '_' . basename($name);
                    $targetFile = $targetDir . $fileName;
                    if (move_uploaded_file($_FILES['images']['tmp_name'][$idx], $targetFile)) {
                        $stmt = $pdo->prepare("INSERT INTO recipe_media (recipe_id, file_path, media_type) VALUES (?, ?, 'image')");
                        $stmt->execute([$id, $targetFile]);
                        $success = true;
                    } else {
                        $errors[] = "Erreur upload image : $name";
                    }
                } else {
                    $errors[] = "Image $name : format ou taille non valide (max 4Mo).";
                }
            }
        }
    }
    // Upload videos
    if (!empty($_FILES['videos']['name'][0])) {
        $allowed = ['mp4','webm','ogg','mpg'];
        $targetDir = 'videos/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        foreach ($_FILES['videos']['name'] as $idx => $name) {
            $error = $_FILES['videos']['error'][$idx];
            $size = $_FILES['videos']['size'][$idx];
            $fileType = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if ($error === UPLOAD_ERR_OK) {
                if (in_array($fileType, $allowed) && $size < 30*1024*1024) {
                    $fileName = uniqid() . '_' . basename($name);
                    $targetFile = $targetDir . $fileName;
                    if (move_uploaded_file($_FILES['videos']['tmp_name'][$idx], $targetFile)) {
                        $stmt = $pdo->prepare("INSERT INTO recipe_media (recipe_id, file_path, media_type) VALUES (?, ?, 'video')");
                        $stmt->execute([$id, $targetFile]);
                        $success = true;
                    } else {
                        $errors[] = "Erreur PHP lors de l'upload de la vidéo '$name' (move_uploaded_file).";
                    }
                } else {
                    $errors[] = "Vidéo '$name' : extension ($fileType) non autorisée ou taille ($size octets) > 30Mo.";
                }
            } else {
                $phpFileUploadErrors = array(
                    0 => 'Aucun problème, upload réussi',
                    1 => 'Le fichier dépasse la taille max upload_max_filesize',
                    2 => 'Le fichier dépasse la taille max post_max_size',
                    3 => 'Upload partiel',
                    4 => 'Aucun fichier',
                    6 => 'Dossier temporaire manquant',
                    7 => 'Échec d’écriture sur le disque',
                    8 => 'Upload stoppé par extension PHP'
                );
                $errMsg = isset($phpFileUploadErrors[$error]) ? $phpFileUploadErrors[$error] : 'Erreur inconnue';
                $errors[] = "Erreur upload vidéo '$name' (code $error) : $errMsg.";
            }
        }
    }

    if (empty($errors)) {
    $_SESSION['success_message'] = "Recette modifiée avec succès !";
        // Correction : autoriser la modification par admin
        if (!empty($_SESSION['is_admin']) && $_SESSION['is_admin']) {
            $stmt = $pdo->prepare("UPDATE recipes SET title=?, description=?, ingredients=?, steps=?, category_id=?, prep_time=?, cook_time=?, difficulty=? WHERE id=?");
            $stmt->execute([$title, $description, $ingredients, $steps, $category_id, $prep_time, $cook_time, $difficulty, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE recipes SET title=?, description=?, ingredients=?, steps=?, category_id=?, prep_time=?, cook_time=?, difficulty=? WHERE id=? AND user_id=?");
            $stmt->execute([$title, $description, $ingredients, $steps, $category_id, $prep_time, $cook_time, $difficulty, $id, $_SESSION['user_id']]);
        }

        // --- Synchronisation des tags dans recipe_tags ---
        $pdo->prepare('DELETE FROM recipe_tags WHERE recipe_id = ?')->execute([$id]);
        if (!empty($_POST['tags'])) {
            $tag_ids = array_filter(array_map('intval', explode(',', $_POST['tags'])));
            foreach ($tag_ids as $tag_id) {
                $pdo->prepare('INSERT INTO recipe_tags (recipe_id, tag_id) VALUES (?, ?)')->execute([$id, $tag_id]);
            }
        }
        // --- Synchronisation des ingrédients dans recipe_ingredients ---
        $stmt = $pdo->prepare("DELETE FROM recipe_ingredients WHERE recipe_id = ?");
        $stmt->execute([$id]);

        if (!empty($ingredients)) {
            // Nouveau format JSON : tableau d'objets {id, name, quantity, unit}
            if (is_array($ingredients) && isset($ingredients[0]['id'])) {
                foreach ($ingredients as $ing) {
                    $ingredient_id = $ing['id'];
                    $name = $ing['name'];
                    $quantity = $ing['quantity'];
                    $unit = $ing['unit'];
                    // Si pas d'id (cas "autre" ou ancien), chercher/créer par nom
                    if (!$ingredient_id) {
                        $stmt = $pdo->prepare('SELECT id FROM ingredients WHERE name = ?');
                        $stmt->execute([$name]);
                        $ingredient_id = $stmt->fetchColumn();
                        if (!$ingredient_id) {
                            $stmt = $pdo->prepare('INSERT INTO ingredients (name) VALUES (?)');
                            $stmt->execute([$name]);
                            $ingredient_id = $pdo->lastInsertId();
                        }
                    }
                    // Recherche de l'id de l'unité si renseignée
                    $unit_id = null;
                    if ($unit !== '') {
                        if (ctype_digit($unit)) {
                            $unit_id = (int)$unit;
                        } else {
                            $stmtu = $pdo->prepare('SELECT id FROM units WHERE name = ?');
                            $stmtu->execute([$unit]);
                            $unit_id = $stmtu->fetchColumn();
                            if (!$unit_id && $unit !== '') {
                                $stmtu = $pdo->prepare('INSERT INTO units (name) VALUES (?)');
                                $stmtu->execute([$unit]);
                                $unit_id = $pdo->lastInsertId();
                            }
                        }
                    }
                    $stmt = $pdo->prepare('INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity, unit_id) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$id, $ingredient_id, $quantity, $unit_id]);
                }
            } else {
                // Ancien format texte (compatibilité)
                $lines = explode("\n", $ingredients);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!$line) continue;
                    if (preg_match('/^(.+?)\s*:\s*([\d.,]+)?\s*(.*)$/u', $line, $m)) {
                        $name = trim($m[1]);
                        $quantity = isset($m[2]) ? trim($m[2]) : '';
                        $unit = isset($m[3]) ? trim($m[3]) : '';
                    } else {
                        $name = $line;
                        $quantity = '';
                        $unit = '';
                    }
                    $stmt = $pdo->prepare('SELECT id FROM ingredients WHERE name = ?');
                    $stmt->execute([$name]);
                    $ingredient_id = $stmt->fetchColumn();
                    if (!$ingredient_id) {
                        $stmt = $pdo->prepare('INSERT INTO ingredients (name) VALUES (?)');
                        $stmt->execute([$name]);
                        $ingredient_id = $pdo->lastInsertId();
                    }
                    $unit_id = null;
                    if ($unit !== '') {
                        if (ctype_digit($unit)) {
                            $unit_id = (int)$unit;
                        } else {
                            $stmtu = $pdo->prepare('SELECT id FROM units WHERE name = ?');
                            $stmtu->execute([$unit]);
                            $unit_id = $stmtu->fetchColumn();
                            if (!$unit_id && $unit !== '') {
                                $stmtu = $pdo->prepare('INSERT INTO units (name) VALUES (?)');
                                $stmtu->execute([$unit]);
                                $unit_id = $pdo->lastInsertId();
                            }
                        }
                    }
                    $stmt = $pdo->prepare('INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity, unit_id) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$id, $ingredient_id, $quantity, $unit_id]);
                }
            }
        }

        // --- Synchronisation des étapes dans recipe_steps ---
        $stmt = $pdo->prepare("DELETE FROM recipe_steps WHERE recipe_id = ?");
        $stmt->execute([$id]);

        if (!empty($steps)) {
            // On suppose que chaque étape est sur une ligne séparée
            $lines = explode("\n", $steps);
            $num = 1;
            foreach ($lines as $line) {
                $line = trim($line);
                if (!$line) continue;
                $stmt = $pdo->prepare('INSERT INTO recipe_steps (recipe_id, step_number, description) VALUES (?, ?, ?)');
                $stmt->execute([$id, $num++, $line]);
            }
        }

        if (!empty($success)) {
            $_SESSION['success_message'] = "Médias ajoutés avec succès.";
        }
        header('Location: edit_recipe.php?id='.$id); exit;
    }
}
$categories = $pdo->query('SELECT * FROM categories')->fetchAll(PDO::FETCH_ASSOC);
// Gestion des tags
$all_tags = $pdo->query('SELECT * FROM tags ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$selected_tags = $pdo->prepare('SELECT tag_id FROM recipe_tags WHERE recipe_id = ?');
$selected_tags->execute([$id]);
$selected_tags = $selected_tags->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Modifier la recette</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="assets/css/recipe-highlight.css">
<link rel="stylesheet" href="assets/css/modal-success.css">
</head>
<body>
<?php if (!empty($_SESSION['success_message'])): ?>
<div class="modal-success-bg" id="modal-success-bg">
  <div class="modal-success">
    <span class="modal-icon">✅</span>
    <h2>Succès</h2>
    <div><?php echo htmlspecialchars($_SESSION['success_message']); ?></div>
    <button class="modal-btn" onclick="document.getElementById('modal-success-bg').style.display='none';">Fermer</button>
  </div>
</div>
<script>setTimeout(function(){ document.getElementById('modal-success-bg').style.display='none'; }, 3500);</script>
<?php unset($_SESSION['success_message']); endif; ?>
<a class="btn" href="index.php" style="margin:16px 0 8px 0;display:inline-block;">&larr; Retour à l'accueil</a>
<?php include 'navbar.php'; ?>
<div class="container">
    <h1 class="recipe-title-highlight">Modifier la recette : <?php echo htmlspecialchars($recipe['title']); ?></h1>
    <?php if (!empty($errors)) : ?>
        <div class="error"><ul><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <label for="tags-select">Tags (autocomplétion, plusieurs choix) :</label><br>
        <select id="tags-select" multiple>
            <?php foreach ($all_tags as $tag): ?>
                <option value="<?php echo $tag['id']; ?>"<?php echo in_array($tag['id'], $selected_tags) ? ' selected' : ''; ?>><?php echo htmlspecialchars($tag['name']); ?></option>
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
        <br><br>
        <label for="title">Titre de la recette :</label><br>
        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($recipe['title']); ?>" required><br><br>

        <label for="description">Description :</label><br>
        <textarea id="description" name="description"><?php echo htmlspecialchars($recipe['description']); ?></textarea><br><br>

        <label>Ingrédients :</label><br>
<!-- Select2 CSS -->
<link href="vendor/select2.min.css" rel="stylesheet">
<div id="ingredients-group">
    <select id="ingredient-select" style="width:240px;">
        <option value="">Choisir un ingrédient</option>
        <option value="__autre__">Autre...</option>
        <?php
        $all_ingredients = $pdo->query('SELECT id, name FROM ingredients ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($all_ingredients as $ing) {
            echo '<option value="' . htmlspecialchars($ing['id']) . '">' . htmlspecialchars($ing['name']) . '</option>';
        }
        ?>
    </select>
    <input type="text" id="ingredient-other" placeholder="Nom de l\'ingrédient" style="width:160px;display:none;">
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
    <button type="button" id="add-ingredient">Ajouter</button>
    <!-- Select2 JS -->
    <script src="vendor/select2.min.js"></script>
    <script>
    // Initialiser Select2 avec recherche dynamique
    $(document).ready(function() {
        $('#ingredient-select').select2({
            placeholder: "Choisir un ingrédient",
            allowClear: true,
            width: 'resolve',
            language: {
                noResults: function() { return "Aucun ingrédient trouvé"; }
            }
        });
        // Afficher champ texte si "Autre..." sélectionné
        $('#ingredient-select').on('change', function() {
            if ($(this).val() === "__autre__") {
                $('#ingredient-other').show().focus();
            } else {
                $('#ingredient-other').hide();
            }
        });
        // Ajout dynamique d'un nouvel ingrédient via AJAX
        $('#ingredient-other').on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const name = $(this).val().trim();
                if (!name) {
                    alert('Veuillez saisir un nom d\'ingrédient.');
                    return;
                }
                $.post('add_ingredient.php', { name: name }, function(data) {
                    if (data && data.id) {
                        // Ajouter l'option dans Select2 et sélectionner
                        if ($('#ingredient-select option[value="'+data.id+'"').length === 0) {
                            const newOption = new Option(data.name, data.id, true, true);
                            $('#ingredient-select').append(newOption).trigger('change');
                        } else {
                            $('#ingredient-select').val(data.id).trigger('change');
                        }
                        $('#ingredient-other').val('').hide();
                        $('#ingredient-select').select2('close');
                    } else if (data && data.error) {
                        alert(data.error);
                    } else {
                        alert('Erreur lors de l\'ajout de l\'ingrédient.');
                    }
                }, 'json').fail(function(xhr) {
                    alert('Erreur serveur : ' + (xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'inconnue'));
                });
            }
        });
    });
    </script>
    <script>
    // Charger dynamiquement les unités depuis units.php
    fetch('units.php')
        .then(r => r.json())
        .then(units => {
            const select = document.getElementById('ingredient-unit');
            units.forEach(u => {
                const opt = document.createElement('option');
                opt.value = u.id;
                opt.textContent = u.name;
                select.insertBefore(opt, select.querySelector('option[value="__autre__"]'));
            });
        });
    // Afficher le champ texte si "Autre..." est choisi
    const unitSelect = document.getElementById('ingredient-unit');
    const unitOther = document.getElementById('ingredient-unit-other');
    unitSelect.addEventListener('change', function() {
        if (unitSelect.value === "__autre__") {
            unitOther.style.display = '';
            unitOther.focus();
        } else {
            unitOther.style.display = 'none';
        }
    });
    </script>
    <ul id="ingredient-list"></ul>
    <?php
// Reconstruire la liste des ingrédients à partir de recipe_ingredients
$ingredients_arr = [];
$stmt = $pdo->prepare('SELECT ri.ingredient_id as id, i.name, ri.quantity, u.name as unit
    FROM recipe_ingredients ri
    JOIN ingredients i ON ri.ingredient_id = i.id
    LEFT JOIN units u ON ri.unit_id = u.id
    WHERE ri.recipe_id = ?');
$stmt->execute([$id]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $ingredients_arr[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'quantity' => $row['quantity'],
        'unit' => $row['unit'] ?? ''
    ];
}
$ingredients_value = json_encode($ingredients_arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<input type="hidden" name="ingredients" id="ingredients-hidden" value="<?php echo htmlspecialchars($ingredients_value); ?>">
</div>
<script>
// Ajout d'un ingrédient à la liste
const select = document.getElementById('ingredient-select');
const other = document.getElementById('ingredient-other');
const qty = document.getElementById('ingredient-quantity');
const unit = document.getElementById('ingredient-unit');
const addBtn = document.getElementById('add-ingredient');
const list = document.getElementById('ingredient-list');
const hidden = document.getElementById('ingredients-hidden');

select.addEventListener('change', function() {
    if (select.value === '__autre__') {
        other.style.display = '';
        other.focus();
    } else {
        other.style.display = 'none';
    }
});

// Initialiser avec les ingrédients existants (JSON ou ancien format)
let ingredientsArr = [];
try {
    ingredientsArr = hidden.value.trim() ? JSON.parse(hidden.value) : [];
} catch(e) {
    // Ancien format : compatibilité descendante
    ingredientsArr = hidden.value.trim() ? hidden.value.trim().split('\n').map(l => {
        let parts = l.split(':');
        return {
            id: '',
            name: parts[0] ? parts[0].trim() : '',
            quantity: parts[1] ? parts[1].split(' ')[1] || '' : '',
            unit: parts[1] ? parts[1].split(' ')[2] || '' : ''
        };
    }) : [];
}
function updateList() {
    list.innerHTML = '';
    ingredientsArr.forEach((ing, idx) => {
        const li = document.createElement('li');
        li.textContent = (ing.name ? ing.name : '[Ingrédient #' + (ing.id || '?') + ']') + (ing.quantity ? ' : ' + ing.quantity : '') + (ing.unit ? ' ' + ing.unit : '');
        const del = document.createElement('button');
        del.textContent = '✗';
        del.style.marginLeft = '10px';
        del.onclick = function() {
            ingredientsArr.splice(idx, 1);
            updateList();
            updateHidden();
        };
        li.appendChild(del);
        list.appendChild(li);
    });
    updateHidden();
}
function updateHidden() {
    hidden.value = JSON.stringify(ingredientsArr);
}

addBtn.onclick = function() {
    let id = select.value;
    let name = select.value === '__autre__' ? other.value.trim() : select.options[select.selectedIndex].text;
    if (select.value === '__autre__' && !name) return;
    if (!id && !name) return;
    let unitVal = unit.value;
    if (unitVal === "__autre__") unitVal = unitOther.value.trim();
    ingredientsArr.push({ id: id, name: name, quantity: qty.value, unit: unitVal });
    updateList();
    qty.value = '';
    unit.value = '';
    if (select.value === '__autre__') {
        other.value = '';
        other.style.display = 'none';
    }
    select.value = '';
};
updateList();
// Synchronisation finale avant soumission du formulaire
const form = document.querySelector('form');
if (form) {
    form.addEventListener('submit', function(e) {
        updateHidden(); // force la mise à jour du champ caché
    });
}
</script>


        <label for="steps">Étapes (une par ligne) :</label><br>
        <textarea id="steps" name="steps" required><?php echo htmlspecialchars($recipe['steps']); ?></textarea><br><br>

        <label for="category_id">Catégorie :</label><br>
        <select id="category_id" name="category_id">
            <option value="">Catégorie</option>
            <?php foreach ($categories as $cat) echo "<option value='{$cat['id']}'" . ($cat['id'] == $recipe['category_id'] ? ' selected' : '') . ">{$cat['name']}</option>"; ?>
        </select><br><br>

        <label for="prep_time">Temps de préparation (min) :</label><br>
        <input type="number" id="prep_time" name="prep_time" value="<?php echo (int)$recipe['prep_time']; ?>"><br><br>

        <label for="cook_time">Temps de cuisson (min) :</label><br>
        <input type="number" id="cook_time" name="cook_time" value="<?php echo (int)$recipe['cook_time']; ?>"><br><br>

        <label for="difficulty">Difficulté :</label><br>
        <select id="difficulty" name="difficulty">
            <option value="Facile" <?php if ($recipe['difficulty'] == 'Facile') echo 'selected'; ?>>Facile</option>
            <option value="Moyenne" <?php if ($recipe['difficulty'] == 'Moyenne') echo 'selected'; ?>>Moyenne</option>
            <option value="Difficile" <?php if ($recipe['difficulty'] == 'Difficile') echo 'selected'; ?>>Difficile</option>
        </select><br><br>

        <label for="images">Ajouter des images :</label><br>
        <input type="file" name="images[]" id="images" accept="image/*" multiple><br><br>

        <label for="videos">Ajouter des vidéos :</label><br>
        <input type="file" name="videos[]" id="videos" accept="video/mp4,video/webm,video/ogg,video/mpg,video/mpeg,video/*" multiple><br><br>

        <button type="submit" class="btn">Enregistrer</button>
    </form>
    <h3>Médias existants</h3>
    <div class="media-list">
        <?php foreach ($media as $item): ?>
            <div style="display:inline-block;position:relative;margin:5px;vertical-align:top;">
                <?php if ($item['media_type'] === 'image'): ?>
                    <img src="<?php echo htmlspecialchars($item['file_path']); ?>" alt="Image" style="max-width:150px;display:block;">
                <?php elseif ($item['media_type'] === 'video'): ?>
                    <video controls style="max-width:180px;display:block;">
                        <source src="<?php echo htmlspecialchars($item['file_path']); ?>">
                    </video>
                <?php endif; ?>
                <a href="edit_recipe.php?id=<?php echo $id; ?>&delete_media=<?php echo $item['id']; ?>" onclick="return confirm('Supprimer ce média ?');" style="position:absolute;top:2px;right:2px;background:#c00;color:#fff;padding:2px 7px;border-radius:50%;text-decoration:none;font-weight:bold;">×</a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
