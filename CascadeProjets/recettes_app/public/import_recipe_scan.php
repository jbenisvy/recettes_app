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

// Lancer l'OCR (Tesseract)
$ocrTxtFile = tempnam(sys_get_temp_dir(), 'ocr_') . '.txt';
$cmd = "tesseract " . escapeshellarg($latestFile) . " " . escapeshellarg($ocrTxtFile) . " -l fra";
exec($cmd);

$ocrText = @file_get_contents($ocrTxtFile);
@unlink($ocrTxtFile);

if (!$ocrText) {
    echo "<script>alert('Erreur OCR : aucun texte extrait.'); window.location='add_recipe.php';</script>";
    exit;
}

// Appel au script Python pour extraction IA
$pythonScript = '/home/johny/Documents/Scan_Images/ocr_ai_extract.py';
$cmd = "python3 " . escapeshellarg($pythonScript);
$descriptorspec = [
    0 => ["pipe", "r"], // stdin
    1 => ["pipe", "w"], // stdout
    2 => ["pipe", "w"]  // stderr
];
$process = proc_open($cmd, $descriptorspec, $pipes);
if (is_resource($process)) {
    fwrite($pipes[0], $ocrText);
    fclose($pipes[0]);
    $json = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $return_value = proc_close($process);
} else {
    echo "<script>alert('Erreur : impossible de lancer le script IA.'); window.location='add_recipe.php';</script>";
    exit;
}

if (!$json) {
    echo "<script>alert('Erreur IA : aucune donnée extraite.'); window.location='add_recipe.php';</script>";
    exit;
}

$data = json_decode($json, true);
if (!$data) {
    echo "<script>alert('Erreur de parsing JSON IA : " . htmlspecialchars($json) . "'); window.location='add_recipe.php';</script>";
    exit;
}

// Pré-remplir les champs pour add_recipe.php
$_SESSION['import_recipe_ocr'] = $data;

header('Location: add_recipe.php?from=scan');
exit;
