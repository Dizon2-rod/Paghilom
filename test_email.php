<?php
// Test Email Configuration for Paghilom Café
// Access: http://localhost/paghilom/test_email.php

require __DIR__.'/config.php';

// Only allow access from localhost for security
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die('Access denied. This page can only be accessed from localhost.');
}

$result = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_email = trim($_POST['test_email'] ?? '');
    
    if (empty($test_email)) {
        $error = 'Please enter an email address.';
    } elseif (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Test sending email
        try {
            require_once __DIR__ . '/vendor/autoload.php';
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Get SMTP settings
            $smtp_user = get_setting('smtp_user', '');
            $smtp_pass = get_setting('smtp_pass', '');
            $smtp_from = get_setting('smtp_from', $smtp_user);
            $smtp_from_name = get_setting('smtp_from_name', 'Paghilom Cafe');
            
            if (empty($smtp_user) || empty($smtp_pass)) {
                throw new Exception('SMTP credentials not configured. Please run setup_smtp.sql first.');
            }
            
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $smtp_user;
            $mail->Password = $smtp_pass;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->SMTPDebug = 0; // Set to 2 for verbose debugging
            
            // Email content
            $mail->setFrom($smtp_from, $smtp_from_name);
            $mail->addAddress($test_email);
            $mail->isHTML(true);
            $mail->Subject = 'Test Email from Paghilom Cafe';
            
            $logoUrl = APP_URL . 'assets/img/logo.png';
            $mail->Body = "
            <html>
            <head>
                <style>
                    body { margin:0; padding:0; background: #F6FFF6; font-family: Arial, sans-serif; }
                    .container { max-width:600px; margin:20px auto; background:#ffffff; border-radius:8px; padding:30px; }
                    .header { text-align:center; margin-bottom:20px; }
                    .header img { height:60px; margin-bottom:10px; }
                    .header h1 { color:#2A5618; margin:0; }
                    .content { line-height:1.6; color:#333; }
                    .success { background:#d4edda; border:1px solid #c3e6cb; color:#155724; padding:15px; border-radius:5px; margin:20px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <img src='$logoUrl' alt='Paghilom Cafe Logo'>
                        <h1>Test Email Successful!</h1>
                    </div>
                    <div class='content'>
                        <div class='success'>
                            ✅ Your email configuration is working correctly!
                        </div>
                        <p>This is a test email from the Paghilom Café password reset system.</p>
                        <p><strong>SMTP Configuration:</strong></p>
                        <ul>
                            <li>Host: smtp.gmail.com</li>
                            <li>Port: 587</li>
                            <li>From: $smtp_from</li>
                            <li>From Name: $smtp_from_name</li>
                        </ul>
                        <p>If you received this email, your forgot password feature is ready to use!</p>
                        <p style='color:#666; font-size:12px; margin-top:30px;'>
                            You can now safely delete test_email.php from your server.
                        </p>
                    </div>
                </div>
            </body>
            </html>";
            
            $mail->AltBody = "Test email from Paghilom Cafe. If you received this, your SMTP configuration is working!";
            
            $mail->send();
            $result = 'success';
            
        } catch (Exception $e) {
            $error = "Email failed: " . $e->getMessage();
        }
    }
}

// Get current SMTP settings (masked password)
$smtp_user = get_setting('smtp_user', '');
$smtp_pass = get_setting('smtp_pass', '');
$smtp_configured = !empty($smtp_user) && !empty($smtp_pass);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email - Paghilom Cafe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #F6FFF6; padding: 40px 0; }
        .test-container { max-width: 600px; margin: 0 auto; }
        .card-header { background: #2A5618; color: white; }
        .btn-primary { background: #2A5618; border-color: #2A5618; }
        .btn-primary:hover { background: #1f4012; border-color: #1f4012; }
        .status-badge { padding: 5px 15px; border-radius: 20px; font-size: 12px; }
        .status-ok { background: #d4edda; color: #155724; }
        .status-error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="card shadow">
            <div class="card-header">
                <h4 class="mb-0">📧 Test Email Configuration</h4>
            </div>
            <div class="card-body">
                <?php if ($result === 'success'): ?>
                    <div class="alert alert-success">
                        <h5>✅ Email Sent Successfully!</h5>
                        <p class="mb-0">Check your inbox at <strong><?= htmlspecialchars($test_email) ?></strong></p>
                        <hr>
                        <p class="mb-0">Your password reset feature is now fully functional! 🎉</p>
                    </div>
                    <a href="forgot_password.php" class="btn btn-primary">Test Forgot Password</a>
                    <a href="login.php" class="btn btn-outline-secondary">Go to Login</a>
                <?php else: ?>
                    <div class="mb-4">
                        <h6>SMTP Status:</h6>
                        <?php if ($smtp_configured): ?>
                            <span class="status-badge status-ok">✓ Configured</span>
                            <small class="d-block mt-2 text-muted">
                                Using: <?= htmlspecialchars($smtp_user) ?>
                            </small>
                        <?php else: ?>
                            <span class="status-badge status-error">✗ Not Configured</span>
                            <div class="alert alert-warning mt-3">
                                <strong>Setup Required:</strong>
                                <ol class="mb-0">
                                    <li>Open phpMyAdmin: <a href="http://localhost/phpmyadmin" target="_blank">localhost/phpmyadmin</a></li>
                                    <li>Select <code>paghilom</code> database</li>
                                    <li>Go to SQL tab</li>
                                    <li>Open <code>setup_smtp.sql</code> file</li>
                                    <li>Replace email and password</li>
                                    <li>Click "Go"</li>
                                </ol>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <strong>Error:</strong><br>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="test_email" class="form-label">Test Email Address</label>
                            <input type="email" class="form-control" id="test_email" name="test_email" 
                                   placeholder="your-email@gmail.com" required>
                            <small class="text-muted">Enter your Gmail address to receive a test email</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100" <?= !$smtp_configured ? 'disabled' : '' ?>>
                            Send Test Email
                        </button>
                    </form>

                    <hr>
                    
                    <div class="text-muted small">
                        <p><strong>Setup Steps:</strong></p>
                        <ol>
                            <li>Enable 2-Step Verification in Gmail</li>
                            <li>Generate App Password: <a href="https://myaccount.google.com/apppasswords" target="_blank">Click Here</a></li>
                            <li>Run <code>setup_smtp.sql</code> in phpMyAdmin</li>
                            <li>Test email here</li>
                        </ol>
                        <p class="mb-0">📚 <a href="EMAIL_SETUP_GUIDE.md" target="_blank">Full Setup Guide</a></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-center mt-3">
            <small class="text-muted">Delete this file (test_email.php) after testing</small>
        </div>
    </div>

    <script>
    // Form validation
    (function() {
        'use strict';
        var forms = document.querySelectorAll('.needs-validation');
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
    </script>
</body>
</html>
