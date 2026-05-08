<?php
require_once 'config.php';
$oauth_config = require 'oauth_config.php';

// Check for errors from Google
if (isset($_GET['error'])) {
    header('Location: login.php?error=google_auth_failed');
    exit;
}

// Verify state to prevent CSRF
if (!isset($_GET['state']) || $_GET['state'] !== ($_SESSION['oauth_state'] ?? '')) {
    header('Location: login.php?error=invalid_state');
    exit;
}

// Get authorization code
$code = $_GET['code'] ?? '';
if (empty($code)) {
    header('Location: login.php?error=no_code');
    exit;
}

// Exchange code for access token
$token_url = 'https://oauth2.googleapis.com/token';
$token_data = [
    'code' => $code,
    'client_id' => $oauth_config['google']['client_id'],
    'client_secret' => $oauth_config['google']['client_secret'],
    'redirect_uri' => $oauth_config['google']['redirect_uri'],
    'grant_type' => 'authorization_code'
];

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$token_response = curl_exec($ch);
curl_close($ch);

$token_json = json_decode($token_response, true);

if (!isset($token_json['access_token'])) {
    header('Location: login.php?error=token_failed');
    exit;
}

// Get user info from Google
$user_info_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
$ch = curl_init($user_info_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token_json['access_token']
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$user_info_response = curl_exec($ch);
curl_close($ch);

$user_info = json_decode($user_info_response, true);

if (!isset($user_info['email'])) {
    header('Location: login.php?error=no_email');
    exit;
}

$email     = $user_info['email'];
$name      = $user_info['name'] ?? '';
$google_id = $user_info['id'] ?? '';
$picture   = $user_info['picture'] ?? '';

// Ensure password_set column exists (MariaDB supports IF NOT EXISTS)
@$mysqli->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS password_set TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash");

// Find existing user by email
$stmt = $mysqli->prepare("SELECT id, name, email, role, is_active, profile_photo, password_hash, COALESCE(password_set, 0) as password_set FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$need_set_password = false;
$profile_filename = '';

// Helper: download and save profile photo
function save_google_avatar($url, $user_id) {
    if (empty($url) || empty($user_id)) return '';
    $dir = __DIR__ . '/assets/clients';
    if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
    $ext = pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION);
    if (!$ext || strlen($ext) > 4) { $ext = 'jpg'; }
    $filename = 'user_' . intval($user_id) . '_' . time() . '.' . $ext;
    $path = $dir . '/' . $filename;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    $data = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code >= 200 && $code < 300 && $data) {
        file_put_contents($path, $data);
        return $filename;
    }
    return '';
}

if ($user) {
    if (!$user['is_active']) {
        header('Location: login.php?error=account_disabled');
        exit;
    }

    // Update OAuth identifiers and last_login
    $upd = $mysqli->prepare("UPDATE users SET oauth_provider='google', oauth_id=?, avatar=?, last_login=NOW(), email_verified=1 WHERE id=?");
    $upd->bind_param('ssi', $google_id, $picture, $user['id']);
    $upd->execute();

    // If no profile photo saved yet, fetch from Google once
    if (empty($user['profile_photo'])) {
        $saved = save_google_avatar($picture, $user['id']);
        if ($saved) {
            $stmt = $mysqli->prepare("UPDATE users SET profile_photo=? WHERE id=?");
            $stmt->bind_param('si', $saved, $user['id']);
            $stmt->execute();
            $profile_filename = $saved;
        }
    } else {
        $profile_filename = $user['profile_photo'];
    }

    // Decide if user must set password
    if (empty($user['password_hash']) || intval($user['password_set']) === 0) {
        $need_set_password = true;
    }

    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'profile_photo' => $profile_filename,
    ];
} else {
    // Create new user with Google info; no password yet
    $stmt = $mysqli->prepare("INSERT INTO users (name, email, password_hash, profile_photo, avatar, role, email_verified, is_active, oauth_provider, oauth_id, last_login, password_set) VALUES (?, ?, NULL, NULL, ?, 'customer', 1, 1, 'google', ?, NOW(), 0)");
    $stmt->bind_param('ssss', $name, $email, $picture, $google_id);
    if ($stmt->execute()) {
        $user_id = $mysqli->insert_id;
        // Download and save avatar
        $saved = save_google_avatar($picture, $user_id);
        if ($saved) {
            $up = $mysqli->prepare("UPDATE users SET profile_photo=? WHERE id=?");
            $up->bind_param('si', $saved, $user_id);
            $up->execute();
            $profile_filename = $saved;
        }

        $_SESSION['user'] = [
            'id' => $user_id,
            'name' => $name,
            'email' => $email,
            'role' => 'customer',
            'profile_photo' => $profile_filename,
        ];
        $need_set_password = true; // always for new Google users
    } else {
        header('Location: login.php?error=registration_failed');
        exit;
    }
}

// Clear OAuth state
unset($_SESSION['oauth_state']);

// Force set password flow if needed
if ($need_set_password) {
    $_SESSION['require_password_set'] = 1;
    header('Location: set_password.php?first=1');
    exit;
}

// Redirect to home page
header('Location: index.php');
exit;
?>
