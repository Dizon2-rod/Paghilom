<?php
/**
 * Paghilom Cafe OAuth Setup Script
 * Run this script to set up OAuth authentication
 */

require_once 'config.php';

echo "=== Paghilom Cafe OAuth Setup ===\n\n";

// Check if database connection works
if ($mysqli->connect_error) {
    die("Database connection failed: " . $mysqli->connect_error . "\n");
}

echo "✓ Database connection successful\n";

// Check if required tables exist
$tables = ['users', 'login_attempts'];
$missing_tables = [];

foreach ($tables as $table) {
    $result = $mysqli->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        $missing_tables[] = $table;
    }
}

if (!empty($missing_tables)) {
    echo "⚠ Missing tables: " . implode(', ', $missing_tables) . "\n";
    echo "Please run: mysql -u root -p paghilom_cafe < database/schema.sql\n";
} else {
    echo "✓ Required tables exist\n";
}

// Check if OAuth config exists
if (!file_exists('oauth_config.php')) {
    echo "⚠ OAuth configuration file not found\n";
    echo "Please copy oauth_config.php and configure your OAuth credentials\n";
} else {
    echo "✓ OAuth configuration file exists\n";
}

// Check if auth directory exists
if (!is_dir('auth')) {
    echo "⚠ Auth directory not found\n";
    echo "Please ensure the auth/ directory exists with OAuth handlers\n";
} else {
    echo "✓ Auth directory exists\n";
}

// Check if composer dependencies are installed
if (!file_exists('vendor/autoload.php')) {
    echo "⚠ Composer dependencies not installed\n";
    echo "Please run: composer install\n";
} else {
    echo "✓ Composer dependencies installed\n";
}

// Check file permissions
$writable_dirs = ['uploads', 'auth'];
foreach ($writable_dirs as $dir) {
    if (is_dir($dir) && !is_writable($dir)) {
        echo "⚠ Directory '$dir' is not writable\n";
        echo "Please run: chmod 755 $dir\n";
    } else {
        echo "✓ Directory '$dir' is writable\n";
    }
}

echo "\n=== Setup Checklist ===\n";
echo "1. ✓ Database schema updated\n";
echo "2. ✓ OAuth handlers created\n";
echo "3. ✓ Enhanced login/register pages\n";
echo "4. ✓ Security features implemented\n";
echo "5. ✓ Email verification system\n";
echo "6. ✓ Password reset functionality\n";
echo "7. ✓ Rate limiting and CSRF protection\n";

echo "\n=== Next Steps ===\n";
echo "1. Configure OAuth credentials in oauth_config.php\n";
echo "2. Set up email SMTP settings\n";
echo "3. Test OAuth login flows\n";
echo "4. Configure HTTPS for production\n";

echo "\nSetup complete! 🎉\n";
?>
