<?php
/**
 * Payment Callback/Webhook Handler
 * Automatically marks orders as paid after successful payment
 */

require __DIR__.'/config.php';

// Allow both GET and POST for testing and webhooks
$payment_ref = $_REQUEST['payment_ref'] ?? '';
$order_code = $_REQUEST['order_code'] ?? '';
$order_id = (int)($_REQUEST['order_id'] ?? 0);
$status = $_REQUEST['status'] ?? '';
$payment_method = $_REQUEST['payment_method'] ?? 'cash';

// Paymongo webhook data (JSON payload)
$webhook_data = json_decode(file_get_contents('php://input'), true);

// Log all incoming requests for debugging
error_log('Payment callback received: ' . json_encode($_REQUEST));
if ($webhook_data) {
    error_log('Webhook data: ' . json_encode($webhook_data));
}

// Process Paymongo webhook
if ($webhook_data && isset($webhook_data['data'])) {
    $event_type = $webhook_data['data']['attributes']['type'] ?? '';
    
    if ($event_type === 'payment.paid' || $event_type === 'source.chargeable') {
        $payment_data = $webhook_data['data']['attributes']['data'] ?? [];
        $payment_ref = $payment_data['attributes']['billing']['name'] ?? '';
        $order_id_from_desc = extractOrderIdFromDescription($payment_data['attributes']['description'] ?? '');
        
        if ($order_id_from_desc) {
            $order_id = $order_id_from_desc;
            $status = 'paid';
            $payment_method = $payment_data['attributes']['type'] ?? 'paymongo';
        }
    }
}

// Validate required data
if (empty($order_id) || empty($status)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters: order_id and status'
    ]);
    exit;
}

// Verify order exists
$stmt = $mysqli->prepare("SELECT id, code, payment_status, total_amount FROM orders WHERE id = ?");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Order not found'
    ]);
    exit;
}

// Check if already paid
if (in_array(strtolower($order['payment_status']),['paid','successful'])) {
    echo json_encode([
        'success' => true,
        'message' => 'Order already marked as paid',
        'order_id' => $order_id,
        'order_code' => $order['code']
    ]);
    exit;
}

// Process payment status update
if (in_array(strtolower($status), ['paid','success','successful','completed'])) {
    $mysqli->begin_transaction();
    
    try {
        // Update order payment status (unified)
        $stmt = $mysqli->prepare("\r
            UPDATE orders \r
            SET payment_status = 'successful',
                status = 'paid',
                paid_at = NOW(),
                payment_method = ?,
                payment_reference = ?,
                updated_at = NOW()
            WHERE id = ?\r
        ");
        $stmt->bind_param('ssi', $payment_method, $payment_ref, $order_id);
        $stmt->execute();
        $stmt->close();
        
        // Clear ordered items from carts now that payment succeeded
        $pidRes = $mysqli->prepare('SELECT product_id FROM order_items WHERE order_id=?');
        $pidRes->bind_param('i',$order_id); $pidRes->execute();
        $pids = [];
        $rs = $pidRes->get_result(); while($row=$rs->fetch_assoc()){ $pids[]=(int)$row['product_id']; }
        $pidRes->close();
        if($pids){
          foreach($pids as $pid){ unset($_SESSION['cart'][$pid]); }
          // remove from persistent cart
          $uidStmt=$mysqli->prepare('SELECT user_id FROM orders WHERE id=?');
          $uidStmt->bind_param('i',$order_id); $uidStmt->execute(); $ud=$uidStmt->get_result()->fetch_assoc(); $uidStmt->close();
          if(!empty($ud['user_id'])){
            $uid=(int)$ud['user_id'];
            $types=str_repeat('i',count($pids)+1); $params=array_merge([$uid],$pids);
            $del=$mysqli->prepare('DELETE FROM user_carts WHERE user_id=? AND product_id IN ('.implode(',',array_fill(0,count($pids),'?')).')');
            $del->bind_param($types, ...$params); $del->execute(); $del->close();
          }
        }

        // Award points to customer if order has user_id
        if (!empty($order['id'])) {
            require_once __DIR__ . '/includes/points.php';
            $orderDetails = $mysqli->prepare("SELECT user_id, total_amount FROM orders WHERE id = ?");
            $orderDetails->bind_param('i', $order_id);
            $orderDetails->execute();
            $orderData = $orderDetails->get_result()->fetch_assoc();
            $orderDetails->close();
            
            if ($orderData && !empty($orderData['user_id'])) {
                $user_id = (int)$orderData['user_id'];
                $total_amount = (float)($orderData['total_amount'] ?? 0);
                
                if ($user_id > 0 && $total_amount > 0) {
                    // Check if points already awarded for this order
                    $chk = $mysqli->prepare("SELECT id FROM point_transactions WHERE user_id = ? AND ref_type = 'order' AND ref_id = ? LIMIT 1");
                    $chk->bind_param('ii', $user_id, $order_id);
                    $chk->execute();
                    $exists = $chk->get_result()->fetch_assoc();
                    $chk->close();
                    
                    if (!$exists) {
                        // Calculate points: ₱10 = 5 points (₱2 = 1 point)
                        $points_earned = (int)floor($total_amount / 2);
                        if ($points_earned > 0) {
                            $ins = $mysqli->prepare("INSERT INTO point_transactions (user_id, points, type, ref_type, ref_id, note, created_at) VALUES (?, ?, 'earn', 'order', ?, 'Points earned from order payment', NOW())");
                            $ins->bind_param('iii', $user_id, $points_earned, $order_id);
                            $ins->execute();
                            $ins->close();
                            
                            // Update orders table if column exists
                            if ($mysqli->query("SHOW COLUMNS FROM orders LIKE 'points_awarded'")) {
                                $upd = $mysqli->prepare("UPDATE orders SET points_awarded = COALESCE(points_awarded,0) + ? WHERE id = ?");
                                $upd->bind_param('ii', $points_earned, $order_id);
                                $upd->execute();
                                $upd->close();
                            }
                        }
                    }
                }
            }
        }
        
        $mysqli->commit();
        
        // Success response
        echo json_encode([
            'success' => true,
            'message' => 'Payment processed successfully',
            'order_id' => $order_id,
            'order_code' => $order['code'],
            'payment_status' => 'successful'
        ]);
        
    } catch (Exception $e) {
        $mysqli->rollback();
        error_log('Payment processing error: ' . $e->getMessage());
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Payment processing failed: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid payment status: ' . $status
    ]);
}

/**
 * Extract order ID from payment description
 */
function extractOrderIdFromDescription($description) {
    if (preg_match('/Order #(\d+)/i', $description, $matches)) {
        return (int)$matches[1];
    }
    if (preg_match('/ORD[A-Z0-9]+/', $description, $matches)) {
        // Look up by order code
        global $mysqli;
        $stmt = $mysqli->prepare("SELECT id FROM orders WHERE code = ?");
        $stmt->bind_param('s', $matches[0]);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ? $result['id'] : null;
    }
    return null;
}
?>
