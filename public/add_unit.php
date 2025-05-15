<?php
// Ajout dynamique d'une unité via AJAX
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
$db = require __DIR__ . '/../config/db.php';
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
if (!$name) {
    echo json_encode(['error' => "Nom manquant"]); exit;
}
// Vérifier si l'unité existe déjà (insensible à la casse)
$stmt = $pdo->prepare('SELECT id FROM units WHERE LOWER(name) = LOWER(?)');
$stmt->execute([$name]);
$id = $stmt->fetchColumn();
if ($id) {
    echo json_encode(['id' => $id, 'name' => $name, 'exists' => true]); exit;
}
$stmt = $pdo->prepare('INSERT INTO units (name) VALUES (?)');
$stmt->execute([$name]);
$id = $pdo->lastInsertId();
echo json_encode(['id' => $id, 'name' => $name, 'exists' => false]);
