-- NULL marks records whose account policy has not yet been migrated.
ALTER TABLE vouchers ADD COLUMN IF NOT EXISTS per_user_limit TINYINT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE vouchers ADD COLUMN IF NOT EXISTS new_customer_only TINYINT(1) NOT NULL DEFAULT 0;
UPDATE vouchers SET new_customer_only=1 WHERE code='WELCOME100' AND per_user_limit IS NULL;
UPDATE vouchers SET per_user_limit=CASE WHEN code='GIAM10' THEN 2 ELSE 1 END WHERE per_user_limit IS NULL;
ALTER TABLE vouchers MODIFY COLUMN per_user_limit TINYINT UNSIGNED NOT NULL DEFAULT 1;
-- Keep previous uses; allow separate orders to consume a second allowance.
ALTER TABLE voucher_usage ADD UNIQUE INDEX IF NOT EXISTS unique_voucher_user_order (voucher_id,user_id,order_id);
ALTER TABLE voucher_usage DROP INDEX IF EXISTS unique_user_voucher;
