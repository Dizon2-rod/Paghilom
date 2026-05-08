<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/auth.php';

// Load OAuth configuration
$oauth_config = require __DIR__ . '/../oauth_config.php';

// Basic sanity check so we fail with a clear message instead of a vague Google error
$clientId = trim($oauth_config['google']['client_id'] ?? '');
$clientSecret = trim($oauth_config['google']['client_secret'] ?? '');

if (empty($clientId) || empty($clientSecret)) {
    http_response_code(500);
    echo 'Google OAuth is misconfigured: missing client_id or client_secret. ' .
         'Set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in your environment (or via a local env loader).';
    exit;
}

if (!$oauth_config['google']['enabled']) {
    http_response_code(503);
    die('Google OAuth is disabled');
}

// Generate state parameter for CSRF protection
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

// Build Google OAuth URL
$params = [
    'client_id' => $oauth_config['google']['client_id'],
    'redirect_uri' => $oauth_config['google']['redirect_uri'],
    'scope' => implode(' ', $oauth_config['google']['scopes']),
    'response_type' => 'code',
    'state' => $state,
    'access_type' => 'offline',
    'prompt' => 'consent'
];

$auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);

header('Location: ' . $auth_url);
exit;

