<?php
session_start();
require_once __DIR__ . '/../config/db.php';
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: my_recipes.php'); exit;
}
$db = require __DIR__ . '/../config/db.php';
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}", $db['user'], $db['pass']);
$id = intval($_GET['id']);
$stmt = $pdo->prepare('SELECT * FROM recipes WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $_SESSION['user_id']]);
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$recipe) { echo '<h2>Recette introuvable ou accès refusé.</h2>'; exit; }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $ingredients = trim($_POST['ingredients'] ?? '');
    $steps = trim($_POST['steps'] ?? '');
    $category_id = $_POST['category_id'] ?? null;
    $prep_time = intval($_POST['prep_time'] ?? 0);
    $cook_time = intval($_POST['cook_time'] ?? 0);
    $difficulty = $_POST['difficulty'] ?? 'Facile';
    $image = $recipe['image'];
    if (!empty($_FILES['image']['name'])) {
        $targetDir = 'images/';
        $fileName = basename($_FILES['image']['name']);
        $targetFile = $targetDir . uniqid() . '_' . $fileName;
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];
        if (in_array($fileType, $allowed) && $_FILES['image']['size'] < 4*1024*1024) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $image = $targetFile;
            } else {
                $errors[] = "Erreur lors de l'upload de l'image.";
            }
        } else {
            $errors[] = 'Format ou taille de fichier non valide (max 4Mo).';
        }
    }
    if (!$title || !$ingredients || !$steps) {
        $errors[] = 'Titre, ingrédients et étapes sont obligatoires.';
    }
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE recipes SET title=?, description=?, ingredients=?, steps=?, category_id=?, prep_time=?, cook_time=?, difficulty=?, image=? WHERE id=? AND user_id=?");
        $stmt->execute([$title, $description, $ingredients, $steps, $category_id, $prep_time, $cook_time, $difficulty, $image, $id, $_SESSION['user_id']]);
        header('Location: my_recipes.php'); exit;
    }
}
$categories = $pdo->query('SELECT * FROM categories')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Modifier la recette</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<a class="btn" href="index.php" style="margin:16px 0 8px 0;display:inline-block;">&larr; Retour à l'accueil</a>
<?php include 'navbar.php'; ?>
<div class="container">
    <h1>Modifier la recette</h1>
    <?php if (!empty($errors)) : ?>
        <div class="error"><ul><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <input type="text" name="title" value="<?php echo htmlspecialchars($recipe['title']); ?>" required><br>
        <textarea name="description"><?php echo htmlspecialchars($recipe['description']); ?></textarea><br>
        <textarea name="ingredients" required><?php echo htmlspecialchars($recipe['ingredients']); ?></textarea><br>
        <textarea name="steps" required><?php echo htmlspecialchars($recipe['steps']); ?></textarea><br>
        <select name="category_id">
            <option value="">Catégorie</option>
            <?php foreach ($categories as $cat) echo "<option value='{$cat['id']}'" . ($cat['id'] == $recipe['category_id'] ? ' selected' : '') . ">{$cat['name']}</option>"; ?>
        </select><br>
        <input type="number" name="prep_time" value="<?php echo (int)$recipe['prep_time']; ?>" placeholder="Temps de préparation (min)">
        <input type="number" name="cook_time" value="<?php echo (int)$recipe['cook_time']; ?>" placeholder="Temps de cuisson (min)"><br>
        <select name="difficulty">
            <option value="Facile" <?php if ($recipe['difficulty'] == 'Facile') echo 'selected'; ?>>Facile</option>
            <option value="Moyenne" <?php if ($recipe['difficulty'] == 'Moyenne') echo 'selected'; ?>>Moyenne</option>
            <option value="Difficile" <?php if ($recipe['difficulty'] == 'Difficile') echo 'selected'; ?>>Difficile</option>
        </select><br>
        <input type="file" name="image" accept="image/*"><br>
        <?php if ($recipe['image']): ?><img src="<?php echo htmlspecialchars($recipe['image']); ?>" alt="Photo" style="max-width:100px;"><br><?php endif; ?>
        <button type="submit">Enregistrer</button>
    </form>
</div>
</body>
</html>
