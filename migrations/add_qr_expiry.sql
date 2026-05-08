-- Add QR code expiry tracking to orders table
-- Orders QR codes are valid for 3 hours
ALTER TABLE `orders` 
ADD COLUMN `qr_expires_at` DATETIME DEFAULT NULL COMMENT '3 hour expiry for order QR codes' AFTER `created_at`;

-- Update existing orders to have 3-hour expiry from creation
UPDATE `orders` 
SET `qr_expires_at` = DATE_ADD(`created_at`, INTERVAL 3 HOUR) 
WHERE `qr_expires_at` IS NULL;

-- Ensure vouchers table has expires_at column (should already exist based on code)
-- If not, add it with 30-minute default
-- ALTER TABLE `vouchers` 
-- ADD COLUMN `expires_at` DATETIME DEFAULT NULL COMMENT '30 minute expiry for redemption QR codes' AFTER `created_at`;

-- Update existing vouchers to have 30-minute expiry from creation
UPDATE `vouchers` 
SET `expires_at` = DATE_ADD(`created_at`, INTERVAL 30 MINUTE) 
WHERE `expires_at` IS NULL;

-- Add index for faster expiry queries
ALTER TABLE `orders` ADD INDEX `idx_qr_expires_at` (`qr_expires_at`);
ALTER TABLE `vouchers` ADD INDEX `idx_expires_at` (`expires_at`);
