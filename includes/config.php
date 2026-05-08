<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'paghilom_cafe');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Site Configuration
define('SITE_URL', 'http://localhost/paghilom_cafe');
define('SITE_NAME', 'Paghilom Cafe');

// Email Configuration (for Gmail verification)
define('SMTP_HOST', 'rasheddizon7@gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'rasheddizon7@gmail.com'); // Change this
define('SMTP_PASS', 'vcrf rrau voek xlvf'); // Change this (use App Password for Gmail)
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
?>
