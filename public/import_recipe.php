<?php
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
<?php if ($recipe['title']): ?>
    <h2>Prévisualisation de la recette importée</h2>
    <form method="post" action="add_recipe.php">
        <label>Titre :</label>
        <input type="text" name="title" value="<?php echo htmlspecialchars($recipe['title']); ?>">

        <label>Description :</label>
        <textarea name="description"><?php echo htmlspecialchars($recipe['description']); ?></textarea>

        <label>Catégorie :</label>
        <input type="text" name="category" value="<?php echo htmlspecialchars($recipe['category']); ?>">

        <label>Ingrédients :</label>
        <textarea name="ingredients"><?php echo htmlspecialchars(implode("\n", $recipe['ingredients'])); ?></textarea>

        <label>Étapes :</label>
        <textarea name="steps"><?php echo htmlspecialchars(implode("\n", $recipe['steps'])); ?></textarea>

        <label>Temps de préparation :</label>
        <input type="text" name="prep_time" value="<?php echo htmlspecialchars($recipe['prep_time']); ?>">

        <label>Temps de cuisson :</label>
        <input type="text" name="cook_time" value="<?php echo htmlspecialchars($recipe['cook_time']); ?>">

        <label>Difficulté :</label>
        <input type="text" name="difficulty" value="<?php echo htmlspecialchars($recipe['difficulty']); ?>">

        <label>Tags :</label>
        <input type="text" name="tags" value="<?php echo htmlspecialchars(implode(", ", $recipe['tags'])); ?>">

        <label>Image principale :</label>
        <?php if ($recipe['image_url']): ?>
            <img src="<?php echo htmlspecialchars($recipe['image_url']); ?>" class="preview-img"><br>
        <?php endif; ?>
        <input type="text" name="image_url" value="<?php echo htmlspecialchars($recipe['image_url']); ?>">

        <button type="submit">Créer la recette</button>
    </form>
<?php endif; ?>
</body>
</html>
