<?php
// Récupère la liste des unités depuis la base de données et la renvoie en JSON
require_once __DIR__ . '/../config/db.php';
$db = require __DIR__ . '/../config/db.php';
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);
$units = $pdo->query('SELECT id, name FROM units ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode($units);
