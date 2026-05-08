<?php include __DIR__.'/../includes/header.php';
$db=db(); $act=[]; $recent=[];
if($db){
  $actRes=$db->query("SELECT id, code, status, created_at, payment_status FROM orders WHERE status IN ('pending','queued','in_progress','ready') ORDER BY id DESC LIMIT 50");
  while($actRes && ($r=$actRes->fetch_assoc())) $act[]=$r;
  $rRes=$db->query("SELECT id, code, status, created_at, payment_status FROM orders ORDER BY id DESC LIMIT 100");
  while($rRes && ($r=$rRes->fetch_assoc())) $recent[]=$r;
}
?>
<div class="topbar"><div class="title">POS & Kiosk Monitor</div></div>
<div class="card"><div class="card-header">Active Queue</div><div class="card-body">
  <?php if(!$act): ?><div class="small">No active orders.</div><?php else: ?>
  <table class="table"><thead><tr><th>ID</th><th>Code</th><th>Status</th><th>Payment</th><th>Created</th></tr></thead><tbody>
    <?php foreach($act as $r): ?>
      <tr><td><?= (int)$r['id'] ?></td><td><?= e($r['code']) ?></td><td><?= e($r['status']) ?></td><td><?= e($r['payment_status']) ?></td><td><?= e($r['created_at']) ?></td></tr>
    <?php endforeach; ?>
  </tbody></table>
  <?php endif; ?>
</div></div>
<div class="card" style="margin-top:12px;"><div class="card-header">Recent Orders</div><div class="card-body">
  <table class="table"><thead><tr><th>ID</th><th>Code</th><th>Status</th><th>Payment</th><th>Created</th></tr></thead><tbody>
    <?php foreach($recent as $r): ?>
      <tr><td><?= (int)$r['id'] ?></td><td><?= e($r['code']) ?></td><td><?= e($r['status']) ?></td><td><?= e($r['payment_status']) ?></td><td><?= e($r['created_at']) ?></td></tr>
    <?php endforeach; ?>
  </tbody></table>
</div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>


