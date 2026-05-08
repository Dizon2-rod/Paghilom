<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/qr_unified.php';
require_once __DIR__ . '/../includes/qr_expiry_helper.php';

// Accept: ?code=ORD... or ?type=order|reward or legacy ?mode=&id=
$code = trim($_GET['code'] ?? '');
$type = strtolower(trim($_GET['type'] ?? ''));
$mode = strtolower(trim($_GET['mode'] ?? '')); // legacy
$id   = trim($_GET['id'] ?? '');

// Resolve code if mode+id supplied
if (!$code && $mode && $id) {
    $type = $mode === 'reward' ? 'reward' : 'order';
    // Lookup code from DB
    if ($type === 'order') {
        $stmt = $mysqli->prepare("SELECT code FROM orders WHERE id=? LIMIT 1");
        $stmt->bind_param('s', $id);
    } else {
        $stmt = $mysqli->prepare("SELECT voucher_code AS code FROM vouchers WHERE id=? LIMIT 1");
        $stmt->bind_param('s', $id);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res? $res->fetch_assoc(): null;
    $stmt->close();
    if ($row && !empty($row['code'])) { $code = $row['code']; }
}

// If still empty, try to parse if full URL was passed
if ($code && filter_var($code, FILTER_VALIDATE_URL)) {
    $u = parse_url($code);
    if (!empty($u['query'])) { parse_str($u['query'], $q); $code = $q['code'] ?? $q['order'] ?? $code; }
}

// Pull order/reward details
$order = null; $reward = null; $items = []; $total = 0.0; $desc = '';
if ($code) {
    if (!$type) { $type = preg_match('/^PHC-/i', $code) ? 'reward' : 'order'; }
    if ($type === 'order') {
        $stmt = $mysqli->prepare("SELECT * FROM orders WHERE code=? LIMIT 1");
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($order) {
            $total = (float)($order['total_amount'] ?? 0);
            $desc = 'Order Payment';
            // load items
            if ($order && $mysqli->query("SHOW TABLES LIKE 'order_items'")) {
                $st = $mysqli->prepare("SELECT * FROM order_items WHERE order_id=?");
                $st->bind_param('i', $order['id']);
                $st->execute();
                $rs = $st->get_result();
                $ids = [];
                while ($rs && ($row = $rs->fetch_assoc())) { $items[] = $row; if (isset($row['id'])) $ids[] = (int)$row['id']; }
                $st->close();
                // Load add-ons
                if ($ids && $mysqli->query("SHOW TABLES LIKE 'order_item_options'")) {
                    $in = implode(',', array_fill(0, count($ids), '?'));
                    $types = str_repeat('i', count($ids));
                    // Detect column names dynamically
                    $optItemCol = 'order_item_id';
                    $res = $mysqli->query("SHOW COLUMNS FROM order_item_options LIKE 'order_item_id'");
                    if (!$res || $res->num_rows===0) { $res2=$mysqli->query("SHOW COLUMNS FROM order_item_options LIKE 'item_id'"); if($res2&&$res2->num_rows>0){ $optItemCol='item_id'; } }
                    $nameCol = 'addon_name'; $r3=$mysqli->query("SHOW COLUMNS FROM order_item_options LIKE 'addon_name'");
                    if (!$r3 || $r3->num_rows===0){ foreach(['name','label','option_name','title','description','option'] as $cand){ $r=$mysqli->query("SHOW COLUMNS FROM order_item_options LIKE '".$mysqli->real_escape_string($cand)."'"); if($r&&$r->num_rows>0){ $nameCol=$cand; break; } } }
                    $priceCol = null; foreach(['price','amount','add_price','addon_price','value'] as $cand){ $r=$mysqli->query("SHOW COLUMNS FROM order_item_options LIKE '".$mysqli->real_escape_string($cand)."'"); if($r&&$r->num_rows>0){ $priceCol=$cand; break; } }
                    $sel = "`$optItemCol` AS order_item_id, `$nameCol` AS name" . ($priceCol? ", `$priceCol` AS price" : ", NULL AS price");
                    $sql = "SELECT $sel FROM order_item_options WHERE `$optItemCol` IN ($in)";
                    $op = $mysqli->prepare($sql);
                    $op->bind_param($types, ...$ids);
                    $op->execute();
                    $or = $op->get_result();
                    $map = [];
                    while ($or && ($o = $or->fetch_assoc())) { $map[(int)$o['order_item_id']][] = $o; }
                    $op->close();
                    foreach ($items as &$it) { $oid = (int)($it['id'] ?? 0); if ($oid && isset($map[$oid])) $it['__options'] = $map[$oid]; }
                    unset($it);
                }
            }
        }
    } else {
        $stmt = $mysqli->prepare("SELECT * FROM vouchers WHERE voucher_code=? LIMIT 1");
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $reward = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($reward) {
            $total = (float)($reward['amount_due'] ?? 0);
            $desc = 'Rewards Redemption';
        }
    }
}

if (!$order && !$reward) {
    http_response_code(404);
    echo 'Record not found.'; exit;
}

// Handle payment submit
$payError = null; $method = null; $amountPaid = null; $changeDue = 0.0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay'])) {
    $method = trim($_POST['method'] ?? '');
    $amountPaid = isset($_POST['amount_paid']) ? (float)str_replace([',',' '],['',''], (string)$_POST['amount_paid']) : null;
    if ($method === '') $payError = 'Choose a payment method.';

    if (!$payError) {
        if ($method === 'cash') {
            if ($amountPaid === null || $amountPaid <= 0) $payError = 'Enter amount tendered.';
            elseif ($amountPaid + 1e-9 < (float)$total) $payError = 'Insufficient cash.';
            else $changeDue = max(0, round($amountPaid - (float)$total, 2));
        } else { $amountPaid = (float)$total; $changeDue = 0.0; }
    }

    if (!$payError) {
        $mysqli->begin_transaction();
        try {
            if ($type === 'order') {
                $st = $mysqli->prepare("UPDATE orders SET payment_status='successful', status='paid', paid_at=NOW() WHERE code=?");
                $st->bind_param('s', $code); $st->execute(); $st->close();
                // Optional stock deduction to sync with analytics
                if (!empty($items) && $mysqli->query("SHOW TABLES LIKE 'products'")) {
                    // detect columns
                    $hasStock = $mysqli->query("SHOW COLUMNS FROM products LIKE 'stock'") && $mysqli->query("SHOW COLUMNS FROM products LIKE 'stock'")->num_rows>0;
                    $stockCol = $hasStock ? 'stock' : ( ($r=$mysqli->query("SHOW COLUMNS FROM products LIKE 'quantity'")) && $r->num_rows>0 ? 'quantity' : null );
                    if ($stockCol) {
                        foreach ($items as $it) {
                            $pid = (int)($it['product_id'] ?? $it['item_id'] ?? 0);
                            $qty = (int)($it['quantity'] ?? $it['qty'] ?? 1);
                            if ($pid>0 && $qty>0) {
                                $mysqli->query("UPDATE products SET `$stockCol` = `$stockCol` - ".$qty." WHERE id=".$pid);
                            }
                        }
                    }
                }
            } else {
                $st = $mysqli->prepare("UPDATE vouchers SET status='redeemed', redeemed_at=NOW() WHERE voucher_code=?");
                $st->bind_param('s', $code); $st->execute(); $st->close();
            }
            // Insert to payments
            if ($mysqli->query("SHOW TABLES LIKE 'payments'")) {
                if ($type === 'order') $refId = (string)$order['id']; else $refId = (string)$reward['id'];
                if ($mysqli->query("SHOW COLUMNS FROM payments LIKE 'amount_tendered'")) {
                    $p = $mysqli->prepare("INSERT INTO payments (ref_type, ref_id, method, amount, amount_tendered) VALUES (?,?,?,?,?)");
                    $refType = $type; $amt=(float)$total; $tender=(float)$amountPaid;
                    $p->bind_param('sssdd', $refType, $refId, $method, $amt, $tender);
                    $p->execute(); $p->close();
                } else {
                    $p = $mysqli->prepare("INSERT INTO payments (ref_type, ref_id, method, amount) VALUES (?,?,?,?)");
                    $refType = $type; $amt=(float)$total;
                    $p->bind_param('sssd', $refType, $refId, $method, $amt);
                    $p->execute(); $p->close();
                }
            }
            
            // Award points to customer if order is paid and has user_id
            if ($type === 'order' && $order && !empty($order['user_id'])) {
                require_once __DIR__ . '/../includes/points.php';
                $user_id = (int)$order['user_id'];
                $total_amount = (float)($order['total_amount'] ?? 0);
                
                if ($user_id > 0 && $total_amount > 0) {
                    // Check if points already awarded for this order
                    $chk = $mysqli->prepare("SELECT id FROM point_transactions WHERE user_id = ? AND ref_type = 'order' AND ref_id = ? LIMIT 1");
                    $chk->bind_param('ii', $user_id, $order['id']);
                    $chk->execute();
                    $exists = $chk->get_result()->fetch_assoc();
                    $chk->close();
                    
                    if (!$exists) {
                        // Calculate points: ₱10 = 5 points (₱2 = 1 point)
                        $points_earned = (int)floor($total_amount / 2);
                        if ($points_earned > 0) {
                            $ins = $mysqli->prepare("INSERT INTO point_transactions (user_id, points, type, ref_type, ref_id, note, created_at) VALUES (?, ?, 'earn', 'order', ?, 'Points earned from order payment', NOW())");
                            $ins->bind_param('iii', $user_id, $points_earned, $order['id']);
                            $ins->execute();
                            $ins->close();
                            
                            // Update orders table if column exists
                            if ($mysqli->query("SHOW COLUMNS FROM orders LIKE 'points_awarded'")) {
                                $upd = $mysqli->prepare("UPDATE orders SET points_awarded = COALESCE(points_awarded,0) + ? WHERE id = ?");
                                $upd->bind_param('ii', $points_earned, $order['id']);
                                $upd->execute();
                                $upd->close();
                            }
                        }
                    }
                }
            }
            
            $mysqli->commit();
            // Render success screen below
        } catch (Throwable $e) { $mysqli->rollback(); $payError='Payment failed.'; }
    }
}

$site_name = function_exists('get_setting') ? get_setting('site_name', 'Paghilom Café') : 'Paghilom Café';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Payment | <?= htmlspecialchars($site_name) ?></title>
  <link href="<?= APP_URL ?>assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    :root { --brand:#2A5618; --brand-2:#184212; --bg:#F6FFF6; --line:#e5e7eb; --muted:#6b7280; }
body { background: var(--bg); color:#000; }
    header.top { background: var(--brand); color:#fff; padding:12px 16px; box-shadow:0 2px 8px rgba(0,0,0,.12); }
    header.top .brand { font-weight:800; letter-spacing:.2px; }
    main { max-width:1100px; margin:18px auto; padding:0 16px; }
    .steps { display:flex; gap:10px; margin:12px 0 18px; }
    .step { flex:1; display:flex; align-items:center; gap:10px; padding:10px 12px; border:1px solid var(--line); border-radius:10px; background:#fff; font-weight:600; }
    .step .num{ width:28px; height:28px; border-radius:50%; border:2px solid var(--line); display:flex; align-items:center; justify-content:center; }
    .step.active{ border-color:var(--brand); box-shadow:0 0 0 4px rgba(42,86,24,.08); }
    .step.active .num{ border-color:var(--brand); color:var(--brand); }
    .card{ border:1px solid var(--line); border-radius:12px; box-shadow:0 6px 14px rgba(16,24,40,.06); background:#fff; }
    .card header{ padding:14px 16px; border-bottom:1px solid var(--line); font-weight:700; }
    .card .body{ padding:16px; }
    .items{ list-style:none; margin:0; padding:0; max-height:52vh; overflow:auto; }
    .item{ display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px dashed var(--line); }
    .item:last-child{ border-bottom:none; }
    .muted{ color:var(--muted); }
    .totals .row{ display:flex; justify-content:space-between; margin:6px 0; }
    .btn-brand{ background:var(--brand); color:#fff; border:1px solid var(--brand); border-radius:10px; padding:10px 16px; font-weight:700; }
    .btn-brand:hover{ background:var(--brand-2); border-color:var(--brand-2); }
    .methods{ display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; }
    .method{ position:relative; }
    .method input{ position:absolute; inset:0; opacity:0; }
    .method label{ display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; padding:18px 12px; border:2px solid var(--line); border-radius:12px; cursor:pointer; transition:.15s; background:#fff; text-align:center; }
    .method input:checked + label{ border-color:var(--brand); box-shadow:0 0 0 4px rgba(42,86,24,.10); }
    .success-check{ width:80px; height:80px; border-radius:50%; background:#e7f8ee; display:flex; align-items:center; justify-content:center; color:var(--brand); font-size:42px; margin:0 auto 12px; box-shadow:0 0 0 6px rgba(42,86,24,.08); }
  </style>
</head>
<body>
  <header class="top">
    <div class="container d-flex justify-content-between">
      <div class="brand"><?= htmlspecialchars($site_name) ?></div>
      <a class="text-white text-decoration-none" href="<?= APP_URL ?>"><i class="bi bi-house me-1"></i>Home</a>
    </div>
  </header>
  <main>
    <div class="steps">
      <div class="step"><div class="num">1</div>Confirm</div>
      <div class="step active"><div class="num">2</div>Pay</div>
      <div class="step"><div class="num">3</div>Receipt</div>
    </div>

    <?php if ($_SERVER['REQUEST_METHOD']==='POST' && !$payError): ?>
      <div class="card mb-3">
        <header>Payment Successful</header>
        <div class="body text-center">
          <div class="success-check"><i class="bi bi-check2"></i></div>
          <p class="mb-1">Transaction: <strong><?= htmlspecialchars(strtoupper($type)) ?> • <?= htmlspecialchars($code) ?></strong></p>
          <p class="mb-2">Method: <strong><?= htmlspecialchars(strtoupper($method)) ?></strong> • Total: <strong>₱<?= number_format((float)$total,2) ?></strong></p>
          <?php if ($method==='cash'): ?>
          <p class="mb-2">Amount Given: ₱<?= number_format((float)$amountPaid,2) ?> • Change: <strong>₱<?= number_format((float)$changeDue,2) ?></strong></p>
          <?php endif; ?>
          <?php if ($type==='order' && $order): ?>
            <?php $qrimg = generate_qr_image($order['code']); ?>
            <div class="mt-3">
              <img src="<?= htmlspecialchars($qrimg) ?>" alt="Receipt QR" style="width:180px;height:180px;padding:8px;background:#fff;border-radius:10px;border:1px solid var(--line)">
              <div class="small mt-2 muted">Show this QR for verification</div>
            </div>
          <?php endif; ?>
          <div class="mt-3">
            <a class="btn btn-brand" href="<?= APP_URL ?>"><i class="bi bi-check2-circle me-1"></i>Done</a>
          </div>
        </div>
      </div>
    <?php else: ?>
      <div class="row g-3">
        <div class="col-lg-7">
          <div class="card">
            <header>Order Summary</header>
            <div class="body">
              <div class="muted mb-2">Reference: <?= htmlspecialchars($code) ?></div>
              <?php if ($type==='order' && !empty($items)): ?>
                <ul class="items">
                  <?php foreach ($items as $it): $qty=(int)($it['quantity']??$it['qty']??1); $price=(float)($it['price']??0); $name=$it['name']??('Item '.($it['product_id']??'')); ?>
                  <li class="item">
                    <div>
                      <div><strong><?= htmlspecialchars($name) ?></strong></div>
                      <div class="muted">x<?= $qty ?> @ ₱<?= number_format($price,2) ?></div>
                      <?php $opts=$it['__options']??[]; if ($opts): ?>
                      <ul class="mt-1" style="margin:6px 0 0 14px; padding:0; list-style:disc; color:#475569; font-size:.9rem;">
                        <?php foreach ($opts as $op): ?>
                          <li><?= htmlspecialchars($op['name']??'') ?><?php if(isset($op['price'])): ?> (+₱<?= number_format((float)$op['price'],2) ?>)<?php endif; ?></li>
                        <?php endforeach; ?>
                      </ul>
                      <?php endif; ?>
                    </div>
                    <div><strong>₱<?= number_format($qty*$price,2) ?></strong></div>
                  </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <p class="muted">No item breakdown available.</p>
              <?php endif; ?>
              <div class="totals mt-2">
                <div class="row"><span>Subtotal</span><span>₱<?= number_format((float)$total,2) ?></span></div>
                <div class="row" style="font-weight:800;font-size:1.1rem;"><span>Total</span><span>₱<?= number_format((float)$total,2) ?></span></div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-5">
          <div class="card">
            <header>Payment</header>
            <div class="body">
              <?php if ($payError): ?><div class="alert alert-danger"><?= htmlspecialchars($payError) ?></div><?php endif; ?>
              <form method="post">
                <div class="methods mb-3">
                  <div class="method"><input id="pm-cash" type="radio" name="method" value="cash"><label for="pm-cash"><div>💵</div>Cash<small>Pay with cash</small></label></div>
                  <div class="method"><input id="pm-gcash" type="radio" name="method" value="gcash"><label for="pm-gcash"><div>📱</div>GCash<small>Mobile e‑wallet</small></label></div>
                  <div class="method"><input id="pm-card" type="radio" name="method" value="card"><label for="pm-card"><div>💳</div>Card<small>Debit/Credit</small></label></div>
                </div>
                <div id="cashFields" style="display:none;">
                  <label class="form-label">Amount Tendered</label>
                  <input class="form-control" name="amount_paid" id="amountPaid" type="number" step="0.01" min="0" placeholder="0.00" style="max-width:220px;">
                  <div class="muted mt-1" id="changeText">Change: ₱0.00</div>
                </div>
                <div class="mt-3 d-grid"><button class="btn btn-brand" id="confirmBtn" name="pay" value="1"><i class="bi bi-check2-circle me-1"></i>Confirm Payment — ₱<?= number_format((float)$total,2) ?></button></div>
              </form>
            </div>
          </div>
        </div>
      </div>
      <script>
        (function(){
          const total = <?= json_encode((float)$total) ?>;
          const cashFields = document.getElementById('cashFields');
          const amountPaid = document.getElementById('amountPaid');
          const changeText = document.getElementById('changeText');
          const btn = document.getElementById('confirmBtn');
          function fmt(n){ return '₱' + (Number(n||0)).toFixed(2); }
          function update(){
            const m = document.querySelector('input[name="method"]:checked')?.value;
            if (m==='cash'){
              cashFields.style.display='';
              const val = parseFloat(amountPaid.value||'0');
              const change = Math.max(0, val - total);
              changeText.textContent = 'Change: ' + fmt(change);
              btn.disabled = !(val>=total);
            } else { cashFields.style.display='none'; btn.disabled = !m; }
          }
          document.querySelectorAll('input[name="method"]').forEach(r=>r.addEventListener('change', update));
          if (amountPaid) amountPaid.addEventListener('input', update);
          update();
        })();
      </script>
    <?php endif; ?>
  </main>
</body>
</html>
