-- Correct only the four untouched starter campaign limits. Preserve actual usage and custom campaigns.
ALTER TABLE vouchers ALTER COLUMN max_usage SET DEFAULT 100;
UPDATE vouchers SET max_usage=100
WHERE code IN ('GIADUNG20','GIADUNG50','GIAM10','WELCOME100')
  AND max_usage=999999 AND used_count<=100;
