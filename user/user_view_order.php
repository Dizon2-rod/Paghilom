<?php
require __DIR__.'/../config.php';
require_login();

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id <= 0) { header('Location: orders.php'); exit; }

$uid = (int)($_SESSION['user']['id'] ?? 0);
if(!$uid){ header('Location: ../login.php'); exit; }

// Fetch order for this user
$st = $mysqli->prepare('SELECT * FROM orders WHERE id=? AND user_id=? LIMIT 1');
$st->bind_param('ii', $order_id, $uid); $st->execute(); $order = $st->get_result()->fetch_assoc(); $st->close();
if(!$order){
  include __DIR__.'/../partials/header.php';
  echo '<section class="container py-5"><div class="alert alert-danger">Order not found or you do not have permission to view it.</div><a href="orders.php" class="btn btn-primary">Back to My Orders</a></section>';
  include __DIR__.'/../partials/footer.php';
  exit;
}

// Items
$items = [];
$st = $mysqli->prepare('SELECT name, qty, price, subtotal FROM order_items WHERE order_id=?');
$st->bind_param('i', $order_id); $st->execute(); $res = $st->get_result();
while($r = $res->fetch_assoc()){ $items[] = $r; }
$st->close();

function peso($n){ $n = (float)$n; return number_format($n,2); }
$status = $order['status'] ?? ($order['order_status'] ?? 'pending');
$status_color = ($status==='completed'||$status==='paid')?'success':(($status==='cancelled')?'danger':(($status==='ready'||$status==='queued')?'info':'secondary'));
// Normalize payment status display
$ps = strtolower($order['payment_status'] ?? '');
$payment_display = '';
$payment_color = 'warning';
if (in_array($ps, ['paid','successful'])) {
  $payment_display = 'PAID';
  $payment_color = 'success';
} elseif (in_array($ps, ['failed'])) {
  $payment_display = 'FAILED';
  $payment_color = 'danger';
} else {
  $payment_display = 'UNPAID';
  $payment_color = 'warning';
}

include __DIR__.'/../partials/header.php';
?>

<section class="section-header py-4">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <h1 class="h3 title mb-1">Order Details</h1>
        <p class="subtitle mb-0">Code: <code>#<?= e($order['code'] ?? $order['id']) ?></code></p>
      </div>
      <div>
        <a href="orders.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left"></i> Back to My Orders</a>
      </div>
    </div>
  </div>
</section>

<section class="container py-4">
  <div class="card shadow-soft border-0 mb-3">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <div><strong>Date:</strong> <?= e(date('M d, Y g:i A', strtotime($order['created_at'] ?? 'now'))) ?></div>
          <div><strong>Status:</strong> <span class="badge bg-<?= e($status_color) ?>"><?= e(ucfirst($status)) ?></span></div>
        </div>
        <div class="col-md-6 text-md-end">
          <div><strong>Payment Method:</strong> <?= e($order['payment_method'] ?? 'cash') ?></div>
          <div><strong>Payment Status:</strong> <span class="badge bg-<?= e($payment_color) ?>"><?= e($payment_display) ?></span></div>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-soft border-0 mb-3">
    <div class="card-header bg-white"><h5 class="mb-0">Items</h5></div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead><tr><th>Item</th><th class="text-center" style="width:120px">Qty</th><th class="text-end" style="width:140px">Price</th><th class="text-end" style="width:160px">Subtotal</th></tr></thead>
          <tbody>
            <?php foreach($items as $it): ?>
            <tr>
              <td><?= e($it['name']) ?></td>
              <td class="text-center"><?= (int)$it['qty'] ?></td>
              <td class="text-end">₱<?= peso($it['price']) ?></td>
              <td class="text-end">₱<?= peso($it['subtotal']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="card shadow-soft border-0 mb-3">
    <div class="card-body d-flex justify-content-end">
      <div style="min-width:320px">
        <table class="table table-sm mb-0">
          <?php if ((float)($order['discount_amount'] ?? 0) > 0): ?>
          <tr>
            <td>Subtotal:</td>
            <td class="text-end">₱<?= peso(($order['total_amount'] ?? 0) + ($order['discount_amount'] ?? 0)) ?></td>
          </tr>
          <tr>
            <td>Discount:</td>
            <td class="text-end text-danger">-₱<?= peso($order['discount_amount'] ?? 0) ?></td>
          </tr>
          <?php endif; ?>
          <tr class="fw-bold">
            <td>Total:</td>
            <td class="text-end">₱<?= peso($order['total_amount'] ?? 0) ?></td>
          </tr>
        </table>
      </div>
    </div>
  </div>

  <?php if (!empty($order['notes'])): ?>
  <div class="card shadow-soft border-0 mb-3">
    <div class="card-header bg-white"><h5 class="mb-0">Notes</h5></div>
    <div class="card-body">
      <p class="mb-0 text-muted"><?= nl2br(e($order['notes'])) ?></p>
    </div>
  </div>
  <?php endif; ?>

  <div class="d-flex gap-2">
    <a class="btn btn-outline-primary" href="orders.php"><i class="bi bi-list"></i> Back to My Orders</a>
    <?php if (!empty($order['code'])): ?>
      <a class="btn btn-primary" href="../payment_success.php?order=<?= urlencode($order['code']) ?>" target="_blank"><i class="bi bi-receipt"></i> View Receipt</a>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__.'/../partials/footer.php'; ?>
