<?php
$db = require __DIR__ . '/../config/db.php';
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$dbName = $pdo->query('select database()')->fetchColumn();
echo "Base utilisée par PDO : $dbName\n";
$name = 'test_cascade_' . rand(1000,9999);
$stmt = $pdo->prepare('INSERT INTO ingredients (name) VALUES (?)');
$stmt->execute([$name]);
echo "Ajouté : $name\n";
