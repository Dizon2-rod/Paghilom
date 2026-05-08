<?php require __DIR__.'/../config.php'; require_pos(); ?>
<!doctype html><html><head><meta charset="utf-8"><title>Kitchen Board</title>
<meta http-equiv="refresh" content="10">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{background:#111;color:#eee} .card{background:#1b1b1b;border:1px solid #333} .badge{font-size:.9rem}</style></head><body>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4">Kitchen Board</h1>
    <div class="text-muted small">Auto-refresh every 10s</div>
  </div>
  <div class="row g-3">
    <?php $stmt=$mysqli->prepare('SELECT * FROM orders WHERE status IN ("queued","in_progress","ready") ORDER BY id DESC LIMIT 24'); $stmt->execute(); $orders=$stmt->get_result();
    while($o=$orders->fetch_assoc()): ?>
    <div class="col-12 col-md-6 col-lg-4">
      <div class="card p-3 h-100">
        <div class="d-flex justify-content-between align-items-center">
          <div><strong>#<?=$o['id']?></strong> — <?=htmlspecialchars($o['customer_name']?:'Guest')?></div>
          <span class="badge bg-secondary"><?=$o['status']?></span>
        </div>
        <div class="mt-2">
          <?php $it=$mysqli->prepare('SELECT oi.*, p.name FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE order_id=?'); $it->bind_param('i',$o['id']); $it->execute(); $items=$it->get_result();
            while($row=$items->fetch_assoc()): ?>
            <div class="mb-1"><strong><?=$row['qty']?> x <?=htmlspecialchars($row['name'])?></strong></div>
            <?php $oa=$mysqli->prepare('SELECT oa.*, a.name FROM order_addons oa JOIN addons a ON a.id=oa.addon_id WHERE order_item_id=?'); $oa->bind_param('i',$row['id']); $oa->execute(); $ads=$oa->get_result(); while($ad=$ads->fetch_assoc()): ?>
              <div class="ms-3 small">+ <?=$ad['qty']?> x <?=htmlspecialchars($ad['name'])?></div>
            <?php endwhile; $oa->close(); ?>
          <?php endwhile; $it->close(); ?>
        </div>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
</div>
</body></html>