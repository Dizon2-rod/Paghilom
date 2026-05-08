<?php
/**
 * Fix Missing order_addons Table
 * Creates all required tables for addon functionality
 */

require __DIR__ . '/config.php';
require_admin();

$messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_tables'])) {
    
    // 1. Create addons table
    $sql = "CREATE TABLE IF NOT EXISTS `addons` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `description` text DEFAULT NULL,
        `price` decimal(10,2) NOT NULL DEFAULT 0.00,
        `is_active` tinyint(1) NOT NULL DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_is_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Product add-ons like extra shots, syrups, etc'";
    
    try {
        $mysqli->query($sql);
        $messages[] = "✅ Table 'addons' created successfully";
    } catch (Exception $e) {
        $errors[] = "❌ Error creating addons table: " . $e->getMessage();
    }
    
    // 2. Create product_addons junction table
    $sql = "CREATE TABLE IF NOT EXISTS `product_addons` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `product_id` int(11) NOT NULL,
        `addon_id` int(11) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `product_addon_unique` (`product_id`, `addon_id`),
        KEY `idx_product_id` (`product_id`),
        KEY `idx_addon_id` (`addon_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Links products to their available addons'";
    
    try {
        $mysqli->query($sql);
        $messages[] = "✅ Table 'product_addons' created successfully";
    } catch (Exception $e) {
        $errors[] = "❌ Error creating product_addons table: " . $e->getMessage();
    }
    
    // 3. Create order_addons table (THE MISSING TABLE)
    $sql = "CREATE TABLE IF NOT EXISTS `order_addons` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `order_item_id` int(11) NOT NULL COMMENT 'Links to order_items.id',
        `addon_id` int(11) NOT NULL,
        `qty` int(11) NOT NULL DEFAULT 1,
        `price_each` decimal(10,2) NOT NULL DEFAULT 0.00,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_order_item_id` (`order_item_id`),
        KEY `idx_addon_id` (`addon_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Add-ons attached to specific order items'";
    
    try {
        $mysqli->query($sql);
        $messages[] = "✅ Table 'order_addons' created successfully";
    } catch (Exception $e) {
        $errors[] = "❌ Error creating order_addons table: " . $e->getMessage();
    }
    
    // 4. Add sample addons if table is empty
    if (empty($errors)) {
        $check = $mysqli->query("SELECT COUNT(*) as count FROM addons");
        $row = $check->fetch_assoc();
        
        if ($row['count'] == 0) {
            $sample_addons = [
                ['Extra Espresso Shot', 'Add an extra shot of espresso', 25.00],
                ['Vanilla Syrup', 'Sweet vanilla flavoring', 20.00],
                ['Caramel Syrup', 'Rich caramel flavoring', 20.00],
                ['Hazelnut Syrup', 'Nutty hazelnut flavoring', 20.00],
                ['Chocolate Sauce', 'Rich chocolate drizzle', 15.00],
                ['Whipped Cream', 'Fresh whipped cream topping', 15.00],
                ['Extra Milk', 'Add extra milk', 10.00],
                ['Oat Milk Upgrade', 'Switch to oat milk', 30.00],
                ['Almond Milk Upgrade', 'Switch to almond milk', 30.00],
                ['Cinnamon Powder', 'Sprinkle of cinnamon', 5.00],
            ];
            
            $stmt = $mysqli->prepare("INSERT INTO addons (name, description, price, is_active) VALUES (?, ?, ?, 1)");
            
            $inserted = 0;
            foreach ($sample_addons as $addon) {
                try {
                    $stmt->bind_param('ssd', $addon[0], $addon[1], $addon[2]);
                    $stmt->execute();
                    $inserted++;
                } catch (Exception $e) {
                    $errors[] = "Failed to insert {$addon[0]}: " . $e->getMessage();
                }
            }
            $stmt->close();
            
            if ($inserted > 0) {
                $messages[] = "✅ Added {$inserted} sample add-ons";
            }
        } else {
            $messages[] = "ℹ️ Add-ons already exist ({$row['count']} items)";
        }
    }
}

include __DIR__ . '/partials/header.php';
?>

<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">🔧 Fix Order Add-ons System</h3>
                    <p class="mb-0 opacity-75">Create missing database tables</p>
                </div>
                <div class="card-body">
                    
                    <?php if (!empty($messages)): ?>
                        <div class="alert alert-success">
                            <h5 class="alert-heading">✅ Success!</h5>
                            <ul class="mb-0">
                                <?php foreach ($messages as $msg): ?>
                                    <li><?= htmlspecialchars($msg) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="pos/edit_order.php?id=5" class="btn btn-primary btn-lg">
                                Try Edit Order Again
                            </a>
                            <a href="admin/" class="btn btn-outline-secondary btn-lg">
                                Back to Admin
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <h5 class="alert-heading">⚠ Errors</h5>
                            <ul class="mb-0">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= htmlspecialchars($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($messages) && empty($errors)): ?>
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle"></i> About This Fix</h5>
                            <p class="mb-0">This will create the missing database tables needed for the order add-ons system.</p>
                        </div>
                        
                        <div class="card bg-light border-0 mb-4">
                            <div class="card-body">
                                <h6>📋 Tables to Create:</h6>
                                <ol class="mb-0">
                                    <li><strong>addons</strong> - Available add-ons (extra shots, syrups, etc.)</li>
                                    <li><strong>product_addons</strong> - Links products to their available add-ons</li>
                                    <li><strong>order_addons</strong> - Tracks add-ons in customer orders</li>
                                </ol>
                            </div>
                        </div>
                        
                        <div class="card border-primary mb-4">
                            <div class="card-body">
                                <h6 class="text-primary">✨ Sample Add-ons Included:</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <ul class="small">
                                            <li>Extra Espresso Shot (₱25)</li>
                                            <li>Vanilla Syrup (₱20)</li>
                                            <li>Caramel Syrup (₱20)</li>
                                            <li>Chocolate Sauce (₱15)</li>
                                            <li>Whipped Cream (₱15)</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <ul class="small">
                                            <li>Extra Milk (₱10)</li>
                                            <li>Oat Milk Upgrade (₱30)</li>
                                            <li>Almond Milk Upgrade (₱30)</li>
                                            <li>Cinnamon Powder (₱5)</li>
                                            <li>And more...</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <form method="post" class="text-center">
                            <button type="submit" name="create_tables" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-check-circle me-2"></i>Create Tables & Fix Issue
                            </button>
                            <a href="admin/" class="btn btn-outline-secondary btn-lg px-5">
                                Cancel
                            </a>
                        </form>
                    <?php endif; ?>
                    
                </div>
            </div>
            
            <!-- Error Details -->
            <div class="card mt-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">❌ Original Error</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Error Message:</strong></p>
                    <pre class="bg-light p-3 rounded"><code>Fatal error: Table 'paghilom_cafe.order_addons' doesn't exist
in C:\xampp\htdocs\paghilom_cafe\pos\edit_order.php:42</code></pre>
                    
                    <p class="mb-2 mt-3"><strong>Root Cause:</strong></p>
                    <p class="mb-0">The <code>order_addons</code> table is missing from your database. This table is required to track add-ons (like extra shots, syrups) that customers add to their orders.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
