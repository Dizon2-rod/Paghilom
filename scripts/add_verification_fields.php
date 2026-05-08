<?php
require_once 'config.php';

echo "Adding email verification fields to users table...\n";

// Add verification_code and verification_code_expiry columns if they don't exist
$sql = "ALTER TABLE users 
        ADD COLUMN IF NOT EXISTS verification_code VARCHAR(10) NULL,
        ADD COLUMN IF NOT EXISTS verification_code_expiry DATETIME NULL,
        ADD COLUMN IF NOT EXISTS email_verified TINYINT(1) DEFAULT 0";

if ($mysqli->query($sql)) {
    echo "✓ Verification fields added successfully!\n";
} else {
    echo "Error: " . $mysqli->error . "\n";
}

// Check if columns exist
$result = $mysqli->query("SHOW COLUMNS FROM users LIKE 'verification_code'");
if ($result->num_rows > 0) {
    echo "✓ verification_code column exists\n";
}

$result = $mysqli->query("SHOW COLUMNS FROM users LIKE 'email_verified'");
if ($result->num_rows > 0) {
    echo "✓ email_verified column exists\n";
}

echo "\nDatabase migration completed!\n";
?>
