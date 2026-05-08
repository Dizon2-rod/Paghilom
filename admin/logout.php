<?php
// Secure logout for Owner area
require_once dirname(__DIR__) . '/config.php';

// Unset all session variables
$_SESSION = [];

// Delete the session cookie if set
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Destroy the session
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// Create a fresh session ID to prevent fixation on next login
session_start();
session_regenerate_id(true);

// Redirect to login page
header('Location: ' . APP_URL . 'login.php');
exit;


