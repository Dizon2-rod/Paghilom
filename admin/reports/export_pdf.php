<?php
// Printable Reports & Analytics summary (use browser "Save as PDF")
include __DIR__.'/../includes/header.php';

$db = db();
$sumSales = 0; $orders = 0; $top = [];
$labels7 = []; $data7 = [];
$labelsM = []; $dataM = [];

if ($db) {
  // Total sales (all time)
  if ($r = $db->query("SELECT COALESCE(SUM(total_amount),0) s FROM orders WHERE payment_status='paid'")) {
    $sumSales = (float)($r->fetch_assoc()['s'] ?? 0);
  }
  // Total orders
  if ($r = $db->query("SELECT COUNT(*) c FROM orders")) {
    $orders = (int)($r->fetch_assoc()['c'] ?? 0);
  }
  // Top 5 products
  $r = $db->query("SELECT p.name, SUM(oi.qty) qty
                    FROM order_items oi
                    LEFT JOIN products p ON p.id = oi.product_id
                    GROUP BY p.name
                    ORDER BY qty DESC
                    LIMIT 5");
  while ($r && ($row = $r->fetch_assoc())) {
    $top[] = $row;
  }
  // Last 7 days
  $res = $db->query("SELECT DATE(created_at) d, SUM(total_amount) s
                     FROM orders
                     WHERE payment_status='paid'
                       AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                     GROUP BY DATE(created_at)
                     ORDER BY d");
  $map = [];
  while ($res && ($x = $res->fetch_assoc())) {
    $map[$x['d']] = (float)$x['s'];
  }
  for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $labels7[] = date('D', strtotime($d));
    $data7[] = $map[$d] ?? 0;
  }
  // Monthly this year
  $mm = [1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0,8=>0,9=>0,10=>0,11=>0,12=>0];
  $res = $db->query("SELECT MONTH(created_at) m, SUM(total_amount) s
                     FROM orders
                     WHERE payment_status='paid'
                       AND YEAR(created_at)=YEAR(CURDATE())
                     GROUP BY MONTH(created_at)");
  while ($res && ($x = $res->fetch_assoc())) {
    $mm[(int)$x['m']] = (float)$x['s'];
  }
  $labelsM = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  $dataM = array_values($mm);
}
?>
<div class="card">
  <div class="card-header">Reports &amp; Analytics (Printable)</div>
  <div class="card-body">
    <p class="small">Generated: <strong><?= e(date('Y-m-d H:i')) ?></strong></p>

    <h3 class="h5">Summary</h3>
    <table class="table" style="max-width:400px;">
      <tbody>
        <tr>
          <th scope="row">Total Sales</th>
          <td><?= money($sumSales) ?></td>
        </tr>
        <tr>
          <th scope="row">Total Orders</th>
          <td><?= (int)$orders ?></td>
        </tr>
      </tbody>
    </table>

    <h3 class="h5" style="margin-top:16px;">Sales (Last 7 Days)</h3>
    <table class="table" style="max-width:500px;">
      <thead>
        <tr>
          <th>Day</th>
          <th class="text-end">Sales</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($labels7 as $idx => $lbl): ?>
          <tr>
            <td><?= e($lbl) ?></td>
            <td class="text-end"><?= money($data7[$idx] ?? 0) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <h3 class="h5" style="margin-top:16px;">Monthly Revenue (This Year)</h3>
    <table class="table" style="max-width:500px;">
      <thead>
        <tr>
          <th>Month</th>
          <th class="text-end">Revenue</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($labelsM as $idx => $lbl): ?>
          <tr>
            <td><?= e($lbl) ?></td>
            <td class="text-end"><?= money($dataM[$idx] ?? 0) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <h3 class="h5" style="margin-top:16px;">Top 5 Products</h3>
    <?php if (!$top): ?>
      <div class="small">No product data available.</div>
    <?php else: ?>
      <table class="table" style="max-width:500px;">
        <thead>
          <tr>
            <th>Product</th>
            <th class="text-end">Qty Sold</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($top as $row): ?>
            <tr>
              <td><?= e($row['name']) ?></td>
              <td class="text-end"><?= (int)$row['qty'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <button class="btn" onclick="window.print()"><i class="bi bi-printer"></i> Print / Save as PDF</button>
  </div>
</div>
<script>
  // Auto-open print dialog to streamline PDF export
  window.addEventListener('load', function(){ window.print(); });
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>


