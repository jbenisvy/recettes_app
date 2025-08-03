ALTER TABLE users ADD COLUMN admin BOOLEAN DEFAULT 0;
UPDATE users SET admin = 1 WHERE username = 'testuser';
