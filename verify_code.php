<?php
require __DIR__.'/config.php';
require __DIR__.'/email_helper.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$email = $_SESSION['pending_verification_email'] ?? '';
if (empty($email)) {
    header('Location: register.php');
    exit;
}

include __DIR__.'/partials/header.php';

$msg = '';
$err = '';

// Show error message if email failed to send during registration
if (isset($_SESSION['verification_email_error'])) {
    $err = $_SESSION['verification_email_error'];
    unset($_SESSION['verification_email_error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    
    $code = trim($_POST['code'] ?? '');
    
    if (empty($code)) {
        $err = 'Please enter the verification code.';
    } else {
        $result = verify_code($mysqli, $email, $code);
        
        if ($result['success']) {
            $msg = $result['message'];
            unset($_SESSION['pending_verification_email']);
            echo "<script>alert('{$result['message']}'); window.location.href='login.php';</script>";
            exit;
        } else {
            $err = $result['message'];
        }
    }
}

// Resend code functionality
if (isset($_GET['resend'])) {
    $stmt = $mysqli->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if ($user) {
        $verification_code = generate_verification_code(6);
        save_verification_code($mysqli, $user['id'], $verification_code);
        
        if (send_verification_code_email($email, $user['name'], $verification_code)) {
            $msg = 'Verification code resent! Please check your email.';
        } else {
            $err = 'Failed to resend verification code. Please try again.';
        }
    }
}
?>

<style>
body {
    background: url('uploads/gallery/background.jpeg') no-repeat center center fixed !important;
    background-size: cover !important;
    position: relative;
    min-height: 100vh;
}

body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.3);
    z-index: 0;
}

main {
    position: relative;
    z-index: 1;
}

.card {
    position: relative;
    z-index: 1;
}

.code-input {
    font-size: 2rem;
    text-align: center;
    letter-spacing: 1rem;
    font-weight: bold;
}
</style>

<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <?php if (file_exists('uploads/paghilom_logo.png')): ?>
                            <img src="uploads/paghilom_logo.png" alt="Paghilom Café Logo" style="width: 100px; height: 100px; object-fit: cover; border-radius: 50%; border: 3px solid var(--primary);">
                        <?php else: ?>
                            <div class="mb-3" style="font-size: 50px;">🍃</div>
                        <?php endif; ?>
                        <h3 class="fw-bold mb-2">Verify Your Email</h3>
                        <p class="text-muted small mb-0">Enter the 6-digit code sent to<br><strong><?= e($email) ?></strong></p>
                    </div>

                    <?php if ($msg): ?>
                        <div class="alert alert-success small"><?= $msg ?></div>
                    <?php endif; ?>
                    <?php if ($err): ?>
                        <div class="alert alert-danger small"><?= $err ?></div>
                    <?php endif; ?>

                    <form method="POST" autocomplete="off">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label text-center w-100">Verification Code</label>
                            <input type="text" name="code" class="form-control code-input" required 
                                   maxlength="6" pattern="[0-9]{6}" placeholder="000000"
                                   inputmode="numeric">
                            <small class="text-muted d-block text-center mt-2">Enter the 6-digit code from your email</small>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold mb-3">Verify Email</button>
                        
                        <div class="text-center">
                            <small class="text-muted">Didn't receive the code?</small><br>
                            <a href="?resend=1" class="text-decoration-none fw-semibold" style="color: var(--primary);">Resend Code</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Auto-format code input
document.querySelector('.code-input').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});
</script>

<?php include __DIR__.'/partials/footer.php'; ?>
