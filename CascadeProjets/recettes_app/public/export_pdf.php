<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';
session_start();

use Mpdf\Mpdf;

// Déterminer si export de la liste utilisateur ou d'une sélection partagée
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$list_id = null;
$ingredients = [];

$db = require __DIR__ . '/../config/db.php';
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);

if ($user_id && !isset($_GET['id'])) {
    // Export de la liste de courses de l'utilisateur
    $list_stmt = $pdo->prepare('SELECT id FROM shopping_lists WHERE user_id = ?');
    $list_stmt->execute([$user_id]);
    $list_id = $list_stmt->fetchColumn();
    if ($list_id) {
        $stmt = $pdo->prepare("SELECT i.name, sli.quantity, sli.unit, sli.checked FROM shopping_list_items sli JOIN ingredients i ON sli.ingredient_id = i.id WHERE sli.list_id = ? ORDER BY i.name");
        $stmt->execute([$list_id]);
        $ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    $title = "Ma liste de courses";
} elseif (isset($_GET['id'])) {
    // Export d'une sélection partagée (id=1,2,3)
    $ids = [];
    if (is_array($_GET['id'])) {
        foreach ($_GET['id'] as $rid) if (is_numeric($rid)) $ids[] = intval($rid);
    } else {
        foreach (explode(',', $_GET['id']) as $rid) if (is_numeric($rid)) $ids[] = intval($rid);
    }
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT i.name, ri.unit, SUM(CASE WHEN ri.quantity REGEXP '^[0-9.]+$' THEN ri.quantity ELSE 0 END) as quantity, ri.unit FROM recipe_ingredients ri JOIN ingredients i ON ri.ingredient_id = i.id WHERE ri.recipe_id IN ($placeholders) GROUP BY i.name, ri.unit ORDER BY i.name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($ids);
        $ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    $title = "Liste de courses partagée";
} else {
    die('Aucune liste à exporter.');
}

$mpdf = new Mpdf(["default_font" => "dejavusans"]);
$html = '<h2 style="color:#236665;">' . htmlspecialchars($title) . '</h2>';
$html .= '<ul style="font-size:1.1em;">';
foreach ($ingredients as $ing) {
    $checked = isset($ing['checked']) && $ing['checked'] ? '✔ ' : '';
    $html .= '<li>' . $checked . htmlspecialchars($ing['name']);
    if (!empty($ing['quantity'])) {
        $html .= ' : <strong>' . htmlspecialchars($ing['quantity']) . ' ' . htmlspecialchars($ing['unit']) . '</strong>';
    }
    $html .= '</li>';
}
$html .= '</ul>';
$html .= '<div style="margin-top:2em;font-size:0.9em;color:#888;">Exporté depuis l’application Recettes App.</div>';
$mpdf->WriteHTML($html);
$mpdf->SetTitle($title);
$mpdf->Output("liste_courses.pdf", \Mpdf\Output\Destination::DOWNLOAD);
exit;
