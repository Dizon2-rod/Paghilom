<?php
/**
 * Paghilom Cafe Management System - Comprehensive Test Script
 * Run this script to verify all components are working correctly
 */

// Web access: require logged-in admin; CLI access: allowed without session.
if (PHP_SAPI !== 'cli') {
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../config/auth.php';
    require_admin();
} else {
    // When run via CLI, bootstrap app config only
    require_once __DIR__ . '/../config.php';
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$tests_passed = 0;
$tests_failed = 0;
$errors = [];

echo "<html><head><title>System Test</title><style>
body{font-family:sans-serif;padding:20px;background:#f5f5f5;}
.test{padding:10px;margin:10px 0;border-radius:5px;}
.pass{background:#d4edda;color:#155724;border:1px solid #c3e6cb;}
.fail{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;}
h1{color:#333;}
.summary{padding:15px;margin:20px 0;border-radius:5px;font-size:18px;font-weight:bold;}
.summary.good{background:#d4edda;color:#155724;}
.summary.bad{background:#f8d7da;color:#721c24;}
</style></head><body>";

echo "<h1>🧪 Paghilom Cafe System Test</h1>";
echo "<p>Testing all critical components...</p>";

// Test 1: Database Connection
try {
    if ($mysqli->ping()) {
        echo "<div class='test pass'>✓ Database connection successful</div>";
        $tests_passed++;
    } else {
        echo "<div class='test fail'>✗ Database connection failed</div>";
        $tests_failed++;
        $errors[] = "Database connection failed";
    }
} catch (Exception $e) {
    echo "<div class='test fail'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
    $tests_failed++;
    $errors[] = "Database error: " . $e->getMessage();
}

// Test 2: Check Required Tables
$required_tables = [
    'users', 'categories', 'products', 'product_images', 'orders', 'order_items',
    'settings', 'pages', 'addons', 'milks', 'stores', 'rewards', 'clients',
    'stock_movements', 'login_attempts', 'point_transactions'
];

$missing_tables = [];
foreach ($required_tables as $table) {
    $result = $mysqli->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        $missing_tables[] = $table;
    }
}

if (empty($missing_tables)) {
    echo "<div class='test pass'>✓ All required tables exist (" . count($required_tables) . " tables)</div>";
    $tests_passed++;
} else {
    echo "<div class='test fail'>✗ Missing tables: " . implode(', ', $missing_tables) . "</div>";
    $tests_failed++;
    $errors[] = "Missing tables: " . implode(', ', $missing_tables);
}

// Test 3: Check Configuration Functions
$required_functions = [
    'is_logged_in', 'is_admin', 'is_staff', 'require_login', 
    'require_admin', 'csrf_field', 'csrf_check', 'e',
    'generate_token', 'validate_password_strength', 'get_setting', 'set_setting'
];

$missing_functions = [];
foreach ($required_functions as $func) {
    if (!function_exists($func)) {
        $missing_functions[] = $func;
    }
}

if (empty($missing_functions)) {
    echo "<div class='test pass'>✓ All required functions exist (" . count($required_functions) . " functions)</div>";
    $tests_passed++;
} else {
    echo "<div class='test fail'>✗ Missing functions: " . implode(', ', $missing_functions) . "</div>";
    $tests_failed++;
    $errors[] = "Missing functions: " . implode(', ', $missing_functions);
}

// Test 4: Check Helper Functions File
if (file_exists(__DIR__ . '/includes/helpers.php')) {
    require_once __DIR__ . '/includes/helpers.php';
    
    $helper_functions = [
        'upload_product_image', 'resize_image', 'format_phone', 
        'generate_order_code', 'generate_voucher_code', 'is_low_stock',
        'get_status_badge_color', 'format_date'
    ];
    
    $missing_helpers = [];
    foreach ($helper_functions as $func) {
        if (!function_exists($func)) {
            $missing_helpers[] = $func;
        }
    }
    
    if (empty($missing_helpers)) {
        echo "<div class='test pass'>✓ All helper functions exist (" . count($helper_functions) . " helpers)</div>";
        $tests_passed++;
    } else {
        echo "<div class='test fail'>✗ Missing helper functions: " . implode(', ', $missing_helpers) . "</div>";
        $tests_failed++;
        $errors[] = "Missing helper functions: " . implode(', ', $missing_helpers);
    }
} else {
    echo "<div class='test fail'>✗ helpers.php file not found</div>";
    $tests_failed++;
    $errors[] = "helpers.php file not found";
}

// Test 5: Check Admin User Exists
$admin_check = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
$admin_count = $admin_check->fetch_assoc()['count'];

if ($admin_count > 0) {
    echo "<div class='test pass'>✓ Admin users exist ($admin_count admin(s))</div>";
    $tests_passed++;
} else {
    echo "<div class='test fail'>✗ No admin users found</div>";
    $tests_failed++;
    $errors[] = "No admin users found";
}

// Test 6: Check Seed Data
$categories = $mysqli->query("SELECT COUNT(*) as count FROM categories")->fetch_assoc()['count'];
$settings = $mysqli->query("SELECT COUNT(*) as count FROM settings")->fetch_assoc()['count'];
$addons = $mysqli->query("SELECT COUNT(*) as count FROM addons")->fetch_assoc()['count'];
$milks = $mysqli->query("SELECT COUNT(*) as count FROM milks")->fetch_assoc()['count'];
$stores = $mysqli->query("SELECT COUNT(*) as count FROM stores")->fetch_assoc()['count'];

if ($categories > 0 && $settings > 0 && $addons > 0 && $milks > 0 && $stores > 0) {
    echo "<div class='test pass'>✓ Seed data loaded (Categories: $categories, Settings: $settings, Addons: $addons, Milks: $milks, Stores: $stores)</div>";
    $tests_passed++;
} else {
    echo "<div class='test fail'>✗ Incomplete seed data (Categories: $categories, Settings: $settings, Addons: $addons, Milks: $milks, Stores: $stores)</div>";
    $tests_failed++;
    $errors[] = "Incomplete seed data";
}

// Test 7: Check Critical Files Exist
$critical_files = [
    'config.php', 'index.php', 'login.php', 'register.php', 'cart.php', 'checkout.php',
    'admin/index.php', 'admin/login.php', 'admin/products.php', 'admin/inventory.php',
    'partials/header.php', 'partials/footer.php', 'partials/navbar.php',
    'includes/helpers.php', 'points.php'
];

$missing_files = [];
foreach ($critical_files as $file) {
    if (!file_exists(__DIR__ . '/' . $file)) {
        $missing_files[] = $file;
    }
}

if (empty($missing_files)) {
    echo "<div class='test pass'>✓ All critical files exist (" . count($critical_files) . " files)</div>";
    $tests_passed++;
} else {
    echo "<div class='test fail'>✗ Missing files: " . implode(', ', $missing_files) . "</div>";
    $tests_failed++;
    $errors[] = "Missing files: " . implode(', ', $missing_files);
}

// Test 8: Check Upload Directories
$upload_dirs = [
    'uploads', 
    'uploads/products', 
    'uploads/gallery',
    'assets/img'
];

$missing_dirs = [];
$readonly_dirs = [];
foreach ($upload_dirs as $dir) {
    $full_path = __DIR__ . '/' . $dir;
    if (!is_dir($full_path)) {
        $missing_dirs[] = $dir;
    } elseif (!is_writable($full_path)) {
        $readonly_dirs[] = $dir;
    }
}

if (empty($missing_dirs) && empty($readonly_dirs)) {
    echo "<div class='test pass'>✓ All upload directories exist and are writable</div>";
    $tests_passed++;
} else {
    $msg = [];
    if (!empty($missing_dirs)) $msg[] = "Missing: " . implode(', ', $missing_dirs);
    if (!empty($readonly_dirs)) $msg[] = "Read-only: " . implode(', ', $readonly_dirs);
    echo "<div class='test fail'>✗ Directory issues: " . implode('; ', $msg) . "</div>";
    $tests_failed++;
    $errors[] = "Directory issues: " . implode('; ', $msg);
}

// Test 9: Check Points System
if (file_exists(__DIR__ . '/points.php')) {
    require_once __DIR__ . '/points.php';
    
    if (function_exists('points_balance') && function_exists('award_points_for_order')) {
        echo "<div class='test pass'>✓ Points system functions available</div>";
        $tests_passed++;
    } else {
        echo "<div class='test fail'>✗ Points system functions missing</div>";
        $tests_failed++;
        $errors[] = "Points system functions missing";
    }
} else {
    echo "<div class='test fail'>✗ points.php file not found</div>";
    $tests_failed++;
    $errors[] = "points.php file not found";
}

// Test 10: Check Database Indexes
$indexes_check = $mysqli->query("
    SELECT COUNT(*) as count 
    FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name IN ('products', 'orders', 'users', 'order_items')
");
$index_count = $indexes_check->fetch_assoc()['count'];

if ($index_count > 0) {
    echo "<div class='test pass'>✓ Database indexes configured ($index_count indexes)</div>";
    $tests_passed++;
} else {
    echo "<div class='test fail'>✗ No database indexes found</div>";
    $tests_failed++;
    $errors[] = "No database indexes found";
}

// Summary
$total_tests = $tests_passed + $tests_failed;
$pass_rate = ($total_tests > 0) ? round(($tests_passed / $total_tests) * 100, 2) : 0;

echo "<hr>";
echo "<div class='summary " . ($tests_failed == 0 ? "good" : "bad") . "'>";
echo "Test Results: $tests_passed/$total_tests passed ($pass_rate%)";
echo "</div>";

if ($tests_failed == 0) {
    echo "<div class='test pass' style='font-size:18px;'>";
    echo "🎉 <strong>All tests passed!</strong> The system is ready to use.";
    echo "</div>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li>Access the admin panel at <a href='admin/login.php'>admin/login.php</a></li>";
    echo "<li>Default admin credentials: admin@paghilom.local / ChangeMe123!</li>";
    echo "<li>Change the default password immediately</li>";
    echo "<li>Add products and test the complete order flow</li>";
    echo "<li>Configure email settings for notifications</li>";
    echo "</ul>";
} else {
    echo "<div class='test fail' style='font-size:18px;'>";
    echo "⚠️ <strong>Some tests failed.</strong> Please review the errors above.";
    echo "</div>";
    echo "<p><strong>Errors Found:</strong></p>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
}

echo "</body></html>";
?>
