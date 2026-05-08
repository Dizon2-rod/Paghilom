
<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Database config ---
if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1'); // Changed from localhost to 127.0.0.1 for better performance
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'paghilom_cafe');
if (!defined('DB_PORT')) define('DB_PORT', 3306); // Default MySQL port

// --- Application config ---
// Compute a stable base URL that points to the project root (works in subfolders)
if (!defined('APP_BASE')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $doc    = rtrim(str_replace('\\','/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $baseDir= str_replace('\\','/', realpath(__DIR__));
    $path   = '';
    if ($doc && strpos($baseDir, $doc) === 0) {
        $path = substr($baseDir, strlen($doc));
    } else {
        // Fallback to previous behaviour if DOCUMENT_ROOT is not set as expected
        $path = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    }
    if ($path === '') { $path = '/'; }
    define('APP_BASE', rtrim($path, '/'));
    if (!defined('APP_URL')) define('APP_URL', $scheme.'://'.$host.APP_BASE.'/');
}

// Configure error reporting for mysqli
if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF); // Disable error reporting, we'll handle errors manually
}

// Function to check database connection
function check_database_connection($host, $user, $pass, $dbname, $port = 3306) {
    $mysqli = @new mysqli($host, $user, $pass, '', $port);
    if ($mysqli->connect_error) {
        return false;
    }
    
    // Check if database exists, if not create it
    if (!$mysqli->select_db($dbname)) {
        $create_db = $mysqli->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        if (!$create_db) {
            $mysqli->close();
            return false;
        }
        $mysqli->select_db($dbname);
    }
    
    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}

// Try to connect to the database
$mysqli = check_database_connection(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// If connection fails, try with 127.0.0.1 instead of localhost
if (!$mysqli && DB_HOST === 'localhost') {
    $mysqli = check_database_connection('127.0.0.1', DB_USER, DB_PASS, DB_NAME, DB_PORT);
}

// If still no connection, show error
if (!$mysqli) {
    $error_message = 'Database connection failed. Please ensure:';
    $error_message .= '\n1. MySQL server is running';
    $error_message .= '\n2. The database credentials in config.php are correct';
    $error_message .= '\n3. The database user has proper permissions';
    
    if (PHP_SAPI === 'cli') {
        die($error_message . PHP_EOL);
    } else {
        http_response_code(503);
        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Database Connection Error</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; color: #333; }
                .container { max-width: 800px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
                h1 { color: #d32f2f; }
                pre { background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; }
                .btn { display: inline-block; padding: 10px 15px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; }
                .btn:hover { background: #45a049; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>⚠️ Database Connection Error</h1>
                <p>We couldn\'t connect to the database. Please check the following:</p>
                <ol>
                    <li>Make sure MySQL server is running</li>
                    <li>Verify the database credentials in <code>config.php</code> are correct</li>
                    <li>Check that the database user has proper permissions</li>
                </ol>
                <p>If you need to install the database, follow these steps:</p>
                <ol>
                    <li>Open phpMyAdmin or your preferred MySQL client</li>
                    <li>Create a new database named: <code>paghilom_cafe</code></li>
                    <li>Import the database schema from: <code>database/paghilom_cafe.sql</code></li>
                </ol>
                <p>For more help, please contact your system administrator.</p>
                <p><a href="javascript:window.location.reload()" class="btn">Retry Connection</a></p>
            </div>
        </body>
        </html>';
        exit;
    }
}

// Set connection character set
$mysqli->set_charset('utf8mb4');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Dynamically derive app name from settings
if (!defined('APP_NAME')) {
    $appNameDefault = 'Paghilom Cafe';
    $appName = $appNameDefault;
    if ($mysqli) {
        if ($res = $mysqli->query("SELECT value FROM settings WHERE `key`='site_name' LIMIT 1")) {
            $row = $res->fetch_assoc();
            if (!empty($row['value'])) { $appName = $row['value']; }
            $res->close();
        }
    }
    define('APP_NAME', $appName);
}

// Simple CSRF token helper
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }

if (!function_exists('csrf_field')) {
    function csrf_field() {
        return '<input type="hidden" name="csrf" value="' . htmlspecialchars($_SESSION['csrf']) . '">';
    }
}

if (!function_exists('csrf_check')) {
    function csrf_check() {
        if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
            http_response_code(400);
            die('Invalid CSRF token.');
        }
    }
}

// Enhanced Auth helpers
if (!function_exists('is_logged_in')) {
    function is_logged_in() { 
        return isset($_SESSION['user']) && !empty($_SESSION['user']['id']); 
    }
}

// True platform admin (no more separate "owner" role)
if (!function_exists('is_admin')) {
    function is_admin() { 
        return is_logged_in() && (($_SESSION['user']['role'] ?? '') === 'admin'); 
    }
}

// Backwards-compatible helper kept for places that specifically expect "admin" only
if (!function_exists('is_admin_role')) {
    function is_admin_role() { 
        return is_logged_in() && (($_SESSION['user']['role'] ?? '') === 'admin'); 
    }
}

// Owner role removed – this helper now always aliases to admin for backward compatibility
if (!function_exists('is_owner')) {
    function is_owner() { 
        return is_admin(); 
    }
}

// Operational staff – used for POS access
if (!function_exists('is_staff')) {
    function is_staff() { 
        return is_logged_in() && (($_SESSION['user']['role'] ?? '') === 'staff'); 
    }
}

// Customers are the only ones allowed to place online orders
if (!function_exists('is_customer')) {
    function is_customer() {
        return is_logged_in() && (($_SESSION['user']['role'] ?? '') === 'customer');
    }
}

if (!function_exists('require_login')) {
    function require_login() {
        // Prevent caching for authenticated pages
        if (!headers_sent()) {
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
            header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
        }
        
        if (!is_logged_in()) {
            $current_page = $_SERVER['REQUEST_URI'];
            header('Location: ' . APP_URL . 'login.php?redirect=' . urlencode($current_page));
            exit;
        }
    }
}

if (!function_exists('require_admin')) {
    function require_admin() {
        require_login();
        if (!is_admin()) {
            http_response_code(403);
            die('Access denied. Admin privileges required.');
        }
    }
}

if (!function_exists('require_staff')) {
    function require_staff() {
        require_login();
        if (!is_staff()) {
            http_response_code(403);
            die('Access denied. Staff privileges required.');
        }
    }
}

if (!function_exists('require_pos')) {
    function require_pos() {
        require_login();
        // Staff can access POS normally
        if (function_exists('is_staff') && is_staff()) {
            return;
        }
        // If admin hits POS URL, quietly redirect to Admin Dashboard instead of showing an error
        if (function_exists('is_admin') && is_admin()) {
            if (!headers_sent()) {
                header('Location: ' . APP_URL . 'admin/dashboard.php');
            }
            exit;
        }
        // Everyone else: generic 403
        http_response_code(403);
        if (!headers_sent()) {
            header('Location: ' . APP_URL . 'index.php');
        }
        exit;
    }
}

// Utility functions
if (!function_exists('e')) {
    function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('generate_token')) {
    function generate_token($length = 32) {
        return bin2hex(random_bytes($length));
    }
}

if (!function_exists('validate_password_strength')) {
    function validate_password_strength($password) {
        $errors = [];
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }
        return $errors;
    }
}

if (!function_exists('send_verification_email')) {
    function send_verification_email($email, $token, $mysqli) {
        // This is a placeholder - implement actual email sending
        // For now, just log the verification link
        $verification_link = APP_URL . 'verify_email.php?token=' . $token;
        error_log("Verification email for $email: $verification_link");
        return true; // Return true for now
    }
}

if (!function_exists('send_password_reset_email')) {
    function send_password_reset_email($email, $token, $mysqli) {
        // Try to use email_helper.php if available (more reliable implementation)
        $emailHelperPath = __DIR__ . '/email_helper.php';
        if (file_exists($emailHelperPath)) {
            require_once $emailHelperPath;
            // Check if the function from email_helper.php is now available
            // We need to call it directly since function_exists will return true for this function
            try {
                // Use the email_helper implementation which uses oauth_config
                global $oauth_config;
                if (empty($oauth_config)) {
                    if (file_exists(__DIR__ . '/oauth_config.php')) {
                        $oauth_config = require __DIR__ . '/oauth_config.php';
                    }
                }
                
                // If oauth_config is available, use email_helper's send_email_smtp
                if (!empty($oauth_config['email']['smtp']['username']) && !empty($oauth_config['email']['smtp']['app_password'])) {
                    // Get user name for email
                    $stmt = $mysqli->prepare("SELECT name FROM users WHERE email = ?");
                    $stmt->bind_param('s', $email);
                    $stmt->execute();
                    $user = $stmt->get_result()->fetch_assoc();
                    if ($user && function_exists('send_email_smtp')) {
                        $reset_link = APP_URL . 'reset_password.php?token=' . urlencode($token);
                        $subject = "Reset Your Password - Paghilom Cafe";
                        $body = "
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <style>
                                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                                .header { background-color: #2A5618; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                                .content { background-color: #F6FFF6; padding: 30px; border-radius: 0 0 8px 8px; }
                                .reset-btn { display: inline-block; padding: 12px 30px; background: #2A5618; color: white; text-decoration: none; border-radius: 8px; margin: 20px 0; font-weight: bold; }
                                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                            </style>
                        </head>
                        <body>
                            <div class='container'>
                                <div class='header'>
                                    <h1>Paghilom Cafe</h1>
                                    <p style='margin: 0; opacity: 0.9;'>Password Reset Request</p>
                                </div>
                                <div class='content'>
                                    <h2>Hello, {$user['name']}!</h2>
                                    <p>We received a request to reset your password for your Paghilom Cafe account.</p>
                                    <p>Click the button below to reset your password:</p>
                                    <div style='text-align: center;'>
                                        <a href='{$reset_link}' class='reset-btn'>Reset My Password</a>
                                    </div>
                                    <p><strong>Or copy and paste this link into your browser:</strong></p>
                                    <p style='word-break: break-all; background: #fff; padding: 10px; border: 1px solid #ddd; border-radius: 4px;'>{$reset_link}</p>
                                    <p style='color: #856404; background: #fff3cd; padding: 15px; border-radius: 5px;'><strong>Important:</strong> This link will expire in 1 hour for security reasons.</p>
                                    <p>If you didn't request a password reset, please ignore this email. Your password will remain unchanged.</p>
                                </div>
                                <div class='footer'>
                                    <p>&copy; " . date('Y') . " Paghilom Cafe. All rights reserved.</p>
                                </div>
                            </div>
                        </body>
                        </html>";
                        return send_email_smtp($email, $subject, $body);
                    }
                }
            } catch (Exception $e) {
                error_log('Error using email_helper: ' . $e->getMessage());
                // Fall through to direct implementation
            }
        }
        
        // Fallback to direct PHPMailer implementation
        $vendorAutoload = __DIR__ . '/vendor/autoload.php';
        if (!file_exists($vendorAutoload)) {
            error_log('PHPMailer autoload file not found at: ' . $vendorAutoload);
            return false;
        }
        
        require_once $vendorAutoload;
        
        // Get SMTP credentials
        $smtpUser = getenv('SMTP_USER') ?: get_setting('smtp_user', '');
        $smtpPass = getenv('SMTP_PASS') ?: get_setting('smtp_pass', '');
        
        if (empty($smtpUser) || empty($smtpPass)) {
            error_log('SMTP credentials are missing. Please configure SMTP settings in admin panel or environment variables.');
            return false;
        }
        
        $reset_link = APP_URL . 'reset_password.php?token=' . urlencode($token);

        $logoUrl = APP_URL . 'assets/img/logo.png';
        $brandColor = '#2A5618';
        $bgColor = '#F6FFF6';

        $subject = 'Reset Your Password - ' . (defined('APP_NAME') ? APP_NAME : 'Paghilom Cafe');
        $body = "
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { margin:0; padding:0; background: {$bgColor}; font-family: Arial, sans-serif; color:#222; }
                .container { max-width:600px; margin:20px auto; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.06); }
                .header { background: {$brandColor}; color:#ffffff; padding:20px; text-align:center; }
                .header img { height:48px; display:block; margin:0 auto 8px; }
                .content { padding:28px; line-height:1.6; }
                .btn { display:inline-block; background: {$brandColor}; color:#ffffff !important; text-decoration:none; padding:12px 20px; border-radius:6px; margin:16px 0; }
                .muted { color:#666; font-size:12px; }
                .link { word-break:break-all; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img alt='Logo' src='{$logoUrl}' onerror=\"this.style.display='none';\" />
                    <div style='font-weight:600; font-size:18px'>" . e(defined('APP_NAME') ? APP_NAME : 'Paghilom Cafe') . "</div>
                </div>
                <div class='content'>
                    <h2 style='margin:0 0 10px'>Password reset request</h2>
                    <p>We received a request to reset the password for your account. Click the button below to set a new password.</p>
                    <p><a class='btn' href='" . e($reset_link) . "'>Reset Password</a></p>
                    <p class='muted'>If the button doesn't work, copy and paste this link into your browser:</p>
                    <p class='link'><a href='" . e($reset_link) . "'>" . e($reset_link) . "</a></p>
                    <p class='muted'>This link expires in 1 hour. If you did not request this, you can safely ignore this email.</p>
                </div>
            </div>
        </body>
        </html>";

        try {
            if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                error_log('PHPMailer class not found');
                return false;
            }
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // SMTP config
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';
            $mail->Timeout = 30;

            // From/To
            $fromEmail = get_setting('smtp_from', $smtpUser);
            $fromName  = get_setting('smtp_from_name', defined('APP_NAME') ? APP_NAME : 'Paghilom Cafe');
            if ($envFrom = getenv('SMTP_FROM')) { $fromEmail = $envFrom; }
            if ($envFromName = getenv('SMTP_FROM_NAME')) { $fromName = $envFromName; }

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;

            // Plain-text alt body
            $mail->AltBody = 'Reset your password: ' . $reset_link . "\nThis link expires in 1 hour.";

            $mail->send();
            error_log("Password reset email sent successfully to: $email");
            return true;
        } catch (Exception $e) {
            $errorMsg = 'Failed to send reset email: ' . $e->getMessage();
            error_log($errorMsg);
            // Log more details for debugging
            if (method_exists($e, 'getSMTP')) {
                $smtp = $e->getSMTP();
                if ($smtp) {
                    error_log('SMTP Error: ' . $smtp->getError());
                }
            }
            return false;
        } catch (Throwable $e) {
            error_log('Failed to send reset email: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('get_setting')) {
    function get_setting($key, $default = '') {
        global $mysqli;
        if (!$mysqli) return $default;
        $stmt = $mysqli->prepare("SELECT value FROM settings WHERE `key` = ?");
        if (!$stmt) return $default;
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result ? $result['value'] : $default;
    }
}

if (!function_exists('set_setting')) {
    function set_setting($key, $value) {
        global $mysqli;
        if (!$mysqli) return false;
        $stmt = $mysqli->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
        if (!$stmt) return false;
        $stmt->bind_param('ss', $key, $value);
        return $stmt->execute();
    }
}
?>
