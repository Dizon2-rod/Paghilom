<?php
require __DIR__.'/config.php';

// Prevent caching of logout page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Start a new clean session for flash messages
session_start();
$_SESSION['logout_message'] = 'You have been logged out successfully.';

// Redirect to login page
header('Location: login.php');
exit;
?>
