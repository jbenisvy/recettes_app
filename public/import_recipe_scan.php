<?php
session_start();
// Vérification de l'utilisateur
if (!isset($_SESSION['user_id']) || $_SESSION['email'] !== 'johny.benisvy@gmail.com') {
    echo "<script>alert('Cette fonctionnalité est réservée à l\'administrateur.'); window.location='add_recipe.php';</script>";
    exit;
}

$scanDir = '/home/johny/Documents/Scan_Images/Images';
$latestFile = null;
$latestTime = 0;

// Trouver le dernier fichier image (jpg/png/jpeg)
foreach (glob($scanDir.'/*.{jpg,jpeg,png}', GLOB_BRACE) as $file) {
    if (filemtime($file) > $latestTime) {
        $latestTime = filemtime($file);
        $latestFile = $file;
    }
}

if (!$latestFile) {
    echo "<script>alert('Aucun scan trouvé dans $scanDir'); window.location='add_recipe.php';</script>";
    exit;
}

// Lancer l\'OCR (exemple avec Tesseract via shell)
$ocrTxtFile = tempnam(sys_get_temp_dir(), 'ocr_') . '.txt';
$cmd = "tesseract " . escapeshellarg($latestFile) . " " . escapeshellarg($ocrTxtFile) . " -l fra";
exec($cmd);

$ocrText = @file_get_contents($ocrTxtFile);
@unlink($ocrTxtFile);

if (!$ocrText) {
    echo "<script>alert('Erreur OCR : aucun texte extrait.'); window.location='add_recipe.php';</script>";
    exit;
}

// Ici, parser $ocrText pour extraire titre, ingrédients, unités, étapes, etc.
// Pour l'exemple, on pré-remplit juste la zone description.
$_SESSION['import_recipe_ocr'] = [
    'title' => '',
    'description' => $ocrText,
    'ingredients' => '',
    'steps' => '',
    // ... autres champs si besoin
];

header('Location: import_recipe_edit.php?from=scan');
exit;
