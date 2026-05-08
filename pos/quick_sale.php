<?php 
require __DIR__.'/../config.php'; 
require_pos();

$pid = (int)($_POST['product_id'] ?? 0); 
$qty = max(1, (int)($_POST['qty'] ?? 1)); 
$name = trim($_POST['customer_name'] ?? 'Walk-in');
$payment_method = $_POST['payment_method'] ?? 'cash'; // Default to cash payment

// Basic validation
if ($pid <= 0) {
    $_SESSION['error'] = 'Invalid product selected';
    header('Location: index.php');
    exit;
}

$store_id = 1; 
$ts = date('Y-m-d H:i:s', time() + 600); // default 10 mins pickup
$mysqli->begin_transaction();

try {
    $status = 'paid'; // Set to paid since we're processing payment directly
    $payment = 'paid';
    $otype = 'pickup';

  // Create order shell
  $stmt=$mysqli->prepare('INSERT INTO orders(user_id,order_type,pickup_store_id,pickup_time,status,payment_status,customer_name) VALUES(NULL,?,?,?,?,?,?)');
  if (!$stmt) { throw new Exception('Failed to prepare order insert: '.$mysqli->error); }
  $stmt->bind_param('sissss',$otype,$store_id,$ts,$status,$payment,$name);
  $stmt->execute();
  $oid=$stmt->insert_id;
  $stmt->close();

  // Load product; fail gracefully if missing
  $p=$mysqli->prepare('SELECT id,name,price FROM products WHERE id=?');
  if (!$p) { throw new Exception('Failed to prepare product query: '.$mysqli->error); }
  $p->bind_param('i',$pid);
  $p->execute();
  $prd=$p->get_result()->fetch_assoc();
  $p->close();
  if(!$prd){
    throw new Exception('Product not found for quick sale.');
  }

  // Insert order item
  $oi=$mysqli->prepare('INSERT INTO order_items(order_id,product_id,qty,price_each) VALUES(?,?,?,?)');
  if (!$oi) { throw new Exception('Failed to prepare order_items insert: '.$mysqli->error); }
  $priceEach = (float)$prd['price'];
  $oi->bind_param('iiid',$oid,$pid,$qty,$priceEach);
  $oi->execute();
  $oi->close();

  // recipe-based ingredient deduction (best effort)
  $r=$mysqli->prepare('SELECT ingredient_id, qty_per_unit FROM product_recipes WHERE product_id=?');
  if ($r) {
    $r->bind_param('i',$pid);
    $r->execute();
    $rr=$r->get_result();
    while($ri=$rr->fetch_assoc()){
      $chg=-1*$ri['qty_per_unit']*$qty;
      $im=$mysqli->prepare('INSERT INTO ingredient_movements(ingredient_id,change_qty,reason,ref_id) VALUES(?,?,"order",?)');
      if ($im) {
        $im->bind_param('iii',$ri['ingredient_id'],$chg,$oid);
        $im->execute();
        $im->close();
      }
    }
    $r->close();
  }

  // Award loyalty points if helper exists
  if (file_exists(__DIR__.'/../points.php')) {
    require_once __DIR__.'/../points.php';
    if (function_exists('award_points_for_order')) {
      award_points_for_order($mysqli, $oid, $_SESSION['user']['id'] ?? 0);
    }
  }

    // Generate a unique order code
    $orderCode = 'ORD' . strtoupper(substr(md5(uniqid($oid, true)), 0, 8));
    
    // Update order with payment details and code
    $updateStmt = $mysqli->prepare('UPDATE orders SET code = ?, payment_status = ?, status = ?, payment_method = ?, paid_at = NOW() WHERE id = ?');
    $updateStmt->bind_param('ssssi', $orderCode, $payment, $status, $payment_method, $oid);
    $updateStmt->execute();
    $updateStmt->close();
    
    // Record payment in payments table if it exists
    if (table_exists($mysqli, 'payments')) {
        $paymentStmt = $mysqli->prepare('INSERT INTO payments (ref_type, ref_id, method, amount, created_at) VALUES (?, ?, ?, ?, NOW())');
        $refType = 'order';
        $amount = (float)$prd['price'] * $qty;
        $paymentStmt->bind_param('siss', $refType, $oid, $payment_method, $amount);
        $paymentStmt->execute();
        $paymentStmt->close();
    }
    
    $mysqli->commit();
    
    // Redirect directly to receipt page
    header('Location: receipt.php?id=' . $oid);
    exit;
  
} catch (Throwable $e) {
    $mysqli->rollback();
    // Log error for debugging
    error_log('quick_sale error: '.$e->getMessage());
    // Redirect back with error
    $_SESSION['error'] = 'Failed to process order: ' . $e->getMessage();
    header('Location: index.php');
    exit;
}
