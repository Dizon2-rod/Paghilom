<?php
require __DIR__.'/config.php';

if(is_logged_in()){
    header('Location: index.php');
    exit;
}

include __DIR__.'/partials/header.php';

$msg = '';
$err = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $err = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Please enter a valid email address.';
    } else {
        // Check if email exists
        $stmt = $mysqli->prepare("SELECT id, name FROM users WHERE email = ? AND is_active = 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user) {
            // Generate reset token
            $reset_token = generate_token();
            // Use UTC time to avoid timezone issues, add 1 hour
            $reset_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Update user with reset token
            $stmt = $mysqli->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?");
            $stmt->bind_param('ssi', $reset_token, $reset_expires, $user['id']);
            
            if (!$stmt->execute()) {
                $err = 'Failed to generate reset token. Please try again.';
                error_log('Failed to update password reset token: ' . $stmt->error);
            } else {
                // Verify the token was saved correctly
                $check_stmt = $mysqli->prepare("SELECT password_reset_token, password_reset_expires FROM users WHERE id = ?");
                $check_stmt->bind_param('i', $user['id']);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result()->fetch_assoc();
                if ($check_result['password_reset_token'] !== $reset_token) {
                    error_log('Token mismatch after save. Expected: ' . $reset_token . ', Got: ' . ($check_result['password_reset_token'] ?? 'NULL'));
                }
                $check_stmt->close();
            }
            $stmt->close();
            
            // Send reset email
            if (send_password_reset_email($email, $reset_token, $mysqli)) {
                $msg = 'Password reset instructions have been sent to your email address.';
            } else {
                $err = 'Failed to send reset email. Please try again or contact support.';
            }
        } else {
            // Don't reveal if email exists or not for security
            $msg = 'If an account with that email exists, password reset instructions have been sent.';
        }
    }
}
?>

<style>
.password-reset-form {
    max-width: 400px;
    margin: 0 auto;
}
body { background: #F6FFF6; }
.card .card-body h1 { color: #2A5618; }
.btn.btn-success { background-color: #2A5618; border-color: #2A5618; }
.btn.btn-success:hover { background-color: #244a15; border-color: #244a15; }
</style>

<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="text-center mb-3">
                        <?php 
                        $logo_path = '';
                        if (file_exists('assets/img/logo.png')) {
                            $logo_path = 'assets/img/logo.png';
                        } elseif (file_exists('uploads/logo.png')) {
                            $logo_path = 'uploads/logo.png';
                        }
                        
                        if ($logo_path): ?>
                            <img src="<?= e($logo_path) ?>" alt="Paghilom Café Logo" style="height: 200px; margin-bottom: -50px; margin-top: -50;">
                        <?php endif; ?>
                    </div>
                    <h1 class="h4 mb-1 text-center">Reset Password</h1>
                    <p class="text-muted text-center small mb-4">We'll email you a link that expires in 1 hour.</p>
                    
                    <?php if($msg): ?>
                        <div class="alert alert-success small"><?= $msg ?></div>
                    <?php endif; ?>
                    
                    <?php if($err): ?>
                        <div class="alert alert-danger small"><?= $err ?></div>
                    <?php endif; ?>
                    
                    <?php if(empty($msg)): ?>
                    <form method="post" class="needs-validation" novalidate>
                        <?= csrf_field() ?>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            <div class="invalid-feedback">
                                Please provide a valid email address.
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100 mb-3">
                            Send Reset Instructions
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <div class="text-center">
                        <p class="small">
                            Remember your password? 
                            <a href="login.php" class="text-decoration-none">Sign in</a>
                        </p>
                        <p class="small">
                            Don't have an account? 
                            <a href="register.php" class="text-decoration-none">Create one</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Bootstrap form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();
</script>

<?php include __DIR__.'/partials/footer.php'; ?>
