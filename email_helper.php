<?php
require_once 'config.php';
$oauth_config = require 'oauth_config.php';

// Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if vendor/autoload.php exists
$vendor_autoload = __DIR__ . '/vendor/autoload.php';
if (!file_exists($vendor_autoload)) {
    error_log("PHPMailer autoload file not found at: $vendor_autoload");
    // Try alternative path
    $vendor_autoload = dirname(__DIR__) . '/vendor/autoload.php';
}

if (file_exists($vendor_autoload)) {
    require $vendor_autoload;
} else {
    error_log("PHPMailer not found. Email functionality will be disabled.");
}

/**
 * Generate a random verification code
 */
if (!function_exists('generate_verification_code')) {
    function generate_verification_code($length = 6) {
        return str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }
}

/**
 * Send email using PHPMailer with Gmail SMTP
 */
if (!function_exists('send_email_smtp')) {
    function send_email_smtp($to, $subject, $body) {
        global $oauth_config;
        
        // Check if PHPMailer class exists
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            error_log("PHPMailer class not found. Cannot send email.");
            return false;
        }
        
        // Validate email configuration
        if (empty($oauth_config['email']['smtp']['username']) || empty($oauth_config['email']['smtp']['app_password'])) {
            error_log("SMTP credentials are missing. Cannot send email.");
            return false;
        }
        
        $mail = new PHPMailer(true);
        
        try {
            // Enable verbose debug output (only in development)
            // $mail->SMTPDebug = 2;
            // $mail->Debugoutput = function($str, $level) { error_log("SMTP: $str"); };
            
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $oauth_config['email']['smtp']['username'];
            $mail->Password   = $oauth_config['email']['smtp']['app_password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';
            
            // Timeout settings
            $mail->Timeout = 30;
            
            // Recipients
            $mail->setFrom($oauth_config['email']['smtp']['from_email'], $oauth_config['email']['smtp']['from_name']);
            $mail->addAddress($to);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body); // Plain text version
            
            $mail->send();
            error_log("Verification email sent successfully to: $to");
            return true;
        } catch (Exception $e) {
            // Log detailed error information
            $error_details = [
                'error' => $mail->ErrorInfo,
                'exception' => $e->getMessage(),
                'to' => $to,
                'subject' => $subject,
                'smtp_user' => $oauth_config['email']['smtp']['username']
            ];
            error_log("Email sending failed: " . json_encode($error_details));
            return false;
        }
    }
}

/**
 * Send verification code email
 */
if (!function_exists('send_verification_code_email')) {
    function send_verification_code_email($email, $name, $code) {
    $subject = "Verify Your Email - Paghilom Cafe";
    
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #2A5618; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background-color: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
            .code-box { background-color: #2A5618; color: white; font-size: 32px; font-weight: bold; text-align: center; padding: 20px; margin: 20px 0; border-radius: 8px; letter-spacing: 8px; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Paghilom Cafe</h1>
            </div>
            <div class='content'>
                <h2>Welcome, {$name}!</h2>
                <p>Thank you for registering with Paghilom Cafe. To complete your registration, please verify your email address.</p>
                <p>Your verification code is:</p>
                <div class='code-box'>{$code}</div>
                <p>This code will expire in 1 hour.</p>
                <p>If you didn't create an account with us, please ignore this email.</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " Paghilom Cafe. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
        
        return send_email_smtp($email, $subject, $body);
    }
}

/**
 * Save verification code to database
 */
if (!function_exists('save_verification_code')) {
    function save_verification_code($mysqli, $user_id, $code) {
    global $oauth_config;
    $expiry = date('Y-m-d H:i:s', time() + $oauth_config['email']['verification_code_expiry']);
        $stmt = $mysqli->prepare("UPDATE users SET verification_code = ?, verification_code_expiry = ?, email_verified = 0 WHERE id = ?");
        $stmt->bind_param("ssi", $code, $expiry, $user_id);
        return $stmt->execute();
    }
}

/**
 * Verify the code entered by user
 */
if (!function_exists('verify_code')) {
    function verify_code($mysqli, $email, $code) {
    $stmt = $mysqli->prepare("SELECT id, name, verification_code_expiry FROM users WHERE email = ? AND verification_code = ?");
    $stmt->bind_param("ss", $email, $code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Invalid verification code.'];
    }
    
    $user = $result->fetch_assoc();
    
    // Check if code has expired
    if (strtotime($user['verification_code_expiry']) < time()) {
        return ['success' => false, 'message' => 'Verification code has expired. Please request a new one.'];
    }
    
    // Mark email as verified
    $stmt = $mysqli->prepare("UPDATE users SET email_verified = 1, verification_code = NULL, verification_code_expiry = NULL WHERE id = ?");
    $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        
        return ['success' => true, 'message' => 'Email verified successfully! You can now login.'];
    }
}

/**
 * Send password reset email with token
 */
if (!function_exists('send_password_reset_email')) {
    function send_password_reset_email($email, $reset_token, $mysqli) {
        // Get user name
        $stmt = $mysqli->prepare("SELECT name FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if (!$user) return false;
        
        $subject = "Reset Your Password - Paghilom Cafe";
        $reset_link = (defined('APP_URL') ? APP_URL : 'http://localhost/paghilom/') . "reset_password.php?token=" . $reset_token;
        
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #2A5618; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .logo { width: 60px; height: 60px; border-radius: 50%; margin-bottom: 10px; }
                .content { background-color: #F6FFF6; padding: 30px; border-radius: 0 0 8px 8px; }
                .reset-btn { display: inline-block; padding: 12px 30px; background: #2A5618; color: white; text-decoration: none; border-radius: 8px; margin: 20px 0; font-weight: bold; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='" . (defined('APP_URL') ? APP_URL : 'http://localhost/paghilom/') . "uploads/paghilom_logo.png' alt='Paghilom Cafe' class='logo' style='width: 60px; height: 60px; border-radius: 50%; object-fit: cover;'>
                    <h1>Paghilom Cafe</h1>
                    <p style='margin: 0; opacity: 0.9;'>Password Reset Request</p>
                </div>
                <div class='content'>
                    <h2>Hello, {$user['name']}!</h2>
                    <p>We received a request to reset your password for your Paghilom Cafe account.</p>
                    <p>Click the button below to reset your password:</p>
                    <div style='text-align: center;'>
                        <a href='{$reset_link}' class='reset-btn'>Reset My Password</a>
                    </div>
                    <p><strong>Or copy and paste this link into your browser:</strong></p>
                    <p style='word-break: break-all; background: #fff; padding: 10px; border: 1px solid #ddd; border-radius: 4px;'>{$reset_link}</p>
                    <div class='warning'>
                        <strong>Important:</strong> This link will expire in 1 hour for security reasons.
                    </div>
                    <p>If you didn't request a password reset, please ignore this email. Your password will remain unchanged.</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " Paghilom Cafe. All rights reserved.</p>
                    <p>4091 Sitio 2 Barangay Bagumbayan, Sta. Cruz, Laguna</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return send_email_smtp($email, $subject, $body);
    }
}

/**
 * Generate secure token for password reset
 */
if (!function_exists('generate_token')) {
    function generate_token($length = 32) {
        return bin2hex(random_bytes($length));
    }
}
?>
