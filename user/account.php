<?php
require __DIR__.'/../config.php';
require __DIR__.'/../lib/auth.php';

// Check if user is logged in
if (!isset($_COOKIE['client_phone'])) {
    header('Location: ../login.php');
    exit;
}

$phone = $_COOKIE['client_phone'];
$client = $mysqli->query("SELECT * FROM clients WHERE phone='$phone' LIMIT 1")->fetch_assoc();

if (!$client) {
    header('Location: ../login.php');
    exit;
}

$success = '';
$errors = [];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    csrf_check();
    
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $new_phone = trim($_POST['phone'] ?? '');
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (empty($new_phone)) {
        $errors[] = "Phone number is required";
    }
    
    // Check if email/phone already taken by another user
    if (empty($errors)) {
        $stmt = $mysqli->prepare("SELECT id FROM clients WHERE (email=? OR phone=?) AND id != ?");
        $stmt->bind_param('ssi', $email, $new_phone, $client['id']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Email or phone number already in use";
        }
    }
    
    if (empty($errors)) {
        $stmt = $mysqli->prepare("UPDATE clients SET name=?, email=?, phone=? WHERE id=?");
        $stmt->bind_param('sssi', $name, $email, $new_phone, $client['id']);
        
        if ($stmt->execute()) {
            // Update cookie if phone changed
            if ($new_phone !== $phone) {
                setcookie('client_phone', $new_phone, time() + (86400 * 30), '/');
            }
            $success = "Profile updated successfully!";
            $client = $mysqli->query("SELECT * FROM clients WHERE id={$client['id']} LIMIT 1")->fetch_assoc();
        } else {
            $errors[] = "Update failed. Please try again.";
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    csrf_check();
    
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password)) {
        $errors[] = "Current password is required";
    } elseif (!password_verify($current_password, $client['password'])) {
        $errors[] = "Current password is incorrect";
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = "New passwords do not match";
    }
    
    $pwd_errors = validate_password_strength($new_password);
    if (!empty($pwd_errors)) {
        $errors = array_merge($errors, $pwd_errors);
    }
    
    if (empty($errors)) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("UPDATE clients SET password=? WHERE id=?");
        $stmt->bind_param('si', $hashed, $client['id']);
        
        if ($stmt->execute()) {
            $success = "Password changed successfully!";
        } else {
            $errors[] = "Password change failed. Please try again.";
        }
    }
}

$page_title = "My Account";
include __DIR__.'/includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold">My Account</h2>
        <p class="text-muted">Manage your personal information and security</p>
    </div>
</div>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i>
    <?= e($success) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle me-2"></i>
    <strong>Error:</strong>
    <ul class="mb-0 mt-2">
        <?php foreach ($errors as $error): ?>
        <li><?= e($error) ?></li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <!-- Profile Information -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <h4 class="fw-bold mb-4">
                    <i class="fas fa-user me-2 text-primary"></i>
                    Profile Information
                </h4>
                
                <form method="post">
                    <?= csrf_field() ?>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name</label>
                        <input type="text" name="name" class="form-control" required value="<?= e($client['name']) ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" name="email" class="form-control" required value="<?= e($client['email']) ?>">
                        <small class="text-muted">Used for order notifications</small>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="tel" name="phone" class="form-control" required value="<?= e($client['phone']) ?>">
                        <small class="text-muted">Used for loyalty rewards</small>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary w-100">
                        <i class="fas fa-save me-2"></i>Update Profile
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Change Password -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <h4 class="fw-bold mb-4">
                    <i class="fas fa-lock me-2 text-warning"></i>
                    Change Password
                </h4>
                
                <form method="post">
                    <?= csrf_field() ?>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Current Password</label>
                        <div class="position-relative">
                            <input type="password" name="current_password" id="current_password" class="form-control" required>
                            <span class="password-toggle" onclick="togglePasswordField('current_password')" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                                <i class="fas fa-eye" id="current_password-icon"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Password</label>
                        <div class="position-relative">
                            <input type="password" name="new_password" id="new_password" class="form-control" required minlength="8">
                            <span class="password-toggle" onclick="togglePasswordField('new_password')" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                                <i class="fas fa-eye" id="new_password-icon"></i>
                            </span>
                        </div>
                        <small class="text-muted">
                            Must contain: 8+ characters, uppercase, lowercase, number, special character
                        </small>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Confirm New Password</label>
                        <div class="position-relative">
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="8">
                            <span class="password-toggle" onclick="togglePasswordField('confirm_password')" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                                <i class="fas fa-eye" id="confirm_password-icon"></i>
                            </span>
                        </div>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-warning w-100">
                        <i class="fas fa-key me-2"></i>Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Account Information -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="fw-bold mb-4">
                    <i class="fas fa-info-circle me-2 text-info"></i>
                    Account Information
                </h4>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="text-muted small">Customer ID</label>
                        <p class="fw-bold">#<?= str_pad($client['id'], 6, '0', STR_PAD_LEFT) ?></p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted small">QR Code</label>
                        <p class="fw-bold"><?= e($client['qr_code']) ?></p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted small">Member Since</label>
                        <p class="fw-bold"><?= date('M d, Y', strtotime($client['created_at'])) ?></p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted small">Current Points</label>
                        <p class="fw-bold text-success"><?= number_format($client['points']) ?> points</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted small">Email Verified</label>
                        <p class="fw-bold">
                            <?php if ($client['email_verified']): ?>
                            <span class="badge bg-success"><i class="fas fa-check"></i> Verified</span>
                            <?php else: ?>
                            <span class="badge bg-warning"><i class="fas fa-clock"></i> Pending</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted small">Account Status</label>
                        <p class="fw-bold">
                            <span class="badge bg-success">Active</span>
                        </p>
                    </div>
                </div>
                
                <div class="mt-4">
                    <a href="../show_my_qr.php" class="btn btn-outline-primary">
                        <i class="fas fa-qrcode me-2"></i>View My QR Code
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePasswordField(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '-icon');
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<?php include __DIR__.'/includes/footer.php'; ?>
