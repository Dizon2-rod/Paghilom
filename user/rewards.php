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

// Handle voucher redemption
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redeem_voucher'])) {
    csrf_check();
    
    $voucher_id = (int)$_POST['voucher_id'];
    $voucher = $mysqli->query("SELECT * FROM vouchers WHERE id=$voucher_id AND is_active=1 LIMIT 1")->fetch_assoc();
    
    if (!$voucher) {
        $errors[] = "Invalid voucher";
    } elseif ($client['points'] < $voucher['points_required']) {
        $errors[] = "Insufficient points. You need {$voucher['points_required']} points but only have {$client['points']} points.";
    } else {
        // Deduct points
        $new_points = $client['points'] - $voucher['points_required'];
        $mysqli->query("UPDATE clients SET points=$new_points WHERE id={$client['id']}");
        
        // Record voucher redemption (you can add a redeemed_vouchers table)
        $mysqli->query("INSERT INTO client_vouchers (client_id, voucher_id, redeemed_at) VALUES ({$client['id']}, $voucher_id, NOW())");
        
        // Record points history
        $mysqli->query("INSERT INTO points_history (client_id, points, type, description, created_at) 
                       VALUES ({$client['id']}, -{$voucher['points_required']}, 'redeemed', 'Redeemed: {$voucher['name']}', NOW())");
        
        $success = "Voucher redeemed successfully! You now have $new_points points.";
        $client['points'] = $new_points;
    }
}

// Get all active vouchers
$vouchers = $mysqli->query("SELECT * FROM vouchers WHERE is_active=1 ORDER BY points_required ASC");

// Get redeemed vouchers
$redeemed_query = "SELECT v.*, cv.redeemed_at 
                   FROM client_vouchers cv 
                   JOIN vouchers v ON cv.voucher_id = v.id 
                   WHERE cv.client_id = {$client['id']} 
                   ORDER BY cv.redeemed_at DESC 
                   LIMIT 10";
$redeemed = $mysqli->query($redeemed_query);

$page_title = "My Rewards";
include __DIR__.'/includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold">My Rewards</h2>
        <p class="text-muted">Redeem your points for amazing rewards and discounts</p>
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

<!-- Points Summary -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card dashboard-card bg-success text-white">
            <div class="card-body text-center">
                <i class="fas fa-star fa-3x mb-3"></i>
                <h3 class="fw-bold"><?= number_format($client['points']) ?></h3>
                <p class="mb-0">Available Points</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <i class="fas fa-gift fa-3x text-primary mb-3"></i>
                <h3 class="fw-bold">
                    <?= $mysqli->query("SELECT COUNT(*) as c FROM vouchers WHERE is_active=1 AND points_required <= {$client['points']}")->fetch_assoc()['c'] ?>
                </h3>
                <p class="mb-0">Available Rewards</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <i class="fas fa-history fa-3x text-warning mb-3"></i>
                <h3 class="fw-bold">
                    <?= $mysqli->query("SELECT COUNT(*) as c FROM client_vouchers WHERE client_id={$client['id']}")->fetch_assoc()['c'] ?>
                </h3>
                <p class="mb-0">Redeemed Rewards</p>
            </div>
        </div>
    </div>
</div>

<!-- How It Works -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-info-circle me-2 text-info"></i>
                    How It Works
                </h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-center">
                            <div class="stat-icon green mx-auto">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <h6 class="fw-bold">1. Order & Earn</h6>
                            <p class="small text-muted">Get 1 point for every ₱5 you spend</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <div class="stat-icon gold mx-auto">
                                <i class="fas fa-star"></i>
                            </div>
                            <h6 class="fw-bold">2. Collect Points</h6>
                            <p class="small text-muted">Accumulate points with each order</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <div class="stat-icon green mx-auto">
                                <i class="fas fa-gift"></i>
                            </div>
                            <h6 class="fw-bold">3. Redeem Rewards</h6>
                            <p class="small text-muted">Exchange points for vouchers and discounts</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Available Vouchers -->
<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold mb-3">Available Vouchers</h4>
    </div>
</div>

<div class="row g-4 mb-4">
    <?php while ($voucher = $vouchers->fetch_assoc()): 
        $can_redeem = $client['points'] >= $voucher['points_required'];
    ?>
    <div class="col-md-4">
        <div class="card h-100 <?= $can_redeem ? 'border-success' : '' ?>" style="transition: transform 0.3s;">
            <div class="card-body">
                <div class="text-center mb-3">
                    <i class="fas fa-gift fa-3x <?= $can_redeem ? 'text-success' : 'text-muted' ?>"></i>
                </div>
                <h5 class="fw-bold text-center"><?= e($voucher['name']) ?></h5>
                <p class="text-center text-muted"><?= e($voucher['description']) ?></p>
                
                <div class="text-center mb-3">
                    <span class="badge <?= $can_redeem ? 'bg-success' : 'bg-secondary' ?> py-2 px-3">
                        <i class="fas fa-star me-1"></i>
                        <?= $voucher['points_required'] ?> Points Required
                    </span>
                </div>
                
                <?php if ($can_redeem): ?>
                <form method="POST" class="text-center">
                    <?= csrf_field() ?>
                    <input type="hidden" name="voucher_id" value="<?= $voucher['id'] ?>">
                    <button type="submit" name="redeem_voucher" class="btn btn-success w-100">
                        <i class="fas fa-check-circle me-2"></i>Redeem Now
                    </button>
                </form>
                <?php else: ?>
                <div class="text-center">
                    <button class="btn btn-secondary w-100" disabled>
                        <i class="fas fa-lock me-2"></i>
                        Need <?= $voucher['points_required'] - $client['points'] ?> more points
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
</div>

<!-- Redeemed Vouchers -->
<?php if ($redeemed->num_rows > 0): ?>
<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold mb-3">Recently Redeemed</h4>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Voucher</th>
                                <th>Description</th>
                                <th>Points Used</th>
                                <th>Redeemed On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($red = $redeemed->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= e($red['name']) ?></strong></td>
                                <td><?= e($red['description']) ?></td>
                                <td><span class="badge bg-warning"><?= $red['points_required'] ?> pts</span></td>
                                <td><?= date('M d, Y g:i A', strtotime($red['redeemed_at'])) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}
</style>

<?php include __DIR__.'/includes/footer.php'; ?>
