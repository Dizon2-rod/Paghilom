<?php
/**
 * Paghilom Cafe Setup Script
 * Run this script to set up the database and verify installation
 */

// Check PHP version
if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    die('PHP 8.0 or higher is required. Current version: ' . PHP_VERSION);
}

// Check required extensions
$required_extensions = ['mysqli', 'session', 'json', 'fileinfo'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

if (!empty($missing_extensions)) {
    die('Missing required PHP extensions: ' . implode(', ', $missing_extensions));
}

echo "<h1>Paghilom Cafe Setup</h1>\n";
echo "<h2>System Check</h2>\n";
echo "✅ PHP Version: " . PHP_VERSION . " (Required: 8.0+)\n<br>";
echo "✅ Required Extensions: " . implode(', ', $required_extensions) . "\n<br>";

// Check file permissions
$writable_dirs = ['uploads', 'uploads/products'];
echo "<h2>File Permissions</h2>\n";

foreach ($writable_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    if (is_writable($dir)) {
        echo "✅ $dir is writable\n<br>";
    } else {
        echo "❌ $dir is not writable (chmod 755 required)\n<br>";
    }
}

// Check database connection
echo "<h2>Database Connection</h2>\n";

try {
    require_once 'config.php';
    
    if ($mysqli->connect_errno) {
        echo "❌ Database connection failed: " . $mysqli->connect_error . "\n<br>";
    } else {
        echo "✅ Database connection successful\n<br>";
        
        // Check if tables exist
        $tables = ['users', 'products', 'categories', 'settings'];
        $missing_tables = [];
        
        foreach ($tables as $table) {
            $result = $mysqli->query("SHOW TABLES LIKE '$table'");
            if ($result->num_rows == 0) {
                $missing_tables[] = $table;
            }
        }
        
        if (empty($missing_tables)) {
            echo "✅ All required tables exist\n<br>";
        } else {
            echo "❌ Missing tables: " . implode(', ', $missing_tables) . "\n<br>";
            echo "Please import database/schema_unified.sql\n<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Configuration error: " . $e->getMessage() . "\n<br>";
}

echo "<h2>Next Steps</h2>\n";
echo "1. Import database/schema_unified.sql into your database\n<br>";
echo "2. Update config.php with your database credentials\n<br>";
echo "3. Visit /admin/login.php to access admin panel\n<br>";
echo "4. Default admin login: admin@paghilom.local / ChangeMe123!\n<br>";
echo "5. Visit /register.php to create customer accounts\n<br>";

echo "<h2>Security Reminders</h2>\n";
echo "⚠️ Change the default admin password immediately\n<br>";
echo "⚠️ Use HTTPS in production\n<br>";
echo "⚠️ Set proper file permissions (755 for directories, 644 for files)\n<br>";
echo "⚠️ Keep PHP and dependencies updated\n<br>";

echo "<p><strong>Setup complete!</strong> You can now delete this setup.php file for security.</p>\n";
?>
