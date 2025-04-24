<?php
// add_ingredient.php : ajoute un ingrédient à la base si non présent, retourne l'id et le nom
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}
$name = trim($_POST['name'] ?? '');
if (!$name) {
    http_response_code(400);
    echo json_encode(['error' => 'Nom requis']);
    exit;
}
require_once __DIR__ . '/../config/db.php';
$db = require __DIR__ . '/../config/db.php';
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$stmt = $pdo->prepare('SELECT id FROM ingredients WHERE name = ?');
$stmt->execute([$name]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    echo json_encode(['id' => $row['id'], 'name' => $name, 'new' => false]);
    exit;
}
$stmt = $pdo->prepare('INSERT INTO ingredients (name) VALUES (?)');
$stmt->execute([$name]);
$id = $pdo->lastInsertId();
echo json_encode(['id' => $id, 'name' => $name, 'new' => true]);
