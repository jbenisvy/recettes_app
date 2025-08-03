<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');
$db = require __DIR__ . '/../config/db.php';
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($q !== '') {
    // Filtre sur le début d'un mot (mot entier ou partiel)
    $stmt = $pdo->prepare('SELECT id, name FROM ingredients WHERE name LIKE ? OR name LIKE ? ORDER BY name LIMIT 20');
    $stmt->execute([$q . '%', '% ' . $q . '%']);
} else {
    // Afficher tous les ingrédients si aucun texte n'est saisi
    $stmt = $pdo->query('SELECT id, name FROM ingredients ORDER BY name LIMIT 50');
}
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
