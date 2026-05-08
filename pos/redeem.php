
<?php require __DIR__.'/../config.php'; require_pos();
$uid = (int)($_SESSION['user']['id'] ?? 0);
$role = $_SESSION['user']['role'] ?? 'staff';
$msg=''; $err=''; $voucher=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
  csrf_check();
  $code=trim($_POST['code'] ?? '');
  if($code){
    $stmt=$mysqli->prepare("SELECT v.*, c.name cname, c.phone, r.name rname FROM vouchers v
                            LEFT JOIN clients c ON c.id=v.client_id
                            LEFT JOIN rewards r ON r.id=v.reward_id
                            WHERE v.code=? LIMIT 1");
    $stmt->bind_param('s',$code); $stmt->execute(); $voucher=$stmt->get_result()->fetch_assoc();
    if(!$voucher){ $err='Voucher not found.'; }
    else {
      if(isset($_POST['claim_now'])){
        if($voucher['status']!=='issued'){ $err='Voucher cannot be claimed (status: '.$voucher['status'].')'; }
        else {
          // Enforce 30-minute claim window
          $created = strtotime($voucher['created_at']);
          $expires_at = $voucher['expires_at'] ? strtotime($voucher['expires_at']) : ($created + 30*60);
          $now = time();
          if($now > $expires_at){
            // Mark expired if not yet
            $up=$mysqli->prepare("UPDATE vouchers SET status='expired', expires_at = COALESCE(expires_at, DATE_ADD(created_at, INTERVAL 30 MINUTE)) WHERE id=? AND status='issued'");
            $up->bind_param('i',$voucher['id']); $up->execute(); $up->close();
            $err='Voucher expired (valid for 30 minutes from issuance).';
          } else {
            $stmt=$mysqli->prepare("UPDATE vouchers SET status='claimed', claimed_at=NOW() WHERE id=? AND status='issued'");
            $stmt->bind_param('i',$voucher['id']); $stmt->execute();
            if($mysqli->affected_rows>0){ $msg='Voucher claimed successfully.'; }
            else { $err='Claim failed. Try again.'; }
          }
        }
      }
    }
  }
}
?>
<!doctype html><html><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>POS Redeem - Paghilom</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
  .pos-mobile .form-control, .pos-mobile .btn { font-size: 1.15rem; padding: 0.9rem 1rem; border-radius: 1rem; }
  .pos-mobile #reader { max-width: 100% !important; }
  @media (max-width: 576px){ .pos-mobile h3 { font-size: 1.35rem; } }
</style>

</head><body class="bg-light pos-mobile">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>POS — Redeem Voucher</h3>
    <div><a class="btn btn-outline-secondary btn-sm" href="../admin/">Admin</a></div>
  </div>
  <?php if($msg): ?><div class="alert alert-success rounded-4"><?= e($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert alert-danger rounded-4"><?= e($err) ?></div><?php endif; ?>

  <form method="post" class="row g-3 mb-4">
    <?= csrf_field() ?>
    <div class="col-md-6">
      <label class="form-label">Voucher Code</label>
      <input name="code" class="form-control" placeholder="Enter code or scan barcode/QR input" value="<?= e($_POST['code'] ?? $_GET['code'] ?? '') ?>" required>
    </div>
    <div class="col-md-6 align-self-end">
      <button class="btn btn-dark">Lookup</button>
      <?php if($voucher && $voucher['status']==='issued'): ?>
        <button class="btn btn-success ms-2" name="claim_now" value="1">Claim Now</button>
      <?php endif; ?>
    </div>
  </form>

<div class="card rounded-4 shadow-sm mb-4">
  <div class="card-body">
    <h5 class="fw-bold mb-2">Scan QR with Camera (optional)</h5>
    <div id="reader" style="max-width:420px"></div>
    <small class="text-muted d-block mt-2">Allow camera permission, then point at the customer's voucher QR.</small>
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
  const html5QrcodeScanner = new Html5QrcodeScanner('reader', { fps: 10, qrbox: 200 });
  html5QrcodeScanner.render(onScanSuccess);
</script>


  <?php if($voucher): ?>
  <div class="card rounded-4 shadow-sm">
    <div class="card-body">
      <h5 class="fw-bold mb-3">Voucher Details</h5>
      <div class="row g-3">
        <div class="col-md-3"><div class="text-muted small">Code</div><div class="fw-semibold"><?= e($voucher['code']) ?></div></div>
        <div class="col-md-3"><div class="text-muted small">Reward</div><div class="fw-semibold"><?= e($voucher['rname']) ?></div></div>
        <div class="col-md-3"><div class="text-muted small">Client</div><div class="fw-semibold"><?= e(($voucher['cname']?:$voucher['phone'])) ?></div></div>
        <div class="col-md-3"><div class="text-muted small">Status</div><div class="fw-semibold text-<?= $voucher['status']==='issued'?'warning':($voucher['status']==='claimed'?'success':'secondary') ?>"><?= e($voucher['status']) ?></div></div>
      </div>
      <div class="row g-3 mt-2">
        <div class="col-md-3"><div class="text-muted small">Created</div><div class="fw-semibold"><?= e($voucher['created_at']) ?></div></div>
        <div class="col-md-3"><div class="text-muted small">Expires</div><div class="fw-semibold"><?= e($voucher['expires_at'] ?: date('Y-m-d H:i:s', strtotime($voucher['created_at'].' +30 minutes'))) ?></div></div>
        <div class="col-md-3"><div class="text-muted small">Claimed</div><div class="fw-semibold"><?= e($voucher['claimed_at']) ?></div></div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
</body></html>
