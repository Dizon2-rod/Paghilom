<?php
require_once 'config.php';
$oauth_config = require 'oauth_config.php';

// If already logged in, redirect to home
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

// Get Google OAuth configuration
$client_id = $oauth_config['google']['client_id'];
$redirect_uri = $oauth_config['google']['redirect_uri'];
$scopes = implode(' ', [
    'https://www.googleapis.com/auth/userinfo.email',
    'https://www.googleapis.com/auth/userinfo.profile'
]);

// Generate state token for CSRF protection
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

// Build Google OAuth URL
$google_auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id' => $client_id,
    'redirect_uri' => $redirect_uri,
    'response_type' => 'code',
    'scope' => $scopes,
    'state' => $state,
    'access_type' => 'offline',
    'prompt' => 'select_account'
]);

// Redirect to Google
header('Location: ' . $google_auth_url);
exit;
?>
