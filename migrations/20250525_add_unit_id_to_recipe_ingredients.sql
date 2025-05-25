-- Ajoute une colonne unit_id à recipe_ingredients, liée à units(id), sans supprimer la colonne existante unit
ALTER TABLE recipe_ingredients ADD COLUMN unit_id INT NULL AFTER ingredient_id;
ALTER TABLE recipe_ingredients ADD CONSTRAINT fk_unit_id FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL;
-- (Optionnel) Migration des anciennes données si possible
-- UPDATE recipe_ingredients ri JOIN units u ON ri.unit = u.name SET ri.unit_id = u.id;
-- (Optionnel) Vous pouvez supprimer la colonne unit après migration complète et validation du fonctionnement.
