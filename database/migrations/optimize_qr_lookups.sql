-- Optimize QR Code Lookups
-- Adds indexes for instant QR code validation

-- Add index on orders.order_code for faster lookup
ALTER TABLE `orders` 
ADD INDEX IF NOT EXISTS `idx_order_code` (`order_code`);

-- Add composite index for order validation (code + status)
ALTER TABLE `orders` 
ADD INDEX IF NOT EXISTS `idx_order_validation` (`order_code`, `payment_status`, `status`);

-- Add index on vouchers.voucher_code for faster lookup
ALTER TABLE `vouchers` 
ADD INDEX IF NOT EXISTS `idx_voucher_code` (`voucher_code`);

-- Add composite index for voucher validation (code + status + expiry)
ALTER TABLE `vouchers` 
ADD INDEX IF NOT EXISTS `idx_voucher_validation` (`voucher_code`, `status`, `expires_at`);

-- Ensure order_code column exists and is properly sized
ALTER TABLE `orders` 
MODIFY COLUMN `order_code` VARCHAR(20) DEFAULT NULL COMMENT 'Unique QR code for order';

-- Ensure voucher_code column exists and is properly sized  
ALTER TABLE `vouchers` 
MODIFY COLUMN `voucher_code` VARCHAR(20) DEFAULT NULL COMMENT 'Unique QR code for voucher';

-- Add scan_logs table for tracking QR scans (optional but recommended)
CREATE TABLE IF NOT EXISTS `qr_scan_logs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `type` ENUM('order', 'reward', 'unknown') DEFAULT 'unknown',
  `status` ENUM('success', 'failed', 'expired', 'invalid') DEFAULT 'failed',
  `error_message` TEXT DEFAULT NULL,
  `scanned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_code` (`code`),
  INDEX `idx_scanned_at` (`scanned_at`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
