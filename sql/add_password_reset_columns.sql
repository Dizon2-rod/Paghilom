-- Add password reset columns to users table if they don't exist
-- Run this SQL in phpMyAdmin or MySQL command line

-- Add password_reset_token column
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `password_reset_token` VARCHAR(255) NULL DEFAULT NULL 
AFTER `password_hash`;

-- Add password_reset_expires column
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `password_reset_expires` DATETIME NULL DEFAULT NULL 
AFTER `password_reset_token`;

-- Add index for faster token lookups
ALTER TABLE `users` 
ADD INDEX IF NOT EXISTS `idx_password_reset_token` (`password_reset_token`);

-- Add SMTP settings to settings table
INSERT INTO `settings` (`key`, `value`) VALUES 
('smtp_user', ''),
('smtp_pass', ''),
('smtp_from', 'noreply@paghilomcafe.com'),
('smtp_from_name', 'Paghilom Cafe')
ON DUPLICATE KEY UPDATE `key` = VALUES(`key`);

-- Verify the columns were added
DESCRIBE `users`;

-- Show current SMTP settings
SELECT * FROM `settings` WHERE `key` LIKE 'smtp%';
