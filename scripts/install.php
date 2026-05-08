<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paghilom Cafe - Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h1 class="h3 mb-4 text-center">Paghilom Cafe Management System</h1>
                        <h2 class="h5 mb-4 text-center text-muted">Installation Wizard</h2>
                        
﻿<?php
// Installer is disabled in a running system for security reasons.
if (file_exists(__DIR__ . '/config.php')) {
    http_response_code(403);
    die('Installer disabled. To reinstall, remove config.php and run this script again.');
}
                        $step = $_GET['step'] ?? 1;
                        $message = '';
                        $error = '';
                        
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 2) {
                            $db_host = $_POST['db_host'] ?? 'localhost';
                            $db_user = $_POST['db_user'] ?? 'root';
                            $db_pass = $_POST['db_pass'] ?? '';
                            $db_name = $_POST['db_name'] ?? 'paghilom_cafe';
                            $admin_email = $_POST['admin_email'] ?? '';
                            $admin_password = $_POST['admin_password'] ?? '';
                            $admin_name = $_POST['admin_name'] ?? '';
                            
                            try {
                                // Test connection
                                $conn = new mysqli($db_host, $db_user, $db_pass);
                                
                                if ($conn->connect_error) {
                                    throw new Exception("Connection failed: " . $conn->connect_error);
                                }
                                
                                // Create database if not exists
                                $conn->query("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                                $conn->select_db($db_name);
                                
                                // Read and execute schema
                                $schema_file = __DIR__ . '/database/schema_unified.sql';
                                if (file_exists($schema_file)) {
                                    $sql = file_get_contents($schema_file);
                                    
                                    // Remove comments and split by semicolon
                                    $sql = preg_replace('/--.*$/m', '', $sql);
                                    $sql = preg_replace('/\s+/', ' ', $sql);
                                    $statements = array_filter(array_map('trim', explode(';', $sql)));
                                    
                                    foreach ($statements as $statement) {
                                        if (!empty($statement) && $statement !== 'USE paghilom_cafe') {
                                            $conn->query($statement);
                                        }
                                    }
                                }
                                
                                // Create admin user
                                $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
                                $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, role, is_active, email_verified) VALUES (?, ?, ?, 'owner', 1, 1) ON DUPLICATE KEY UPDATE name=VALUES(name)");
                                $stmt->bind_param('sss', $admin_name, $admin_email, $password_hash);
                                $stmt->execute();
                                $stmt->close();
                                
                                // Update config file
                                $config_content = "<?php
// Database Configuration
define('DB_HOST', '$db_host');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');
define('DB_NAME', '$db_name');

// Create connection
\$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (\$conn->connect_error) {
    die(\"Connection failed: \" . \$conn->connect_error);
}

// Set charset to utf8mb4
\$conn->set_charset(\"utf8mb4\");

// Site Configuration
define('SITE_URL', 'http://localhost/paghilom_cafe');
define('SITE_NAME', 'Paghilom Cafe');

// Email Configuration (for Gmail verification)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com'); // Change this
define('SMTP_PASS', 'your-app-password'); // Change this (use App Password for Gmail)
define('SMTP_FROM', 'noreply@paghilomcafe.com');
define('SMTP_FROM_NAME', 'Paghilom Cafe');

// PayMongo Configuration
define('PAYMONGO_SECRET_KEY', 'sk_test_your_secret_key'); // Change this
define('PAYMONGO_PUBLIC_KEY', 'pk_test_your_public_key'); // Change this

// Upload directories
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', SITE_URL . '/assets/uploads/');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>";
                                
                                file_put_contents(__DIR__ . '/includes/config.php', $config_content);
                                
                                $conn->close();
                                
                                $message = "Installation completed successfully! You can now <a href='login.php'>login</a> with your admin credentials.";
                                $step = 3;
                                
                            } catch (Exception $e) {
                                $error = "Installation failed: " . $e->getMessage();
                            }
                        }
                        
                        if ($step == 1): ?>
                            <div class="alert alert-info">
                                <h5>Requirements Check</h5>
                                <ul class="mb-0">
                                    <li>PHP <?= phpversion() ?> <?= version_compare(phpversion(), '7.4.0', '>=') ? '✓' : '✗' ?></li>
                                    <li>MySQL/MariaDB <?= extension_loaded('mysqli') ? '✓' : '✗' ?></li>
                                    <li>GD Library <?= extension_loaded('gd') ? '✓' : '✗' ?></li>
                                    <li>cURL <?= extension_loaded('curl') ? '✓' : '✗' ?></li>
                                </ul>
                            </div>
                            
                            <div class="text-center">
                                <a href="?step=2" class="btn btn-success btn-lg">Continue to Setup</a>
                            </div>
                            
                        <?php elseif ($step == 2): ?>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?= $error ?></div>
                            <?php endif; ?>
                            
                            <form method="post">
                                <h5 class="mb-3">Database Configuration</h5>
                                
                                <div class="mb-3">
                                    <label class="form-label">Database Host</label>
                                    <input type="text" class="form-control" name="db_host" value="localhost" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Database User</label>
                                    <input type="text" class="form-control" name="db_user" value="root" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Database Password</label>
                                    <input type="password" class="form-control" name="db_pass">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Database Name</label>
                                    <input type="text" class="form-control" name="db_name" value="paghilom_cafe" required>
                                </div>
                                
                                <hr class="my-4">
                                
                                <h5 class="mb-3">Admin Account</h5>
                                
                                <div class="mb-3">
                                    <label class="form-label">Admin Name</label>
                                    <input type="text" class="form-control" name="admin_name" value="Admin" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Admin Email</label>
                                    <input type="email" class="form-control" name="admin_email" value="admin@paghilom.local" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Admin Password</label>
                                    <input type="password" class="form-control" name="admin_password" required minlength="8">
                                    <div class="form-text">Minimum 8 characters</div>
                                </div>
                                
                                <button type="submit" class="btn btn-success w-100">Install Now</button>
                            </form>
                            
                        <?php elseif ($step == 3): ?>
                            
                            <div class="alert alert-success">
                                <?= $message ?>
                            </div>
                            
                            <div class="text-center">
                                <a href="login.php" class="btn btn-success btn-lg">Go to Login</a>
                            </div>
                            
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="text-center mt-4 text-muted">
                    <small>Paghilom Cafe Management System v1.0</small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
