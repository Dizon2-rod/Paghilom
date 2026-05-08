<?php
require __DIR__.'/config.php';

if(is_logged_in()){
    header('Location: index.php');
    exit;
}

include __DIR__.'/partials/header.php';

$msg = '';
$err = '';
$token = trim($_GET['token'] ?? '');

if (empty($token)) {
    header('Location: forgot_password.php');
    exit;
}

// Decode URL-encoded token (in case it was double-encoded)
$token = urldecode($token);

// Verify token - check if it exists first, then check expiration
$stmt = $mysqli->prepare("SELECT id, name, password_reset_token, password_reset_expires FROM users WHERE password_reset_token = ? AND is_active = 1");
$stmt->bind_param('s', $token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    $err = 'Invalid reset token. Please request a new password reset.';
} else {
    // Check expiration separately for better error messages
    if (empty($user['password_reset_expires'])) {
        $err = 'Invalid reset token. Please request a new password reset.';
        $user = null;
    } else {
        $expires = strtotime($user['password_reset_expires']);
        $now = time();
        
        // Add 5 minute grace period for timezone/server differences
        if ($expires < ($now - 300)) {
            $err = 'Reset token has expired. Please request a new password reset.';
            $user = null;
        } else {
            // Token is valid, keep user data but remove sensitive fields
            unset($user['password_reset_token']);
            unset($user['password_reset_expires']);
        }
    }
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    csrf_check();
    
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    if (empty($password)) {
        $err = 'Password is required.';
    } elseif (empty($confirm_password)) {
        $err = 'Please confirm your password.';
    } elseif ($password !== $confirm_password) {
        $err = 'Passwords do not match. Please make sure both passwords are identical.';
    } else {
        // Only validate password strength if passwords match
        $password_errors = validate_password_strength($password);
        if (!empty($password_errors)) {
            $err = implode('<br>', $password_errors);
        }
    }
    
    if (empty($err)) {
        // Update password and clear reset token
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("UPDATE users SET password_hash = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?");
        $stmt->bind_param('si', $password_hash, $user['id']);
        
        if ($stmt->execute()) {
            $msg = 'Password updated successfully! You can now sign in with your new password.';
            $user = null; // Hide the form
        } else {
            $err = 'Failed to update password. Please try again.';
        }
    }
}
?>

<style>
body { background: #F6FFF6; }
.password-strength {
    margin-top: 5px;
    font-size: 12px;
}

.strength-bar {
    height: 4px;
    background: #e9ecef;
    border-radius: 2px;
    overflow: hidden;
    margin-top: 5px;
}

.strength-fill {
    height: 100%;
    transition: all 0.3s ease;
    border-radius: 2px;
}

.strength-weak { background: #dc3545; width: 25%; }
.strength-fair { background: #ffc107; width: 50%; }
.strength-good { background: #17a2b8; width: 75%; }
.strength-strong { background: #2A5618; width: 100%; }

.password-toggle {
    position: relative;
}

.password-toggle .toggle-btn {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
}
.btn.btn-success { background-color: #2A5618; border-color: #2A5618; }
.btn.btn-success:hover { background-color: #244a15; border-color: #244a15; }
</style>

<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="text-center mb-3">
                        <img src="assets/img/logo.png" alt="Paghilom Café Logo" style="height: 100px; margin-bottom: 10px;">
                    </div>
                    <h1 class="h4 mb-1 text-center" style="color:#2A5618">Set New Password</h1>
                    <p class="text-muted text-center small mb-4">Create a strong password to keep your account secure.</p>
                    
                    <?php if($msg): ?>
                        <div class="alert alert-success small"><?= $msg ?></div>
                        <div class="text-center">
                            <a href="login.php" class="btn btn-success">Sign In</a>
                        </div>
                    <?php else: ?>
                    
                    <?php if($err): ?>
                        <div class="alert alert-danger small"><?= $err ?></div>
                    <?php endif; ?>
                    
                    <?php if($user): ?>
                    <form method="post" class="needs-validation" novalidate>
                        <?= csrf_field() ?>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <div class="password-toggle">
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button type="button" class="toggle-btn" onclick="togglePassword('password')">
                                    <i class="fas fa-eye" id="toggleIcon-password"></i>
                                </button>
                            </div>
                            <div class="password-strength">
                                <div class="strength-bar">
                                    <div class="strength-fill" id="strengthBar"></div>
                                </div>
                                <span id="strengthText">Enter a password</span>
                            </div>
                            <div class="invalid-feedback">
                                Please provide a strong password.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <div class="password-toggle">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <button type="button" class="toggle-btn" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye" id="toggleIcon-confirm_password"></i>
                                </button>
                            </div>
                            <div id="passwordMatchMessage" class="small mt-1" style="display:none;"></div>
                            <div class="invalid-feedback">
                                Passwords do not match.
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100 mb-3">
                            Update Password
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <div class="text-center">
                        <p class="small">
                            Remember your password? 
                            <a href="login.php" class="text-decoration-none">Sign in</a>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function togglePassword(fieldId) {
    const passwordInput = document.getElementById(fieldId);
    const toggleIcon = document.getElementById('toggleIcon-' + fieldId);
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// Password strength checker
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    
    if (password.length === 0) {
        strengthBar.className = 'strength-fill';
        strengthText.textContent = 'Enter a password';
        return;
    }
    
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    if (/[A-Z]/.test(password) && /[a-z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    
    if (strength <= 1) {
        strengthBar.className = 'strength-fill strength-weak';
        strengthText.textContent = 'Weak';
    } else if (strength <= 2) {
        strengthBar.className = 'strength-fill strength-fair';
        strengthText.textContent = 'Fair';
    } else if (strength <= 3) {
        strengthBar.className = 'strength-fill strength-good';
        strengthText.textContent = 'Good';
    } else {
        strengthBar.className = 'strength-fill strength-strong';
        strengthText.textContent = 'Strong';
    }
});

// Password confirmation checker - real-time validation
const confirmPasswordInput = document.getElementById('confirm_password');
const passwordInput = document.getElementById('password');
const matchMessage = document.getElementById('passwordMatchMessage');

function checkPasswordMatch() {
    const password = passwordInput.value;
    const confirmPassword = confirmPasswordInput.value;
    
    if (confirmPassword.length === 0) {
        matchMessage.style.display = 'none';
        confirmPasswordInput.setCustomValidity('');
        confirmPasswordInput.classList.remove('is-invalid', 'is-valid');
        return;
    }
    
    if (password !== confirmPassword) {
        matchMessage.style.display = 'block';
        matchMessage.textContent = 'Passwords do not match';
        matchMessage.style.color = '#dc3545';
        confirmPasswordInput.setCustomValidity('Passwords do not match');
        confirmPasswordInput.classList.add('is-invalid');
        confirmPasswordInput.classList.remove('is-valid');
    } else {
        matchMessage.style.display = 'block';
        matchMessage.textContent = 'Passwords match ✓';
        matchMessage.style.color = '#2A5618';
        confirmPasswordInput.setCustomValidity('');
        confirmPasswordInput.classList.add('is-valid');
        confirmPasswordInput.classList.remove('is-invalid');
    }
}

// Check match when either field changes
passwordInput.addEventListener('input', checkPasswordMatch);
confirmPasswordInput.addEventListener('input', checkPasswordMatch);

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
