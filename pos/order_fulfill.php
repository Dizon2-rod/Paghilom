
<?php require __DIR__.'/../config.php'; require_pos();
$msg=''; $err=''; $ord=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
  csrf_check();
  $code=trim($_POST['code'] ?? '');
  if($code){
    $stmt=$mysqli->prepare("SELECT * FROM orders WHERE code=? LIMIT 1");
    $stmt->bind_param('s',$code); $stmt->execute(); $ord=$stmt->get_result()->fetch_assoc();
    if(!$ord){ $err='Order not found.'; }
    else {
      if(isset($_POST['fulfill_now'])){
        if($ord['status']!=='paid'){ $err='Order not paid yet (status: '.$ord['status'].').'; }
        else {
          $stmt=$mysqli->prepare("UPDATE orders SET status='fulfilled', fulfilled_at=NOW() WHERE id=? AND status='paid'");
          $stmt->bind_param('i',$ord['id']); $stmt->execute();
          if($mysqli->affected_rows>0){ $msg='Order marked as fulfilled. Enjoy!'; $ord['status']='fulfilled'; $ord['fulfilled_at']=date('Y-m-d H:i:s'); }
          else { $err='Fulfill failed. Try again.'; }
        }
      }
    }
  }
}
?>
<!doctype html><html><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>POS — Fulfill Order</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
  .pos-mobile .form-control, .pos-mobile .btn { font-size: 1.15rem; padding: 0.9rem 1rem; border-radius: 1rem; }
  .pos-mobile #reader { max-width: 100% !important; }
  @media (max-width: 576px){ .pos-mobile h3 { font-size: 1.35rem; } }
</style>

</head><body class="bg-light pos-mobile">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>POS — Scan Order QR</h3>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary btn-sm" href="../admin/">Admin</a>
      <a class="btn btn-outline-secondary btn-sm" href="redeem.php">Voucher Redeem</a>
    </div>
  </div>
  <?php if($msg): ?><div class="alert alert-success rounded-4"><?= e($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert alert-danger rounded-4"><?= e($err) ?></div><?php endif; ?>

  <form method="post" class="row g-3 mb-4">
    <?= csrf_field() ?>
    <div class="col-md-6">
      <label class="form-label">Order Code</label>
      <input name="code" class="form-control" placeholder="Scan or enter order code" value="<?= e($_POST['code'] ?? $_GET['code'] ?? '') ?>" required>
    </div>
    <div class="col-md-6 align-self-end">
      <button class="btn btn-dark">Lookup</button>
      <?php if($ord && $ord['status']==='paid'): ?>
      <button class="btn btn-success ms-2" name="fulfill_now" value="1">Mark Fulfilled</button>
      <?php endif; ?>
    </div>
  </form>

  <div class="card rounded-4 shadow-sm mb-4">
    <div class="card-body">
      <h5 class="fw-bold mb-2">Scan QR with Camera</h5>
      <div id="reader" style="max-width:420px"></div>
      <small class="text-muted d-block mt-2">Allow camera permission, then point at the customer's Order QR.</small>
    </div>
  </div>
  <script src="https://unpkg.com/html5-qrcode"></script>
  <script>
    
  const playBeep = () => {
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const o = ctx.createOscillator(); const g = ctx.createGain();
      o.type='sine'; o.frequency.value=880; o.connect(g); g.connect(ctx.destination);
      g.gain.setValueAtTime(0.001, ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.1, ctx.currentTime + 0.01);
      o.start(); setTimeout(()=>{ g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.05); o.stop(ctx.currentTime + 0.06); }, 60);
    } catch(e) {}
  };

    const onScanSuccess = (decodedText) => {
      const input = document.querySelector('input[name="code"]');
      if (input) { input.value = decodedText; playBeep(); if (navigator.vibrate) navigator.vibrate(50); }
    };
    const scanner = new Html5QrcodeScanner('reader', { fps: 10, qrbox: 200 });
    scanner.render(onScanSuccess);
  </script>

  <?php if($ord): ?>
  <div class="card rounded-4 shadow-sm">
    <div class="card-body">
      <h5 class="fw-bold mb-3">Order Details</h5>
      <div class="row g-3">
        <div class="col-md-3"><div class="text-muted small">Code</div><div class="fw-semibold"><?= e($ord['code']) ?></div></div>
        <div class="col-md-3"><div class="text-muted small">Customer</div><div class="fw-semibold"><?= e($ord['customer_name']) ?></div></div>
        <div class="col-md-3"><div class="text-muted small">Phone</div><div class="fw-semibold"><?= e($ord['phone']) ?></div></div>
        <div class="col-md-3"><div class="text-muted small">Status</div><div class="fw-semibold"><?= e($ord['status']) ?></div></div>
      </div>
      <div class="row g-3 mt-2">
        <div class="col-md-3"><div class="text-muted small">Total</div><div class="fw-semibold">₱<?= number_format($ord['total_amount'],2) ?></div></div>
        <div class="col-md-3"><div class="text-muted small">Created</div><div class="fw-semibold"><?= e($ord['created_at']) ?></div></div>
        <div class="col-md-3"><div class="text-muted small">Fulfilled</div><div class="fw-semibold"><?= e($ord['fulfilled_at']) ?></div></div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
</body></html>
