-- Ajout de la colonne unit_id à recipe_ingredients, et clé étrangère vers units
ALTER TABLE recipe_ingredients
    ADD COLUMN unit_id INT DEFAULT NULL,
    ADD CONSTRAINT fk_unit FOREIGN KEY (unit_id) REFERENCES units(id);
