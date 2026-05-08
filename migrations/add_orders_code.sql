-- Ensure orders.code exists, is populated, and unique
START TRANSACTION;

-- 1) Add code column if missing
ALTER TABLE `orders`
  ADD COLUMN `code` VARCHAR(32) NULL AFTER `id`;

-- 2) Backfill from existing columns if present
UPDATE `orders` SET `code` = `order_code` WHERE `code` IS NULL AND `order_code` IS NOT NULL;
UPDATE `orders` SET `code` = `order_number` WHERE `code` IS NULL AND `order_number` IS NOT NULL;

-- 3) Generate codes for any remaining NULLs (deterministic from id+created_at)
UPDATE `orders`
SET `code` = CONCAT('ORD', UPPER(SUBSTRING(MD5(CONCAT(`id`, '-', COALESCE(DATE_FORMAT(`created_at`,'%Y%m%d%H%i%s'),'0'))), 1, 8)))
WHERE `code` IS NULL OR `code` = '';

-- 4) Resolve any accidental duplicates by appending ID suffix
UPDATE `orders` o
JOIN (
  SELECT `code`, COUNT(*) c FROM `orders` GROUP BY `code` HAVING c > 1
) d ON d.`code` = o.`code`
SET o.`code` = CONCAT(o.`code`, LPAD(o.`id` % 1000, 3, '0'));

-- 5) Enforce NOT NULL and UNIQUE constraint
ALTER TABLE `orders`
  MODIFY `code` VARCHAR(32) NOT NULL,
  ADD UNIQUE KEY `uq_orders_code` (`code`),
  ADD INDEX `idx_orders_code` (`code`);

COMMIT;

-- Optional: set QR expiry if you want 3-hour validity by default
-- ALTER TABLE `orders` ADD COLUMN `qr_expires_at` DATETIME NULL AFTER `created_at`;
-- UPDATE `orders` SET `qr_expires_at` = DATE_ADD(`created_at`, INTERVAL 3 HOUR) WHERE `qr_expires_at` IS NULL;