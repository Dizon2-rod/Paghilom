<?php
require_once __DIR__ . '/includes/db_bootstrap.php';

$conn = kiosk_db_connect();
$mode = kiosk_safe_param('mode', 'GET');
$id = kiosk_safe_param('id', 'GET');
$method = kiosk_safe_param('method', 'GET');

$table = $mode === 'order' ? 'orders' : 'reward_redemptions';
$idCol = $mode === 'order' ? 'order_id' : 'reward_id';
$idCol = first_existing_column($conn, $table, ['id', $idCol]) ?: 'id';

$record = null;
if ($mode && $id && table_exists($conn, $table)) {
    $stmt = $conn->prepare("SELECT * FROM `{$table}` WHERE `{$idCol}` = ? LIMIT 1");
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $record = $res ? $res->fetch_assoc() : null;
    $stmt->close();
}

$total = 0.0;
$desc = $mode === 'order' ? 'Order Payment' : 'Reward Redemption';
if ($mode === 'order') {
    if ($record) {
        if (isset($record['total_amount'])) $total = (float)$record['total_amount'];
        elseif (isset($record['amount'])) $total = (float)$record['amount'];
    }
} else if ($mode === 'reward') {
    if ($record) {
        if (isset($record['amount_due'])) $total = (float)$record['amount_due'];
        elseif (isset($record['price_due'])) $total = (float)$record['price_due'];
        if (isset($record['reward_name'])) $desc = 'Redeem: ' . $record['reward_name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Kiosk | Success</title>
  <link rel="icon" type="image/png" href="../assets/images/logo.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
  <style>
    body { min-height:100vh; display:flex; align-items:center; justify-content:center; }
    main { max-width: 700px; width:100%; padding: 1rem; text-align:center; }
    .receipt { background:#fff; border:1px dashed #aaa; padding:1rem; border-radius:8px; margin-top:12px; text-align:left; }
  </style>
</head>
<body>
  <main class="container">
    <h2>Payment Successful</h2>
    <p>Reference: <?php echo htmlspecialchars(($mode ?? 'n/a') . '#' . ($id ?? '')); ?></p>
    <article class="receipt">
      <p><strong><?php echo htmlspecialchars($desc); ?></strong></p>
      <p>Paid via: <?php echo htmlspecialchars($method ?? ''); ?></p>
      <p>Total: ₱<?php echo number_format((float)$total, 2); ?></p>
    </article>
    <div class="grid" style="margin-top:16px;">
      <button onclick="window.print()">Print Receipt</button>
      <a href="index.php" role="button" class="secondary">Done</a>
    </div>
  </main>
</body>
</html>
