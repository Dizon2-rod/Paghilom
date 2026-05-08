-- QR Code Security Implementation Migration
-- Add columns for secure QR code validation
-- Run this SQL in your paghilom_cafe database

-- ==================================================
-- Add QR Code column to orders table
-- ==================================================

-- Check if column doesn't exist before adding
ALTER TABLE `orders` 
ADD COLUMN IF NOT EXISTS `order_code` VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'Unique QR code for this order',
ADD COLUMN IF NOT EXISTS `paid_at` DATETIME NULL COMMENT 'Timestamp when order was paid',
ADD UNIQUE INDEX IF NOT EXISTS `idx_order_code` (`order_code`);

-- ==================================================
-- Add QR Code column to vouchers table
-- ==================================================

-- Check if table exists first
CREATE TABLE IF NOT EXISTS `vouchers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `voucher_code` VARCHAR(20) NOT NULL DEFAULT '',
  `points_required` INT(11) NOT NULL DEFAULT 0,
  `status` ENUM('active', 'redeemed', 'expired') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME NULL,
  `redeemed_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_voucher_code` (`voucher_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- If table already exists, add columns if they don't exist
ALTER TABLE `vouchers` 
ADD COLUMN IF NOT EXISTS `voucher_code` VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'Unique QR code for this voucher',
ADD COLUMN IF NOT EXISTS `redeemed_at` DATETIME NULL COMMENT 'Timestamp when voucher was redeemed',
ADD UNIQUE INDEX IF NOT EXISTS `idx_voucher_code` (`voucher_code`);

-- ==================================================
-- Create audit log table for QR scans (optional)
-- ==================================================

CREATE TABLE IF NOT EXISTS `qr_scan_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `qr_code` VARCHAR(50) NOT NULL,
  `scan_type` ENUM('order', 'reward', 'invalid') NOT NULL,
  `scan_location` VARCHAR(50) NULL COMMENT 'POS or Kiosk',
  `status` VARCHAR(20) NOT NULL COMMENT 'success, failed, already_used, expired',
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `scanned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_qr_code` (`qr_code`),
  INDEX `idx_scanned_at` (`scanned_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================================================
-- Update existing orders with QR codes (one-time)
-- ==================================================

-- Generate QR codes for existing orders that don't have one
UPDATE `orders` 
SET `order_code` = CONCAT('ORD', UPPER(SUBSTRING(MD5(CONCAT(id, NOW(), RAND())), 1, 8)))
WHERE `order_code` = '' OR `order_code` IS NULL;

-- ==================================================
-- Verification queries
-- ==================================================

-- Check if columns were added successfully
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT 
FROM 
    INFORMATION_SCHEMA.COLUMNS 
WHERE 
    TABLE_SCHEMA = 'paghilom_cafe' 
    AND TABLE_NAME = 'orders' 
    AND COLUMN_NAME IN ('order_code', 'paid_at');

SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT 
FROM 
    INFORMATION_SCHEMA.COLUMNS 
WHERE 
    TABLE_SCHEMA = 'paghilom_cafe' 
    AND TABLE_NAME = 'vouchers' 
    AND COLUMN_NAME IN ('voucher_code', 'redeemed_at');

-- ==================================================
-- Sample queries to test QR code functionality
-- ==================================================

-- View orders with QR codes
SELECT id, order_code, customer_name, total_amount, payment_status, status 
FROM orders 
WHERE order_code != '' 
ORDER BY id DESC 
LIMIT 10;

-- View vouchers with QR codes
SELECT id, voucher_code, points_required, status, expires_at, redeemed_at 
FROM vouchers 
WHERE voucher_code != '' 
ORDER BY id DESC 
LIMIT 10;

-- Check for duplicate QR codes (should return 0 rows)
SELECT order_code, COUNT(*) as count 
FROM orders 
WHERE order_code != '' 
GROUP BY order_code 
HAVING count > 1;

SELECT voucher_code, COUNT(*) as count 
FROM vouchers 
WHERE voucher_code != '' 
GROUP BY voucher_code 
HAVING count > 1;
