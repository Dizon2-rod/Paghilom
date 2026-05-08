<?php
require_once __DIR__ . '/../kiosk/includes/db_bootstrap.php';
session_start();

function current_user_id(): ?string {
    $keys = ['user_id','customer_id','account_id','id'];
    foreach ($keys as $k) {
        if (!empty($_SESSION[$k])) return (string)$_SESSION[$k];
    }
    return null;
}

$conn = kiosk_db_connect();

$orderId = kiosk_safe_param('id', 'GET');
if (!$orderId) {
    http_response_code(400);
    echo 'Missing order id';
    exit;
}

$userId = current_user_id();
if (!$userId) {
    http_response_code(401);
    echo 'You must be logged in.';
    exit;
}

if (!table_exists($conn, 'orders')) {
    http_response_code(500);
    echo 'Orders table not found';
    exit;
}

$orderIdCol = first_existing_column($conn, 'orders', ['id','order_id']);
$userCol = first_existing_column($conn, 'orders', ['user_id','customer_id','account_id']);
if (!$orderIdCol || !$userCol) {
    http_response_code(403);
    echo 'Access denied.';
    exit;
}

$stmt = $conn->prepare("SELECT * FROM `orders` WHERE `{$orderIdCol}` = ? AND `{$userCol}` = ? LIMIT 1");
$stmt->bind_param('ss', $orderId, $userId);
$stmt->execute();
$res = $stmt->get_result();
$order = $res ? $res->fetch_assoc() : null;
$stmt->close();

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Order Details</title>
  <link rel="icon" type="image/png" href="../assets/images/logo.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
  <style>
    :root { --brand:#2A5618; --bg:#F6FFF6; }
    body { background: var(--bg); }
    header { background: var(--brand); color:#fff; padding: 12px 16px; }
    main { max-width: 980px; margin: 0 auto; padding: 16px; }
    .grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; }
    .items ul { list-style:none; padding:0; margin:0; }
    .items li { display:flex; align-items:center; justify-content:space-between; padding:10px 0; border-bottom:1px dashed #eee; }
    .items img { width:44px; height:44px; object-fit:cover; border-radius:8px; margin-right:10px; }
    .muted { color:#64748b; }
    .back { color:#fff; text-decoration:none; }
  </style>
</head>
<body>
  <header>
    <div style="display:flex;align-items:center;justify-content:space-between;">
      <strong>Order Details</strong>
      <a class="back" href="javascript:history.back()">Back</a>
    </div>
  </header>
  <main>
    <div id="alert" style="display:none; color:#b00020; margin-bottom:10px;"></div>
    <div id="content" style="display:none;">
      <div class="grid-2">
        <section class="card">
          <h3>Order Information</h3>
          <dl>
            <dt>Order ID</dt><dd id="ov-id"></dd>
            <dt>Date & Time</dt><dd id="ov-datetime"></dd>
            <dt>Type</dt><dd id="ov-type"></dd>
            <dt>Status</dt><dd id="ov-status"></dd>
            <dt>Remarks</dt><dd id="ov-remarks" class="muted"></dd>
            <dt>Serving Staff</dt><dd id="ov-staff" class="muted"></dd>
            <dt>Reward Points</dt><dd id="ov-points" class="muted"></dd>
          </dl>
        </section>
        <section class="card">
          <h3>Payment Summary</h3>
          <p><strong>Total:</strong> ₱<span id="pay-total">0.00</span></p>
          <p><strong>Method:</strong> <span id="pay-method" class="muted">—</span></p>
          <p><strong>Status:</strong> <span id="pay-status" class="muted">—</span></p>
          <p><strong>Transaction Ref:</strong> <span id="pay-ref" class="muted">—</span></p>
        </section>
      </div>
      <section class="card items" style="margin-top:16px;">
        <h3>Items Ordered</h3>
        <ul id="items"></ul>
      </section>
    </div>
  </main>

  <script src="assets/js/order_view.js"></script>
  <script>
    (function(){
      const id = new URLSearchParams(location.search).get('id');
      loadOrderView({ id, endpoint: 'api/order_get.php' });
    })();
  </script>
</body>
</html>
