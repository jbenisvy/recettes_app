-- Migration pour créer la table de compteur de vues par page
CREATE TABLE IF NOT EXISTS page_views (
    page VARCHAR(255) PRIMARY KEY,
    views INT NOT NULL DEFAULT 0
);
