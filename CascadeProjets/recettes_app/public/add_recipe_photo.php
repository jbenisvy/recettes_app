<?php
// Page d'ajout de recette à partir d'une photo (OCR)
require_once __DIR__ . '/../config/db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); exit;
}
$db = require __DIR__ . '/../config/db.php';
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);
$errors = [];
$title = $description = $steps = $ocr_text = '';
$ingredients = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_FILES['photo']['tmp_name'])) {
        // Utilisation de Tesseract OCR via shell_exec (nécessite tesseract installé sur le serveur)
        $tmp = $_FILES['photo']['tmp_name'];
        $output_txt = tempnam(sys_get_temp_dir(), 'ocr_');
        $cmd = "tesseract " . escapeshellarg($tmp) . " " . escapeshellarg($output_txt) . " -l fra";
        shell_exec($cmd);
        $ocr_text = file_get_contents($output_txt . '.txt');
        unlink($output_txt);
        unlink($output_txt . '.txt');
        // Extraction naïve des champs (titre, ingrédients, étapes)
        // (À améliorer selon la structure des recettes)
        if (preg_match('/titre[:\s]*(.+)/i', $ocr_text, $m)) $title = trim($m[1]);
        if (preg_match('/ingr[ée]dients?[:\s]*([\s\S]+?)etape/i', $ocr_text, $m)) {
            $ingredients = preg_split('/\r?\n/', trim($m[1]));
        }
        if (preg_match('/etapes?[:\s]*([\s\S]+)/i', $ocr_text, $m)) $steps = trim($m[1]);
        $description = '';
    } else {
        $errors[] = 'Aucune photo envoyée.';
    }
    // Pré-remplir le formulaire classique si OCR réussi
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Ajouter une recette par photo</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<a class="btn" href="index.php" style="margin:16px 0 8px 0;display:inline-block;">&larr; Retour à l'accueil</a>
<?php include 'navbar.php'; ?>
<div class="container">
    <h1>Ajouter une recette à partir d'une photo</h1>
    <?php if (!empty($errors)): ?>
        <div class="error"><ul><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <label>Photo de la recette (image ou scan) :</label><br>
        <input type="file" name="photo" accept="image/*" required><br><br>
        <button type="submit">Analyser la photo (OCR)</button>
    </form>
    <?php if ($ocr_text): ?>
        <h3>Texte extrait :</h3>
        <pre style="background:#f7f7f7;padding:1em;border:1px solid #ccc;"><?php echo htmlspecialchars($ocr_text); ?></pre>
        <h3>Pré-remplir le formulaire d'ajout ?</h3>
        <form method="post" action="add_recipe.php">
            <input type="hidden" name="title" value="<?php echo htmlspecialchars($title); ?>">
            <input type="hidden" name="description" value="<?php echo htmlspecialchars($description); ?>">
            <input type="hidden" name="steps" value="<?php echo htmlspecialchars($steps); ?>">
            <input type="hidden" name="ingredients_data" value='<?php echo json_encode(array_map(function($i){return ["name"=>$i,"quantity"=>"","unit"=>""];},$ingredients)); ?>'>
            <button type="submit">Créer la recette à partir de ces données</button>
        </form>
    <?php endif; ?>
    <div style="margin-top:2em;color:#888;font-size:0.95em;">
        L'OCR fonctionne mieux avec une photo nette et un texte bien structuré ("Titre", "Ingrédients", "Etapes").<br>
        <b>Attention :</b> Cette fonctionnalité nécessite que <code>tesseract</code> soit installé sur le serveur.
    </div>
</div>
</body>
</html>
