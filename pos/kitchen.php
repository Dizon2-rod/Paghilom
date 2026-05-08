<?php require __DIR__.'/../config.php'; require_pos();
$id=(int)($_GET['id']??0); $st=$mysqli->prepare('SELECT * FROM orders WHERE id=?'); $st->bind_param('i',$id); $st->execute(); $o=$st->get_result()->fetch_assoc(); $st->close();
$it=$mysqli->prepare('SELECT oi.*, p.name FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE order_id=?'); $it->bind_param('i',$id); $it->execute(); $items=$it->get_result(); $it->close();
?><!doctype html><html><head><meta charset="utf-8"><title>Kitchen #<?=$id?></title>
<style>body{font:16px/1.4 ui-monospace, Menlo, monospace}.ticket{width:80mm;margin:0 auto} .h{border-top:2px dashed #000;margin:.5rem 0} @media print{ .noprint{display:none}}</style></head><body>
<div class="ticket">
  <img src="../uploads/paghilom_logo.png" alt="Paghilom" style="width:42px;height:42px;border-radius:50%;object-fit:cover"><h2 style="margin:0">KITCHEN TICKET</h2>
  <div>Order #<?=$o['id']?> — <?=$o['status']?></div>
  <div>Pickup: <?=$o['pickup_time']?></div>
  <div class="h"></div>
  <?php foreach($items as $row): ?>
    <div><strong><?=$row['qty']?> x <?=htmlspecialchars($row['name'])?></strong></div>
    <?php $oa=$mysqli->prepare('SELECT oa.*, a.name FROM order_addons oa JOIN addons a ON a.id=oa.addon_id WHERE order_item_id=?'); $oa->bind_param('i',$row['id']); $oa->execute(); $ads=$oa->get_result(); while($ad=$ads->fetch_assoc()): ?>
      <div style="padding-left:1rem"> + <?=$ad['qty']?> x <?=htmlspecialchars($ad['name'])?></div>
    <?php endwhile; $oa->close(); ?>
  <?php endforeach; ?>
  <div class="h"></div>
  <div>Customer: <?=htmlspecialchars($o['customer_name']?:'Guest')?></div>
  <button class="noprint" onclick="window.print()">Print</button>
</div></body></html>