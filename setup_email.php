<?php
// Quick SMTP Setup - Paghilom Café
// Access: http://localhost/paghilom/setup_email.php
// DELETE THIS FILE AFTER SETUP!

require __DIR__.'/config.php';

// Only allow from localhost
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die('Access denied');
}

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $smtp_user = trim($_POST['smtp_user'] ?? '');
    $smtp_pass = trim($_POST['smtp_pass'] ?? '');
    
    if (empty($smtp_user) || empty($smtp_pass)) {
        $error = 'Both fields are required';
    } else {
        // Add settings to database
        $settings = [
            'smtp_user' => $smtp_user,
            'smtp_pass' => $smtp_pass,
            'smtp_from' => $smtp_user,
            'smtp_from_name' => 'Paghilom Cafe'
        ];
        
        $success = true;
        foreach ($settings as $key => $value) {
            if (!set_setting($key, $value)) {
                $success = false;
                break;
            }
        }
        
        if ($success) {
            $msg = 'SMTP settings saved successfully! You can now test the email.';
        } else {
            $error = 'Failed to save settings. Check database connection.';
        }
    }
}

// Check current settings
$current_user = get_setting('smtp_user', '');
$current_pass = get_setting('smtp_pass', '');
$is_configured = !empty($current_user) && !empty($current_pass);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup Email - Paghilom Cafe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #F6FFF6; padding: 40px; }
        .container { max-width: 600px; }
        .card-header { background: #2A5618; color: white; }
        .btn-primary { background: #2A5618; border-color: #2A5618; }
        .btn-primary:hover { background: #1f4012; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card shadow">
            <div class="card-header">
                <h4 class="mb-0">⚙️ SMTP Email Setup</h4>
            </div>
            <div class="card-body">
                <?php if ($is_configured): ?>
                    <div class="alert alert-success">
                        ✅ SMTP is configured!
                        <div class="mt-2">
                            <strong>Using:</strong> <?= htmlspecialchars($current_user) ?>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="test_email.php" class="btn btn-primary">Test Email Now</a>
                        <a href="forgot_password.php" class="btn btn-outline-primary">Test Forgot Password</a>
                    </div>
                    <hr>
                    <p class="text-muted small mb-0">Need to update credentials? Fill the form below.</p>
                <?php endif; ?>

                <?php if ($msg): ?>
                    <div class="alert alert-success"><?= $msg ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" class="mt-3">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Gmail Address</label>
                        <input type="email" name="smtp_user" class="form-control" 
                               placeholder="your-email@gmail.com" 
                               value="<?= htmlspecialchars($current_user) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Gmail App Password</label>
                        <input type="text" name="smtp_pass" class="form-control" 
                               placeholder="16-character app password" 
                               value="<?= htmlspecialchars($current_pass) ?>" required>
                        <small class="text-muted">
                            Get from: <a href="https://myaccount.google.com/apppasswords" target="_blank">Gmail App Passwords</a>
                        </small>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Save SMTP Settings</button>
                </form>

                <hr>

                <div class="alert alert-info small">
                    <strong>📖 Setup Steps:</strong>
                    <ol class="mb-0">
                        <li>Enable 2-Step Verification in Gmail</li>
                        <li>Go to <a href="https://myaccount.google.com/apppasswords" target="_blank">App Passwords</a></li>
                        <li>Select "Mail" → "Windows Computer"</li>
                        <li>Copy the 16-character password</li>
                        <li>Paste it above and click Save</li>
                    </ol>
                </div>

                <div class="alert alert-warning small">
                    <strong>⚠️ Security:</strong> Delete this file (setup_email.php) after configuration!
                </div>
            </div>
        </div>
    </div>
</body>
</html>
