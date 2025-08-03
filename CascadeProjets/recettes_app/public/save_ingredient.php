<?php
// save_ingredient.php : ajoute un ingrédient à la table 'ingredients' (AJAX)
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
$db = require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['name'])) {
    echo json_encode(['success' => false, 'error' => 'Requête invalide']);
    exit;
}

$name = trim($_POST['name']);
if ($name === '') {
    echo json_encode(['success' => false, 'error' => 'Nom vide']);
    exit;
}

try {
    $pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Vérifier si l'ingrédient existe déjà
    $stmt = $pdo->prepare('SELECT id FROM ingredients WHERE name = ?');
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => true, 'already_exists' => true]);
        exit;
    }
    // Ajouter l'ingrédient
    $stmt = $pdo->prepare('INSERT INTO ingredients (name) VALUES (?)');
    $stmt->execute([$name]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
