<?php
/**
 * Backfill Points Script
 * This script will create point_transactions entries for existing paid orders
 * that have points_awarded but no corresponding point_transactions entry
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/points.php';

// Security: Uncomment to require admin login
// require_login();
// if (!is_admin()) {
//     die('Access denied. Admin only.');
// }

$mysqli->begin_transaction();
try {
    // Find all paid orders with points_awarded but no point_transactions
    $query = "
        SELECT o.id, o.user_id, o.total_amount, o.points_awarded, o.code, o.created_at
        FROM orders o
        WHERE o.payment_status IN ('paid', 'successful')
        AND o.user_id IS NOT NULL
        AND o.points_awarded > 0
        AND NOT EXISTS (
            SELECT 1 FROM point_transactions pt
            WHERE pt.user_id = o.user_id
            AND pt.ref_type = 'order'
            AND pt.ref_id = o.id
        )
        ORDER BY o.created_at DESC
    ";
    
    $result = $mysqli->query($query);
    $orders_to_fix = [];
    $fixed_count = 0;
    $errors = [];
    
    if ($result && $result->num_rows > 0) {
        while ($order = $result->fetch_assoc()) {
            $orders_to_fix[] = $order;
        }
    }
    
    echo "Found " . count($orders_to_fix) . " orders to backfill points for.\n\n";
    
    foreach ($orders_to_fix as $order) {
        $user_id = (int)$order['user_id'];
        $order_id = (int)$order['id'];
        $points = (int)$order['points_awarded'];
        $order_code = $order['code'];
        
        if ($user_id > 0 && $order_id > 0 && $points > 0) {
            // Insert point transaction
            $ins = $mysqli->prepare("INSERT INTO point_transactions (user_id, points, type, ref_type, ref_id, note, created_at) VALUES (?, ?, 'earn', 'order', ?, ?, ?)");
            $note = "Points earned from order payment (backfilled) - Order: $order_code";
            $created_at = $order['created_at'];
            $ins->bind_param('iiiss', $user_id, $points, $order_id, $note, $created_at);
            
            if ($ins->execute()) {
                $fixed_count++;
                echo "✓ Fixed Order #$order_id ($order_code): Awarded $points points to user #$user_id\n";
            } else {
                $errors[] = "Failed to insert points for Order #$order_id: " . $mysqli->error;
                echo "✗ Error for Order #$order_id: " . $mysqli->error . "\n";
            }
            $ins->close();
        }
    }
    
    $mysqli->commit();
    
    echo "\n=== Summary ===\n";
    echo "Orders processed: " . count($orders_to_fix) . "\n";
    echo "Successfully fixed: $fixed_count\n";
    echo "Errors: " . count($errors) . "\n";
    
    if (!empty($errors)) {
        echo "\nErrors:\n";
        foreach ($errors as $error) {
            echo "  - $error\n";
        }
    }
    
    echo "\nPoints backfill completed!\n";
    
} catch (Exception $e) {
    $mysqli->rollback();
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

