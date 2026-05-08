<?php
require_once __DIR__.'/config.php';
require_login(); // User must be logged in

// Prevent caching to ensure points always reflect latest data
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Include points functions
require_once __DIR__.'/includes/points.php';
// Also include legacy points functions (create_redemption, etc.) if present
if (file_exists(__DIR__.'/points.php')) { require_once __DIR__.'/points.php'; }

$user_id = (int)$_SESSION['user']['id'];
$user_name = $_SESSION['user']['name'];

// Get current points balance - force fresh query to avoid caching
// Direct query to ensure we get the absolute latest balance
$stmt = $mysqli->prepare("SELECT COALESCE(SUM(points), 0) AS balance FROM point_transactions WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$points_balance = (int)($row['balance'] ?? 0);
$stmt->close();

// Get recent transactions (last 20) using helper function
$transactions_data = get_user_point_history($user_id, 20, 0);

// Get available rewards from rewards table (managed by admin)
$rewards_query = $mysqli->query("
    SELECT 
        id,
        name,
        description,
        required_points as points_cost,
        reward_type,
        value,
        thumb,
        NULL as stock_qty,
        NULL as terms,
        30 as valid_days
    FROM rewards 
    WHERE is_active = 1
    ORDER BY required_points ASC
");

// Get redemptions - try both rewards and reward_catalog tables for compatibility
// Refresh the query result to ensure latest status is shown
$redemptions_query = $mysqli->prepare("
    SELECT 
        r.*,
        COALESCE(rw.name, rc.name) as reward_name,
        COALESCE(rw.description, rc.description) as description,
        COALESCE(rw.reward_type, rc.reward_type) as reward_type,
        COALESCE(rw.value, rc.value) as value
    FROM redemptions r
    LEFT JOIN rewards rw ON r.reward_id = rw.id
    LEFT JOIN reward_catalog rc ON r.reward_id = rc.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
    LIMIT 10
");
$redemptions_query->bind_param('i', $user_id);
$redemptions_query->execute();
$redemptions = $redemptions_query->get_result();
// Store results in array to allow re-fetching
$redemptions_array = [];
while($row = $redemptions->fetch_assoc()) {
    $redemptions_array[] = $row;
}
$redemptions = new ArrayObject($redemptions_array); // Convert to iterable object

$redemption_message = '';
$show_voucher_modal = false;
$voucher_code = '';
$redeemed_reward_name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redeem_reward'])) {
    csrf_check();
    $reward_id = (int)$_POST['reward_id'];
    $result = create_redemption($mysqli, $user_id, $reward_id);
    if ($result['ok']) {
        // Force database connection refresh to ensure latest data
        $mysqli->commit(); // Ensure transaction is committed
        
        $rw_query = $mysqli->prepare('SELECT name FROM rewards WHERE id=?');
        $rw_query->bind_param('i', $reward_id);
        $rw_query->execute();
        $rw_result = $rw_query->get_result()->fetch_assoc();
        $redeemed_reward_name = $rw_result['name'] ?? 'Reward';
        $rw_query->close();
        $voucher_code = $result['voucher_code'];
        $show_voucher_modal = true;
        
        // Force fresh calculation of points balance after redemption
        // Clear any potential query cache by using a fresh query
        // Force fresh points balance calculation after redemption
        $stmt = $mysqli->prepare("SELECT COALESCE(SUM(points), 0) AS balance FROM point_transactions WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $points_balance = (int)($row['balance'] ?? 0);
        $stmt->close();
        
        $transactions_data = get_user_point_history($user_id, 20, 0);
        
        // Add a flag to trigger immediate refresh in JavaScript
        $force_refresh = true;
    } else {
        $redemption_message = '<div class="alert alert-danger">' . htmlspecialchars($result['msg']) . '</div>';
        $force_refresh = false;
    }
} else {
    $force_refresh = false;
}

include __DIR__.'/partials/header.php';
?>
<!-- Prevent caching to ensure points always reflect latest data -->
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

<style>
/* Base styles */
.points-hero {
    background: linear-gradient(135deg, var(--primary-dark), var(--primary));
    color: white;
    padding: 1.5rem 0;
    border-radius: var(--radius-lg);
    margin-bottom: 1.25rem;
}

.points-badge {
    font-size: 2.5rem;
    font-weight: 700;
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
    line-height: 1;
}

.reward-card {
    transition: all 0.2s ease;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius);
    margin-bottom: 1rem;
    height: 100%;
}

.reward-card .card-body {
    padding: 1rem;
}

.reward-card .card-title {
    font-size: 1rem;
    margin-bottom: 0.5rem;
}

.reward-card .card-text {
    font-size: 0.875rem;
    margin-bottom: 1rem;
}

.reward-badge {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    background: var(--primary);
    color: white;
    padding: 0.35rem 0.75rem;
    border-radius: 1rem;
    font-weight: 600;
    font-size: 0.75rem;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.transaction-item {
    border-left: 3px solid var(--gray-300);
    padding: 0.75rem 0;
    margin-bottom: 0.5rem;
}

.transaction-earn {
    border-left-color: var(--success);
}

.transaction-redeem {
    border-left-color: var(--danger);
}

/* Mobile optimizations */
@media (max-width: 767.98px) {
    /* Base typography */
    html {
        font-size: 14px;
    }
    
    /* Hero section */
    .points-hero {
        padding: 1rem 0;
        margin-bottom: 0.75rem;
    }
    
    .points-badge {
        font-size: 1.75rem;
        line-height: 1.2;
    }
    
    /* Headings */
    h1, .h1 { font-size: 1.3rem; }
    h2, .h2 { font-size: 1.2rem; }
    h3, .h3 { font-size: 1.1rem; }
    h4, .h4 { font-size: 1rem; }
    h5, .h5 { font-size: 0.95rem; }
    h6, .h6 { font-size: 0.9rem; }
    
    /* Buttons */
    .btn {
        font-size: 0.8rem;
        padding: 0.3rem 0.75rem;
    }
    
    .btn-lg {
        padding: 0.4rem 0.9rem;
        font-size: 0.85rem;
    }
    
    /* Navigation */
    .nav-tabs .nav-link, 
    .nav-pills .nav-link {
        padding: 0.4rem 0.5rem;
        font-size: 0.75rem;
    }
    
    /* Cards */
    .card {
        margin-bottom: 0.75rem;
    }
    
    .card-body {
        padding: 0.75rem;
    }
    
    .reward-card .card-body {
        padding: 0.5rem;
    }
    
    .reward-card .card-title {
        font-size: 0.85rem;
        margin-bottom: 0.2rem;
        line-height: 1.2;
    }
    
    .reward-card .card-text {
        display: none;
    }
    
    .reward-badge {
        top: 0.4rem;
        right: 0.4rem;
        padding: 0.2rem 0.5rem;
        font-size: 0.65rem;
    }
    
    /* Tables */
    .table {
        font-size: 0.8rem;
    }
    
    .table th, 
    .table td {
        padding: 0.4rem 0.5rem;
    }
    
    /* Transaction items */
    .transaction-item {
        padding: 0.4rem 0;
        font-size: 0.8rem;
    }
    
    /* How it works section */
    .display-4 {
        font-size: 2rem;
        margin-bottom: 0.5rem !important;
    }
    
    /* Modal content */
    .modal-content {
        font-size: 0.9rem;
    }
    
    /* Form controls */
    .form-control, 
    .form-select {
        font-size: 0.85rem;
        padding: 0.3rem 0.5rem;
    }
    
    /* Badges */
    .badge {
        font-size: 0.7rem;
        padding: 0.25em 0.5em;
    }
    
    /* Small text */
    small, .small {
        font-size: 0.75rem;
    }
}

/* Desktop styles */
@media (min-width: 768px) {
    .points-hero {
        padding: 2rem 0;
        border-radius: var(--radius-xl);
        margin-bottom: 2rem;
    }
    
    .points-badge {
        font-size: 3.5rem;
    }
    
    .reward-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-lg);
        border-color: var(--primary);
    }
}
</style>

<section class="container py-3 py-md-4">
    <!-- Points Balance Hero -->
    <div class="points-hero text-center">
        <div class="container">
            <h1 class="h4 mb-1">My Points</h1>
            <div class="points-badge mb-1"><?= number_format($points_balance) ?></div>
            <p class="mb-0 small">Available Points</p>
            <small class="opacity-75 d-none d-sm-inline">Earn ₱10 = 5 points</small>
        </div>
    </div>

    <?= $redemption_message ?>

    <!-- Quick Actions -->
    <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
        <a href="rewards.php" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-gift me-1"></i>All Rewards
        </a>
        <a href="<?= APP_URL ?>menu.php" class="btn btn-outline-success btn-sm">
            <i class="fas fa-shopping-cart me-1"></i>Earn Points
        </a>
        <a href="<?= APP_URL ?>menu.php" class="btn btn-outline-success btn-sm">
            <i class="fas fa-shopping-cart me-1"></i>Shop & Earn
        </a>
    </div>

    <!-- Navigation Tabs -->
    <div class="card mb-3">
        <div class="card-body p-2">
            <ul class="nav nav-pills nav-fill" id="pointsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="rewards-tab" data-bs-toggle="tab" data-bs-target="#rewards" type="button" role="tab">
                        <i class="fas fa-gift d-none d-sm-inline me-1"></i> Rewards
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">
                        <i class="fas fa-exchange-alt d-none d-sm-inline me-1"></i> History
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="redemptions-tab" data-bs-toggle="tab" data-bs-target="#redemptions" type="button" role="tab">
                        <i class="fas fa-ticket-alt d-none d-sm-inline me-1"></i> Vouchers
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <div class="tab-content">
        <!-- Available Rewards Tab -->
        <div class="tab-pane fade show active" id="rewards" role="tabpanel">
            <div class="row g-4">
                <?php while($reward = $rewards_query->fetch_assoc()): ?>
                <div class="col-6 col-md-6 col-lg-4">
                    <div class="card reward-card h-100 position-relative">
                        <?php if (!empty($reward['thumb']) && file_exists(__DIR__ . '/uploads/rewards/' . $reward['thumb'])): ?>
                            <img src="<?= APP_URL ?>uploads/rewards/<?= e($reward['thumb']) ?>" class="card-img-top" style="height: 200px; object-fit: cover; border-radius: 8px 8px 0 0;" alt="<?= e($reward['name']) ?>">
                        <?php endif; ?>
                        <span class="reward-badge"><?= number_format($reward['points_cost']) ?> pts</span>
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($reward['name']) ?></h5>
                            <p class="text-secondary mb-3"><?= htmlspecialchars($reward['description']) ?></p>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-<?= $reward['reward_type'] === 'free_item' ? 'success' : ($reward['reward_type'] === 'discount' ? 'primary' : 'info') ?>">
                                    <?= ucfirst(str_replace('_', ' ', $reward['reward_type'])) ?>
                                </span>
                                <?php if($reward['value']): ?>
                                    <?php if(is_numeric($reward['value'])): ?>
                                        <span class="text-muted">Worth ₱<?= number_format($reward['value'], 2) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted"><?= htmlspecialchars($reward['value']) ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>

                            <?php if($reward['terms']): ?>
                            <small class="text-secondary d-block mb-3"><?= htmlspecialchars($reward['terms']) ?></small>
                            <?php endif; ?>

                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="reward_id" value="<?= $reward['id'] ?>">
                                <button 
                                    type="submit" 
                                    name="redeem_reward" 
                                    class="btn btn-primary w-100"
                                    <?= $points_balance < $reward['points_cost'] ? 'disabled' : '' ?>
                                    <?= $reward['stock_qty'] !== null && $reward['stock_qty'] <= 0 ? 'disabled' : '' ?>
                                >
                                    <?php if($points_balance < $reward['points_cost']): ?>
                                        Not Enough Points
                                    <?php elseif($reward['stock_qty'] !== null && $reward['stock_qty'] <= 0): ?>
                                        Out of Stock
                                    <?php else: ?>
                                        Redeem Now
                                    <?php endif; ?>
                                </button>
                            </form>

                            <?php if($reward['stock_qty'] !== null): ?>
                            <small class="text-secondary d-block text-center mt-2">
                                <?= $reward['stock_qty'] ?> left in stock
                            </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>

                <?php if($rewards_query->num_rows === 0): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <h5>No rewards available at the moment</h5>
                        <p class="mb-0">Check back later for exciting rewards!</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Transaction History Tab -->
        <div class="tab-pane fade" id="history" role="tabpanel">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Transaction History</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if (empty($transactions_data)): ?>
                        <div class="list-group-item text-center py-5">
                            <p class="text-secondary mb-0">No transactions yet. Make a purchase to earn points!</p>
                        </div>
                        <?php else: foreach ($transactions_data as $tx): ?>
                        <div class="list-group-item transaction-item <?= ($tx['points'] > 0 ? 'transaction-earn' : 'transaction-redeem') ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">
                                        <?= ($tx['points'] > 0 ? '➕' : '➖') ?>
                                        <?= ucfirst($tx['type']) ?>
                                    </h6>
                                    <small class="text-secondary"><?= e($tx['note'] ?? '') ?></small>
                                </div>
                                <div class="text-end">
                                    <strong class="<?= ($tx['points'] > 0 ? 'text-success' : 'text-danger') ?>" style="font-size: 1.25rem;">
                                        <?= ($tx['points'] > 0 ? '+' : '') . number_format((int)$tx['points']) ?>
                                    </strong>
                                    <br>
                                        <small class="text-secondary"><?= e(date('M d, Y g:i A', strtotime($tx['created_at']))) ?></small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- My Redemptions Tab -->
        <div class="tab-pane fade" id="redemptions" role="tabpanel">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">My Redemptions</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Reward</th>
                                    <th>Points</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Voucher</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Re-fetch redemptions to ensure latest status
                                $redemptions_fresh = $mysqli->prepare("
                                    SELECT 
                                        r.*,
                                        COALESCE(rw.name, rc.name) as reward_name,
                                        COALESCE(rw.description, rc.description) as description,
                                        COALESCE(rw.reward_type, rc.reward_type) as reward_type,
                                        COALESCE(rw.value, rc.value) as value
                                    FROM redemptions r
                                    LEFT JOIN rewards rw ON r.reward_id = rw.id
                                    LEFT JOIN reward_catalog rc ON r.reward_id = rc.id
                                    WHERE r.user_id = ?
                                    ORDER BY r.created_at DESC
                                    LIMIT 10
                                ");
                                $redemptions_fresh->bind_param('i', $user_id);
                                $redemptions_fresh->execute();
                                $redemptions_result = $redemptions_fresh->get_result();
                                $redemptions_count = $redemptions_result->num_rows;
                                ?>
                                <?php while($red = $redemptions_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($red['reward_name'] ?? 'Reward') ?></strong>
                                        <br>
                                        <small class="text-secondary"><?= htmlspecialchars($red['description'] ?? '') ?></small>
                                    </td>
                                    <td><?= number_format($red['points_spent'] ?? 0) ?></td>
                                    <td>
                                        <?php 
                                        $status = strtolower($red['status'] ?? 'pending');
                                        $status_badge = 'secondary';
                                        $status_text = 'Unknown';
                                        if ($status === 'approved') {
                                            $status_badge = 'success';
                                            $status_text = 'Approved';
                                        } elseif ($status === 'pending') {
                                            $status_badge = 'warning';
                                            $status_text = 'Pending';
                                        } elseif ($status === 'rejected' || $status === 'cancelled') {
                                            $status_badge = 'danger';
                                            $status_text = ucfirst($status);
                                        } else {
                                            $status_text = ucfirst($status);
                                        }
                                        ?>
                                        <span class="badge bg-<?= $status_badge ?>">
                                            <?= htmlspecialchars($status_text) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($red['created_at'] ?? 'now')) ?></td>
                                    <td>
                                        <?php if(!empty($red['voucher_code'])): ?>
                                            <code><?= htmlspecialchars($red['voucher_code']) ?></code>
                                        <?php else: ?>
                                            <small class="text-secondary">Pending</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php $redemptions_fresh->close(); ?>

                                <?php if($redemptions_count === 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        No redemptions yet. Browse available rewards above!
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- How it Works Section -->
    <div class="card mt-4 bg-light border-0">
        <div class="card-body p-4">
            <h5 class="mb-3 text-center">💡 How Points Work</h5>
            <div class="row g-4">
                <div class="col-6 col-md-3 text-center">
                    <div class="display-4 mb-1">🛒</div>
                    <h6>1. Shop</h6>
                    <small class="text-muted">Make purchases at Paghilom Cafe</small>
                </div>
                <div class="col-6 col-md-3 text-center">
                    <div class="display-4 mb-1">⭐</div>
                    <h6>2. Earn</h6>
                    <small class="text-muted">Get 5 points for every ₱10 spent</small>
                </div>
                <div class="col-6 col-md-3 text-center">
                    <div class="display-4 mb-1">🎁</div>
                    <h6>3. Redeem</h6>
                    <small class="text-muted">Exchange points for rewards</small>
                </div>
                <div class="col-6 col-md-3 text-center">
                    <div class="display-4 mb-1">☕</div>
                    <h6>4. Enjoy</h6>
                    <small class="text-muted">Claim your rewards in-store</small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Voucher Code Modal -->
<?php if($show_voucher_modal): ?>
<div class="modal fade show" id="voucherModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);" aria-modal="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg" style="border-radius: var(--radius-xl);">
            <div class="modal-header bg-success text-white" style="border-radius: var(--radius-xl) var(--radius-xl) 0 0;">
                <h5 class="modal-title"><i class="fas fa-check-circle me-2"></i>Redemption Successful!</h5>
            </div>
            <div class="modal-body text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-ticket-alt fa-4x text-success mb-3"></i>
                    <h4 class="mb-2"><?= htmlspecialchars($redeemed_reward_name) ?></h4>
                    <p class="text-muted">Show this code at the counter</p>
                </div>
                
                <div class="voucher-code-display mb-4 p-4" style="background: linear-gradient(135deg, var(--primary-light), var(--primary)); border-radius: var(--radius-lg); box-shadow: var(--shadow-md);">
                    <div class="mb-2" style="font-size: 0.9rem; color: rgba(255,255,255,0.9); font-weight: 500;">Your Voucher Code</div>
                    <div style="font-size: 2.5rem; font-weight: 700; color: white; letter-spacing: 0.1em; font-family: 'Courier New', monospace;">
                        <?= htmlspecialchars($voucher_code) ?>
                    </div>
                </div>
                
                <div class="alert alert-info mb-4">
                    <small>
                        <strong>How to use:</strong><br>
                        1. Take a screenshot of this code<br>
                        2. Show it to our staff at Paghilom Cafe<br>
                        3. Enjoy your reward!
                    </small>
                </div>
                
                <div class="d-flex gap-2 justify-content-center">
                    <button class="btn btn-primary" onclick="window.print();">
                        <i class="fas fa-print me-2"></i>Print Receipt
                    </button>
                    <a href="voucher_receipt.php?code=<?= urlencode($voucher_code) ?>" class="btn btn-outline-primary" target="_blank">
                        <i class="fas fa-file-alt me-2"></i>View Receipt
                    </a>
                    <button class="btn btn-outline-secondary" onclick="document.getElementById('voucherModal').style.display='none';">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Auto-refresh points balance every 5 seconds to ensure it's always up-to-date
(function() {
    let lastBalance = <?= $points_balance ?>;
    let refreshInterval = null;
    let forceRefresh = <?= (isset($force_refresh) && $force_refresh) ? 'true' : 'false' ?>;
    
    function refreshPointsBalance(reloadPage = false) {
        fetch('<?= APP_URL ?>api/get_points_balance.php?t=' + Date.now(), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            cache: 'no-store'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.balance !== undefined) {
                const newBalance = parseInt(data.balance);
                if (newBalance !== lastBalance || reloadPage || forceRefresh) {
                    // If balance changed or forced refresh, reload the page to show all updates
                    if (forceRefresh || reloadPage) {
                        // Force refresh after redemption or when balance changes significantly
                        window.location.href = window.location.href.split('?')[0] + '?refreshed=' + Date.now();
                        return;
                    }
                    
                    // Update the displayed balance
                    const balanceElement = document.querySelector('.points-badge');
                    if (balanceElement) {
                        balanceElement.textContent = newBalance.toLocaleString();
                        lastBalance = newBalance;
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error refreshing points:', error);
        });
    }
    
    // If redemption just happened, refresh immediately
    if (forceRefresh) {
        setTimeout(function() {
            refreshPointsBalance(true);
        }, 500);
    }
    
    // Refresh every 3 seconds (more frequent for better UX)
    refreshInterval = setInterval(function() {
        refreshPointsBalance(false);
    }, 3000);
    
    // Also refresh when page becomes visible (user switches back to tab)
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            refreshPointsBalance(false);
        }
    });
    
    // Refresh on focus
    window.addEventListener('focus', function() {
        refreshPointsBalance(false);
    });
    
    // Refresh when user returns to page (handles case when staff processes voucher in another tab)
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            // Page was loaded from cache, force refresh
            refreshPointsBalance(true);
        }
    });
})();
</script>

<?php include __DIR__.'/partials/footer.php'; ?>
