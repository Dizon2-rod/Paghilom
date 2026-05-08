<?php
require __DIR__.'/../config.php';
require __DIR__.'/../lib/auth.php';

// Check if user is logged in as client
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

// Get client stats
$total_orders = $mysqli->query("SELECT COUNT(*) as count FROM orders WHERE client_id={$client['id']}")->fetch_assoc()['count'];
$total_spent = $mysqli->query("SELECT SUM(total_amount) as total FROM orders WHERE client_id={$client['id']} AND payment_status='paid'")->fetch_assoc()['total'] ?? 0;

// Get recent orders
$recent_orders = $mysqli->query("SELECT * FROM orders WHERE client_id={$client['id']} ORDER BY created_at DESC LIMIT 5");

// Get available rewards
$rewards = $mysqli->query("SELECT * FROM vouchers WHERE is_active=1 ORDER BY points_required ASC");

$page_title = "My Dashboard";
include __DIR__.'/includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold">Welcome back, <?= e($client['name']) ?>!</h2>
        <p class="text-muted">Manage your account, orders, and rewards</p>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card dashboard-card">
            <div class="card-body">
                <div class="stat-icon green">
                    <i class="fas fa-star"></i>
                </div>
                <h3 class="fw-bold mb-0"><?= number_format($client['points']) ?></h3>
                <p class="text-muted mb-0">Reward Points</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card">
            <div class="card-body">
                <div class="stat-icon gold">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h3 class="fw-bold mb-0"><?= $total_orders ?></h3>
                <p class="text-muted mb-0">Total Orders</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card">
            <div class="card-body">
                <div class="stat-icon green">
                    <i class="fas fa-peso-sign"></i>
                </div>
                <h3 class="fw-bold mb-0">₱<?= number_format($total_spent, 2) ?></h3>
                <p class="text-muted mb-0">Total Spent</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card">
            <div class="card-body">
                <div class="stat-icon gold">
                    <i class="fas fa-gift"></i>
                </div>
                <h3 class="fw-bold mb-0"><?= $mysqli->query("SELECT COUNT(*) as c FROM vouchers WHERE is_active=1 AND points_required <= {$client['points']}")->fetch_assoc()['c'] ?></h3>
                <p class="text-muted mb-0">Available Rewards</p>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="fw-bold mb-3">Quick Actions</h4>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="order.php" class="btn btn-primary">
                        <i class="fas fa-shopping-cart me-2"></i>Order Now
                    </a>
                    <a href="rewards.php" class="btn btn-outline-primary">
                        <i class="fas fa-gift me-2"></i>View Rewards
                    </a>
                    <a href="orders.php" class="btn btn-outline-secondary">
                        <i class="fas fa-history me-2"></i>Order History
                    </a>
                    <a href="account.php" class="btn btn-outline-secondary">
                        <i class="fas fa-user-edit me-2"></i>Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0">Recent Orders</h4>
                    <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <?php if ($recent_orders->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Points Earned</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $recent_orders->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?= e($order['order_number']) ?></strong></td>
                                <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                                <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                                <td>
                                    <span class="badge bg-<?= $order['order_status'] == 'completed' ? 'success' : ($order['order_status'] == 'pending' ? 'warning' : 'info') ?>">
                                        <?= ucfirst($order['order_status']) ?>
                                    </span>
                                </td>
                                <td><strong>+<?= $order['points_earned'] ?> pts</strong></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No orders yet. Start ordering to earn rewards!</p>
                    <a href="order.php" class="btn btn-primary">Order Now</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Available Rewards -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0">Available Rewards</h4>
                    <a href="rewards.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="row g-3">
                    <?php 
                    $reward_count = 0;
                    while ($reward = $rewards->fetch_assoc()): 
                        if ($reward_count >= 4) break;
                        $reward_count++;
                        $can_redeem = $client['points'] >= $reward['points_required'];
                    ?>
                    <div class="col-md-3">
                        <div class="card h-100 <?= $can_redeem ? 'border-success' : 'border-secondary' ?>">
                            <div class="card-body text-center">
                                <i class="fas fa-gift fa-2x <?= $can_redeem ? 'text-success' : 'text-muted' ?> mb-2"></i>
                                <h6 class="fw-bold"><?= e($reward['name']) ?></h6>
                                <p class="small text-muted mb-2"><?= e($reward['description']) ?></p>
                                <span class="badge <?= $can_redeem ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= $reward['points_required'] ?> Points
                                </span>
                                <?php if ($can_redeem): ?>
                                <div class="mt-2">
                                    <a href="redeem_reward.php?id=<?= $reward['id'] ?>" class="btn btn-sm btn-success">Redeem</a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__.'/includes/footer.php'; ?>
