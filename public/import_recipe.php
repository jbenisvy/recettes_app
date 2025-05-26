<?php
// Fichier : import_recipe.php
// Script PHP natif pour importer une recette depuis une URL et pré-remplir le formulaire d'ajout
// À placer dans public/ ou à la racine selon ton organisation

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
                    if (!empty($ing['quantite'])) $parts[] = $ing['quantite'];
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
    }
}

function extract_between($string, $start, $end) {
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return trim(substr($string, $ini, $len));
}


if (!empty($_POST['url'])) {
    $url = $_POST['url'];
    $html = @file_get_contents($url);
    if ($html) {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        
        // Extraction du titre
        $nodes = $xpath->query('//h1');
        if ($nodes->length > 0) {
            $recipe['title'] = trim($nodes->item(0)->nodeValue);
        }
        // Description (meta ou p)
        $nodes = $xpath->query('//meta[@name="description"]');
        if ($nodes->length > 0) {
            $recipe['description'] = $nodes->item(0)->getAttribute('content');
        } else {
            $nodes = $xpath->query('//p');
            if ($nodes->length > 0) {
                $recipe['description'] = trim($nodes->item(0)->nodeValue);
            }
        }
        // Ingrédients (ul/li ou class)
        $nodes = $xpath->query('//ul[contains(@class,"ingredient") or contains(@id,"ingredient")]/li | //li[contains(@class,"ingredient")]');
        foreach ($nodes as $li) {
            $recipe['ingredients'][] = trim($li->nodeValue);
        }
        // Extraction spécifique CuisineAZ si aucun ingrédient trouvé
        if (empty($recipe['ingredients'])) {
            // Recherche des ingrédients dans les <div> ou <span> qui suivent le header "Ingrédients"
            $nodes = $xpath->query('//h2[contains(text(),"Ingrédients")]/following-sibling::*');
            foreach ($nodes as $node) {
                foreach ($node->getElementsByTagName('*') as $child) {
                    $txt = trim($child->nodeValue);
                    if ($txt && strlen($txt) > 2 && stripos($txt, 'Préparation') === false && stripos($txt, 'Ingrédient') === false) {
                        $recipe['ingredients'][] = $txt;
                    }
                }
            }
            $recipe['ingredients'] = array_unique($recipe['ingredients']);
        }
        // Étapes (ol/li ou class)
        $nodes = $xpath->query('//ol[contains(@class,"step") or contains(@id,"step")]/li | //li[contains(@class,"step")]');
        foreach ($nodes as $li) {
            $recipe['steps'][] = trim($li->nodeValue);
        }
        // Image principale
        $nodes = $xpath->query('//img[contains(@class,"recipe") or contains(@src,"recette")]');
        if ($nodes->length > 0) {
            $recipe['image_url'] = $nodes->item(0)->getAttribute('src');
        }
        // Catégorie (si présente)
        $nodes = $xpath->query('//*[contains(@class,"category") or contains(@id,"category")]');
        if ($nodes->length > 0) {
            $recipe['category'] = trim($nodes->item(0)->nodeValue);
        }
        // Tags
        $nodes = $xpath->query('//*[contains(@class,"tag") or contains(@id,"tag")]');
        foreach ($nodes as $tag) {
            $recipe['tags'][] = trim($tag->nodeValue);
        }
        // Temps de préparation/cuisson/difficulté (si présents sous forme de span ou div)
        $nodes = $xpath->query('//*[contains(@class,"prep") or contains(@id,"prep")]');
        if ($nodes->length > 0) {
            $recipe['prep_time'] = trim($nodes->item(0)->nodeValue);
        }
        $nodes = $xpath->query('//*[contains(@class,"cook") or contains(@id,"cook")]');
        if ($nodes->length > 0) {
            $recipe['cook_time'] = trim($nodes->item(0)->nodeValue);
        }
        $nodes = $xpath->query('//*[contains(@class,"diff") or contains(@id,"diff") or contains(@class,"difficulte")]');
        if ($nodes->length > 0) {
            $recipe['difficulty'] = trim($nodes->item(0)->nodeValue);
        }
    } else {
        $error = "Impossible de télécharger la page. Vérifie l'URL.";
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
<h1>Importer une recette depuis une URL</h1>
<form method="post">
    <label>URL de la recette à importer :</label>
    <input type="text" name="url" required value="<?php echo isset($_POST['url']) ? htmlspecialchars($_POST['url']) : '' ?>">
    <button type="submit">Analyser</button>
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
    <h2>Prévisualisation de la recette importée</h2>
    <?php if (!empty($missing_fields)): ?>
        <div class="alert-missing">
            <strong>Champs obligatoires manquants :</strong> <?php echo implode(', ', $missing_fields); ?>.<br>
            Merci de compléter ces champs avant de valider.
        </div>
    <?php endif; ?>
    <form method="post" action="add_recipe.php">
        <label<?php if ($recipe['title'] === '') echo ' class="missing-label"'; ?>>Titre :</label>
        <input type="text" name="title" value="<?php echo htmlspecialchars($recipe['title']); ?>"<?php if ($recipe['title'] === '') echo ' class="missing-field"'; ?>>
        <br>
        <label>Description :</label>
        <textarea name="description"><?php echo htmlspecialchars($recipe['description']); ?></textarea>
        <br>
        <label<?php if (empty($recipe['ingredients'])) echo ' class="missing-label"'; ?>>Ingrédients :</label>
        <ul>
        <?php if (!empty($recipe['ingredients'])): ?>
            <?php foreach ($recipe['ingredients'] as $ing): ?>
                <li><input type="text" name="ingredients[]" value="<?php echo htmlspecialchars($ing); ?>"></li>
            <?php endforeach; ?>
        <?php else: ?>
            <li><input type="text" name="ingredients[]" value="" class="missing-field"></li>
        <?php endif; ?>
        </ul>
        <label<?php if (empty($recipe['steps'])) echo ' class="missing-label"'; ?>>Étapes :</label>
        <textarea name="steps"<?php if (empty($recipe['steps'])) echo ' class="missing-field"'; ?>><?php echo htmlspecialchars(is_array($recipe['steps']) ? implode("\n", $recipe['steps']) : $recipe['steps']); ?></textarea>
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
        <button type="submit">Créer la recette</button>
    </form>
<?php endif; ?>
</body>
</html>
