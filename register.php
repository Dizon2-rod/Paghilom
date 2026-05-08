<?php
require __DIR__.'/config.php';
require __DIR__.'/email_helper.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$PAGE_BG = 'auth-hero';
include __DIR__.'/partials/header.php';

$msg = '';
$err = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $agree_terms = isset($_POST['agree_terms']);
    
    // Validation
    if (empty($name)) $errors[] = 'Name is required.';
    elseif (strlen($name) < 2) $errors[] = 'Name must be at least 2 characters long.';
    
    if (empty($email)) $errors[] = 'Email is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (!empty($phone)) {
        if (!preg_match('/^09[0-9]{9}$/', $phone)) {
            $errors[] = 'Phone number must be exactly 11 digits starting with 09.';
        }
    }
    
    if (!$agree_terms) $errors[] = 'You must agree to the terms and conditions.';
    
    // Check if email already exists
    if (empty($errors)) {
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $errors[] = 'An account with this email already exists.';
        }
    }
    
    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Create user account
        $stmt = $mysqli->prepare("INSERT INTO users (name, email, password_hash, phone, role, email_verified) VALUES (?, ?, ?, ?, 'customer', 0)");
        $stmt->bind_param('ssss', $name, $email, $password_hash, $phone);
        
        if ($stmt->execute()) {
            $user_id = $mysqli->insert_id;
            
            // Generate and save verification code
            $verification_code = generate_verification_code(6);
            save_verification_code($mysqli, $user_id, $verification_code);
            
            // Send verification email
            $email_sent = send_verification_code_email($email, $name, $verification_code);
            
            // Always redirect to verification page, even if email fails
            // User can resend the code from there
            $_SESSION['pending_verification_email'] = $email;
            
            if ($email_sent) {
                $_SESSION['verification_email_sent'] = true;
                echo "<script>alert('Account created! Please check your email for the verification code.'); window.location.href='verify_code.php';</script>";
            } else {
                // Log the error for debugging
                error_log("Failed to send verification email to: $email. User ID: $user_id");
                $_SESSION['verification_email_sent'] = false;
                $_SESSION['verification_email_error'] = 'We created your account, but we couldn\'t send the verification email. Please use the "Resend Code" button below.';
                echo "<script>alert('Account created! However, we couldn\\'t send the verification email. Please use the Resend Code button on the next page.'); window.location.href='verify_code.php';</script>";
            }
            exit;
        } else {
            $err = 'Failed to create account. Please try again.';
        }
    } else {
        $err = implode('<br>', $errors);
    }
}
?>


<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <?php 
                        $logo_path = '';
                        if (file_exists('assets/img/logo.png')) {
                            $logo_path = 'assets/img/logo.png';
                        } elseif (file_exists('uploads/logo.png')) {
                            $logo_path = 'uploads/logo.png';
                        } elseif (file_exists('assets/uploads/logo.jpeg')) {
                            $logo_path = 'assets/uploads/logo.jpeg';
                        }
                        
                        if ($logo_path): ?>
                            <img src="<?= e($logo_path) ?>" alt="Paghilom Café Logo" style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%;">
                        <?php else: ?>
                            <div class="mb-3" style="font-size: 50px;">🍃</div>
                        <?php endif; ?>
                        <h3 class="fw-bold mb-2">Paghilom Cafe</h3>
                        <p class="text-muted small mb-0">Create Account</p>
                    </div>

                    <?php if ($msg): ?>
                        <div class="alert alert-success small"><?= $msg ?></div>
                    <?php endif; ?>
                    <?php if ($err): ?>
                        <div class="alert alert-danger small"><?= $err ?></div>
                    <?php endif; ?>

                    <form method="post" class="needs-validation" novalidate>
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" required 
                                   value="<?= htmlspecialchars($name ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required 
                                   value="<?= htmlspecialchars($email ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number (Optional)</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   maxlength="11" placeholder="09XXXXXXXXX"
                                   value="<?= htmlspecialchars($phone ?? '') ?>">
                            <small class="text-muted">11 digits (e.g., 09171234567)</small>
                        </div>

<div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group" style="border-radius: 0.375rem; overflow: hidden;">
                                <input type="password" class="form-control" id="password" name="password" required style="border-right: 0;">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password')" id="toggleIcon-password" aria-label="Show password" style="border-left: 0;">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <!-- Password Strength Indicator -->
                            <div class="mt-2">
                                <div class="progress" style="height: 5px;">
                                    <div id="password-strength-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small id="password-strength-text" class="text-muted"></small>
                            </div>
                            <small class="text-muted d-block mt-1">
                                <div id="password-requirements">
                                    <div id="req-length" class="text-danger"><i class="bi bi-x-circle"></i> At least 8 characters</div>
                                    <div id="req-uppercase" class="text-danger"><i class="bi bi-x-circle"></i> One uppercase letter</div>
                                    <div id="req-lowercase" class="text-danger"><i class="bi bi-x-circle"></i> One lowercase letter</div>
                                    <div id="req-number" class="text-danger"><i class="bi bi-x-circle"></i> One number</div>
                                </div>
                            </small>
                        </div>

<div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="input-group" style="border-radius: 0.375rem; overflow: hidden;">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required style="border-right: 0;">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirm_password')" id="toggleIcon-confirm_password" aria-label="Show password" style="border-left: 0;">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="agree_terms" name="agree_terms" required>
                            <label class="form-check-label" for="agree_terms">
                                I agree to the <a href="terms.php" target="_blank">Terms and Conditions</a>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold">Create Account</button>
                    </form>

                    <div class="text-center my-3">
                        <small class="text-muted">OR</small>
                    </div>

                    <a href="google_login.php" class="btn btn-outline-success w-100 py-2 mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-google me-2" viewBox="0 0 16 16">
                            <path d="M15.545 6.558a9.42 9.42 0 0 1 .139 1.626c0 2.434-.87 4.492-2.384 5.885h.002C11.978 15.292 10.158 16 8 16A8 8 0 1 1 8 0a7.689 7.689 0 0 1 5.352 2.082l-2.284 2.284A4.347 4.347 0 0 0 8 3.166c-2.087 0-3.86 1.408-4.492 3.304a4.792 4.792 0 0 0 0 3.063h.003c.635 1.893 2.405 3.301 4.492 3.301 1.078 0 2.004-.276 2.722-.764h-.003a3.702 3.702 0 0 0 1.599-2.431H8v-3.08h7.545z"/>
                        </svg>
                        Sign up with Google
                    </a>

                    <div class="text-center mt-3">
                        <small>Already have an account? <a href="login.php" class="fw-semibold text-decoration-none" style="color: var(--primary);">Sign in</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function togglePassword(fieldId) {
    const input = document.getElementById(fieldId);
    const iconBtn = document.getElementById('toggleIcon-' + fieldId);
    const icon = iconBtn.querySelector('i');
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
}

// Password strength checker
const passwordInput = document.getElementById('password');
const strengthBar = document.getElementById('password-strength-bar');
const strengthText = document.getElementById('password-strength-text');

passwordInput.addEventListener('input', function() {
    const password = this.value;
    let strength = 0;
    
    // Check requirements
    const hasLength = password.length >= 8;
    const hasUppercase = /[A-Z]/.test(password);
    const hasLowercase = /[a-z]/.test(password);
    const hasNumber = /[0-9]/.test(password);
    
    // Update requirement indicators
    updateRequirement('req-length', hasLength);
    updateRequirement('req-uppercase', hasUppercase);
    updateRequirement('req-lowercase', hasLowercase);
    updateRequirement('req-number', hasNumber);
    
    // Calculate strength
    if (hasLength) strength++;
    if (hasUppercase) strength++;
    if (hasLowercase) strength++;
    if (hasNumber) strength++;
    if (password.length >= 12) strength++;
    if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++;
    
    // Update strength bar
    const percentage = (strength / 6) * 100;
    strengthBar.style.width = percentage + '%';
    
    // Update color and text based on strength
    if (strength === 0) {
        strengthBar.className = 'progress-bar';
        strengthText.textContent = '';
    } else if (strength <= 2) {
        strengthBar.className = 'progress-bar bg-danger';
        strengthText.textContent = 'Weak password';
        strengthText.className = 'text-danger';
    } else if (strength <= 4) {
        strengthBar.className = 'progress-bar bg-warning';
        strengthText.textContent = 'Medium password';
        strengthText.className = 'text-warning';
    } else {
        strengthBar.className = 'progress-bar bg-success';
        strengthText.textContent = 'Strong password';
        strengthText.className = 'text-success';
    }
});

function updateRequirement(id, met) {
    const element = document.getElementById(id);
    if (met) {
        element.className = 'text-success';
        element.innerHTML = element.innerHTML.replace('bi-x-circle', 'bi-check-circle');
    } else {
        element.className = 'text-danger';
        element.innerHTML = element.innerHTML.replace('bi-check-circle', 'bi-x-circle');
    }
}

// Confirm password validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const pw = passwordInput.value;
    this.setCustomValidity(this.value && this.value !== pw ? 'Passwords do not match' : '');
});

// Philippine phone number validation (11 digits only)
const phoneInput = document.getElementById('phone');
phoneInput.addEventListener('input', function(e) {
    // Remove non-digit characters
    this.value = this.value.replace(/\D/g, '');
    
    // Limit to 11 digits
    if (this.value.length > 11) {
        this.value = this.value.slice(0, 11);
    }
});

phoneInput.addEventListener('blur', function() {
    if (this.value && this.value.length !== 11) {
        this.setCustomValidity('Phone number must be exactly 11 digits');
    } else if (this.value && !this.value.startsWith('09')) {
        this.setCustomValidity('Phone number must start with 09');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php include __DIR__.'/partials/footer.php'; ?>
