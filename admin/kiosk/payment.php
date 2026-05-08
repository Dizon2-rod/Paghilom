<?php
require_once __DIR__ . '/includes/db_bootstrap.php';

$conn = kiosk_db_connect();

$mode = kiosk_safe_param('mode', 'GET');
$id = kiosk_safe_param('id', 'GET');
$codeParam = kiosk_safe_param('code', 'GET');

// If code is provided, resolve to type and ID to drive the page
if ($codeParam && (!$mode || !$id)) {
    $code = $codeParam;
    // Search orders then rewards by code columns
    $resolve = function(mysqli $conn, string $table, string $code) {
        if (!table_exists($conn, $table)) return null;
        $codeCol = first_existing_column($conn, $table, ['qr_code','code','token','reference','ref','qr']);
        if (!$codeCol) return null;
        $idCol = first_existing_column($conn, $table, ['id', $table === 'orders' ? 'order_id' : 'reward_id']) ?: 'id';
        $stmt = $conn->prepare("SELECT * FROM `{$table}` WHERE `{$codeCol}` = ? LIMIT 1");
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        if ($row) return ['id'=>$row[$idCol] ?? null, 'table'=>$table, 'idCol'=>$idCol, 'record'=>$row];
        return null;
    };
    $got = $resolve($conn, 'orders', $code) ?: $resolve($conn, 'reward_redemptions', $code);
    if ($got && $got['id']) {
        $mode = $got['table'] === 'orders' ? 'order' : 'reward';
        $id = (string)$got['id'];
        $table = $got['table'];
        $idCol = $got['idCol'];
        $record = $got['record'];
    }
}

if (!$mode || !$id || !in_array($mode, ['order','reward'], true)) {
    http_response_code(400);
    echo 'Invalid request.';
    exit;
}

$table = isset($table) ? $table : ($mode === 'order' ? 'orders' : 'reward_redemptions');
$idCol = isset($idCol) ? $idCol : ($mode === 'order' ? 'order_id' : 'reward_id');
if (!table_exists($conn, $table)) {
    echo '<p>Required table not found: ' . htmlspecialchars($table) . '</p>';
    exit;
}
$idCol = first_existing_column($conn, $table, ['id', $idCol]) ?: 'id';
$statusCol = first_existing_column($conn, $table, ['status','state']);

if (!isset($record)) {
    $stmt = $conn->prepare("SELECT * FROM `{$table}` WHERE `{$idCol}` = ? LIMIT 1");
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $record = $res ? $res->fetch_assoc() : null;
    $stmt->close();
}

if (!$record) {
    echo '<p>Record not found.</p>';
    exit;
}

// Compute payable amount and description
$total = 0.0;
$items = [];
$desc = $mode === 'order' ? 'Order Payment' : 'Reward Redemption';

if ($mode === 'order') {
    // Try to resolve order total and items
    if (isset($record['total_amount'])) $total = (float)$record['total_amount'];
    elseif (isset($record['amount'])) $total = (float)$record['amount'];

    // Load items if possible
    if (table_exists($conn, 'order_items')) {
        $orderItemOrderIdCol = first_existing_column($conn, 'order_items', ['order_id','orderId','order']);
        if ($orderItemOrderIdCol) {
            $stmt = $conn->prepare("SELECT * FROM `order_items` WHERE `{$orderItemOrderIdCol}` = ?");
            $stmt->bind_param('s', $id);
            $stmt->execute();
            $ri = $stmt->get_result();
            while ($ri && ($row = $ri->fetch_assoc())) {
                $items[] = $row;
            }
            $stmt->close();
        }
    }
}

if ($mode === 'reward') {
    // Attempt to infer any remaining amount due
    if (isset($record['amount_due'])) $total = (float)$record['amount_due'];
    elseif (isset($record['price_due'])) $total = (float)$record['price_due'];
    // Description
    if (isset($record['reward_name'])) $desc = 'Redeem: ' . $record['reward_name'];
}

$payError = null;
$paid = false;
$method = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay']) ) {
    $method = isset($_POST['method']) ? trim((string)$_POST['method']) : '';
    if ($method === '') $payError = 'Choose a payment method.';

    if (!$payError) {
        // Mark as paid/redeemed and adjust stock if order
        $conn->begin_transaction();
        try {
            if ($mode === 'reward') {
                // mark redeemed
                if ($statusCol) {
                    $stmt = $conn->prepare("UPDATE `{$table}` SET `{$statusCol}` = 'redeemed' WHERE `{$idCol}` = ?");
                    $stmt->bind_param('s', $id);
                    $stmt->execute();
                    $stmt->close();
                }
            } else {
                // mark order paid + payment_status successful
                $stmt = $conn->prepare("UPDATE orders SET status='paid', payment_status='successful', paid_at=NOW() WHERE `{$idCol}` = ?");
                $stmt->bind_param('s', $id);
                $stmt->execute();
                $stmt->close();
                // Stock deduction if schema supports it (finished goods)
                if (!empty($items) && table_exists($conn, 'products')) {
                    $productIdCol = first_existing_column($conn, 'order_items', ['product_id','item_id']);
                    $qtyCol = first_existing_column($conn, 'order_items', ['quantity','qty']);
                    $stockCol = first_existing_column($conn, 'products', ['stock','quantity']);
                    if ($productIdCol && $qtyCol && $stockCol) {
                        foreach ($items as $it) {
                            $pid = $it[$productIdCol];
                            $qty = (int)$it[$qtyCol];
                            $conn->query("UPDATE `products` SET `{$stockCol}` = `{$stockCol}` - " . (int)$qty . " WHERE `id` = " . intval($pid));
                        }
                    }
                }
                // Ingredients deduction based on recipes (BOM)
                if (!empty($items) && table_exists($conn,'product_recipes') && table_exists($conn,'ingredients')) {
                    $productIdCol = first_existing_column($conn, 'order_items', ['product_id','item_id']);
                    $qtyCol = first_existing_column($conn, 'order_items', ['quantity','qty']);
                    if ($productIdCol && $qtyCol) {
                        $deduct = [];
                        foreach ($items as $it){
                            $pid = (int)$it[$productIdCol]; $oqty = (float)$it[$qtyCol];
                            if ($pid>0 && $oqty>0){
                                $rs = $conn->query("SELECT ingredient_id, qty FROM product_recipes WHERE product_id=".$pid);
                                while($rs && ($r=$rs->fetch_assoc())){
                                    $iid=(int)$r['ingredient_id']; $need=(float)$r['qty']*$oqty; $deduct[$iid]=($deduct[$iid]??0)+$need;
                                }
                            }
                        }
                        foreach($deduct as $iid=>$need){
                            $conn->query("UPDATE ingredients SET quantity = quantity - ".((float)$need)." WHERE id=".(int)$iid);
                        }
                    }
                }
                // Award points to user once paid
                if (column_exists($conn,'orders','user_id')) {
                    $q=$conn->prepare("SELECT user_id,total_amount FROM orders WHERE `{$idCol}`=? LIMIT 1");
                    $q->bind_param('s',$id); $q->execute(); $or=$q->get_result()->fetch_assoc(); $q->close();
                    $uid=(int)($or['user_id']??0); $tamt=(float)($or['total_amount']??0);
                    if ($uid>0 && $tamt>0 && table_exists($conn,'point_transactions')) {
                        $pts=(int)floor($tamt/5);
                        if ($pts>0){
                            $chk=$conn->prepare("SELECT id FROM point_transactions WHERE user_id=? AND ref_type='order' AND ref_id=? LIMIT 1");
                            $chk->bind_param('ii',$uid,$id); $chk->execute(); $exists=$chk->get_result()->fetch_assoc(); $chk->close();
                            if(!$exists){
                                $ins=$conn->prepare("INSERT INTO point_transactions (user_id, points, type, ref_type, ref_id, note, created_at) VALUES (?,?,'earn','order',?, 'Points earned from order payment', NOW())");
                                $ins->bind_param('iii',$uid,$pts,$id); $ins->execute(); $ins->close();
                                if (column_exists($conn,'orders','points_awarded')) { $up=$conn->prepare("UPDATE orders SET points_awarded=COALESCE(points_awarded,0)+? WHERE `{$idCol}`=?"); $up->bind_param('ii',$pts,$id); $up->execute(); $up->close(); }
                            }
                        }
                    }
                }
            }

            // Record payment if a payments table exists
            if (table_exists($conn, 'payments')) {
                $stmt = $conn->prepare("INSERT INTO `payments` (ref_type, ref_id, method, amount) VALUES (?, ?, ?, ?)");
                $refType = $mode;
                $amt = $total;
                $stmt->bind_param('sssd', $refType, $id, $method, $amt);
                $stmt->execute();
                $stmt->close();
            }

            $conn->commit();
            // Redirect to Admin Dashboard after successful payment
            header('Location: ../dashboard.php');
            exit;
        } catch (Throwable $e) {
            $conn->rollback();
            $payError = 'Payment processing failed.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Kiosk | Payment</title>
  <link rel="icon" type="image/png" href="../assets/images/logo.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
  <style>
    body { min-height:100vh; display:flex; align-items:center; justify-content:center; }
    main { max-width: 860px; width:100%; padding: 1rem; }
    .summary { background:#f6f6f6; padding:1rem; border-radius:8px; }
    .methods { display:flex; gap:12px; flex-wrap:wrap; }
    .methods label { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border:1px solid #ddd; border-radius:8px; cursor:pointer; }
  </style>
</head>
<body>
  <main class="container">
    <h2>Payment</h2>
    <article class="summary">
      <header><strong><?php echo htmlspecialchars($desc); ?></strong></header>
      <p>Reference: <?php echo htmlspecialchars(($codeParam ?: ($mode . '#' . $id))); ?></p>
      <?php if ($mode === 'order'): ?>
        <details>
          <summary>Items</summary>
          <?php if (!empty($items)): ?>
            <ul>
              <?php foreach ($items as $it): ?>
                <li><?php echo htmlspecialchars(($it['name'] ?? ('Item ' . ($it['product_id'] ?? '')))) . ' x ' . htmlspecialchars((string)($it['quantity'] ?? $it['qty'] ?? '1')); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p>No item details available.</p>
          <?php endif; ?>
        </details>
      <?php endif; ?>
      <p><strong>Total: ₱<?php echo number_format((float)$total, 2); ?></strong></p>
    </article>

    <?php if ($payError): ?>
      <p style="color:#b00020;"><?php echo htmlspecialchars($payError); ?></p>
    <?php endif; ?>

    <form method="post">
      <fieldset>
        <legend>Select a payment method</legend>
        <div class="methods">
          <label><input type="radio" name="method" value="cash"> 💵 Cash</label>
          <label><input type="radio" name="method" value="gcash"> 📱 GCash / QR</label>
          <label><input type="radio" name="method" value="card"> 💳 Card</label>
        </div>
      </fieldset>
      <div class="grid">
        <a href="scan_qr.php" role="button" class="secondary">Cancel</a>
        <button type="submit" name="pay" value="1">Pay Now</button>
      </div>
    </form>
  </main>
</body>
</html>
