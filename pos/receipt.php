<?php require __DIR__.'/../config.php'; require_pos();
require_once __DIR__.'/../includes/qr_unified.php';
$id=(int)($_GET['id']??0);
// Fetch order data - ensure we get the latest payment status
$st=$mysqli->prepare('SELECT o.*, s.name store_name, s.address store_address FROM orders o LEFT JOIN stores s ON s.id=o.pickup_store_id WHERE o.id=?'); 
$st->bind_param('i',$id); 
$st->execute(); 
$o=$st->get_result()->fetch_assoc(); 
$st->close();
$it=$mysqli->prepare('SELECT oi.*, p.name FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE order_id=?'); $it->bind_param('i',$id); $it->execute(); $items=$it->get_result()->fetch_all(MYSQLI_ASSOC); $it->close();

// Ensure order code exists - generate if missing
$ordCode = $o['code'] ?? null;
if (empty($ordCode)) {
    // Generate order code if it doesn't exist
    $ordCode = 'ORD' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
    // Save to database
    $updateCode = $mysqli->prepare('UPDATE orders SET code = ? WHERE id = ?');
    if ($updateCode) {
        $updateCode->bind_param('si', $ordCode, $id);
        $updateCode->execute();
        $updateCode->close();
        // Refresh order data
        $o['code'] = $ordCode;
    }
}

// QR code will be generated client-side using QRCode.js library (same as payment_success.php)
$site_name = function_exists('get_setting') ? get_setting('site_name', 'Paghilom Café') : 'Paghilom Café';
$date = date('l, F j, Y \@ g:i A');
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8"><title>Receipt #<?=$id?></title>
<style>
  :root{ --brand:#2A5618; --muted:#64748b; --line:#e5e7eb; }
  body{ font-family: system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif; background:#f6fff6; color:#000; padding:18px; }
  .container{ max-width:420px; margin:0 auto; background:#fff; border:1px solid var(--line); border-radius:12px; box-shadow:0 6px 20px rgba(16,24,40,.08); overflow:hidden; }
  .head{ background:linear-gradient(135deg,#2A5618 0%,#1e3d10 100%); color:#fff; text-align:center; padding:18px; }
  .head img{ width:56px; height:56px; border-radius:50%; object-fit:cover; background:#fff; }
  .head .name{ font-weight:800; margin-top:6px; }
  .body{ padding:16px; }
  .row{ display:flex; justify-content:space-between; margin:6px 0; }
  .muted{ color:var(--muted); font-size:.9rem; }
  .section{ border-top:1px dashed var(--line); margin:12px 0; padding-top:12px; }
  .items{ list-style:none; margin:0; padding:0; }
  .item{ display:flex; justify-content:space-between; padding:6px 0; }
  .item .name{ font-weight:600; }
  .addons{ margin:4px 0 0 16px; color:#475569; font-size:.9rem; }
  .qr{ text-align:center; margin:10px 0; }
  .qr img{ width:180px; height:180px; padding:8px; background:#fff; border:1px solid var(--line); border-radius:8px; }
  .foot{ background:#f8fafc; border-top:1px dashed var(--line); padding:14px; text-align:center; color:#334155; }
  @media print{ body{ background:#fff; padding:0 } .container{ box-shadow:none; border:none } }
</style>
</head>
<body>
  <div class="container">
    <div class="head">
      <img src="../uploads/paghilom_logo.png" alt="Logo">
      <div class="name"><?=$site_name?></div>
      <div class="muted"><?=htmlspecialchars($o['store_name']?:'')?></div>
      <div class="muted" style="opacity:.85; font-size:.85rem;"><?=$date?></div>
    </div>
    <div class="body">
      <div class="row"><div class="muted">Order Code</div><div><strong><?=$ordCode?></strong></div></div>
      <?php if (!empty($ordCode)): ?>
        <div class="qr">
          <div id="order-qr" style="display:inline-block; padding:8px; background:#fff; border:1px solid var(--line); border-radius:8px;"></div>
          <div class="muted" style="text-align:center; margin-top:8px;">Show this QR for verification</div>
          <div class="muted" style="text-align:center; font-size:0.85rem; margin-top:4px;">Code: <strong><?=htmlspecialchars($ordCode)?></strong></div>
        </div>
      <?php else: ?>
        <div class="qr" style="padding:20px; text-align:center; color:var(--muted);">
          <p>Order code not available</p>
        </div>
      <?php endif; ?>

      <div class="section">
        <div class="muted" style="margin-bottom:6px;">Items</div>
        <ul class="items">
          <?php $subtotal=0; foreach($items as $row): $qty=(int)($row['qty']??$row['quantity']??1); $price=(float)($row['price']??0); $line = isset($row['subtotal']) ? (float)$row['subtotal'] : ($price*$qty); $subtotal+=$line; ?>
            <li class="item">
              <div>
                <div class="name"><?=htmlspecialchars($row['name'])?></div>
                <div class="muted">x<?=$qty?> @ ₱<?=number_format($price,2)?></div>
                <?php $oa=$mysqli->prepare('SELECT oa.*, a.name FROM order_addons oa JOIN addons a ON a.id=oa.addon_id WHERE order_item_id=?'); $oa->bind_param('i',$row['id']); $oa->execute(); $ads=$oa->get_result(); if($ads && $ads->num_rows): ?>
                  <ul class="addons">
                    <?php while($ad=$ads->fetch_assoc()): ?>
                      <li>+ <?=$ad['qty']?> x <?=htmlspecialchars($ad['name'])?> (₱<?=number_format($ad['qty']*$ad['price_each'],2)?>)</li>
                    <?php endwhile; ?>
                  </ul>
                <?php endif; $oa->close(); ?>
              </div>
              <div><strong>₱<?=number_format($line,2)?></strong></div>
            </li>
          <?php endforeach; ?>
        </ul>
        <div class="row" style="font-weight:700; border-top:1px dashed var(--line); padding-top:8px; margin-top:6px;"><span>Total</span><span>₱<?=number_format($subtotal,2)?></span></div>
      </div>

      <?php
      // Get change amount from URL parameter or calculate from payments table
      $changeDue = 0.0;
      $amountTendered = 0.0;
      
      // First, try to get from URL parameters (if just paid)
      if (isset($_GET['change']) && is_numeric($_GET['change'])) {
          $changeDue = (float)$_GET['change'];
          $amountTendered = isset($_GET['tendered']) && is_numeric($_GET['tendered']) ? (float)$_GET['tendered'] : ($subtotal + $changeDue);
      } else {
          // Try to fetch from payments table
          $payStmt = $mysqli->prepare('SELECT amount, amount_tendered FROM payments WHERE ref_type = ? AND ref_id = ? ORDER BY id DESC LIMIT 1');
          if ($payStmt) {
              $refType = 'order';
              $payStmt->bind_param('si', $refType, $id);
              $payStmt->execute();
              $payResult = $payStmt->get_result();
              if ($payRow = $payResult->fetch_assoc()) {
                  $amountTendered = (float)($payRow['amount_tendered'] ?? 0);
                  if ($amountTendered > 0) {
                      $changeDue = max(0, round($amountTendered - $subtotal, 2));
                  }
              }
              $payStmt->close();
          }
      }
      ?>
      
      <div class="row"><span class="muted">Payment Method</span><span><strong><?=htmlspecialchars(ucfirst($o['payment_method']??'Cash'))?></strong></span></div>
      <?php if ($o['payment_method'] === 'cash' || (empty($o['payment_method']) && $changeDue > 0)): ?>
        <?php if ($amountTendered > 0): ?>
          <div class="row"><span class="muted">Amount Tendered</span><span><strong>₱<?=number_format($amountTendered, 2)?></strong></span></div>
        <?php endif; ?>
        <?php if ($changeDue > 0): ?>
          <div class="row"><span class="muted">Change</span><span><strong>₱<?=number_format($changeDue, 2)?></strong></span></div>
        <?php endif; ?>
      <?php endif; ?>
      <?php 
      // Refresh order data to ensure we have latest payment status
      // This is important if payment was just processed
      $refreshStmt = $mysqli->prepare('SELECT payment_status, status, paid_at, payment_method FROM orders WHERE id=? LIMIT 1');
      $refreshStmt->bind_param('i', $id);
      $refreshStmt->execute();
      $refreshResult = $refreshStmt->get_result();
      if ($refreshRow = $refreshResult->fetch_assoc()) {
          // Update order data with latest payment info
          $o['payment_status'] = $refreshRow['payment_status'];
          $o['status'] = $refreshRow['status'];
          $o['paid_at'] = $refreshRow['paid_at'];
          $o['payment_method'] = $refreshRow['payment_method'];
      }
      $refreshStmt->close();
      
      // Check both payment_status and status columns to determine if paid
      $paymentStatus = strtolower(trim($o['payment_status'] ?? ''));
      $orderStatus = strtolower(trim($o['status'] ?? ''));
      
      // Determine if order is paid - check multiple indicators
      $isPaid = false;
      
      // Check payment_status column - accept multiple paid statuses
      if (in_array($paymentStatus, ['paid', 'successful', 'completed', 'settled'])) {
          $isPaid = true;
      } 
      // Check status column
      elseif (in_array($orderStatus, ['paid', 'completed', 'fulfilled'])) {
          $isPaid = true;
      } 
      // Check paid_at timestamp
      elseif (!empty($o['paid_at']) && $o['paid_at'] !== '0000-00-00 00:00:00' && $o['paid_at'] !== null) {
          $isPaid = true;
      }
      // If status is NOT in unpaid states, consider it paid
      elseif (!in_array($paymentStatus, ['pending', 'unpaid', 'failed', 'refunded', '']) && $paymentStatus !== '') {
          $isPaid = true;
      }
      
      // Display normalized status
      $paymentStatusDisplay = $isPaid ? 'Paid' : 'Unpaid';
      ?>
      <div class="row"><span class="muted">Payment Status</span><span><strong><?=htmlspecialchars($paymentStatusDisplay)?></strong></span></div>
      <div class="row"><span class="muted">Customer</span><span><?=htmlspecialchars($o['customer_name']?:'Guest')?></span></div>
    </div>
    <div class="foot">
      <div style="font-weight:600; color:#2A5618;">Thank you for choosing <?=$site_name?>!</div>
      <div class="muted">Keep this receipt for your records</div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.1/build/qrcode.min.js"></script>
  <script>
    (function(){
      try {
        // Generate QR code using QRCode.js library (same as payment_success.php)
        var qrContent = <?= json_encode($ordCode) ?>;
        var el = document.getElementById('order-qr');
        if (el && window.QRCode){
          var canvas = document.createElement('canvas');
          el.appendChild(canvas);
          // High error correction (H) for better scannability
          QRCode.toCanvas(canvas, qrContent, { 
            width: 180, 
            margin: 2,
            errorCorrectionLevel: 'H'
          });
        }
      } catch(e) { 
        console.error('QR Error:', e); 
        // Fallback: show order code as text if QR generation fails
        var el = document.getElementById('order-qr');
        if (el) {
          el.innerHTML = '<div style="padding:20px; text-align:center; color:var(--muted);">QR Code Error<br><strong><?=htmlspecialchars($ordCode)?></strong></div>';
        }
      }
    })();
  </script>
  <?php if (isset($_GET['print']) && $_GET['print'] === '1'): ?>
  <script>window.addEventListener('load',()=>{ try{ window.print(); }catch(e){} });</script>
  <?php else: ?>
  <script>
    // Add print button functionality
    window.addEventListener('load', function() {
      var printBtn = document.createElement('button');
      printBtn.textContent = 'Print Receipt';
      printBtn.style.cssText = 'position:fixed; bottom:20px; right:20px; padding:12px 24px; background:#2A5618; color:#fff; border:none; border-radius:8px; cursor:pointer; font-size:16px; font-weight:600; box-shadow:0 4px 12px rgba(0,0,0,0.15); z-index:1000;';
      printBtn.onclick = function() { window.print(); };
      document.body.appendChild(printBtn);
    });
  </script>
  <?php endif; ?>
</body>
</html>
