<?php
// Authentication Helper Library

// Strong password validation
if (!function_exists('validate_password_strength')) {
    function validate_password_strength($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }
        
        return $errors;
    }
}

// Get password strength score (0-4)
if (!function_exists('get_password_strength')) {
    function get_password_strength($password) {
        $strength = 0;
        
        if (strlen($password) >= 8) $strength++;
        if (strlen($password) >= 12) $strength++;
        if (preg_match('/[A-Z]/', $password) && preg_match('/[a-z]/', $password)) $strength++;
        if (preg_match('/[0-9]/', $password)) $strength++;
        if (preg_match('/[^A-Za-z0-9]/', $password)) $strength++;
        
        return min(4, $strength);
    }
}

// Send verification email
if (!function_exists('send_verification_email')) {
    function send_verification_email($email, $token, $mysqli) {
    $link = APP_URL . "verify_email.php?token=" . urlencode($token);
    
    $subject = "Verify your email - Paghilom Cafe";
    $message = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #1e3932;'>Verify Your Email</h2>
                <p>Thank you for registering at Paghilom Cafe!</p>
                <p>Please click the button below to verify your email address:</p>
                <p style='text-align: center; margin: 30px 0;'>
                    <a href='$link' style='background: #1e3932; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>Verify Email</a>
                </p>
                <p>Or copy and paste this link: <br><a href='$link'>$link</a></p>
                <p>This link will expire in 24 hours.</p>
                <p>If you didn't create an account, please ignore this email.</p>
            </div>
        </body>
        </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: Paghilom Cafe <noreply@paghilomcafe.com>\r\n";
    
    return mail($email, $subject, $message, $headers);
    }
}

// Send password reset email
if (!function_exists('send_password_reset_email')) {
    function send_password_reset_email($email, $token, $mysqli) {
    $link = APP_URL . "reset_password.php?token=" . urlencode($token);
    
    $subject = "Reset your password - Paghilom Cafe";
    $message = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #1e3932;'>Reset Your Password</h2>
                <p>We received a request to reset your password.</p>
                <p>Click the button below to reset your password:</p>
                <p style='text-align: center; margin: 30px 0;'>
                    <a href='$link' style='background: #1e3932; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>Reset Password</a>
                </p>
                <p>Or copy and paste this link: <br><a href='$link'>$link</a></p>
                <p>This link will expire in 1 hour.</p>
                <p>If you didn't request this, please ignore this email.</p>
            </div>
        </body>
        </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: Paghilom Cafe <noreply@paghilomcafe.com>\r\n";
    
    return mail($email, $subject, $message, $headers);
    }
}

// Generate secure token
if (!function_exists('generate_token')) {
    function generate_token($length = 32) {
        return bin2hex(random_bytes($length));
    }
}

// Login with remember me
if (!function_exists('login_user')) {
    function login_user($user_id, $remember = false) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['last_activity'] = time();
    
    if ($remember) {
        $token = generate_token();
        $expire = time() + (30 * 24 * 60 * 60); // 30 days
        setcookie('remember_token', $token, $expire, '/', '', true, true);
        
        // Store hashed token in database
        global $mysqli;
        $hashed_token = password_hash($token, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("UPDATE users SET remember_token=?, remember_expires=FROM_UNIXTIME(?) WHERE id=?");
        $stmt->bind_param('sii', $hashed_token, $expire, $user_id);
        $stmt->execute();
        }
    }
}

// Check remember me cookie
if (!function_exists('check_remember_me')) {
    function check_remember_me($mysqli) {
    if (!empty($_COOKIE['remember_token']) && empty($_SESSION['user_id'])) {
        $token = $_COOKIE['remember_token'];
        
        $stmt = $mysqli->prepare("SELECT id, remember_token FROM users WHERE remember_expires > NOW() AND is_active=1");
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($user = $result->fetch_assoc()) {
            if (password_verify($token, $user['remember_token'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['last_activity'] = time();
                return true;
            }
        }
    }
    return false;
    }
}

// Logout and clear remember me
if (!function_exists('logout_user')) {
    function logout_user($mysqli) {
    if (!empty($_SESSION['user_id'])) {
        $stmt = $mysqli->prepare("UPDATE users SET remember_token=NULL, remember_expires=NULL WHERE id=?");
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
    }
    
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    session_destroy();
    }
}

// Calculate points earned from amount (₱5 = 1 point)
if (!function_exists('calculate_points_earned')) {
    function calculate_points_earned($amount) {
        return floor($amount / 5);
    }
}

// Award points to client
if (!function_exists('award_points')) {
    function award_points($client_id, $points, $order_id, $mysqli) {
    // Update client balance
    $stmt = $mysqli->prepare("UPDATE clients SET points_balance = points_balance + ? WHERE id = ?");
    $stmt->bind_param('ii', $points, $client_id);
    $stmt->execute();
    
    // Log transaction
    $stmt = $mysqli->prepare("INSERT INTO points_transactions (client_id, points_change, transaction_type, reference_type, reference_id, description) VALUES (?, ?, 'earned', 'order', ?, 'Points earned from order')");
    $stmt->bind_param('iii', $client_id, $points, $order_id);
    $stmt->execute();
    }
}

// Deduct points from client
if (!function_exists('deduct_points')) {
    function deduct_points($client_id, $points, $voucher_id, $mysqli) {
    $stmt = $mysqli->prepare("UPDATE clients SET points_balance = points_balance - ? WHERE id = ? AND points_balance >= ?");
    $stmt->bind_param('iii', $points, $client_id, $points);
    $stmt->execute();
    
    if ($mysqli->affected_rows > 0) {
        $stmt = $mysqli->prepare("INSERT INTO points_transactions (client_id, points_change, transaction_type, reference_type, reference_id, description) VALUES (?, ?, 'spent', 'voucher', ?, 'Points redeemed for voucher')");
        $neg_points = -$points;
        $stmt->bind_param('iii', $client_id, $neg_points, $voucher_id);
        $stmt->execute();
        return true;
        }
        return false;
    }
}
?>
