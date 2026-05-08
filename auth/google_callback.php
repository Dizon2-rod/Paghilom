<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start(['cookie_httponly' => true, 'cookie_secure' => true]);
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/auth.php';

// Enable error reporting for debugging
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Load OAuth configuration
$oauth_config_file = __DIR__ . '/../oauth_config.php';
if (!file_exists($oauth_config_file)) {
    error_log('OAuth configuration file not found');
    header('Location: ' . APP_URL . 'login.php?error=oauth_config_missing');
    exit;
}

$oauth_config = require $oauth_config_file;

if (!$oauth_config['google']['enabled']) {
    http_response_code(503);
    die('Google OAuth is disabled');
}

// Verify state parameter to prevent CSRF
if (!isset($_GET['state']) || empty($_GET['state']) || 
    !isset($_SESSION['oauth_state']) || 
    !hash_equals((string)$_SESSION['oauth_state'], (string)$_GET['state'])) {
    
    error_log('Invalid OAuth state: ' . ($_GET['state'] ?? '') . ' vs ' . ($_SESSION['oauth_state'] ?? ''));
    
    // Clear the state to prevent replay attacks
    unset($_SESSION['oauth_state']);
    
    http_response_code(400);
    header('Location: ' . APP_URL . 'login.php?error=invalid_state');
    exit;
}

// Clear the state after verification
unset($_SESSION['oauth_state']);

// Check for error
if (isset($_GET['error'])) {
    header('Location: ' . APP_URL . 'login.php?error=' . urlencode($_GET['error_description'] ?? $_GET['error']));
    exit;
}

// Validate and sanitize the authorization code
$code = filter_input(INPUT_GET, 'code', FILTER_SANITIZE_STRING);
if (empty($code)) {
    error_log('No authorization code received');
    header('Location: ' . APP_URL . 'login.php?error=no_auth_code');
    exit;
}

// Validate the code format (basic validation)
if (!preg_match('/^[a-zA-Z0-9_\-\.~]+$/', $code)) {
    error_log('Invalid authorization code format');
    header('Location: ' . APP_URL . 'login.php?error=invalid_auth_code');
    exit;
}

// Prepare token request
$token_url = 'https://oauth2.googleapis.com/token';
$token_data = [
    'client_id' => $oauth_config['google']['client_id'],
    'client_secret' => $oauth_config['google']['client_secret'],
    'code' => $code,
    'grant_type' => 'authorization_code',
    'redirect_uri' => $oauth_config['google']['redirect_uri']
];

// Validate required configuration
if (empty($token_data['client_id']) || empty($token_data['client_secret'])) {
    error_log('OAuth client configuration is incomplete');
    header('Location: ' . APP_URL . 'login.php?error=oauth_config_incomplete');
    exit;
}

// Initialize cURL with secure options
$ch = curl_init();
$options = [
    CURLOPT_URL => $token_url,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($token_data),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'User-Agent: Paghilom-Cafe/1.0'
    ]
];

curl_setopt_array($ch, $options);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// Check for cURL errors
if ($response === false) {
    error_log('cURL error: ' . $curl_error);
    header('Location: ' . APP_URL . 'login.php?error=token_request_failed');
    exit;
}

if ($http_code !== 200) {
    error_log('Token exchange failed with status ' . $http_code . ': ' . $response);
    
    // Log the full response for debugging (but don't expose to user)
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        error_log('Token response: ' . print_r($response, true));
    }
    
    header('Location: ' . APP_URL . 'login.php?error=token_exchange_failed');
    exit;
}

$token_data = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE || !$token_data || !isset($token_data['access_token'])) {
    error_log('Invalid token response: ' . $response);
    header('Location: ' . APP_URL . 'login.php?error=invalid_token_response');
    exit;
}

// Validate token data
$required_fields = ['access_token', 'expires_in', 'token_type'];
foreach ($required_fields as $field) {
    if (!isset($token_data[$field])) {
        error_log('Missing required token field: ' . $field);
        header('Location: ' . APP_URL . 'login.php?error=invalid_token_format');
        exit;
    }
}

// Store the token in session (securely)
$_SESSION['oauth_token'] = $token_data['access_token'];
$_SESSION['token_expires'] = time() + ($token_data['expires_in'] ?? 3600);

// Get user info from Google using a secure request
$user_info_url = 'https://www.googleapis.com/oauth2/v2/userinfo';

// Initialize cURL with secure options
$ch = curl_init();
$options = [
    CURLOPT_URL => $user_info_url,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token_data['access_token'],
        'Accept: application/json',
        'User-Agent: Paghilom-Cafe/1.0'
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_TIMEOUT => 10
];

curl_setopt_array($ch, $options);
$user_response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// Check for cURL errors
if ($user_response === false) {
    error_log('cURL error when fetching user info: ' . $curl_error);
    header('Location: ' . APP_URL . 'login.php?error=user_info_fetch_failed');
    exit;
}

if ($http_code !== 200) {
    error_log('Failed to fetch user info. Status: ' . $http_code . ' Response: ' . $user_response);
    
    // Log the full response for debugging (but don't expose to user)
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        error_log('User info response: ' . print_r($user_response, true));
    }
    
    header('Location: ' . APP_URL . 'login.php?error=user_info_fetch_failed');
    exit;
}

$user_info = json_decode($user_response, true);
if (json_last_error() !== JSON_ERROR_NONE || !$user_info || !isset($user_info['email'])) {
    error_log('Invalid user info response: ' . $user_response);
    header('Location: ' . APP_URL . 'login.php?error=invalid_user_info');
    exit;
}

// Validate required user information
$required_fields = ['email', 'id', 'verified_email'];
foreach ($required_fields as $field) {
    if (!isset($user_info[$field])) {
        error_log('Missing required user field: ' . $field);
        header('Location: ' . APP_URL . 'login.php?error=incomplete_user_info');
        exit;
    }
}

// Verify email is verified
if ($user_info['verified_email'] !== true) {
    error_log('Email not verified: ' . $user_info['email']);
    header('Location: ' . APP_URL . 'login.php?error=email_not_verified');
    exit;
}

// Sanitize user input
$email = filter_var($user_info['email'], FILTER_SANITIZE_EMAIL);
$name = isset($user_info['name']) ? filter_var($user_info['name'], FILTER_SANITIZE_STRING) : '';
$google_id = filter_var($user_info['id'], FILTER_SANITIZE_STRING);
$avatar = isset($user_info['picture']) ? filter_var($user_info['picture'], FILTER_VALIDATE_URL) : null;

// Check if user exists using prepared statements
$stmt = $mysqli->prepare("SELECT id, name, email, role, is_active, oauth_provider, oauth_id, avatar, password FROM users WHERE email = ? OR (oauth_provider = 'google' AND oauth_id = ?) LIMIT 1");
if (!$stmt) {
    error_log('Database prepare failed: ' . $mysqli->error);
    header('Location: ' . APP_URL . 'login.php?error=database_error');
    exit;
}

$stmt->bind_param('ss', $email, $google_id);
if (!$stmt->execute()) {
    error_log('Database query failed: ' . $stmt->error);
    header('Location: ' . APP_URL . 'login.php?error=database_error');
    exit;
}

$result = $stmt->get_result();
$user = $result ? $result->fetch_assoc() : null;
$stmt->close();

if ($user) {
    // Check if the account is active
    if (!$user['is_active']) {
        error_log('Login attempt for deactivated account: ' . $email);
        header('Location: ' . APP_URL . 'login.php?error=account_disabled');
        exit;
    }

    // Check if the account has a password (indicating it was created with email/password)
    if (!empty($user['password']) && empty($user['oauth_provider'])) {
        // Account exists with password but no OAuth provider - link the accounts
        $update_stmt = $mysqli->prepare("UPDATE users SET oauth_provider = 'google', oauth_id = ?, avatar = ?, last_login = NOW() WHERE id = ?");
        $update_stmt->bind_param('ssi', $google_id, $avatar, $user['id']);
        
        if (!$update_stmt->execute()) {
            error_log('Failed to link OAuth account: ' . $update_stmt->error);
            // Continue with login even if update fails
        }
        $update_stmt->close();
    }
    // Update OAuth info if not set or incomplete
    else {
        $hasProvider = !empty($user['oauth_provider']);
        $hasOauthId  = !empty($user['oauth_id']);
        $hasAvatar   = !empty($user['avatar']);

        if (!$hasProvider || !$hasOauthId || !$hasAvatar) {
            $update_stmt = $mysqli->prepare("UPDATE users SET oauth_provider = 'google', oauth_id = ?, avatar = ?, last_login = NOW() WHERE id = ?");
            if ($update_stmt) {
                $update_stmt->bind_param('ssi', $google_id, $avatar, $user['id']);
                
                if (!$update_stmt->execute()) {
                    error_log('Failed to update user OAuth info: ' . $update_stmt->error);
                    // Continue with login even if update fails
                }
                $update_stmt->close();
            } else {
                error_log('Prepare failed for user update: ' . $mysqli->error);
                // Continue with login even if prepare fails
            }
        $update_stmt->execute();
    }
    
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    // Set secure session parameters
    $session_params = session_get_cookie_params();
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $httponly = true;
    $samesite = 'Lax';

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => $session_params['lifetime'],
            'path' => '/',
            'domain' => $session_params['domain'],
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite
        ]);
    } else {
        // For PHP < 7.3
        session_set_cookie_params(
            $session_params['lifetime'],
            '/; samesite=' . $samesite,
            $session_params['domain'],
            $secure,
            $httponly
        );
    }

    // Set user session data
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['is_google_auth'] = true;
    $_SESSION['last_activity'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Update last login time
    $update_stmt = $mysqli->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    if ($update_stmt) {
        $update_stmt->bind_param('i', $user['id']);
        $update_stmt->execute();
        $update_stmt->close();
    }

    // Clear any existing OAuth state
    unset($_SESSION['oauth_state']);

    // Log successful login
    error_log('User logged in via Google: ' . $user['email'] . ' (ID: ' . $user['id'] . ')');

    // Redirect to dashboard or previous page with a success message
    $redirect = isset($_SESSION['redirect_after_login']) ? 
        $_SESSION['redirect_after_login'] : 
        'index.php?login=success';

    // Add success parameter if not already present
    $redirect .= (strpos($redirect, '?') === false ? '?' : '&') . 'oauth=google';

    // Clear the redirect URL
    unset($_SESSION['redirect_after_login']);

    // Perform a safe redirect
    header('Location: ' . filter_var(APP_URL . ltrim($redirect, '/'), FILTER_SANITIZE_URL));
    exit;
} else {
    // Create new user with secure defaults
    $default_role = 'customer'; // Default role for new users
    $is_active = 1; // Auto-activate OAuth users by default
    
    // Generate a random password that won't be used but satisfies NOT NULL constraint
    $password_hash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
    
    // Insert the new user with prepared statement
    $stmt = $mysqli->prepare("INSERT INTO users (name, email, password, oauth_provider, oauth_id, avatar, email_verified, is_active, role, created_at, updated_at) VALUES (?, ?, ?, 'google', ?, ?, 1, ?, ?, NOW(), NOW())");
    
    if (!$stmt) {
        error_log('Prepare failed for user creation: ' . $mysqli->error);
        header('Location: ' . APP_URL . 'login.php?error=registration_failed');
        exit;
    }
    
    $stmt->bind_param('ssssss', 
        $name,
        $email,
        $password_hash,
        $google_id,
        $avatar,
        $is_active,
        $default_role
    );
    
    if (!$stmt->execute()) {
        error_log('User creation failed: ' . $stmt->error);
        
        // Check for duplicate entry
        if ($stmt->errno === 1062) { // Duplicate entry error code
            header('Location: ' . APP_URL . 'login.php?error=email_already_exists');
        } else {
            header('Location: ' . APP_URL . 'login.php?error=registration_failed');
        }
        exit;
    }
    
    $user_id = $stmt->insert_id;
    $stmt->close();
    
    // Fetch the newly created user
    $stmt = $mysqli->prepare("SELECT id, name, email, role, is_active, oauth_provider, oauth_id, avatar FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $user_id);
    
    if (!$stmt->execute()) {
        error_log('Failed to fetch new user: ' . $stmt->error);
        // Continue with login even if fetch fails
        $user = [
            'id' => $user_id,
            'name' => $name,
            'email' => $email,
            'role' => $default_role,
            'is_active' => $is_active,
            'oauth_provider' => 'google',
            'oauth_id' => $google_id,
            'avatar' => $avatar
        ];
    } else {
        $result = $stmt->get_result();
        $user = $result ? $result->fetch_assoc() : null;
    }
    $stmt->close();
    $name   = $user_info['name'];
    $email  = $user_info['email'];
    $oauthId= $user_info['id'];
    $stmt->bind_param('ssss', $name, $email, $oauthId, $avatar);
    
    if ($stmt->execute()) {
        $user_id = $mysqli->insert_id;
        
        $_SESSION['user'] = [
            'id' => $user_id,
            'name' => $user_info['name'],
            'email' => $user_info['email'],
            'role' => 'customer'
        ];
        
        header('Location: ' . APP_URL);
        exit;
    } else {
        header('Location: ' . APP_URL . 'login.php?error=Failed to create account');
        exit;
    }
}

// Clear OAuth state
unset($_SESSION['oauth_state']);

