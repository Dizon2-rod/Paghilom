<?php include dirname(__DIR__).'/includes/header.php';
$db = db();
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$where = '1=1';
$params = [];$types='';
if ($from){ $where .= ' AND DATE(created_at)>=?'; $types.='s'; $params[]=$from; }
if ($to){ $where .= ' AND DATE(created_at)<=?'; $types.='s'; $params[]=$to; }
$rows=[];
if($db){
  $sql="SELECT id, code, customer_name, phone, status, payment_status, total_amount, created_at FROM orders WHERE $where ORDER BY id DESC LIMIT 500";
  if($types){ $stmt=$db->prepare($sql); $stmt->bind_param($types, ...$params); $stmt->execute(); $res=$stmt->get_result(); }
  else { $res=$db->query($sql); }
  while($res && ($r=$res->fetch_assoc())) $rows[]=$r;
}
?>
<div class="topbar"><div class="title">Manage Orders</div></div>
<div class="card"><div class="card-header">All Orders</div><div class="card-body">
  <form method="get" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;margin-bottom:12px;">
    <div style="flex:0 0 auto;min-width:150px;"><label class="label">From</label><input class="input" type="date" name="from" value="<?= e($from) ?>" style="width:100%;"></div>
    <div style="flex:0 0 auto;min-width:150px;"><label class="label">To</label><input class="input" type="date" name="to" value="<?= e($to) ?>" style="width:100%;"></div>
    <div style="flex:0 0 auto;margin-left:10px;"><button class="btn primary" type="submit" style="white-space:nowrap;"><i class="bi bi-filter"></i> Filter</button></div>
  </form>
  <div class="table-responsive-sm">
    <table class="table">
      <thead><tr>
        <th>ID</th><th>Code</th><th>Customer</th><th>Phone</th><th>Status</th><th>Payment</th><th>Amount</th><th>Created</th>
      </tr></thead>
      <tbody>
        <?php foreach($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= e($r['code']) ?></td>
          <td><?= e($r['customer_name'] ?: '—') ?></td>
          <td><?= e($r['phone'] ?: '—') ?></td>
          <td><?= e($r['status']) ?></td>
          <td><?= e($r['payment_status']) ?></td>
          <td><?= money($r['total_amount']) ?></td>
          <td><?= e($r['created_at']) ?></td>
        </tr>
        <?php endforeach; if(!$rows): ?>
          <tr><td colspan="8" class="small">No orders found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div></div>
<?php include dirname(__DIR__).'/includes/footer.php'; ?>


