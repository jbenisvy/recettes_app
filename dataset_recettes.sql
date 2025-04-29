-- Jeu de données de test pour application de gestion de recettes
-- Compatible avec le schéma de database.sql

-- Utilisateurs
INSERT INTO users (id, username, email, password, avatar) VALUES
(1, 'alice', 'alice@example.com', 'hashpass1', NULL),
(2, 'bob', 'bob@example.com', 'hashpass2', NULL),
(3, 'carol', 'carol@example.com', 'hashpass3', NULL);

-- Catégories
INSERT INTO categories (id, name) VALUES
(1, 'Entrée'),
(2, 'Plat principal'),
(3, 'Dessert'),
(4, 'Petit-déjeuner');

-- Ingrédients
INSERT INTO ingredients (id, name) VALUES
(1, 'Oeuf'),
(2, 'Farine'),
(3, 'Lait'),
(4, 'Sucre'),
(5, 'Beurre'),
(6, 'Sel'),
(7, 'Tomate'),
(8, 'Poulet'),
(9, 'Pâtes'),
(10, 'Chocolat');

-- Unités
INSERT INTO units (id, name) VALUES
(1, 'g'),
(2, 'ml'),
(3, 'pièce');

-- Recettes
INSERT INTO recipes (id, user_id, title, description, ingredients, steps, category_id, prep_time, cook_time, difficulty, image) VALUES
(1, 1, 'Crêpes faciles', 'Délicieuses crêpes maison', 'Oeuf, Farine, Lait, Sucre, Beurre', '1. Mélanger les ingrédients\n2. Cuire à la poêle', 4, 10, 15, 'Facile', NULL),
(2, 2, 'Poulet à la tomate', 'Un plat savoureux et simple', 'Poulet, Tomate, Sel', '1. Couper le poulet\n2. Cuire avec les tomates et le sel', 2, 15, 30, 'Moyenne', NULL),
(3, 2, 'Gâteau au chocolat', 'Moelleux et gourmand', 'Oeuf, Farine, Sucre, Chocolat, Beurre', '1. Mélanger\n2. Cuire au four', 3, 20, 35, 'Difficile', NULL),
(4, 3, 'Omelette rapide', 'Pour un petit-déjeuner express', 'Oeuf, Sel, Beurre', '1. Battre les oeufs\n2. Cuire à la poêle', 4, 5, 5, 'Facile', NULL);

-- recipe_ingredients
INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity, unit) VALUES
(1, 1, '2', 'pièce'), (1, 2, '250', 'g'), (1, 3, '500', 'ml'), (1, 4, '50', 'g'), (1, 5, '30', 'g'),
(2, 8, '300', 'g'), (2, 7, '2', 'pièce'), (2, 6, '1', 'g'),
(3, 1, '3', 'pièce'), (3, 2, '200', 'g'), (3, 4, '150', 'g'), (3, 10, '100', 'g'), (3, 5, '80', 'g'),
(4, 1, '2', 'pièce'), (4, 6, '2', 'g'), (4, 5, '10', 'g');

-- Favoris
INSERT INTO favorites (user_id, recipe_id) VALUES
(2, 1), (3, 2);

-- Commentaires
INSERT INTO comments (user_id, recipe_id, content, rating) VALUES
(2, 1, 'Super recette, facile à faire !', 5),
(1, 2, 'Très bon, mais j''ai ajouté des épices.', 4),
(3, 3, 'Un peu trop sucré à mon goût.', 3);
