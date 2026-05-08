<?php
require_once __DIR__ . '/../config.php';
require_pos();
require_once __DIR__ . '/../admin/kiosk/includes/db_bootstrap.php';
require_once __DIR__ . '/../includes/qr_unified.php';

$conn = kiosk_db_connect();
$mode = kiosk_safe_param('mode', 'GET');
$id = kiosk_safe_param('id', 'GET');
$method = kiosk_safe_param('method', 'GET');
$paid = isset($_GET['paid']) ? (float)$_GET['paid'] : null; $change = isset($_GET['change']) ? (float)$_GET['change'] : null;

// For rewards, prioritize redemptions table (where records are created when users redeem points)
if ($mode === 'order') {
    $table = 'orders';
    $idCol = 'order_id';
} else {
    $possibleTables = ['redemptions', 'vouchers', 'reward_redemptions'];
    $table = null;
    foreach ($possibleTables as $tbl) {
        if (table_exists($conn, $tbl)) {
            $table = $tbl;
            break;
        }
    }
    if (!$table) {
        die('Required table not found for rewards.');
    }
    $idCol = 'reward_id';
}
$idCol = first_existing_column($conn, $table, ['id', $idCol]) ?: 'id';

$record = null;
$code = kiosk_safe_param('code', 'GET'); // Get code parameter if provided

if ($mode && $id && table_exists($conn, $table)) {
    // For rewards, join with rewards table to get reward name
    if ($mode === 'reward' && $table === 'redemptions') {
        $stmt = $conn->prepare("
            SELECT r.*, 
                   COALESCE(rw.name, rc.name) as reward_name,
                   COALESCE(rw.description, rc.description) as reward_description
            FROM `{$table}` r
            LEFT JOIN rewards rw ON r.reward_id = rw.id
            LEFT JOIN reward_catalog rc ON r.reward_id = rc.id
            WHERE r.`{$idCol}` = ? 
            LIMIT 1
        ");
    } else {
        $stmt = $conn->prepare("SELECT * FROM `{$table}` WHERE `{$idCol}` = ? LIMIT 1");
    }
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $record = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    
    // If not found and it's a reward, try looking up by voucher_code
    if (!$record && $mode === 'reward' && $code) {
        $lookupCode = $code;
        if (table_exists($conn, 'redemptions')) {
            $voucherCodeCol = first_existing_column($conn, 'redemptions', ['voucher_code', 'code']);
            if ($voucherCodeCol) {
                // Join with rewards table to get reward name
                $stmt = $conn->prepare("
                    SELECT r.*, 
                           COALESCE(rw.name, rc.name) as reward_name,
                           COALESCE(rw.description, rc.description) as reward_description
                    FROM `redemptions` r
                    LEFT JOIN rewards rw ON r.reward_id = rw.id
                    LEFT JOIN reward_catalog rc ON r.reward_id = rc.id
                    WHERE r.`{$voucherCodeCol}` = ? 
                    LIMIT 1
                ");
                if ($stmt) {
                    $stmt->bind_param('s', $lookupCode);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $record = $res ? $res->fetch_assoc() : null;
                    $stmt->close();
                    if ($record) {
                        $table = 'redemptions';
                        $idCol = 'id';
                    }
                }
            }
        }
    }
}

$total = 0.0; $desc = $mode === 'order' ? 'Order Payment' : 'Reward Redemption';
$items = [];
if ($mode === 'order' && $record) {
    // totals
    if (isset($record['total_amount'])) $total = (float)$record['total_amount'];
    elseif (isset($record['amount'])) $total = (float)$record['amount'];
    // code column
    $codeCol = first_existing_column($conn, 'orders', ['code','order_code','order_number','reference','ref']);
    if ($codeCol) $code = $record[$codeCol] ?? null;
} else if ($mode === 'reward' && $record) {
    // For rewards, get voucher code
    $voucherCodeCol = first_existing_column($conn, $table, ['voucher_code', 'code']);
    if ($voucherCodeCol) {
        $code = $record[$voucherCodeCol] ?? $code;
    }
    // Get reward name - should be available from the JOIN query
    $reward_name = $record['reward_name'] ?? null;
    if ($reward_name) {
        $desc = 'Redeem: ' . $reward_name;
    } else {
        // Fallback: try to get reward name directly if not in record
        $reward_id = $record['reward_id'] ?? null;
        if ($reward_id) {
            // Try rewards table first
            $rw_stmt = $conn->prepare("SELECT name FROM rewards WHERE id = ? LIMIT 1");
            if ($rw_stmt) {
                $rw_stmt->bind_param('i', $reward_id);
                $rw_stmt->execute();
                $rw_res = $rw_stmt->get_result();
                $rw_row = $rw_res ? $rw_res->fetch_assoc() : null;
                $rw_stmt->close();
                if ($rw_row && isset($rw_row['name'])) {
                    $reward_name = $rw_row['name'];
                    $desc = 'Redeem: ' . $reward_name;
                } else {
                    // Try reward_catalog table
                    $rc_stmt = $conn->prepare("SELECT name FROM reward_catalog WHERE id = ? LIMIT 1");
                    if ($rc_stmt) {
                        $rc_stmt->bind_param('i', $reward_id);
                        $rc_stmt->execute();
                        $rc_res = $rc_stmt->get_result();
                        $rc_row = $rc_res ? $rc_res->fetch_assoc() : null;
                        $rc_stmt->close();
                        if ($rc_row && isset($rc_row['name'])) {
                            $reward_name = $rc_row['name'];
                            $desc = 'Redeem: ' . $reward_name;
                        }
                    }
                }
            }
        }
    }
    // items list
    if (table_exists($conn, 'order_items')) {
        $orderItemOrderIdCol = first_existing_column($conn, 'order_items', ['order_id','orderId','order']);
        if ($orderItemOrderIdCol) {
            $stmt = $conn->prepare("SELECT * FROM `order_items` WHERE `{$orderItemOrderIdCol}` = ?");
            $stmt->bind_param('s', $id); $stmt->execute();
            $ri = $stmt->get_result();
            while ($ri && ($row = $ri->fetch_assoc())) $items[] = $row;
            $stmt->close();
            // add-ons
            if (table_exists($conn, 'order_item_options')) {
                $itemIdCol = first_existing_column($conn, 'order_items', ['id','order_item_id']);
                if ($itemIdCol && !empty($items)) {
                    $ids = array_values(array_filter(array_map(function($r) use($itemIdCol){ return (int)($r[$itemIdCol] ?? 0); }, $items)));
                    if ($ids) {
                        $ph = implode(',', array_fill(0, count($ids), '?'));
                        $types = str_repeat('i', count($ids));
                        $optItemCol = first_existing_column($conn, 'order_item_options', ['order_item_id','item_id','oi_id']);
                        $optNameCol = first_existing_column($conn, 'order_item_options', ['addon_name','name','label','option_name','title','description','option']);
                        $optPriceCol= first_existing_column($conn, 'order_item_options', ['price','amount','add_price','addon_price','value']);
                        if ($optItemCol && $optNameCol) {
                            $sel = "`$optItemCol` AS order_item_id, `$optNameCol` AS name" . ($optPriceCol? ", `$optPriceCol` AS price" : ", NULL AS price");
                            $sql = "SELECT $sel FROM order_item_options WHERE `$optItemCol` IN ($ph)";
                            $opt = $conn->prepare($sql);
                            $opt->bind_param($types, ...$ids);
                            $opt->execute(); $rs = $opt->get_result();
                            $map = [];
                            while ($rs && ($o = $rs->fetch_assoc())) $map[(int)$o['order_item_id']][] = $o;
                            $opt->close();
                            foreach ($items as &$it) { $oid=(int)($it[$itemIdCol]??0); if($oid&&isset($map[$oid])) $it['__options']=$map[$oid]; }
                            unset($it);
                        }
                    }
                }
            }
        }
    }
} else if ($mode === 'reward' && $record) {
    if (isset($record['amount_due'])) $total = (float)$record['amount_due'];
    elseif (isset($record['price_due'])) $total = (float)$record['price_due'];
    if (isset($record['reward_name'])) $desc = 'Redeem: ' . $record['reward_name'];
}

$qr = $code ? generate_qr_image($code, 200) : null;
$site = 'Paghilom Café'; $date = date('l, F j, Y \@ g:i A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>POS | Success</title>
  <style>
    :root{ --brand:#2A5618; --brand-2:#184212; --line:#e5e7eb; --muted:#64748b; --ink:#0f172a; }
    body { background:#f6fff6; color:#000; min-height:100vh; display:flex; align-items:center; justify-content:center; margin:0; font-family: system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif; }
    .container { max-width:820px; width:100%; padding:18px; }
    .card { border:1px solid var(--line); border-radius:16px; box-shadow:0 10px 30px rgba(16,24,40,.10); background:#fff; overflow:hidden; }
    .card header { padding:18px; background:linear-gradient(135deg,var(--brand) 0%,var(--brand-2) 100%); color:#fff; border-bottom:1px solid var(--line); font-weight:800; letter-spacing:.2px; display:flex; align-items:center; gap:12px; }
    .success-badge { width:42px; height:42px; border-radius:50%; background:#e7f8ee; color:var(--brand); display:inline-flex; align-items:center; justify-content:center; font-size:22px; box-shadow:0 0 0 4px rgba(42,86,24,.15); }
    .body { padding:20px; }
    .meta { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; color:var(--muted); }
    .grid { display:grid; grid-template-columns: 2fr 1.15fr; gap:18px; }
    .panel { border:1px solid var(--line); border-radius:12px; padding:14px; background:#fff; }
    .section-title { font-weight:700; margin-bottom:8px; color:var(--ink); }
    .qr { text-align:center; }
    .qr img { width:200px; height:200px; padding:10px; background:#fff; border:1px solid var(--line); border-radius:12px; box-shadow:0 6px 18px rgba(16,24,40,.08); }
    .items { list-style:none; margin:0; padding:0; max-height:48vh; overflow:auto; }
    .item { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px dashed var(--line); }
    .item:last-child{ border-bottom:none; }
    .muted { color:var(--muted); }
    .addons { margin:6px 0 0 14px; font-size:.9rem; color:#475569; }
    .row { display:flex; justify-content:space-between; margin:6px 0; }
    .total { font-weight:800; font-size:1.15rem; border-top:1px dashed var(--line); padding-top:8px; }
    .actions { display:flex; gap:10px; margin-top:14px; justify-content:center; }
    .btn { padding:10px 16px; border-radius:10px; border:1px solid var(--line); background:#fff; cursor:pointer; transition:.15s ease; }
    .btn:hover { box-shadow:0 6px 16px rgba(16,24,40,.10); }
    .btn.primary { background:var(--brand); color:#fff; border-color:var(--brand); }
    @media (max-width: 900px){ .grid{ grid-template-columns:1fr; } }
    @media print { .actions{ display:none } .card{ box-shadow:none; border:none } body{ background:#fff } }
  </style>
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <header><span class="success-badge">✔</span> Payment Successful</header>
      <div class="body">
        <div class="meta">
          <div>Reference: <strong><?= htmlspecialchars(($mode ?? 'n/a') . '#' . ($id ?? '')) ?></strong></div>
          <div><?= date('l, F j, Y @ g:i A') ?></div>
        </div>
        <div class="grid">
          <div class="panel">
            <div class="section-title">Order Summary</div>
            <?php if ($mode==='order' && !empty($items)): ?>
              <ul class="items">
                <?php $subtotal=0; foreach($items as $it): $qty=(int)($it['quantity']??$it['qty']??1); $price=(float)($it['price']??0); $line = isset($it['subtotal']) ? (float)$it['subtotal'] : ($price*$qty); $subtotal+=$line; ?>
                <li class="item">
                  <div>
                    <div><strong><?= htmlspecialchars($it['name'] ?? ('Item '.($it['product_id']??''))) ?></strong></div>
                    <div class="muted">x<?= $qty ?> @ ₱<?= number_format($price,2) ?></div>
                    <?php $opts=$it['__options']??[]; if ($opts): ?>
                    <ul class="addons">
                      <?php foreach ($opts as $op): ?>
                        <li><?= htmlspecialchars($op['name']??'') ?><?php if(isset($op['price'])): ?> (+₱<?= number_format((float)$op['price'],2) ?>)<?php endif; ?></li>
                      <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                  </div>
                  <div><strong>₱<?= number_format($line,2) ?></strong></div>
                </li>
                <?php endforeach; ?>
              </ul>
              <div class="row total"><span>Total</span><span>₱<?= number_format((float)$total,2) ?></span></div>
            <?php elseif ($mode === 'reward'): ?>
              <div style="margin-bottom:12px;">
                <div style="font-weight:600; font-size:1.1rem; margin-bottom:8px;"><?= htmlspecialchars($reward_name ?? 'Reward') ?></div>
                <div class="muted" style="font-size:0.9rem;"><?= htmlspecialchars($desc ?? 'Reward Redemption') ?></div>
              </div>
              <?php if ($code): ?>
                <div class="row" style="margin-top:12px;"><span class="muted">Voucher Code</span><span><strong><?= htmlspecialchars($code) ?></strong></span></div>
              <?php endif; ?>
            <?php else: ?>
              <div class="muted">No item breakdown available.</div>
            <?php endif; ?>
            <?php if ($mode === 'order'): ?>
              <div class="row"><span class="muted">Paid via</span><span><strong><?= htmlspecialchars($method ?? '') ?></strong></span></div>
              <?php if ($paid !== null): ?><div class="row"><span class="muted">Amount Given</span><span>₱<?= number_format($paid,2) ?></span></div><?php endif; ?>
              <?php if ($change !== null): ?><div class="row"><span class="muted">Change</span><span><strong>₱<?= number_format($change,2) ?></strong></span></div><?php endif; ?>
            <?php else: ?>
              <div class="row"><span class="muted">Payment Method</span><span><strong><?= htmlspecialchars($method === 'points' ? 'Points (Free)' : ($method ?? 'N/A')) ?></strong></span></div>
            <?php endif; ?>
          </div>
          <div class="panel qr">
            <div class="section-title">Verification QR</div>
            <?php if ($code): ?>
              <div id="qr-container" style="display:inline-block; padding:10px; background:#fff; border:1px solid var(--line); border-radius:12px; box-shadow:0 6px 18px rgba(16,24,40,.08);"></div>
              <div class="muted" style="margin-top:6px;">Scan to verify • <strong><?= htmlspecialchars($code) ?></strong></div>
              <div class="actions">
                <a class="btn primary" href="index.php">Done</a>
              </div>
            <?php else: ?>
              <div class="muted">No QR available.</div>
              <div class="actions">
                <a class="btn primary" href="index.php">Done</a>
              </div>
            <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php if ($code): ?>
  <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.1/build/qrcode.min.js"></script>
  <script>
    (function(){
      try {
        var qrContent = <?= json_encode($code) ?>;
        var el = document.getElementById('qr-container');
        if (el && window.QRCode){
          var canvas = document.createElement('canvas');
          el.appendChild(canvas);
          QRCode.toCanvas(canvas, qrContent, { 
            width: 200, 
            margin: 2,
            errorCorrectionLevel: 'H'
          });
        }
      } catch(e) { 
        console.error('QR Error:', e); 
        var el = document.getElementById('qr-container');
        if (el) {
          el.innerHTML = '<div class="muted">QR code generation failed</div>';
        }
      }
    })();
  </script>
  <?php endif; ?>
</body>
</html>
