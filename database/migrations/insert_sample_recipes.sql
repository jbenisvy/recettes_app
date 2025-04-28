-- Création d'un utilisateur de test
INSERT INTO users (username, email, password) VALUES
('testuser', 'test@example.com', '$2y$10$abcdefghijklmnopqrstuv');

-- Création de quelques recettes de test
INSERT INTO recipes (user_id, title, description, ingredients, steps, category_id, prep_time, cook_time, difficulty, image) VALUES
(1, 'Quiche Lorraine', 'Une délicieuse quiche traditionnelle.', 'Pâte brisée, lardons, œufs, crème, gruyère', '1. Préchauffer le four à 180°C. 2. Mélanger les œufs, la crème et le gruyère. 3. Ajouter les lardons. 4. Verser sur la pâte et cuire 40 min.', 1, 20, 40, 'Moyenne', NULL),
(1, 'Salade César', 'Une salade fraîche et gourmande.', 'Laitue, poulet, croûtons, parmesan, sauce César', '1. Griller le poulet. 2. Mélanger la laitue, les croûtons et le parmesan. 3. Ajouter le poulet et la sauce.', 1, 10, 15, 'Facile', NULL),
(1, 'Crêpes', 'Des crêpes moelleuses pour le goûter.', 'Farine, œufs, lait, sucre, beurre', '1. Mélanger la farine, les œufs et le lait. 2. Ajouter le sucre et le beurre fondu. 3. Cuire les crêpes à la poêle.', 2, 10, 20, 'Facile', NULL);

-- Exemple d'ingrédients associés à la première recette
INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity, unit) VALUES
(1, 4, '200', 'g'), -- Farine
(1, 7, '3', 'pièce'), -- Oeuf
(1, 5, '100', 'g'), -- Beurre
(1, 20, '150', 'g'); -- Fromage
