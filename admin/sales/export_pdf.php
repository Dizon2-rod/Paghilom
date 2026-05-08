<?php
// Printable sales report for browser "Print to PDF"
include __DIR__.'/../includes/header.php';

$db   = db();
$from = safe('from') ?: date('Y-m-01');
$to   = safe('to') ?: date('Y-m-d');

$rows = [];
if ($db) {
  $stmt = $db->prepare("SELECT id, code, customer_name, total_amount, payment_method, created_at FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND payment_status='paid' ORDER BY created_at DESC");
  $stmt->bind_param('ss', $from, $to);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($res && ($r = $res->fetch_assoc())) {
    $rows[] = $r;
  }
}
?>
<div class="card">
  <div class="card-header">Sales Report (Printable)</div>
  <div class="card-body">
    <p class="small">Period: <strong><?= e($from) ?></strong> to <strong><?= e($to) ?></strong></p>
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Code</th>
          <th>Customer</th>
          <th>Amount</th>
          <th>Method</th>
          <th>Created</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= e($r['code']) ?></td>
          <td><?= e($r['customer_name'] ?: '—') ?></td>
          <td><?= money($r['total_amount']) ?></td>
          <td><?= e(strtoupper($r['payment_method'])) ?></td>
          <td><?= e($r['created_at']) ?></td>
        </tr>
      <?php endforeach; if (!$rows): ?>
        <tr><td colspan="6" class="small">No sales in selected period.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
    <button class="btn" onclick="window.print()"><i class="bi bi-printer"></i> Print / Save as PDF</button>
  </div>
</div>
<script>
  // Auto-open print dialog for faster PDF export
  window.addEventListener('load', function(){ window.print(); });
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>


