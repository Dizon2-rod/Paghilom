<?php
/**
 * Quick Fix: Add Stock to Products
 * Run this page once to add stock to all products
 */

require __DIR__.'/config.php';
require_admin(); // Only admins can run this

$messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    try {
        // Update all existing products to have stock
        $stmt = $mysqli->query("
            UPDATE products 
            SET stock_qty = 100, 
                is_active = 1,
                low_stock_threshold = 10
            WHERE stock_qty = 0 OR stock_qty IS NULL
        ");
        
        $updated = $mysqli->affected_rows;
        $messages[] = "✅ Updated {$updated} products with stock levels";
        
        // Check if we need to create sample products
        $result = $mysqli->query("SELECT COUNT(*) as count FROM products");
        $count = $result->fetch_assoc()['count'];
        
        if ($count < 5) {
            // Ensure categories exist
            $mysqli->query("
                INSERT IGNORE INTO categories (id, name, description, is_active, sort_order) VALUES
                (1, 'Coffee', 'Hot and cold coffee beverages', 1, 1),
                (2, 'Non-Coffee', 'Refreshing non-coffee drinks', 1, 2),
                (3, 'Pastries', 'Fresh baked pastries and breads', 1, 3),
                (4, 'Snacks', 'Light snacks and treats', 1, 4)
            ");
            
            // Create sample coffee products
            $products = [
                ['Americano', 'Bold espresso with hot water', 95.00, 1, 1],
                ['Cappuccino', 'Espresso with steamed milk and foam', 120.00, 1, 1],
                ['Latte', 'Smooth espresso with steamed milk', 130.00, 1, 1],
                ['Caramel Macchiato', 'Vanilla-flavored latte with caramel', 150.00, 1, 1],
                ['Mocha', 'Chocolate and espresso blend', 140.00, 1, 1],
                ['Iced Latte', 'Refreshing iced coffee with milk', 140.00, 1, 1],
                ['Hot Chocolate', 'Rich and creamy hot cocoa', 110.00, 2, 0],
                ['Matcha Latte', 'Premium Japanese green tea latte', 145.00, 2, 1],
                ['Blueberry Cheesecake', 'Creamy cheesecake with blueberry', 165.00, 3, 1],
                ['Chocolate Brownie', 'Rich chocolate brownie', 95.00, 3, 0],
            ];
            
            $stmt = $mysqli->prepare("
                INSERT INTO products (name, description, price, category_id, stock_qty, low_stock_threshold, is_active, is_featured)
                VALUES (?, ?, ?, ?, 100, 10, 1, ?)
                ON DUPLICATE KEY UPDATE stock_qty = 100, is_active = 1
            ");
            
            $created = 0;
            foreach ($products as $p) {
                $stmt->bind_param('ssdii', $p[0], $p[1], $p[2], $p[3], $p[4]);
                $stmt->execute();
                $created++;
            }
            $stmt->close();
            
            $messages[] = "✅ Created {$created} sample products";
        }
        
        $messages[] = "🎉 All done! Products are now available for ordering.";
        
    } catch (Exception $e) {
        $errors[] = "❌ Error: " . $e->getMessage();
    }
}

// Get current product status
$result = $mysqli->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN stock_qty > 0 THEN 1 ELSE 0 END) as in_stock,
        SUM(CASE WHEN stock_qty = 0 THEN 1 ELSE 0 END) as out_of_stock
    FROM products
");
$stats = $result->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Product Stock - Paghilom Cafe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>assets/css/modern.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card-modern">
                    <div class="card-header">
                        <h5 class="mb-0">🛠️ Fix Product Stock</h5>
                    </div>
                    <div class="card-body">
                        
                        <?php if (!empty($messages)): ?>
                            <div class="alert alert-success">
                                <?php foreach ($messages as $msg): ?>
                                    <div><?= $msg ?></div>
                                <?php endforeach; ?>
                            </div>
                            <a href="<?= APP_URL ?>kiosk.php" class="btn btn-primary w-100">
                                Go to Kiosk
                            </a>
                        <?php elseif (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $err): ?>
                                    <div><?= $err ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            
                            <div class="mb-4">
                                <h6 class="mb-3">Current Status:</h6>
                                <div class="row g-3">
                                    <div class="col-4 text-center">
                                        <div class="card border-0 bg-light">
                                            <div class="card-body py-3">
                                                <div class="h3 mb-0"><?= $stats['total'] ?></div>
                                                <small class="text-muted">Total</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4 text-center">
                                        <div class="card border-0 bg-success bg-opacity-10">
                                            <div class="card-body py-3">
                                                <div class="h3 mb-0 text-success"><?= $stats['in_stock'] ?></div>
                                                <small class="text-success">In Stock</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4 text-center">
                                        <div class="card border-0 bg-danger bg-opacity-10">
                                            <div class="card-body py-3">
                                                <div class="h3 mb-0 text-danger"><?= $stats['out_of_stock'] ?></div>
                                                <small class="text-danger">Out of Stock</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($stats['out_of_stock'] > 0 || $stats['total'] < 5): ?>
                                <div class="alert alert-warning">
                                    <strong>⚠️ Action Required</strong>
                                    <p class="mb-0 mt-2">
                                        <?php if ($stats['out_of_stock'] > 0): ?>
                                            You have <?= $stats['out_of_stock'] ?> product(s) out of stock.
                                        <?php endif; ?>
                                        <?php if ($stats['total'] < 5): ?>
                                            You have very few products in your database.
                                        <?php endif; ?>
                                    </p>
                                </div>
                                
                                <div class="mb-3">
                                    <h6>This will:</h6>
                                    <ul class="small">
                                        <li>Set stock_qty to 100 for all products</li>
                                        <li>Activate all products</li>
                                        <li>Set low_stock_threshold to 10</li>
                                        <?php if ($stats['total'] < 5): ?>
                                            <li>Create 10 sample products (if needed)</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                
                                <form method="POST">
                                    <button type="submit" name="confirm" value="1" class="btn btn-primary w-100">
                                        🚀 Fix Stock Levels Now
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-success">
                                    <strong>✅ All Good!</strong>
                                    <p class="mb-0 mt-2">All products are in stock and ready for orders.</p>
                                </div>
                                <a href="<?= APP_URL ?>kiosk.php" class="btn btn-primary w-100">
                                    Go to Kiosk
                                </a>
                            <?php endif; ?>
                            
                        <?php endif; ?>
                        
                        <div class="mt-3 text-center">
                            <a href="<?= APP_URL ?>admin/" class="text-muted small">← Back to Admin</a>
                        </div>
                    </div>
                </div>
                
                <!-- Product List -->
                <?php if (empty($messages)): ?>
                <div class="card-modern mt-3">
                    <div class="card-body">
                        <h6 class="mb-3">Current Products:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $products = $mysqli->query("SELECT name, price, stock_qty, is_active FROM products ORDER BY name LIMIT 20");
                                    while ($p = $products->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($p['name']) ?></td>
                                        <td>₱<?= number_format($p['price'], 2) ?></td>
                                        <td>
                                            <?php if ($p['stock_qty'] == 0): ?>
                                                <span class="badge badge-soft-danger">Out</span>
                                            <?php elseif ($p['stock_qty'] < 10): ?>
                                                <span class="badge badge-soft-warning"><?= $p['stock_qty'] ?></span>
                                            <?php else: ?>
                                                <span class="badge badge-soft-success"><?= $p['stock_qty'] ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($p['is_active']): ?>
                                                <span class="badge badge-soft-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-soft-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</body>
</html>
