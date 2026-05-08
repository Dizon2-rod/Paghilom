<?php
require __DIR__ . '/config.php';
require_admin(); // Only admins can run this

$success = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup'])) {
    
    // 1. Create point_transactions table
    $sql = "CREATE TABLE IF NOT EXISTS `point_transactions` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `points` int(11) NOT NULL COMMENT 'Positive for earning, negative for spending',
      `type` enum('earn','redeem','adjust','bonus','expire') NOT NULL DEFAULT 'earn',
      `ref_type` varchar(50) DEFAULT NULL COMMENT 'order, redemption, manual, etc',
      `ref_id` int(11) DEFAULT NULL COMMENT 'Reference to order_id, redemption_id, etc',
      `note` text DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_user_id` (`user_id`),
      KEY `idx_type` (`type`),
      KEY `idx_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Point transaction history'";
    
    if ($mysqli->query($sql)) {
        $success[] = "✅ Table 'point_transactions' created successfully";
    } else {
        $errors[] = "❌ Error creating point_transactions: " . $mysqli->error;
    }
    
    // 2. Create reward_catalog table
    $sql = "CREATE TABLE IF NOT EXISTS `reward_catalog` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(255) NOT NULL,
      `description` text DEFAULT NULL,
      `points_cost` int(11) NOT NULL COMMENT 'Points required to redeem',
      `reward_type` enum('voucher','discount','free_item','upgrade') NOT NULL DEFAULT 'voucher',
      `value` decimal(10,2) DEFAULT NULL COMMENT 'Monetary value if applicable',
      `terms` text DEFAULT NULL COMMENT 'Terms and conditions',
      `is_active` tinyint(1) NOT NULL DEFAULT 1,
      `stock_qty` int(11) DEFAULT NULL COMMENT 'NULL = unlimited, number = limited stock',
      `valid_days` int(11) DEFAULT 30 COMMENT 'Days until reward expires after redemption',
      `image` varchar(255) DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_is_active` (`is_active`),
      KEY `idx_points_cost` (`points_cost`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Available rewards catalog'";
    
    if ($mysqli->query($sql)) {
        $success[] = "✅ Table 'reward_catalog' created successfully";
    } else {
        $errors[] = "❌ Error creating reward_catalog: " . $mysqli->error;
    }
    
    // 3. Create redemptions table
    $sql = "CREATE TABLE IF NOT EXISTS `redemptions` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `reward_id` int(11) NOT NULL,
      `points_spent` int(11) NOT NULL,
      `status` enum('pending','approved','claimed','rejected','expired') NOT NULL DEFAULT 'pending',
      `voucher_code` varchar(50) DEFAULT NULL COMMENT 'Unique code for voucher redemption',
      `expires_at` datetime DEFAULT NULL,
      `claimed_at` datetime DEFAULT NULL,
      `rejected_reason` text DEFAULT NULL,
      `notes` text DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_user_id` (`user_id`),
      KEY `idx_reward_id` (`reward_id`),
      KEY `idx_status` (`status`),
      KEY `idx_voucher_code` (`voucher_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='User reward redemptions'";
    
    if ($mysqli->query($sql)) {
        $success[] = "✅ Table 'redemptions' created successfully";
    } else {
        $errors[] = "❌ Error creating redemptions: " . $mysqli->error;
    }
    
    // 4. Insert sample rewards
    if (empty($errors)) {
        $check = $mysqli->query("SELECT COUNT(*) as count FROM reward_catalog");
        $row = $check->fetch_assoc();
        
        if ($row['count'] == 0) {
            $rewards = [
                ['Free Espresso', 'Enjoy a complimentary single shot espresso', 30, 'free_item', 50.00],
                ['₱50 Discount Voucher', 'Get ₱50 off your next purchase', 100, 'discount', 50.00],
                ['Free Pastry', 'Choose any pastry from our selection', 40, 'free_item', 60.00],
                ['₱100 Discount Voucher', 'Get ₱100 off your next purchase', 200, 'discount', 100.00],
                ['Free Iced Coffee', 'Complimentary iced coffee of your choice', 50, 'free_item', 80.00],
                ['₱200 Discount Voucher', 'Get ₱200 off your next purchase', 400, 'discount', 200.00],
                ['Size Upgrade', 'Upgrade any drink to the next size free', 25, 'upgrade', 30.00],
                ['Free Meal Combo', 'Get a complete meal combo free', 150, 'free_item', 250.00]
            ];
            
            $stmt = $mysqli->prepare("INSERT INTO reward_catalog (name, description, points_cost, reward_type, value, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            
            foreach ($rewards as $reward) {
                $stmt->bind_param('ssisd', $reward[0], $reward[1], $reward[2], $reward[3], $reward[4]);
                $stmt->execute();
            }
            $stmt->close();
            
            $success[] = "✅ Sample rewards added successfully (8 rewards)";
        } else {
            $success[] = "ℹ️ Rewards already exist, skipped adding samples";
        }
    }
}

include __DIR__ . '/partials/header.php';
?>

<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-gear"></i> Points & Rewards System Setup</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <h5 class="alert-heading"><i class="bi bi-check-circle"></i> Setup Successful!</h5>
                            <ul class="mb-0">
                                <?php foreach ($success as $msg): ?>
                                    <li><?= htmlspecialchars($msg) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <hr>
                            <p class="mb-0">
                                <strong>Next Steps:</strong><br>
                                1. Go to <a href="<?= APP_URL ?>admin/rewards_catalog.php" class="alert-link">Rewards Catalog</a> to manage rewards<br>
                                2. Visit <a href="<?= APP_URL ?>rewards/dashboard.php" class="alert-link">User Dashboard</a> to test the system
                            </p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Errors Occurred</h5>
                            <ul class="mb-0">
                                <?php foreach ($errors as $msg): ?>
                                    <li><?= htmlspecialchars($msg) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($success) && empty($errors)): ?>
                        <div class="alert alert-info">
                            <h5><i class="bi bi-info-circle"></i> About This Setup</h5>
                            <p>This will create the following database tables:</p>
                            <ul>
                                <li><strong>point_transactions</strong> - Stores all point earning/spending</li>
                                <li><strong>reward_catalog</strong> - Available rewards users can redeem</li>
                                <li><strong>redemptions</strong> - Tracks user redemptions</li>
                            </ul>
                            <p class="mb-0">It will also add 8 sample rewards to get you started.</p>
                        </div>
                        
                        <div class="bg-light p-4 rounded mb-3">
                            <h6>Points System Rules:</h6>
                            <ul class="mb-0">
                                <li><strong>₱5 = 1 point</strong> (automatically calculated on checkout)</li>
                                <li>Users can view points balance and redeem rewards</li>
                                <li>Admins manage rewards and approve redemptions</li>
                                <li>Transaction history tracked for transparency</li>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" class="text-center">
                        <?php if (empty($success)): ?>
                            <button type="submit" name="setup" class="btn btn-primary btn-lg">
                                <i class="bi bi-play-circle"></i> Run Setup Now
                            </button>
                        <?php else: ?>
                            <a href="<?= APP_URL ?>admin/" class="btn btn-success">
                                <i class="bi bi-speedometer2"></i> Go to Admin Dashboard
                            </a>
                            <a href="<?= APP_URL ?>rewards/dashboard.php" class="btn btn-primary">
                                <i class="bi bi-award"></i> View Rewards Dashboard
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <!-- Database Info -->
            <div class="card shadow-sm border-0 mt-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="bi bi-database"></i> Database Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Database:</th>
                            <td><code><?= DB_NAME ?></code></td>
                        </tr>
                        <tr>
                            <th>Host:</th>
                            <td><code><?= DB_HOST ?></code></td>
                        </tr>
                        <tr>
                            <th>Connection:</th>
                            <td><span class="badge bg-success">✅ Connected</span></td>
                        </tr>
                    </table>
                    
                    <small class="text-muted">
                        <i class="bi bi-info-circle"></i> 
                        Alternatively, you can import <code>database/points_rewards_tables.sql</code> via phpMyAdmin
                    </small>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
