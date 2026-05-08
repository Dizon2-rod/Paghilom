<?php
/**
 * Paghilom Café Management System - Comprehensive Test Script
 * 
 * Run this script to verify all components are working correctly
 */

require __DIR__ . '/config.php';
require_admin();

error_reporting(E_ALL);
ini_set('display_errors', 1);

$tests_passed = 0;
$tests_failed = 0;
$errors = [];

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>System Test - Paghilom Café</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
    <style>
        :root {
            --primary: #2A5618;
            --primary-light: #3a7020;
            --success: #198754;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #0dcaf0;
        }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: #f8f9fa;
            color: #212529;
            line-height: 1.6;
        }
        .test-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .test {
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            border-left: 4px solid #dee2e6;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .test:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .test.pass {
            border-left-color: var(--success);
            background-color: #f8f9fa;
        }
        .test.fail {
            border-left-color: var(--danger);
            background-color: #fff5f5;
        }
        .test-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .test-title {
            font-weight: 600;
            margin: 0;
        }
        .test-status {
            font-size: 0.9em;
            font-weight: 500;
            padding: 3px 8px;
            border-radius: 4px;
        }
        .test-status.pass { background-color: #e8f5e9; color: var(--success); }
        .test-status.fail { background-color: #ffebee; color: var(--danger); }
        .test-details {
            font-size: 0.9em;
            color: #6c757d;
            margin-top: 8px;
        }
        .summary-card {
            border-radius: 10px;
            margin-bottom: 25px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .summary-header {
            padding: 15px 20px;
            color: white;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .summary-body {
            padding: 20px;
            background: white;
        }
        .progress {
            height: 8px;
            margin: 10px 0;
        }
        .test-time {
            font-size: 0.8em;
            color: #6c757d;
        }
        @media (max-width: 768px) {
            .test-container {
                padding: 10px;
            }
            .test {
                padding: 12px;
            }
            .summary-header {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class='test-container'>
        <div class='d-flex justify-content-between align-items-center mb-4'>
            <h1 class='h3 mb-0'><i class='fas fa-vial me-2 text-primary'></i>Paghilom Café System Test</h1>
            <span class='badge bg-secondary'>v1.0.0</span>
        </div>
        <p class='text-muted mb-4'>Comprehensive system diagnostics and validation</p>
        
        <div class='summary-card'>
            <div class='summary-header bg-primary'>
                <span>Test Summary</span>
                <span id='test-timestamp' class='text-white-50 small'>
                    <i class='far fa-clock me-1'></i>" . date('F j, Y g:i A') . "
                </span>
            </div>
            <div class='summary-body'>
                <div class='row align-items-center'>
                    <div class='col-md-6'>
                        <div class='d-flex align-items-center mb-3'>
                            <div class='me-3'>
                                <div class='bg-success bg-opacity-10 p-3 rounded-circle'>
                                    <i class='fas fa-check-circle text-success' style='font-size: 2rem;'></i>
                                </div>
                            </div>
                            <div>
                                <h3 class='mb-0' id='tests-passed-count'>0</h3>
                                <small class='text-muted'>Tests Passed</small>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <div class='d-flex align-items-center mb-3'>
                            <div class='me-3'>
                                <div class='bg-danger bg-opacity-10 p-3 rounded-circle'>
                                    <i class='fas fa-times-circle text-danger' style='font-size: 2rem;'></i>
                                </div>
                            </div>
                            <div>
                                <h3 class='mb-0' id='tests-failed-count'>0</h3>
                                <small class='text-muted'>Tests Failed</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='progress'>
                    <div id='test-progress' class='progress-bar bg-success' role='progressbar' style='width: 0%' 
                         aria-valuenow='0' aria-valuemin='0' aria-valuemax='100'></div>
                </div>
            </div>
        </div>

        <h5 class='mb-3'><i class='fas fa-tasks me-2 text-primary'></i>Test Results</h5>
        <div id='test-results'>";

// Function to output test results
function output_test_result($test_name, $passed, $message = '', $details = '') {
    global $tests_passed, $tests_failed, $errors;
    
    $test_id = 'test-' . preg_replace('/[^a-z0-9]/', '-', strtolower($test_name));
    $status_class = $passed ? 'pass' : 'fail';
    $status_text = $passed ? 'PASSED' : 'FAILED';
    $icon = $passed ? 'check-circle' : 'times-circle';
    $time = date('H:i:s');
    
    echo "<div id='{$test_id}' class='test {$status_class}'>";
    echo "<div class='test-header'>";
    echo "<h3 class='test-title'><i class='fas fa-{$icon} me-2 text-{$status_class}'></i>{$test_name}</h3>";
    echo "<span class='test-status {$status_class}'>";
    echo "<i class='fas fa-{$icon} me-1'></i>{$status_text}";
    echo "</span>";
    echo "</div>"; // .test-header
    
    if (!empty($message)) {
        echo "<div class='test-message'>{$message}</div>";
    }
    
    if (!empty($details) && is_string($details)) {
        echo "<div class='test-details'><pre class='mb-0'>" . htmlspecialchars($details) . "</pre></div>";
    } elseif (is_array($details)) {
        echo "<div class='test-details'><ul class='mb-0'>";
        foreach ($details as $detail) {
            echo "<li>" . htmlspecialchars($detail) . "</li>";
        }
        echo "</ul></div>";
    }
    
    echo "<div class='test-time'><small><i class='far fa-clock me-1'></i>{$time}</small></div>";
    echo "</div>"; // .test
    
    if ($passed) {
        $tests_passed++;
    } else {
        $tests_failed++;
        $errors[] = $test_name . ': ' . ($message ?: 'Test failed');
    }
    
    // Update the summary counters
    echo "<script>
        document.getElementById('tests-passed-count').textContent = '{$tests_passed}';
        document.getElementById('tests-failed-count').textContent = '{$tests_failed}';
        const total = {$tests_passed} + {$tests_failed};
        const progress = total > 0 ? Math.round(({$tests_passed} / total) * 100) : 0;
        document.getElementById('test-progress').style.width = progress + '%';
        document.getElementById('test-progress').setAttribute('aria-valuenow', progress);
    </script>";
    
    // Force output to be sent to the browser
    flush();
    ob_flush();
}

// Test 1: Database Connection
try {
    $start_time = microtime(true);
    if ($mysqli->ping()) {
        $ping_time = round((microtime(true) - $start_time) * 1000, 2);
        output_test_result(
            'Database Connection', 
            true, 
            'Successfully connected to the database.',
            "Connection time: {$ping_time}ms\nHost: " . $mysqli->host_info
        );
    } else {
        output_test_result(
            'Database Connection', 
            false, 
            'Failed to connect to the database.',
            'The system could not establish a connection to the database.'
        );
    }
} catch (Exception $e) {
    output_test_result(
        'Database Connection', 
        false, 
        'Database error occurred.',
        'Error: ' . htmlspecialchars($e->getMessage())
    );
}

// Test 2: Check Required Tables
try {
    $required_tables = [
        'users', 'categories', 'products', 'product_images', 'orders', 'order_items',
        'settings', 'pages', 'addons', 'milks', 'stores', 'rewards', 'clients',
        'stock_movements', 'login_attempts', 'point_transactions'
    ];

    $missing_tables = [];
    $existing_tables = [];
    
    // Check each required table
    foreach ($required_tables as $table) {
        $result = $mysqli->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows == 0) {
            $missing_tables[] = $table;
        } else {
            $existing_tables[] = $table;
        }
    }
    
    $table_count = count($required_tables);
    $existing_count = count($existing_tables);
    $missing_count = count($missing_tables);
    
    $details = "• Total required tables: {$table_count}\n";
    $details .= "• Found: {$existing_count} tables\n";
    $details .= "• Missing: {$missing_count} tables";
    
    if (!empty($missing_tables)) {
        $details .= "\n\nMissing tables:\n• " . implode("\n• ", $missing_tables);
    }
    
    if (empty($missing_tables)) {
        output_test_result(
            'Database Tables Check',
            true,
            "All {$table_count} required tables exist in the database.",
            $details
        );
    } else {
        output_test_result(
            'Database Tables Check',
            false,
            "Missing {$missing_count} of {$table_count} required tables.",
            $details
        );
    }
} catch (Exception $e) {
    output_test_result(
        'Database Tables Check',
        false,
        'Error while checking database tables.',
        'Error: ' . htmlspecialchars($e->getMessage())
    );
}

// Test 3: Check Configuration Functions
try {
    $required_functions = [
        'is_logged_in', 'is_admin', 'is_staff', 'require_login', 
        'require_admin', 'csrf_field', 'csrf_check', 'e',
        'generate_token', 'validate_password_strength', 'get_setting', 'set_setting'
    ];

    $missing_functions = [];
    $existing_functions = [];
    
    foreach ($required_functions as $func) {
        if (function_exists($func)) {
            $existing_functions[] = $func;
        } else {
            $missing_functions[] = $func;
        }
    }
    
    $total_functions = count($required_functions);
    $existing_count = count($existing_functions);
    $missing_count = count($missing_functions);
    
    $details = [
        "• Total required functions: {$total_functions}",
        "• Found: {$existing_count} functions",
        "• Missing: {$missing_count} functions"
    ];
    
    if (!empty($missing_functions)) {
        $details[] = "\nMissing functions:";
        foreach ($missing_functions as $func) {
            $details[] = "- {$func}()";
        }
    }
    
    if (empty($missing_functions)) {
        output_test_result(
            'Configuration Functions',
            true,
            "All {$total_functions} required functions are available.",
            $details
        );
    } else {
        output_test_result(
            'Configuration Functions',
            false,
            "Missing {$missing_count} of {$total_functions} required functions.",
            $details
        );
    }
} catch (Exception $e) {
    output_test_result(
        'Configuration Functions',
        false,
        'Error while checking required functions.',
        'Error: ' . htmlspecialchars($e->getMessage())
    );
}

// Test 4: Check Helper Functions File
try {
    $helper_file = __DIR__ . '/includes/helpers.php';
    
    if (!file_exists($helper_file)) {
        throw new Exception('Helper functions file not found');
    }
    
    require_once $helper_file;
    
    $helper_functions = [
        'upload_product_image', 'resize_image', 'format_phone', 
        'generate_order_code', 'generate_voucher_code', 'is_low_stock',
        'get_status_badge_color', 'format_date'
    ];
    
    $missing_helpers = [];
    $existing_helpers = [];
    
    foreach ($helper_functions as $func) {
        if (function_exists($func)) {
            $existing_helpers[] = $func;
        } else {
            $missing_helpers[] = $func;
        }
    }
    
    $total_helpers = count($helper_functions);
    $existing_count = count($existing_helpers);
    $missing_count = count($missing_helpers);
    
    $details = [
        "• Helper file: " . htmlspecialchars($helper_file),
        "• Total helper functions: {$total_helpers}",
        "• Found: {$existing_count} functions",
        "• Missing: {$missing_count} functions"
    ];
    
    if (!empty($missing_helpers)) {
        $details[] = "\nMissing helper functions:";
        foreach ($missing_helpers as $func) {
            $details[] = "- {$func}()";
        }
    }
    
    if (empty($missing_helpers)) {
        output_test_result(
            'Helper Functions',
            true,
            "All {$total_helpers} helper functions are available.",
            $details
        );
    } else {
        output_test_result(
            'Helper Functions',
            false,
            "Missing {$missing_count} of {$total_helpers} helper functions.",
            $details
        );
    }
} catch (Exception $e) {
    output_test_result(
        'Helper Functions',
        false,
        'Error while checking helper functions.',
        'Error: ' . htmlspecialchars($e->getMessage())
    );
}

// Test 5: Check Admin User Exists
try {
    $result = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    if ($result === false) {
        throw new Exception('Failed to query admin users: ' . $mysqli->error);
    }
    
    $admin_data = $result->fetch_assoc();
    $admin_count = (int)$admin_data['count'];
    
    $details = [
        "• Admin users required: At least 1",
        "• Admin users found: {$admin_count}"
    ];
    
    // Get list of admin users
    $admin_users = [];
    $admin_result = $mysqli->query("SELECT id, username, email FROM users WHERE role = 'admin' LIMIT 5");
    if ($admin_result && $admin_result->num_rows > 0) {
        $details[] = "\nAdmin users:";
        while ($user = $admin_result->fetch_assoc()) {
            $details[] = sprintf(
                "- #%s: %s (%s)",
                htmlspecialchars($user['id']),
                htmlspecialchars($user['username']),
                htmlspecialchars($user['email'])
            );
        }
        if ($admin_count > 5) {
            $details[] = sprintf("... and %d more admin users", $admin_count - 5);
        }
    }
    
    if ($admin_count > 0) {
        output_test_result(
            'Admin User Check',
            true,
            "Found {$admin_count} admin user" . ($admin_count !== 1 ? 's' : '') . ".",
            $details
        );
    } else {
        output_test_result(
            'Admin User Check',
            false,
            'No admin users found in the system.',
            array_merge($details, [
                "",
                "This is a critical issue. You need at least one admin user to manage the system.",
                "Please run the setup script or create an admin user manually."
            ])
        );
    }
} catch (Exception $e) {
    output_test_result(
        'Admin User Check',
        false,
        'Error while checking admin users.',
        'Error: ' . htmlspecialchars($e->getMessage())
    );
}

// Test 6: Check Seed Data
try {
    $tables = [
        'categories' => 'Product Categories',
        'settings' => 'System Settings',
        'addons' => 'Product Add-ons',
        'milks' => 'Milk Options',
        'stores' => 'Store Locations'
    ];
    
    $missing_tables = [];
    $table_counts = [];
    $all_tables_exist = true;
    
    foreach ($tables as $table => $name) {
        $result = $mysqli->query("SELECT COUNT(*) as count FROM `$table`");
        if ($result === false) {
            $missing_tables[] = $name . ' (table not found)';
            $all_tables_exist = false;
        } else {
            $count = (int)$result->fetch_assoc()['count'];
            $table_counts[] = "• {$name}: {$count} " . strtolower($name);
            
            if ($count === 0) {
                $missing_tables[] = $name . ' (empty)';
                $all_tables_exist = false;
            }
        }
    }
    
    $details = array_merge(
        ["The following tables were checked for seed data:"],
        $table_counts,
        ["", "All tables should contain initial seed data for the application to function properly."]
    );
    
    if ($all_tables_exist) {
        output_test_result(
            'Seed Data Check',
            true,
            'All required seed data is present in the database.',
            $details
        );
    } else {
        output_test_result(
            'Seed Data Check',
            false,
            'Missing or empty seed data in some tables.',
            array_merge($details, [
                "",
                "The following tables are missing or empty:",
                "• " . implode("\n• ", $missing_tables),
                "",
                "Please run the database seeder to populate the required data."
            ])
        );
    }
} catch (Exception $e) {
    output_test_result(
        'Seed Data Check',
        false,
        'Error while checking seed data.',
        'Error: ' . htmlspecialchars($e->getMessage())
    );
}

// Test 7: Check File Permissions
try {
    $writable_dirs = [
        'uploads' => 'File uploads',
        'assets/img' => 'Image storage',
        'logs' => 'System logs',
        'cache' => 'Cache files'
    ];
    
    $unwritable = [];
    $writable = [];
    
    foreach ($writable_dirs as $dir => $name) {
        $path = __DIR__ . '/' . $dir;
        if (!file_exists($path)) {
            $unwritable[] = $name . ' (directory not found: ' . $dir . ')';
        } elseif (!is_writable($path)) {
            $unwritable[] = $name . ' (not writable: ' . $dir . ')';
        } else {
            $writable[] = "• {$name}: " . realpath($path);
        }
    }
    
    $details = [
        "The following directories need to be writable by the web server:",
        "• uploads/ - For file uploads",
        "• assets/img/ - For storing product and other images",
        "• logs/ - For system logs",
        "• cache/ - For cached files",
        "",
        "Writable directories:"
    ];
    
    if (!empty($writable)) {
        $details = array_merge($details, $writable);
    } else {
        $details[] = "No writable directories found.";
    }
    
    if (empty($unwritable)) {
        output_test_result(
            'File Permissions',
            true,
            'All required directories are writable.',
            $details
        );
    } else {
        output_test_result(
            'File Permissions',
            false,
            count($unwritable) . ' directory' . (count($unwritable) !== 1 ? 's are' : ' is') . ' not writable.',
            array_merge($details, [
                "",
                "Issues found:",
                "• " . implode("\n• ", $unwritable),
                "",
                "To fix permission issues on Linux/macOS, run:",
                "<code>chmod -R 755 " . __DIR__ . "/{uploads,assets/img,logs,cache}</code>",
                "<code>chown -R www-data:www-data " . __DIR__ . "/{uploads,assets/img,logs,cache}</code>"
            ])
        );
    }
} catch (Exception $e) {
    output_test_result(
        'File Permissions',
        false,
        'Error while checking file permissions.',
        'Error: ' . htmlspecialchars($e->getMessage())
    );
}

// Test 8: Check PHP Version
try {
    $min_php_version = '7.4.0';
    $current_php_version = PHP_VERSION;
    $is_compatible = version_compare($current_php_version, $min_php_version, '>=');
    
    $details = [
        "• Current version: {$current_php_version}",
        "• Minimum required: {$min_php_version}",
        "• Recommended: 8.0 or higher",
        "",
        "Note: PHP 7.4 reached end of life on November 28, 2022."
    ];
    
    if ($is_compatible) {
        $message = "PHP version {$current_php_version} is compatible.";
        if (version_compare($current_php_version, '8.0.0', '>=')) {
            $message .= " (PHP 8.0+ detected)";
        }
        
        output_test_result(
            'PHP Version Check',
            true,
            $message,
            $details
        );
    } else {
        output_test_result(
            'PHP Version Check',
            false,
            "PHP version {$current_php_version} is below the minimum required version {$min_php_version}.",
            array_merge($details, [
                "",
                "Please upgrade your PHP version to at least {$min_php_version}.",
                "Recommended: PHP 8.1 or higher for better performance and security."
            ])
        );
    }
} catch (Exception $e) {
    output_test_result(
        'PHP Version Check',
        false,
        'Error while checking PHP version.',
        'Error: ' . htmlspecialchars($e->getMessage())
    );
}

// Test 9: Check Required PHP Extensions
try {
    $required_extensions = [
        'mysqli' => 'MySQLi Database',
        'pdo_mysql' => 'PDO MySQL',
        'gd' => 'GD Library (Image Processing)',
        'mbstring' => 'Multibyte String',
        'json' => 'JSON',
        'session' => 'Sessions',
        'openssl' => 'OpenSSL',
        'fileinfo' => 'File Information',
        'curl' => 'cURL',
        'zip' => 'ZIP Archive'
    ];
    
    $missing_extensions = [];
    $loaded_extensions = [];
    
    foreach ($required_extensions as $ext => $name) {
        if (extension_loaded($ext)) {
            $loaded_extensions[] = "• {$name} ({$ext}): <span class='text-success'>Loaded</span>";
        } else {
            $missing_extensions[] = $name . " ({$ext})";
            $loaded_extensions[] = "• {$name} ({$ext}): <span class='text-danger'>Missing</span>";
        }
    }
    
    $details = array_merge(
        ["The following PHP extensions are required for the application to function properly:", ""],
        $loaded_extensions
    );
    
    if (empty($missing_extensions)) {
        output_test_result(
            'PHP Extensions',
            true,
            'All required PHP extensions are loaded.',
            $details
        );
    } else {
        output_test_result(
            'PHP Extensions',
            false,
            'Missing ' . count($missing_extensions) . ' required PHP extension' . (count($missing_extensions) !== 1 ? 's' : '') . '.',
            array_merge($details, [
                "",
                "To install missing extensions on Ubuntu/Debian:",
                "<code>sudo apt-get install php-mysql php-gd php-mbstring php-curl php-zip php-xml</code>",
                "",
                "On CentOS/RHEL:",
                "<code>sudo yum install php-mysqlnd php-gd php-mbstring php-curl php-zip php-xml</code>",
                "",
                "After installing, restart your web server."
            ])
        );
    }
} catch (Exception $e) {
    output_test_result(
        'PHP Extensions',
        false,
        'Error while checking PHP extensions.',
        'Error: ' . htmlspecialchars($e->getMessage())
    );
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
