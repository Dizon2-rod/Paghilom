<?php
require __DIR__.'/config.php';

if(is_logged_in()){
    header('Location: index.php');
    exit;
}

include __DIR__.'/partials/header.php';

$msg = '';
$err = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: login.php');
    exit;
}

// Verify token
$stmt = $mysqli->prepare("SELECT id, name, email FROM users WHERE email_verification_token = ? AND is_active = 1");
$stmt->bind_param('s', $token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    $err = 'Invalid verification token. Please check your email or request a new verification link.';
} else {
    // Mark email as verified
    $stmt = $mysqli->prepare("UPDATE users SET email_verified = 1, email_verification_token = NULL WHERE id = ?");
    $stmt->bind_param('i', $user['id']);
    
    if ($stmt->execute()) {
        $msg = 'Email verified successfully! You can now sign in to your account.';
    } else {
        $err = 'Failed to verify email. Please try again or contact support.';
    }
}
?>

<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4 text-center">
                    <h1 class="h4 mb-4">Email Verification</h1>
                    
                    <?php if($msg): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle fa-2x mb-3 text-success"></i>
                            <p class="mb-0"><?= $msg ?></p>
                        </div>
                        <a href="login.php" class="btn btn-success">
                            <i class="fas fa-sign-in-alt me-1"></i>Sign In
                        </a>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle fa-2x mb-3 text-danger"></i>
                            <p class="mb-0"><?= $err ?></p>
                        </div>
                        <div class="d-flex gap-2 justify-content-center">
                            <a href="login.php" class="btn btn-outline-primary">
                                <i class="fas fa-sign-in-alt me-1"></i>Sign In
                            </a>
                            <a href="register.php" class="btn btn-outline-secondary">
                                <i class="fas fa-user-plus me-1"></i>Register
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__.'/partials/footer.php'; ?>
