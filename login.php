<?php
require_once 'config.php';

// Prevent caching of login page - forces fresh check on back button
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

// Check if user is already logged in
if (is_logged_in()) {
    // Regenerate session ID for security
    if (!isset($_SESSION['regenerated'])) {
        session_regenerate_id(true);
        $_SESSION['regenerated'] = true;
    }
    
    // Redirect based on role
    $role = strtolower($_SESSION['user']['role'] ?? '');
    if ($role === 'admin') {
        // Admins now land on the new admin dashboard
        header('Location: admin/index.php');
    } elseif ($role === 'staff') {
        header('Location: pos/');
    } else {
        header('Location: index.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        // Check if input is email or username
        $hasPhotoCol = true;
        try {
            $stmt = $mysqli->prepare("SELECT id, name, email, password_hash, role, is_active, profile_photo, email_verified FROM users WHERE email = ? OR name = ?");
        } catch (Throwable $e) {
            // Fallback if profile_photo column doesn't exist in this DB
            $hasPhotoCol = false;
            $stmt = $mysqli->prepare("SELECT id, name, email, password_hash, role, is_active, email_verified FROM users WHERE email = ? OR name = ?");
        }
        if ($stmt) {
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();

                // Check if account is active
                if (!$user['is_active']) {
                    $error = "Account is disabled. Please contact support.";
                } elseif (isset($user['email_verified']) && !$user['email_verified']) {
                    $error = "Please verify your email before logging in. Check your email for the verification code.";
                } elseif (password_verify($password, $user['password_hash'])) {
                    // ✅ Login successful - Update last login
                    $update = $mysqli->prepare("UPDATE users SET last_login = NOW(), login_attempts = 0 WHERE id = ?");
                    $update->bind_param("i", $user['id']);
                    $update->execute();
                    
                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'role' => $user['role'],
                        'profile_photo' => $user['profile_photo'] ?? ''
                    ];

                    // Redirect based on role
                    $default = 'index.php';
                    $role = strtolower($user['role']);
                    if ($role === 'admin') {
                        // Admins now land on the new admin dashboard
                        header('Location: admin/index.php');
                        exit;
                    } elseif ($role === 'staff') {
                        header('Location: pos/');
                        exit;
                    }
                    $redirect = $_GET['redirect'] ?? 'index.php';
                    header("Location: " . $redirect);
                    exit;
                } else {
                    // Track failed login attempt
                    $update = $mysqli->prepare("UPDATE users SET login_attempts = login_attempts + 1 WHERE id = ?");
                    $update->bind_param("i", $user['id']);
                    $update->execute();
                    
                    $error = "Incorrect password.";
                }
            } else {
                $error = "Account not found. Please check your credentials.";
            }

            $stmt->close();
        } else {
            $error = "Database error. Please contact support.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<?php 
$PAGE_BG='auth-hero'; 

// Add meta refresh as ultimate fallback if JS fails
if (is_logged_in()) {
    $role = strtolower($_SESSION['user']['role'] ?? '');
    $redirect_url = 'index.php';
    if ($role === 'admin') {
        // Admins now land on the new admin dashboard
        $redirect_url = 'admin/index.php';
    } elseif ($role === 'staff') {
        $redirect_url = 'pos/';
    }
    // This is a backup - PHP header redirect should already work
    echo '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirect_url) . '">';
}

include __DIR__.'/partials/header.php'; 
?>

<body class="auth-hero">
<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <?php 
                        $logo_path = '';
                        if (file_exists('uploads/paghilom_logo.png')) {
                            $logo_path = 'uploads/paghilom_logo.png';
                        } elseif (file_exists('assets/img/logo.png')) {
                            $logo_path = 'assets/img/logo.png';
                        } elseif (file_exists('uploads/logo.png')) {
                            $logo_path = 'uploads/logo.png';
                        } elseif (file_exists('assets/uploads/logo.jpeg')) {
                            $logo_path = 'assets/uploads/logo.jpeg';
                        }
                        
if (file_exists('assets/img/logo.png')) { $logo_path = 'assets/img/logo.png'; }
                        if ($logo_path): ?>
                            <img src="<?= e($logo_path) ?>" alt="Paghilom Café Logo" style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%;">
                        <?php else: ?>
                            <div class="mb-3" style="font-size: 50px;">🍃</div>
                        <?php endif; ?>
                        <h3 class="fw-bold mb-0">Paghilom Cafe</h3>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger small"><?= e($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" autocomplete="off">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label">Email or Username</label>
                            <input type="text" name="username" class="form-control" required value="<?= e($_POST['username'] ?? '') ?>">
                        </div>

<div class="mb-3">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" name="password" id="password" class="form-control" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePw" aria-label="Show password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Login</button>
                    </form>

                    <div class="text-center my-3">
                        <small class="text-muted">OR</small>
                    </div>

                    <a href="google_login.php" class="btn btn-outline-success w-100 py-2 mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-google me-2" viewBox="0 0 16 16">
                            <path d="M15.545 6.558a9.42 9.42 0 0 1 .139 1.626c0 2.434-.87 4.492-2.384 5.885h.002C11.978 15.292 10.158 16 8 16A8 8 0 1 1 8 0a7.689 7.689 0 0 1 5.352 2.082l-2.284 2.284A4.347 4.347 0 0 0 8 3.166c-2.087 0-3.86 1.408-4.492 3.304a4.792 4.792 0 0 0 0 3.063h.003c.635 1.893 2.405 3.301 4.492 3.301 1.078 0 2.004-.276 2.722-.764h-.003a3.702 3.702 0 0 0 1.599-2.431H8v-3.08h7.545z"/>
                        </svg>
                        Continue with Google
                    </a>

                    <div class="text-center mt-3">
                        <small>
                            <a href="register.php" class="text-decoration-none fw-semibold" style="color: var(--primary);">Create Account</a> |
                            <a href="forgot_password.php" class="text-decoration-none fw-semibold" style="color: var(--primary);">Forgot Password?</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Prevent logged-in users from staying on login page
(function() {
    // This will be set by PHP if user is logged in (already handled by PHP redirect)
    // But add JS failsafe in case of browser back button
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            // Page loaded from cache, reload to check session
            window.location.reload();
        }
    });
})();

const toggleBtn = document.getElementById('togglePw');
if (toggleBtn) {
  toggleBtn.addEventListener('click', function(){
    const field = document.getElementById('password');
    const icon = this.querySelector('i');
    const show = field.type === 'password';
    field.type = show ? 'text' : 'password';
    icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
  });
}
</script>

<?php include __DIR__.'/partials/footer.php'; ?>
