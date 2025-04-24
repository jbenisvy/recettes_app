<?php
session_start();
require_once __DIR__ . '/../config/db.php';
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php?deleted=1'); exit;
}
$db = require __DIR__ . '/../config/db.php';
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);
$id = intval($_GET['id']);
$stmt = $pdo->prepare('SELECT * FROM recipes WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $_SESSION['user_id']]);
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$recipe) { echo '<h2>Recette introuvable ou accès refusé.</h2>'; exit; }
$pdo->prepare('DELETE FROM recipes WHERE id = ? AND user_id = ?')->execute([$id, $_SESSION['user_id']]);
header('Location: index.php?deleted=1'); exit;
