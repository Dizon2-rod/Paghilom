<?php
// OAuth Configuration for Paghilom Cafe
// Copy this file to oauth_config.php and fill in your actual credentials

// Read sensitive credentials from environment variables.
// Define these in your server config or a .env loader, not in source code.
$googleClientId     = getenv('GOOGLE_CLIENT_ID') ?: '';
$googleClientSecret = getenv('GOOGLE_CLIENT_SECRET') ?: '';

// Optional local override file (NOT for committing to git).
$localEnvFile = __DIR__ . '/local.env.php';
if (file_exists($localEnvFile)) {
    $local = include $localEnvFile;
    if (is_array($local)) {
        if (!empty($local['GOOGLE_CLIENT_ID'])) {
            $googleClientId = $local['GOOGLE_CLIENT_ID'];
        }
        if (!empty($local['GOOGLE_CLIENT_SECRET'])) {
            $googleClientSecret = $local['GOOGLE_CLIENT_SECRET'];
        }
    }
}

return [
    'google' => [
        'client_id'    => $googleClientId,
        'client_secret'=> $googleClientSecret,
        // NOTE: Callback file is in the auth/ directory
        'redirect_uri' => APP_URL . 'auth/google_callback.php',
        'scopes'       => ['email', 'profile'],
        // Auto-disable if credentials are missing
        'enabled'      => !empty($googleClientId) && !empty($googleClientSecret)
    ],
    'facebook' => [
        'app_id' => 'YOUR_FACEBOOK_APP_ID',
        'app_secret' => 'YOUR_FACEBOOK_APP_SECRET',
        'redirect_uri' => APP_URL . 'auth/facebook_callback.php',
        'scopes' => ['email', 'public_profile'],
        'enabled' => true
    ],
    'security' => [
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 minutes
        'session_timeout' => 3600, // 1 hour
        'remember_me_duration' => 2592000, // 30 days
        'csrf_token_lifetime' => 3600
    ],
    'email' => [
        'verification_required' => true,
        'verification_token_lifetime' => 86400, // 24 hours
        'password_reset_token_lifetime' => 3600, // 1 hour
        'verification_code_length' => 6,
        'verification_code_expiry' => 3600, // 1 hour
        // Gmail SMTP settings (values pulled from environment or DB settings)
        'smtp' => [
            'username'    => getenv('SMTP_USER') ?: 'rasheddizon7@gmail.com',
            'app_password'=> getenv('SMTP_PASS') ?: 'vcrf rrau voek xlvf',
            'from_name'   => 'Paghilom Cafe',
            'from_email'  => getenv('SMTP_FROM') ?: 'rasheddizon7@gmail.com'
        ]
    ]
];

