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

// Pré-remplissage depuis l'import OCR IA (scan)
if (isset($_SESSION['import_recipe_ocr'])) {
    $import = $_SESSION['import_recipe_ocr'];
    $title = isset($import['title']) ? trim($import['title']) : '';
    $description = isset($import['description']) ? trim($import['description']) : '';
    $steps = isset($import['steps']) ? (is_array($import['steps']) ? implode("\n", $import['steps']) : trim($import['steps'])) : '';
    $category_id = null; // à mapper si possible
    $prep_time = isset($import['prep_time']) ? intval($import['prep_time']) : 0;
    $cook_time = isset($import['cook_time']) ? intval($import['cook_time']) : 0;
    $difficulty = isset($import['difficulty']) ? $import['difficulty'] : 'Facile';
    // Encodage des ingrédients pour le champ JS
    if (isset($import['ingredients']) && is_array($import['ingredients'])) {
        $ingredients_data = json_encode($import['ingredients'], JSON_UNESCAPED_UNICODE);
    } else {
        $ingredients_data = '';
    }
    unset($_SESSION['import_recipe_ocr']); // On vide après usage
} else {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $steps = trim($_POST['steps'] ?? '');
    $category_id = $_POST['category_id'] ?? null;
    $prep_time = intval($_POST['prep_time'] ?? 0);
    $cook_time = intval($_POST['cook_time'] ?? 0);
    $difficulty = $_POST['difficulty'] ?? 'Facile';
    $ingredients_data = $_POST['ingredients_data'] ?? '';
}
$errors = [];
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

                    // Recherche ou création de l'unité
                    $unit_id = null;
                    if ($unit !== '') {
                        $stmt = $pdo->prepare('SELECT id FROM units WHERE name = ?');
                        $stmt->execute([$unit]);
                        $unit_row = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($unit_row) {
                            $unit_id = $unit_row['id'];
                        } else {
                            $stmt = $pdo->prepare('INSERT INTO units (name) VALUES (?)');
                            $stmt->execute([$unit]);
                            $unit_id = $pdo->lastInsertId();
                        }
                    }

                    // Association recette-ingrédient avec unit_id et unit (nom)
                    $stmt = $pdo->prepare('INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity, unit_id, unit) VALUES (?, ?, ?, ?, ?)');
                    $stmt->execute([$recipe_id, $ingredient_id, $quantity, $unit_id, $unit]);
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
<?php if (!empty($_SESSION['success_message'])): ?>
    <script>
        setTimeout(function() { alert(<?php echo json_encode($_SESSION['success_message']); ?>); }, 100);
    </script>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>
    <?php include 'navbar.php'; ?>
    <link rel="stylesheet" href="assets/css/recipe-highlight.css">
<link rel="stylesheet" href="assets/css/modal-success.css">
    <div class="container">
<h1 class="recipe-title-highlight" id="dynamic-recipe-title">Créer une nouvelle recette</h1>
<button id="import-url-btn" type="button" class="btn btn-secondary" style="float:right;margin-bottom:10px;" onclick="window.location.href='import_recipe.php'">Importer une recette depuis une URL</button>
<?php if (isset($_SESSION['email']) && $_SESSION['email'] === 'johny.benisvy@gmail.com'): ?>
    <button id="import-ocr-btn" type="button" class="btn btn-secondary" style="float:right;margin-right:10px;margin-bottom:10px;">Importer une recette scannée</button>
    <input type="file" id="ocr-file" accept="image/*" style="display:none;">
    <div id="ocr-progress"></div>
    <script src="https://unpkg.com/tesseract.js@5.0.1/dist/tesseract.min.js"></script>
    <script>
    const currentUser = '<?php echo $_SESSION['email'] ?? ""; ?>';
    document.getElementById('import-ocr-btn').addEventListener('click', function() {
        if (currentUser !== 'johny.benisvy@gmail.com') {
            alert("Seul l'administrateur johny.benisvy@gmail.com peut utiliser cette fonctionnalité.");
            return;
        }
        document.getElementById('ocr-file').click();
    });
    document.getElementById('ocr-file').addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (!file) return;
        const progressDiv = document.getElementById('ocr-progress');
        progressDiv.textContent = "Analyse OCR en cours...";
        const reader = new FileReader();
        reader.onload = function(e) {
            Tesseract.recognize(
                e.target.result,
                'fra+eng',
                {
                    logger: m => {
                        if (m.status === 'recognizing text') {
                            progressDiv.textContent = `OCR : ${Math.round(m.progress * 100)}%`;
                        }
                    }
                }
            ).then(({ data: { text } }) => {
                progressDiv.textContent = "OCR terminé.";
                if (!text.trim()) {
                    alert("Erreur OCR : aucun texte extrait.");
                    return;
                }
                const recette = parseRecipeFromText(text);
                // Envoi POST vers import_recipe.php avec les données OCR
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'import_recipe.php';
                form.style.display = 'none';
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ocr_data';
                input.value = JSON.stringify(recette);
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            });
        };
        reader.readAsDataURL(file);
    });
    function parseRecipeFromText(text) {
        text = text.replace(/\r/g, '');
        const lines = text.split('\n').map(l => l.trim()).filter(l => l.length > 0);

        let recette = {
            titre: '',
            description: '',
            ingredients: [],
            etapes: [],
            categorie: '',
            temps_preparation: '',
            temps_cuisson: '',
            difficulte: '',
            tags: []
        };

        // Heuristiques pour deviner les sections si les mots-clés sont absents ou mal reconnus
        const regexIngredients = /(ingr[ée]dients?|ngredients?|liste des ing|ingredients?)/i;
        const regexEtapes = /(étapes?|etapes?|etaps?|préparation|preparation|etape de la recette|etapes de la recette)/i;
        const regexIngredientLine = /^([\d,.]+)\s*([a-zA-Zéûèêàôîâç%]*)?\s*([\w\s'’\-éèàêâîôûç]+)$/;
        const regexEtapeLine = /^\s*([\d]+)[\.|\-|\)]\s*(.+)$/;
        let section = 'titre';
        let titreLines = [];
        let descriptionLines = [];
        for (let i = 0; i < lines.length; i++) {
            let line = lines[i];
            // Changement de section selon les mots-clés
            if (regexIngredients.test(line)) { section = 'ingredients'; continue; }
            if (regexEtapes.test(line)) { section = 'etapes'; continue; }
            if (/^description/i.test(line)) { section = 'description'; continue; }
            if (/cat[ée]gorie/i.test(line)) { section = 'categorie'; recette.categorie = line.replace(/.*?:\s*/i, ''); continue; }
            if (/temps\s*de\s*pr[ée]paration/i.test(line)) { recette.temps_preparation = line.replace(/.*?:\s*/i, '').replace(/\D/g, ''); continue; }
            if (/temps\s*de\s*cuisson/i.test(line)) { recette.temps_cuisson = line.replace(/.*?:\s*/i, '').replace(/\D/g, ''); continue; }
            if (/difficult[ée]/i.test(line)) { recette.difficulte = line.replace(/.*?:\s*/i, ''); continue; }
            if (/tags?/i.test(line)) { recette.tags = line.replace(/.*?:\s*/i, '').split(/[,;]/).map(t => t.trim()); continue; }

            // Heuristique : si on est hors section, deviner la section
            if (section === 'titre' || section === 'description') {
                // Si la ligne ressemble à un ingrédient, basculer en mode ingrédients
                if (regexIngredientLine.test(line)) {
                    section = 'ingredients';
                } else if (regexEtapeLine.test(line)) {
                    section = 'etapes';
                }
            }

            // Remplissage intelligent
            if (section === 'titre') {
                titreLines.push(line);
            } else if (section === 'description') {
                descriptionLines.push(line);
            } else if (section === 'ingredients') {
                let match = line.match(regexIngredientLine);
                if (match) {
                    let quantite = match[1] ? match[1].replace(',', '.') : '';
                    let unite = match[2] ? match[2] : '';
                    let nom = match[3] ? match[3] : line;
                    recette.ingredients.push({ nom: nom.trim(), quantite: quantite.trim(), unite: unite.trim() });
                } else if (line.length > 0) {
                    recette.ingredients.push({ nom: line, quantite: '', unite: '' });
                }
            } else if (section === 'etapes') {
                let matchEtape = line.match(regexEtapeLine);
                if (matchEtape) {
                    recette.etapes.push(matchEtape[2].trim());
                } else if (line.length > 0) {
                    recette.etapes.push(line);
                }
            }
        }
        // Si pas d'étapes trouvées, considérer les lignes suivantes les ingrédients comme étapes si elles commencent par un chiffre
        if (recette.etapes.length === 0 && recette.ingredients.length > 0) {
            let possibleEtapes = recette.ingredients.filter(ing => /^\d/.test(ing.nom));
            if (possibleEtapes.length > 0) {
                recette.etapes = possibleEtapes.map(ing => ing.nom);
                recette.ingredients = recette.ingredients.filter(ing => !/^\d/.test(ing.nom));
            }
        }
        recette.titre = titreLines.join(' ').trim();
        recette.description = descriptionLines.join(' ').trim();
        recette.ingredients = recette.ingredients.filter(l => l.nom.length > 0);
        recette.etapes = recette.etapes.filter(l => l.length > 0);

        return recette;
    }
    function fillFormWithRecipe(recette) {
        document.querySelector('[name="title"]').value = recette.titre || '';
        document.querySelector('[name="description"]').value = recette.description || '';
        document.querySelector('[name="steps"]').value = recette.etapes.join('\n');
        if (recette.categorie) {
            let select = document.querySelector('[name="category_id"]');
            for (let opt of select.options) {
                if (opt.text.toLowerCase().includes(recette.categorie.toLowerCase())) {
                    select.value = opt.value;
                    break;
                }
            }
        }
        document.querySelector('[name="prep_time"]').value = recette.temps_preparation || '';
        document.querySelector('[name="cook_time"]').value = recette.temps_cuisson || '';
        if (recette.difficulte) {
            let select = document.querySelector('[name="difficulty"]');
            for (let opt of select.options) {
                if (opt.text.toLowerCase().includes(recette.difficulte.toLowerCase())) {
                    select.value = opt.value;
                    break;
                }
            }
        }
        if (recette.tags.length) {
            let select = document.getElementById('tags-select');
            for (let option of select.options) {
                option.selected = recette.tags.some(tag => option.text.toLowerCase().includes(tag.toLowerCase()));
            }
            if (window.$ && $(select).data('select2')) $(select).trigger('change');
        }
        if (recette.ingredients.length) {
            for (const ing of recette.ingredients) {
                $('#ingredients-select').val(ing.nom).trigger('change');
                $('#ingredient-quantity').val(ing.quantite);
                $('#ingredient-unit').val(ing.unite);
                $('#add-ingredient-btn').click();
            }
        }
        ['title','steps','category_id'].forEach(name => {
            let el = document.querySelector(`[name="${name}"]`);
            if (el) el.style.background = el.value.trim() === '' ? '#ffe0e0' : '';
        });
    }
    </script>
<?php endif; ?>
        
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
                <select id="ingredients-select" style="width:100%;">
                    <option value="">Choisir un ingrédient</option>
                    <option value="__autre__">Autre...</option>
                    <?php
                    $all_ingredients = $pdo->query('SELECT name FROM ingredients ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($all_ingredients as $ing) {
                        echo '<option value="' . htmlspecialchars($ing) . '">' . htmlspecialchars($ing) . '</option>';
                    }
                    ?>
                </select>
                <!-- Select2 CSS & JS -->
                <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
                <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
                <script>
                $(document).ready(function() {
                    $('#ingredients-select').select2({
                        placeholder: 'Rechercher ou sélectionner un ingrédient',
                        allowClear: true,
                        width: 'resolve',
                        dropdownAutoWidth: true,
                        language: {
                            noResults: function() { return "Aucun ingrédient trouvé"; },
                            searching: function() { return "Recherche..."; }
                        }
                    });
                });
                </script>
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
=======
                <input type="text" id="ingredient-quantity" placeholder="Quantité">
<label for="ingredient-unit" style="display:block;margin-top:8px;margin-bottom:2px;"><b>Unité&nbsp;:</b></label>
<select id="ingredient-unit" style="width:100%;">
    <option value="">Unité</option>
    <option value="__autre__">Autre...</option>
    <?php
    $all_units = $pdo->query('SELECT name FROM units ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($all_units as $unit) {
        echo '<option value="' . htmlspecialchars($unit) . '">' . htmlspecialchars($unit) . '</option>';
    }
    ?>
</select>
                <!-- Select2 CSS & JS (déjà chargés pour ingrédients) -->
                <script>
                $(document).ready(function() {
                $('#ingredient-unit').select2({
                    placeholder: 'Rechercher ou sélectionner une unité',
                    allowClear: true,
                    width: 'resolve',
                    dropdownAutoWidth: true,
                    language: {
                        noResults: function() { return "Aucune unité trouvée"; },
                        searching: function() { return "Recherche..."; }
                    }
                });
                        placeholder: 'Rechercher ou sélectionner une unité',
                        allowClear: true,
                        width: 'resolve',
                        dropdownAutoWidth: true,
                        language: {
                            noResults: function() { return "Aucune unité trouvée"; },
                            searching: function() { return "Recherche..."; }
>>>>>>> 027d053 (Initialisation du dépôt local recettes_app)
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

    addBtn.addEventListener('click', function() {
        let name = select.value === '__autre__' ? other.value.trim() : select.value;
        let quantity = qty.value.trim();
        let unitVal = unit.value === '__autre__' ? unitOther.value.trim() : unit.value;
        if (!name) return;
        // Vérifie doublon
        if (selected.some(e => e.name === name && e.unit === unitVal)) return;

        // Si nouvel ingrédient, l'ajoute dynamiquement à la liste ET à la base
        if (select.value === '__autre__') {
            let newOption = new Option(name, name, true, true);
            $(select).append(newOption).trigger('change');
            $.ajax({
                url: 'save_ingredient.php',
                method: 'POST',
                data: { name: name },
                dataType: 'json'
            });
        }

        // Si nouvelle unité, l'ajoute dynamiquement à la liste ET à la base
        if (unit.value === '__autre__' && unitOther.value.trim()) {
            let newUnit = unitOther.value.trim();
            let newUnitOption = new Option(newUnit, newUnit, true, true);
            $(unit).append(newUnitOption).trigger('change');
            $.ajax({
                url: 'save_unit.php',
                method: 'POST',
                data: { name: newUnit },
                dataType: 'json'
            });
        }

        selected.push({name, quantity, unit: unitVal});
        renderTags();
        updateHidden();
        if (select.value === '__autre__') other.value = '';
        qty.value = '';
        unit.value = '';
        unitOther.value = '';
        select.value = '';
        $(select).val('').trigger('change');
        $(unit).val('').trigger('change');
    });

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
