<?php
require __DIR__.'/config.php';
require_login();
require_once __DIR__.'/includes/points.php';

$user_id = $_SESSION['user']['id'];
$success = '';
$errors = [];

// Get user data
$user_query = $mysqli->prepare("SELECT * FROM users WHERE id = ?");
$user_query->bind_param('i', $user_id);
$user_query->execute();
$user = $user_query->get_result()->fetch_assoc();
$user_query->close();

if(!$user) {
    header('Location: login.php');
    exit;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    csrf_check();
    
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Validation
    if (empty($name)) {
        $errors[] = "Name is required";
    } elseif (strlen($name) < 2) {
        $errors[] = "Name must be at least 2 characters";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    // Check if email already taken by another user
    if (empty($errors)) {
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE email=? AND id != ?");
        $stmt->bind_param('si', $email, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Email already in use by another account";
        }
        $stmt->close();
    }
    
    if (empty($errors)) {
        $stmt = $mysqli->prepare("UPDATE users SET name=?, email=?, phone=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param('sssi', $name, $email, $phone, $user_id);
        
        if ($stmt->execute()) {
            // Update session
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;
            
            $success = "Profile updated successfully!";
            
            // Refresh user data
            $user_query = $mysqli->prepare("SELECT * FROM users WHERE id = ?");
            $user_query->bind_param('i', $user_id);
            $user_query->execute();
            $user = $user_query->get_result()->fetch_assoc();
            $user_query->close();
        } else {
            $errors[] = "Update failed. Please try again.";
        }
        $stmt->close();
    }
}

// Handle profile photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {
    csrf_check();
    
    if(isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_photo'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        // Validate file type
        if(!in_array($file['type'], $allowed_types)) {
            $errors[] = "Only JPG, PNG, and GIF images are allowed";
        }
        
        // Validate file size
        if($file['size'] > $max_size) {
            $errors[] = "File size must be less than 5MB";
        }
        
        if(empty($errors)) {
            // Create unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'user_' . $user_id . '_' . time() . '.' . $extension;
            $upload_path = __DIR__ . '/assets/clients/' . $filename;
            
            // Delete old photo if exists
            if(!empty($user['profile_photo'])) {
                $old_photo = __DIR__ . '/assets/clients/' . $user['profile_photo'];
                if(file_exists($old_photo)) {
                    unlink($old_photo);
                }
            }
            
            // Upload new photo
            if(move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Update database
                $stmt = $mysqli->prepare("UPDATE users SET profile_photo=?, updated_at=NOW() WHERE id=?");
                $stmt->bind_param('si', $filename, $user_id);
                
                if($stmt->execute()) {
                    $success = "Profile photo updated successfully!";
                    
                    // Update session
                    $_SESSION['user']['profile_photo'] = $filename;
                    
                    // Refresh user data
                    $user_query = $mysqli->prepare("SELECT * FROM users WHERE id = ?");
                    $user_query->bind_param('i', $user_id);
                    $user_query->execute();
                    $user = $user_query->get_result()->fetch_assoc();
                    $user_query->close();
                } else {
                    $errors[] = "Failed to update profile photo in database";
                }
                $stmt->close();
            } else {
                $errors[] = "Failed to upload photo";
            }
        }
    } else {
        $errors[] = "Please select a photo to upload";
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
    } elseif (!password_verify($current_password, $user['password_hash'])) {
        $errors[] = "Current password is incorrect";
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = "New passwords do not match";
    }
    
    if (strlen($new_password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    
    if (empty($errors)) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("UPDATE users SET password_hash=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param('si', $hashed, $user_id);
        
        if ($stmt->execute()) {
            $success = "Password changed successfully!";
        } else {
            $errors[] = "Password change failed. Please try again.";
        }
        $stmt->close();
    }
}

include __DIR__.'/partials/header.php';
?>

<style>
/* Mobile optimizations */
@media (max-width: 767.98px) {
    /* Base typography */
    html {
        font-size: 14px;
    }
    
    /* Layout adjustments */
    .py-5 {
        padding-top: 1.5rem !important;
        padding-bottom: 1.5rem !important;
    }
    
    /* Header section */
    .mb-4 {
        margin-bottom: 1rem !important;
    }
    
    .h3 {
        font-size: 1.25rem;
    }
    
    /* Profile photo */
    .profile-photo-container {
        width: 100px !important;
        height: 100px !important;
        margin: 0 auto 1rem;
    }
    
    .profile-photo-container img,
    #photoPreview,
    #photoPlaceholder {
        width: 100% !important;
        height: 100% !important;
    }
    
    /* Form controls */
    .form-control, .form-select {
        font-size: 0.9rem;
        padding: 0.4rem 0.75rem;
    }
    
    .form-label {
        font-size: 0.85rem;
        margin-bottom: 0.3rem;
    }
    
    /* Buttons */
    .btn {
        padding: 0.4rem 0.8rem;
        font-size: 0.9rem;
    }
    
    /* Cards */
    .card {
        margin-bottom: 1rem;
        border-radius: 0.5rem;
    }
    
    .card-header {
        padding: 0.75rem 1rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    /* Account info grid */
    .account-info .col-md-3 {
        flex: 0 0 50%;
        max-width: 50%;
        margin-bottom: 1rem;
    }
    
    /* Hide decorative elements on mobile */
    .text-muted.small {
        font-size: 0.75rem;
    }
    
    /* Adjust points section */
    .points-summary {
        flex-direction: column;
        text-align: center;
    }
    
    .points-badge {
        font-size: 1.8rem !important;
        margin-bottom: 0.5rem;
    }
}

/* Tablet and up */
@media (min-width: 768px) {
    .profile-photo-container {
        width: 150px;
        height: 150px;
    }
}
</style>
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Header -->
            <div class="d-flex align-items-center justify-content-between flex-column flex-md-row points-summary">
                <div>
                    <h1 class="h3 mb-1">My Profile</h1>
                    <p class="text-muted mb-0">Manage your personal information and security</p>
                </div>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-home me-2"></i>Home
                </a>
            </div>
            
            <!-- Success Message -->
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Profile Photo Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-camera me-2 text-success"></i>
                        Profile Photo
                    </h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3 position-relative profile-photo-container">
                        <?php if(!empty($user['profile_photo']) && file_exists(__DIR__ . '/assets/clients/' . $user['profile_photo'])): ?>
                            <img src="assets/clients/<?= htmlspecialchars($user['profile_photo']) ?>" 
                                 alt="Profile Photo" 
                                 id="photoPreview"
                                 class="rounded-circle" 
                                 style="object-fit: cover; border: 3px solid var(--primary);">
                        <?php else: ?>
                            <img id="photoPreview" 
                                 src="" 
                                 alt="Preview" 
                                 class="rounded-circle" 
                                 style="object-fit: cover; border: 3px solid var(--primary); display: none;">
                            <div id="photoPlaceholder" class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" 
                                 style="width: 100%; height: 100%; border: 3px solid var(--gray-300);">
                                <i class="fas fa-user fa-4x text-muted"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <form method="post" enctype="multipart/form-data" id="photoForm">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <input type="file" name="profile_photo" id="profile_photo" 
                                   class="form-control" accept="image/*" required>
                            <small class="text-muted">JPG, PNG, or GIF. Max 5MB</small>
                        </div>
                        <button type="submit" name="upload_photo" class="btn btn-success">
                            <i class="fas fa-upload me-2"></i>Upload Photo
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="row g-4">
                <!-- Profile Information -->
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-user me-2 text-primary"></i>
                                Profile Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <?= csrf_field() ?>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" required 
                                           value="<?= htmlspecialchars($user['name']) ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" required 
                                           value="<?= htmlspecialchars($user['email']) ?>">
                                    <small class="text-muted">Used for order notifications</small>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Phone Number</label>
                                    <input type="tel" name="phone" class="form-control" 
                                           placeholder="09XXXXXXXXX"
                                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                    <small class="text-muted">Optional - for order updates</small>
                                </div>
                                
                                <button type="submit" name="update_profile" class="btn btn-primary w-100">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Change Password -->
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-lock me-2 text-warning"></i>
                                Change Password
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <?= csrf_field() ?>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Current Password <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <input type="password" name="current_password" id="current_password" 
                                               class="form-control" required>
                                        <span class="password-toggle" onclick="togglePassword('current_password')" 
                                              style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                                            <i class="fas fa-eye" id="current_password-icon"></i>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">New Password <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <input type="password" name="new_password" id="new_password" 
                                               class="form-control" required minlength="8">
                                        <span class="password-toggle" onclick="togglePassword('new_password')" 
                                              style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                                            <i class="fas fa-eye" id="new_password-icon"></i>
                                        </span>
                                    </div>
                                    <small class="text-muted">At least 8 characters</small>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Confirm New Password <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <input type="password" name="confirm_password" id="confirm_password" 
                                               class="form-control" required minlength="8">
                                        <span class="password-toggle" onclick="togglePassword('confirm_password')" 
                                              style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer;">
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
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2 text-info"></i>
                        Account Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-6 col-md-3">
                            <label class="text-muted small">User ID</label>
                            <p class="fw-bold">#<?= str_pad($user['id'], 6, '0', STR_PAD_LEFT) ?></p>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="text-muted small">Account Type</label>
                            <p class="fw-bold">
                                <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'staff' ? 'warning' : 'primary') ?>">
                                    <?= ucfirst($user['role']) ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="text-muted small">Member Since</label>
                            <p class="fw-bold"><?= date('M d, Y', strtotime($user['created_at'])) ?></p>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="text-muted small">Account Status</label>
                            <p class="fw-bold">
                                <span class="badge bg-<?= $user['is_active'] ? 'success' : 'secondary' ?>">
                                    <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Points Summary -->
            <?php $points_balance = get_user_points_balance($user_id); $recent_points = get_user_point_history($user_id, 5, 0); ?>
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-star me-2 text-warning"></i>
                        Loyalty Points
                    </h5>
                    <a href="my_points.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3 mb-md-0">
                        <div class="col-md-4">
                            <div class="p-3 rounded bg-light text-center">
                                <div class="text-muted small">Total Points</div>
                                <div class="display-6 fw-bold" style="letter-spacing: 1px;">
                                    <?= number_format($points_balance) ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="table-responsive" style="font-size: 0.85rem;">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th class="text-center">Change</th>
                                            <th>Type</th>
                                            <th>Note</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recent_points)): ?>
                                            <tr><td colspan="4" class="text-muted">No point activity yet.</td></tr>
                                        <?php else: foreach ($recent_points as $pt): ?>
                                            <tr>
                                                <td><?= e(date('M d, Y', strtotime($pt['created_at']))) ?></td>
                                                <td class="text-center <?= ($pt['points'] >= 0 ? 'text-success' : 'text-danger') ?>">
                                                    <?= ($pt['points'] >= 0 ? '+' : '') . number_format($pt['points']) ?>
                                                </td>
                                                <td><span class="badge bg-secondary"><?= e($pt['type']) ?></span></td>
                                                <td><?= e($pt['note'] ?? '') ?></td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-link me-2 text-success"></i>
                        Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="my_points.php" class="btn btn-outline-primary mt-3 mt-md-0">
                            <i class="fas fa-star me-2"></i>My Points
                        </a>
                        <a href="user/orders.php" class="btn btn-outline-secondary">
                            <i class="fas fa-shopping-bag me-2"></i>My Orders
                        </a>
                        <a href="cart.php" class="btn btn-outline-success">
                            <i class="fas fa-shopping-cart me-2"></i>View Cart
                        </a>
                        <a href="kiosk.php" class="btn btn-outline-info">
                            <i class="fas fa-coffee me-2"></i>Order Now
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function togglePassword(fieldId) {
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

// Photo preview before upload
document.getElementById('profile_photo').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            this.value = '';
            return;
        }
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            alert('Only JPG, PNG, and GIF images are allowed');
            this.value = '';
            return;
        }
        
        // Show preview
        const reader = new FileReader();
        reader.onload = function(event) {
            const preview = document.getElementById('photoPreview');
            const placeholder = document.getElementById('photoPlaceholder');
            if (preview) {
                preview.src = event.target.result;
                preview.style.display = 'block';
                if (placeholder) {
                    placeholder.style.display = 'none';
                }
            }
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php include __DIR__.'/partials/footer.php'; ?>
