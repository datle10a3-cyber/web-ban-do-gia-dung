ALTER TABLE users ADD COLUMN IF NOT EXISTS display_name VARCHAR(100) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS display_name_custom TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS password_enabled TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE users ADD COLUMN IF NOT EXISTS auth_version INT NOT NULL DEFAULT 1;
UPDATE users SET display_name=username WHERE display_name IS NULL AND username NOT REGEXP '^(google|facebook)_[a-f0-9]{16}$';
UPDATE users u JOIN oauth_accounts a ON a.user_id=u.id SET u.password_enabled=0
WHERE u.display_name IS NULL AND u.username REGEXP '^(google|facebook)_[a-f0-9]{16}$';
ALTER TABLE orders ADD COLUMN IF NOT EXISTS inventory_restored TINYINT(1) NOT NULL DEFAULT 0;
