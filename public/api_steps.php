<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');
$db = require __DIR__ . '/../config/db.php';
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if (strlen($q) < 3) { echo json_encode([]); exit; }
// Recherche d'étapes similaires (par début de phrase ou mot-clé)
$stmt = $pdo->prepare('SELECT DISTINCT steps FROM recipes WHERE steps LIKE ? ORDER BY id DESC LIMIT 30');
$stmt->execute(['%' . $q . '%']);
$results = [];
foreach ($stmt as $row) {
    foreach (preg_split('/\r?\n/', $row['steps']) as $step) {
        if (stripos($step, $q) !== false && strlen(trim($step)) > 5) {
            $results[] = trim($step);
        }
    }
}
$results = array_values(array_unique($results));
echo json_encode(array_slice($results, 0, 10));
