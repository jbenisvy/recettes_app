-- Ajout d'ingrédients de base
INSERT INTO ingredients (id, name) VALUES
  (1, 'Tomate'),
  (2, 'Oignon'),
  (3, 'Pâtes'),
  (4, 'Sel'),
  (5, 'Poivre')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Exemple d'association ingrédients/recettes (adapte les recipe_id si besoin)
INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity, unit) VALUES
  (1, 1, '2', 'pièce'),
  (1, 2, '1', 'pièce'),
  (1, 4, '1', 'c.à.c'),
  (2, 3, '200', 'g'),
  (2, 1, '1', 'pièce'),
  (2, 5, '1', 'c.à.c');
