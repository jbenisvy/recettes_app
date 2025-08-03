<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fichier : import_recipe.php
// Script PHP natif pour importer une recette depuis une URL et pré-remplir le formulaire d'ajout
// À placer dans public/ ou à la racine selon ton organisation

function extract_between($string, $start, $end) {
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return trim(substr($string, $ini, $len));
}

if (isset($_FILES['scan']) && $_FILES['scan']['error'] === UPLOAD_ERR_OK) {
    $tmp_name = $_FILES['scan']['tmp_name'];
    $filename = basename($_FILES['scan']['name']);
    $upload_dir = __DIR__ . '/uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    $dest_path = $upload_dir . $filename;
    move_uploaded_file($tmp_name, $dest_path);

    // Appel du script Python
    $cmd = "python3 /home/johny/Documents/Scan_Images/app.py " . escapeshellarg($dest_path);
    $json = shell_exec($cmd);

    if (empty($json) || strpos($json, 'Erreur :') === 0) {
        echo "<div style='color:red'>Erreur lors de l'analyse IA :<br>$json</div>";
        $data = [];
    } else {
        $data = json_decode($json, true);
    }
} else {
    $data = [];
}

$titre = htmlspecialchars($data['titre'] ?? '');
$ingredients = htmlspecialchars(json_encode($data['ingredients'] ?? [], JSON_UNESCAPED_UNICODE));
$etapes = htmlspecialchars(json_encode($data['etapes'] ?? [], JSON_UNESCAPED_UNICODE));
$description = htmlspecialchars($data['description'] ?? '');
$temps_preparation = htmlspecialchars($data['temps_preparation'] ?? '');
$temps_cuisson = htmlspecialchars($data['temps_cuisson'] ?? '');
$difficulte = htmlspecialchars($data['difficulte'] ?? '');
$tags = htmlspecialchars(json_encode($data['tags'] ?? [], JSON_UNESCAPED_UNICODE));
$categorie = htmlspecialchars($data['categorie'] ?? '');
?>

<?php
$recipe = [
    'title' => '',
    'description' => '',
    'ingredients' => [],
    'steps' => [],
    'category' => '',
    'prep_time' => '',
    'cook_time' => '',
    'difficulty' => '',
    'image_url' => '',
    'tags' => [],
];

$force_preview = false;
if (!empty($_POST['ocr_data'])) {
    
    
    $ocr = json_decode($_POST['ocr_data'], true);
    file_put_contents('/tmp/debug_ocr_post.txt', print_r($_POST['ocr_data'], true));
    file_put_contents('/tmp/debug_ocr_parsed.txt', print_r($ocr, true));
    if ($ocr) {
        $recipe['title'] = $ocr['titre'] ?? '';
        $recipe['description'] = $ocr['description'] ?? '';
        $recipe['ingredients'] = [];
        if (!empty($ocr['ingredients'])) {
            foreach ($ocr['ingredients'] as $ing) {
                if (is_array($ing)) {
                    $parts = [];
                    if (!empty($ing['unite'])) $parts[] = $ing['unite'];
                    if (!empty($ing['nom'])) $parts[] = $ing['nom'];
                    $str = implode(' ', $parts);
                    $recipe['ingredients'][] = trim($str);
                } else {
                    $recipe['ingredients'][] = $ing;
                }
            }
        }
        $recipe['steps'] = $ocr['etapes'] ?? [];
        $recipe['category'] = $ocr['categorie'] ?? '';
        $recipe['prep_time'] = $ocr['temps_preparation'] ?? '';
        $recipe['cook_time'] = $ocr['temps_cuisson'] ?? '';
        $recipe['difficulty'] = $ocr['difficulte'] ?? '';
        $recipe['tags'] = $ocr['tags'] ?? [];
        $force_preview = true;
    } else {
        echo '<div style="color:red">Erreur : le fichier JSON est invalide.</div>';
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Importer une recette</title>
    <style>
        body { font-family: Arial; margin: 30px; }
        label { display: block; margin-top: 10px; }
        input, textarea { width: 100%; padding: 5px; margin-top: 3px; }
        .preview-img { max-width: 200px; margin: 10px 0; }
    </style>
</head>
<body>
<h1>Importer une recette depuis un fichier JSON</h1>
<form method="post" enctype="multipart/form-data">
    <label for="recipe_json_file">Importer une recette depuis un fichier JSON :</label>
    <input type="file" name="recipe_json_file" id="recipe_json_file" accept="application/json">
    <input type="submit" value="Importer / Prévisualiser recette">
    <p style="font-size:small; color:#555;">
      Structure attendue du JSON :<br>
      <pre style="background:#f8f8f8; border:1px solid #ddd; padding:6px;">
{
  "title": "Crêpes faciles",
  "description": "...",
  "ingredients": ["250 g farine", "3 œufs", ...],
  "steps": ["Mélanger...", "Ajouter...", ...],
  "category": "Dessert",
  "prep_time": "10 min",
  "cook_time": "20 min",
  "difficulty": "Facile",
  "tags": ["crêpes", "dessert"],
  "image_url": "https://exemple.com/crepes.jpg"
}
      </pre>
    </p>
</form>
<?php if (!empty($error)) { echo '<p style="color:red">'.$error.'</p>'; } ?>
<?php
// Détection des champs obligatoires manquants
$missing_fields = [];
if ($recipe['title'] === '') $missing_fields[] = 'Titre';
if (empty($recipe['ingredients'])) $missing_fields[] = 'Ingrédients';
if (empty($recipe['steps'])) $missing_fields[] = 'Étapes';
if ($recipe['category'] === '') $missing_fields[] = 'Catégorie';
?>
<?php if ($force_preview): ?>
    <form method="get" action="add_recipe.php" style="margin-bottom:20px;">
        <button type="submit" style="background:#eee;padding:8px 16px;">&#8592; Retour à l'ajout d'une recette</button>
    </form>
    <h2>Prévisualisation de la recette importée</h2>
    <?php if (!empty($missing_fields)): ?>
        <div class="alert-missing">
            <strong>Champs obligatoires manquants :</strong> <?php echo implode(', ', $missing_fields); ?>.<br>
            Merci de compléter ces champs avant de valider.
        </div>
    <?php endif; ?>
    <form method="post" action="import_recipe.php">
        <input type="hidden" name="recipe_json" value='<?php echo htmlspecialchars(json_encode($recipe, JSON_UNESCAPED_UNICODE)); ?>'>
        <label<?php if ($recipe['title'] === '') echo ' class="missing-label"'; ?>>Titre :</label>
        <input type="text" name="title" value="<?php echo htmlspecialchars($recipe['title']); ?>"<?php if ($recipe['title'] === '') echo ' class="missing-field"'; ?>>
        <br>
        <label>Description :</label>
        <textarea name="description" rows="3"><?php echo htmlspecialchars($recipe['description']); ?></textarea>
        <br>
        <label<?php if (empty($recipe['ingredients'])) echo ' class="missing-label"'; ?>>Ingrédients :</label>
        <textarea name="ingredients_text" rows="6" style="width:100%;"<?php if (empty($recipe['ingredients'])) echo ' class="missing-field"'; ?>><?php echo htmlspecialchars(is_array($recipe['ingredients']) ? implode("\n", $recipe['ingredients']) : $recipe['ingredients']); ?></textarea>
        <label<?php if (empty($recipe['steps'])) echo ' class="missing-label"'; ?>>Étapes :</label>
        <textarea name="steps_text" rows="8" style="width:100%;"<?php if (empty($recipe['steps'])) echo ' class="missing-field"'; ?>><?php echo htmlspecialchars(is_array($recipe['steps']) ? implode("\n", $recipe['steps']) : $recipe['steps']); ?></textarea>
        <br>
        <label<?php if ($recipe['category'] === '') echo ' class="missing-label"'; ?>>Catégorie :</label>
        <input type="text" name="category" value="<?php echo htmlspecialchars($recipe['category']); ?>"<?php if ($recipe['category'] === '') echo ' class="missing-field"'; ?>>
        <br>
        <label>Temps de préparation (min) :</label>
        <input type="text" name="prep_time" value="<?php echo htmlspecialchars($recipe['prep_time']); ?>">
        <br>
        <label>Temps de cuisson (min) :</label>
        <input type="text" name="cook_time" value="<?php echo htmlspecialchars($recipe['cook_time']); ?>">
        <br>
        <label>Difficulté :</label>
        <input type="text" name="difficulty" value="<?php echo htmlspecialchars($recipe['difficulty']); ?>">
        <br>
        <label>Tags :</label>
        <input type="text" name="tags" value="<?php echo htmlspecialchars(is_array($recipe['tags']) ? implode(", ", $recipe['tags']) : $recipe['tags']); ?>">
        <br>
        <label>Image principale :</label>
        <?php if ($recipe['image_url']): ?>
            <img src="<?php echo htmlspecialchars($recipe['image_url']); ?>" class="preview-img"><br>
        <?php endif; ?>
        <input type="text" name="image_url" value="<?php echo htmlspecialchars($recipe['image_url']); ?>">
        <br>
        <button type="submit" name="validate_recipe">Créer la recette</button>
    </form>
<?php endif; ?>

<?php
// Traitement de la prévalidation via JSON
if (isset($_POST['validate_recipe']) && isset($_POST['recipe_json'])) {
    $recipe = json_decode($_POST['recipe_json'], true);
    // On écrase les champs édités par l'utilisateur
    $recipe['title'] = $_POST['title'] ?? $recipe['title'];
    $recipe['description'] = $_POST['description'] ?? $recipe['description'];
    $recipe['category'] = $_POST['category'] ?? $recipe['category'];
    $recipe['prep_time'] = $_POST['prep_time'] ?? $recipe['prep_time'];
    $recipe['cook_time'] = $_POST['cook_time'] ?? $recipe['cook_time'];
    $recipe['difficulty'] = $_POST['difficulty'] ?? $recipe['difficulty'];
    $recipe['image_url'] = $_POST['image_url'] ?? $recipe['image_url'];
    // Ingrédients et étapes : split sur les retours à la ligne
    $recipe['ingredients'] = array_filter(array_map('trim', explode("\n", $_POST['ingredients_text'] ?? '')));
    $recipe['steps'] = array_filter(array_map('trim', explode("\n", $_POST['steps_text'] ?? '')));
    // Tags : split sur la virgule
    $recipe['tags'] = array_filter(array_map('trim', explode(',', $_POST['tags'] ?? '')));
    // Ici tu peux valider ou enregistrer $recipe
    echo '<pre style="background:#f8f8f8; border:1px solid #ccc; padding:10px;">';
    echo "Recette validée :\n";
    print_r($recipe);
    echo '</pre>';
}
?>
</body>
</html>
