<?php
/**
 * Paghilom Cafe - Comprehensive Error Checker and Fixer
 * This script checks for all possible errors and attempts to fix them
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<html><head><title>System Check & Fix</title>";
echo "<style>
body{font-family:sans-serif;padding:20px;background:#f5f5f5;}
.check{padding:8px;margin:8px 0;border-radius:5px;border-left:4px solid #ccc;}
.ok{background:#d4edda;border-color:#28a745;}
.warn{background:#fff3cd;border-color:#ffc107;}
.error{background:#f8d7da;border-color:#dc3545;}
.fix{background:#d1ecf1;border-color:#17a2b8;}
h2{color:#333;border-bottom:2px solid #007bff;padding-bottom:10px;}
.section{background:white;padding:20px;margin:20px 0;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);}
pre{background:#f8f9fa;padding:10px;border-radius:5px;overflow-x:auto;}
</style></head><body>";

echo "<h1>🔧 Paghilom Cafe System Check & Fix</h1>";

$fixes_applied = [];
$errors_found = [];
$warnings_found = [];

// ============= DATABASE CHECKS =============
echo "<div class='section'><h2>📊 Database Checks</h2>";

try {
    $mysqli = new mysqli('localhost', 'root', '', 'paghilom_cafe');
    
    if ($mysqli->connect_error) {
        echo "<div class='check error'>✗ Database connection failed: " . htmlspecialchars($mysqli->connect_error) . "</div>";
        $errors_found[] = "Database connection failed";
    } else {
        echo "<div class='check ok'>✓ Database connection successful</div>";
        
        // Check character set
        $result = $mysqli->query("SELECT @@character_set_database, @@collation_database");
        $charset = $result->fetch_row();
        if ($charset[0] !== 'utf8mb4') {
            echo "<div class='check warn'>⚠ Database charset is not utf8mb4 (current: {$charset[0]})</div>";
            $warnings_found[] = "Database charset should be utf8mb4";
        } else {
            echo "<div class='check ok'>✓ Database charset: utf8mb4</div>";
        }
        
        // Check for required tables
        $required_tables = [
            'users', 'categories', 'products', 'product_images', 'orders', 'order_items',
            'order_item_options', 'settings', 'pages', 'addons', 'milks', 'product_addons',
            'product_milks', 'stores', 'rewards', 'clients', 'vouchers', 'stock_movements',
            'login_attempts', 'point_transactions', 'loyalty_ledger', 'promos',
            'ingredients', 'product_recipes', 'ingredient_movements',
            'reward_catalog', 'redemptions', 'point_promos', 'gallery'
        ];
        
        $existing_tables = [];
        $result = $mysqli->query("SHOW TABLES");
        while ($row = $result->fetch_row()) {
            $existing_tables[] = $row[0];
        }
        
        $missing_tables = array_diff($required_tables, $existing_tables);
        
        if (empty($missing_tables)) {
            echo "<div class='check ok'>✓ All " . count($required_tables) . " required tables exist</div>";
        } else {
            echo "<div class='check error'>✗ Missing tables: " . implode(', ', $missing_tables) . "</div>";
            $errors_found[] = "Missing database tables: " . implode(', ', $missing_tables);
            echo "<div class='check fix'>→ Fix: Run database/add_missing_tables.sql</div>";
        }
        
        // Check for orphaned records (data integrity)
        $integrity_checks = [
            "SELECT COUNT(*) FROM order_items WHERE product_id NOT IN (SELECT id FROM products)" => "Orphaned order items",
            "SELECT COUNT(*) FROM product_addons WHERE product_id NOT IN (SELECT id FROM products)" => "Orphaned product addons",
            "SELECT COUNT(*) FROM stock_movements WHERE product_id NOT IN (SELECT id FROM products)" => "Orphaned stock movements",
        ];
        
        foreach ($integrity_checks as $query => $desc) {
            $result = $mysqli->query($query);
            if ($result) {
                $count = $result->fetch_row()[0];
                if ($count > 0) {
                    echo "<div class='check warn'>⚠ Found $count $desc</div>";
                    $warnings_found[] = "$count $desc";
                }
            }
        }
        
        echo "<div class='check ok'>✓ Data integrity checks completed</div>";
    }
} catch (Exception $e) {
    echo "<div class='check error'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
    $errors_found[] = "Database exception: " . $e->getMessage();
}

echo "</div>";

// ============= FILE SYSTEM CHECKS =============
echo "<div class='section'><h2>📁 File System Checks</h2>";

$critical_files = [
    'config.php' => 'Main configuration',
    'index.php' => 'Homepage',
    'login.php' => 'Customer login',
    'register.php' => 'Registration',
    'cart.php' => 'Shopping cart',
    'checkout.php' => 'Checkout',
    'product.php' => 'Product details',
    'points.php' => 'Points system',
    'includes/config.php' => 'Database config',
    'includes/helpers.php' => 'Helper functions',
    'partials/header.php' => 'Header template',
    'partials/footer.php' => 'Footer template',
    'partials/navbar.php' => 'Navigation bar',
    'admin/index.php' => 'Admin dashboard',
    'admin/login.php' => 'Admin login',
    'admin/products.php' => 'Product management',
    'admin/inventory.php' => 'Inventory management',
    'admin/orders.php' => 'Order management',
    'pos/dashboard.php' => 'POS dashboard',
    'assets/js/app.js' => 'Main JavaScript',
    'assets/css/styles.css' => 'Main stylesheet',
];

$missing_files = [];
foreach ($critical_files as $file => $desc) {
    if (!file_exists(__DIR__ . '/' . $file)) {
        echo "<div class='check error'>✗ Missing: $file ($desc)</div>";
        $missing_files[] = $file;
        $errors_found[] = "Missing file: $file";
    }
}

if (empty($missing_files)) {
    echo "<div class='check ok'>✓ All " . count($critical_files) . " critical files exist</div>";
} else {
    echo "<div class='check error'>✗ " . count($missing_files) . " critical files missing</div>";
}

// Check directories
$required_dirs = [
    'uploads' => 'Main uploads directory',
    'uploads/products' => 'Product images',
    'uploads/gallery' => 'Gallery images',
    'assets' => 'Static assets',
    'assets/css' => 'Stylesheets',
    'assets/js' => 'JavaScript files',
    'assets/img' => 'Images',
    'admin' => 'Admin panel',
    'pos' => 'Point of Sale',
    'partials' => 'Template parts',
    'includes' => 'Include files',
    'database' => 'Database files',
];

$created_dirs = [];
foreach ($required_dirs as $dir => $desc) {
    $full_path = __DIR__ . '/' . $dir;
    if (!is_dir($full_path)) {
        if (mkdir($full_path, 0755, true)) {
            echo "<div class='check fix'>✓ Created directory: $dir ($desc)</div>";
            $created_dirs[] = $dir;
            $fixes_applied[] = "Created directory: $dir";
        } else {
            echo "<div class='check error'>✗ Failed to create: $dir</div>";
            $errors_found[] = "Cannot create directory: $dir";
        }
    } else {
        // Check if writable
        if (!is_writable($full_path)) {
            echo "<div class='check warn'>⚠ Directory not writable: $dir</div>";
            $warnings_found[] = "Directory not writable: $dir";
        }
    }
}

if (!empty($created_dirs)) {
    echo "<div class='check fix'>✓ Created " . count($created_dirs) . " missing directories</div>";
}

echo "</div>";

// ============= PHP SYNTAX CHECKS =============
echo "<div class='section'><h2>🔍 PHP Syntax Checks</h2>";

$php_files_to_check = [
    'config.php',
    'points.php',
    'includes/helpers.php',
    'admin/inventory.php',
    'admin/products.php',
    'login.php',
    'register.php',
    'checkout.php',
];

$syntax_errors = [];
foreach ($php_files_to_check as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        $output = [];
        $return_var = 0;
        exec("C:\\xampp\\php\\php.exe -l \"" . __DIR__ . "/$file\" 2>&1", $output, $return_var);
        
        if ($return_var !== 0) {
            echo "<div class='check error'>✗ Syntax error in $file</div>";
            echo "<pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
            $syntax_errors[] = $file;
            $errors_found[] = "Syntax error in $file";
        }
    }
}

if (empty($syntax_errors)) {
    echo "<div class='check ok'>✓ No syntax errors found in " . count($php_files_to_check) . " checked files</div>";
} else {
    echo "<div class='check error'>✗ Found syntax errors in " . count($syntax_errors) . " files</div>";
}

echo "</div>";

// ============= CONFIGURATION CHECKS =============
echo "<div class='section'><h2>⚙️ Configuration Checks</h2>";

if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
    
    $required_functions = [
        'is_logged_in', 'is_admin', 'is_staff', 'require_login', 'require_admin',
        'csrf_field', 'csrf_check', 'e', 'generate_token', 'validate_password_strength',
        'get_setting', 'set_setting'
    ];
    
    $missing_functions = [];
    foreach ($required_functions as $func) {
        if (!function_exists($func)) {
            $missing_functions[] = $func;
        }
    }
    
    if (empty($missing_functions)) {
        echo "<div class='check ok'>✓ All " . count($required_functions) . " required functions exist</div>";
    } else {
        echo "<div class='check error'>✗ Missing functions: " . implode(', ', $missing_functions) . "</div>";
        $errors_found[] = "Missing functions: " . implode(', ', $missing_functions);
    }
    
    // Check session
    if (session_status() === PHP_SESSION_ACTIVE) {
        echo "<div class='check ok'>✓ Session is active</div>";
    } else {
        echo "<div class='check warn'>⚠ Session not started</div>";
        $warnings_found[] = "Session not started";
    }
    
    // Check CSRF token
    if (isset($_SESSION['csrf'])) {
        echo "<div class='check ok'>✓ CSRF token exists</div>";
    } else {
        echo "<div class='check warn'>⚠ CSRF token not set</div>";
        $warnings_found[] = "CSRF token not set";
    }
}

echo "</div>";

// ============= ASSETS CHECKS =============
echo "<div class='section'><h2>🎨 Assets Checks</h2>";

// Check if main CSS exists
if (!file_exists(__DIR__ . '/assets/css/styles.css')) {
    echo "<div class='check warn'>⚠ Main stylesheet missing: assets/css/styles.css</div>";
    echo "<div class='check fix'>Creating basic stylesheet...</div>";
    
    $basic_css = "/* Paghilom Cafe - Main Stylesheet */

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f8f9fa;
}

.hero {
    min-height: 500px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-size: cover;
    background-position: center;
}

.card.product {
    transition: transform 0.2s;
}

.card.product:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.badge-float {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #ff6b6b;
    color: white;
    padding: 5px 10px;
    font-size: 12px;
}

.navbar.scrolled {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.password-strength-bar {
    height: 4px;
    background: #e9ecef;
    border-radius: 2px;
    overflow: hidden;
    margin-top: 5px;
}

.password-strength-bar.strength-weak { background: #dc3545; width: 25%; }
.password-strength-bar.strength-fair { background: #ffc107; width: 50%; }
.password-strength-bar.strength-good { background: #17a2b8; width: 75%; }
.password-strength-bar.strength-strong { background: #28a745; width: 100%; }
";
    
    if (!is_dir(__DIR__ . '/assets/css')) {
        mkdir(__DIR__ . '/assets/css', 0755, true);
    }
    
    if (file_put_contents(__DIR__ . '/assets/css/styles.css', $basic_css)) {
        echo "<div class='check fix'>✓ Created basic stylesheet</div>";
        $fixes_applied[] = "Created assets/css/styles.css";
    }
} else {
    echo "<div class='check ok'>✓ Main stylesheet exists</div>";
}

// Check JavaScript
if (file_exists(__DIR__ . '/assets/js/app.js')) {
    echo "<div class='check ok'>✓ Main JavaScript file exists</div>";
} else {
    echo "<div class='check warn'>⚠ Main JavaScript missing</div>";
    $warnings_found[] = "assets/js/app.js missing";
}

// Check placeholder image script
if (file_exists(__DIR__ . '/assets/img/placeholder.php')) {
    echo "<div class='check ok'>✓ Placeholder image script exists</div>";
} else {
    echo "<div class='check warn'>⚠ Placeholder image script missing</div>";
    echo "<div class='check fix'>Creating placeholder script...</div>";
    
    if (!is_dir(__DIR__ . '/assets/img')) {
        mkdir(__DIR__ . '/assets/img', 0755, true);
    }
    
    $placeholder_php = "<?php
\$width = \$_GET['w'] ?? 400;
\$height = \$_GET['h'] ?? 400;
\$text = \$_GET['text'] ?? 'No Image';
\$bg = \$_GET['bg'] ?? 'cccccc';
\$color = \$_GET['color'] ?? '666666';

\$width = min(max((int)\$width, 50), 2000);
\$height = min(max((int)\$height, 50), 2000);

\$image = imagecreatetruecolor(\$width, \$height);
\$bgColor = imagecolorallocate(\$image, hexdec(substr(\$bg, 0, 2)), hexdec(substr(\$bg, 2, 2)), hexdec(substr(\$bg, 4, 2)));
\$textColor = imagecolorallocate(\$image, hexdec(substr(\$color, 0, 2)), hexdec(substr(\$color, 2, 2)), hexdec(substr(\$color, 4, 2)));

imagefilledrectangle(\$image, 0, 0, \$width, \$height, \$bgColor);

\$fontSize = min(\$width, \$height) / 10;
\$bbox = imagettfbbox(\$fontSize, 0, __DIR__ . '/../../uploads/arial.ttf', \$text);
if (!\$bbox) {
    \$textX = (\$width - (strlen(\$text) * \$fontSize * 0.6)) / 2;
    \$textY = (\$height + \$fontSize) / 2;
    imagestring(\$image, 5, \$textX, \$textY - 10, \$text, \$textColor);
} else {
    \$textWidth = \$bbox[2] - \$bbox[0];
    \$textHeight = \$bbox[1] - \$bbox[7];
    \$textX = (\$width - \$textWidth) / 2;
    \$textY = (\$height + \$textHeight) / 2;
    imagettftext(\$image, \$fontSize, 0, \$textX, \$textY, \$textColor, __DIR__ . '/../../uploads/arial.ttf', \$text);
}

header('Content-Type: image/png');
imagepng(\$image);
imagedestroy(\$image);
";
    
    if (file_put_contents(__DIR__ . '/assets/img/placeholder.php', $placeholder_php)) {
        echo "<div class='check fix'>✓ Created placeholder image script</div>";
        $fixes_applied[] = "Created assets/img/placeholder.php";
    }
}

echo "</div>";

// ============= SUMMARY =============
echo "<div class='section'>";
echo "<h2>📊 Summary</h2>";

$total_issues = count($errors_found) + count($warnings_found);
$total_fixes = count($fixes_applied);

if (count($errors_found) > 0) {
    echo "<div class='check error'><strong>✗ Errors Found: " . count($errors_found) . "</strong>";
    echo "<ul>";
    foreach ($errors_found as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul></div>";
}

if (count($warnings_found) > 0) {
    echo "<div class='check warn'><strong>⚠ Warnings: " . count($warnings_found) . "</strong>";
    echo "<ul>";
    foreach ($warnings_found as $warning) {
        echo "<li>" . htmlspecialchars($warning) . "</li>";
    }
    echo "</ul></div>";
}

if (count($fixes_applied) > 0) {
    echo "<div class='check fix'><strong>✓ Fixes Applied: " . count($fixes_applied) . "</strong>";
    echo "<ul>";
    foreach ($fixes_applied as $fix) {
        echo "<li>" . htmlspecialchars($fix) . "</li>";
    }
    echo "</ul></div>";
}

if (count($errors_found) === 0 && count($warnings_found) === 0) {
    echo "<div class='check ok' style='font-size:18px;padding:20px;'>";
    echo "<strong>🎉 PERFECT! No errors or warnings found!</strong><br>";
    echo "Your Paghilom Cafe system is fully operational and ready for production.";
    echo "</div>";
} else if (count($errors_found) === 0) {
    echo "<div class='check ok' style='font-size:16px;padding:15px;'>";
    echo "<strong>✓ No critical errors found.</strong><br>";
    echo "Only minor warnings detected. System is operational.";
    echo "</div>";
}

echo "<div style='margin-top:20px;padding:15px;background:#e7f3ff;border-radius:5px;'>";
echo "<strong>Next Steps:</strong><br>";
echo "1. <a href='test_system.php'>Run Full System Test</a><br>";
echo "2. <a href='admin/login.php'>Access Admin Panel</a><br>";
echo "3. <a href='index.php'>View Homepage</a><br>";
echo "4. Review the <a href='QUICK_START.md'>Quick Start Guide</a>";
echo "</div>";

echo "</div>";

echo "</body></html>";
?>
