-SELECT id, title FROM recipes;
if (empty($errors)) {
    $stmt = $pdo->prepare("UPDATE recipes SET ...");
    $stmt->execute([...]);
    // <-- AJOUTE ICI LE BLOC CI-DESSUS
    if (!empty($success)) {
        $_SESSION['success_message'] = "Médias ajoutés avec succès.";
    }
    header('Location: edit_recipe.php?id='.$id); exit;
}
