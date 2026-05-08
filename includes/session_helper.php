<?php
/**
 * Session Management Helper Functions
 * For Paghilom Cafe Management System
 */

/**
 * Initialize secure session
 */
function init_secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        // Set secure session parameters
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', 1);
        }
        
        session_start();
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } elseif (time() - $_SESSION['created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}

/**
 * Set flash message
 */
function set_flash($type, $message) {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash messages
 */
function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return [];
}

/**
 * Check if flash messages exist
 */
function has_flash() {
    return isset($_SESSION['flash']) && !empty($_SESSION['flash']);
}

/**
 * Display flash messages HTML
 */
function display_flash() {
    $messages = get_flash();
    $html = '';
    
    foreach ($messages as $flash) {
        $type = htmlspecialchars($flash['type']);
        $message = htmlspecialchars($flash['message']);
        $html .= "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
            {$message}
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>";
    }
    
    return $html;
}

/**
 * Set session value
 */
function set_session($key, $value) {
    $_SESSION[$key] = $value;
}

/**
 * Get session value
 */
function get_session($key, $default = null) {
    return $_SESSION[$key] ?? $default;
}

/**
 * Unset session value
 */
function unset_session($key) {
    if (isset($_SESSION[$key])) {
        unset($_SESSION[$key]);
    }
}

/**
 * Check if session key exists
 */
function has_session($key) {
    return isset($_SESSION[$key]);
}

/**
 * Destroy all session data
 */
function destroy_session() {
    $_SESSION = [];
    
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    
    session_destroy();
}

/**
 * Regenerate session ID
 */
function regenerate_session() {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

/**
 * Set user session
 */
function set_user_session($user) {
    // Normalize legacy roles to the new model (owner→admin, cashier→staff)
    $role = $user['role'];
    if ($role === 'owner') { $role = 'admin'; }
    if ($role === 'cashier') { $role = 'staff'; }
    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $role,
        'logged_in_at' => time()
    ];
}

/**
 * Get user from session
 */
function get_user_session() {
    return $_SESSION['user'] ?? null;
}

/**
 * Check if user is logged in
 */
function is_user_logged_in() {
    return isset($_SESSION['user']) && isset($_SESSION['user']['id']);
}

/**
 * Get current user ID
 */
function get_current_user_id() {
    return $_SESSION['user']['id'] ?? null;
}

/**
 * Get current user role
 */
function get_current_user_role() {
    return $_SESSION['user']['role'] ?? 'guest';
}

/**
 * Check if current user is admin
 */
function is_current_user_admin() {
    $role = get_current_user_role();
    return $role === 'admin';
}

/**
 * Check if current user is staff
 */
function is_current_user_staff() {
    $role = get_current_user_role();
    return $role === 'staff';
}

/**
 * Check if current user has role
 */
function user_has_role($role) {
    return get_current_user_role() === $role;
}

/**
 * Set remember me cookie
 */
function set_remember_cookie($token, $days = 30) {
    $expire = time() + ($days * 24 * 60 * 60);
    setcookie('remember_token', $token, $expire, '/', '', true, true);
}

/**
 * Get remember me cookie
 */
function get_remember_cookie() {
    return $_COOKIE['remember_token'] ?? null;
}

/**
 * Delete remember me cookie
 */
function delete_remember_cookie() {
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
}

/**
 * Check session timeout
 */
function check_session_timeout($timeout = 3600) {
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > $timeout) {
            destroy_session();
            return false;
        }
    }
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Prevent session fixation
 */
function prevent_session_fixation() {
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
    }
}

/**
 * Set old input for form repopulation
 */
function set_old_input($data) {
    $_SESSION['old_input'] = $data;
}

/**
 * Get old input value
 */
function old($key, $default = '') {
    $value = $_SESSION['old_input'][$key] ?? $default;
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Clear old input
 */
function clear_old_input() {
    unset($_SESSION['old_input']);
}

/**
 * Set validation errors
 */
function set_errors($errors) {
    $_SESSION['errors'] = $errors;
}

/**
 * Get validation errors
 */
function get_errors() {
    if (isset($_SESSION['errors'])) {
        $errors = $_SESSION['errors'];
        unset($_SESSION['errors']);
        return $errors;
    }
    return [];
}

/**
 * Get single error
 */
function get_error($key) {
    $errors = get_errors();
    return $errors[$key] ?? null;
}

/**
 * Check if error exists
 */
function has_error($key = null) {
    if ($key === null) {
        return isset($_SESSION['errors']) && !empty($_SESSION['errors']);
    }
    return isset($_SESSION['errors'][$key]);
}

/**
 * Display error message
 */
function display_error($key) {
    if (isset($_SESSION['errors'][$key])) {
        $error = htmlspecialchars($_SESSION['errors'][$key]);
        return "<div class='invalid-feedback d-block'>{$error}</div>";
    }
    return '';
}
?>
