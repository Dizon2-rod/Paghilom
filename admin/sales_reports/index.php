<?php
include __DIR__ . '/../includes/header.php';

$db   = db();
$from = safe('from') ?: date('Y-m-01');
$to   = safe('to')   ?: date('Y-m-d');

// Normalize date range
if (strtotime($from) === false) { $from = date('Y-m-01'); }
if (strtotime($to) === false)   { $to   = date('Y-m-d'); }

// --------------------
// SUMMARY (current range)
// --------------------
$summary = [
    'total_sales'   => 0.0,
    'orders_count'  => 0,
    'avg_order'     => 0.0,
    'customers_cnt' => 0,
];
if ($db) {
    $stmt = $db->prepare("SELECT COALESCE(SUM(total_amount),0) s,
                                 COUNT(*) c,
                                 COUNT(DISTINCT COALESCE(NULLIF(customer_name,''), CONCAT('user#',user_id))) cust
                          FROM orders
                          WHERE payment_status='paid'
                            AND DATE(created_at) BETWEEN ? AND ?");
    $stmt->bind_param('ss', $from, $to);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && ($row = $res->fetch_assoc())) {
        $summary['total_sales']  = (float)($row['s'] ?? 0);
        $summary['orders_count'] = (int)($row['c'] ?? 0);
        $summary['customers_cnt']= (int)($row['cust'] ?? 0);
        $summary['avg_order']    = $summary['orders_count'] > 0
            ? $summary['total_sales'] / $summary['orders_count']
            : 0.0;
    }
    $stmt->close();
}

// --------------------
// DAILY SALES (by day)
// --------------------
$daily = [];
if ($db) {
    $stmt = $db->prepare("SELECT DATE(created_at) d, SUM(total_amount) total
                           FROM orders
                           WHERE payment_status='paid'
                             AND DATE(created_at) BETWEEN ? AND ?
                           GROUP BY DATE(created_at)
                           ORDER BY d DESC");
    $stmt->bind_param('ss', $from, $to);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($res && ($r = $res->fetch_assoc())) {
        $daily[] = $r;
    }
    $stmt->close();
}

// --------------------
// WEEKLY SALES (by ISO week)
// --------------------
$weekly = [];
if ($db) {
    $stmt = $db->prepare("SELECT YEAR(created_at) y, WEEK(created_at, 1) w, SUM(total_amount) total
                           FROM orders
                           WHERE payment_status='paid'
                             AND DATE(created_at) BETWEEN ? AND ?
                           GROUP BY YEAR(created_at), WEEK(created_at,1)
                           ORDER BY y DESC, w DESC");
    $stmt->bind_param('ss', $from, $to);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($res && ($r = $res->fetch_assoc())) {
        $weekly[] = $r;
    }
    $stmt->close();
}

// --------------------
// MONTHLY SALES (by month)
// --------------------
$monthly = [];
if ($db) {
    $stmt = $db->prepare("SELECT YEAR(created_at) y, MONTH(created_at) m, SUM(total_amount) total
                           FROM orders
                           WHERE payment_status='paid'
                             AND DATE(created_at) BETWEEN ? AND ?
                           GROUP BY YEAR(created_at), MONTH(created_at)
                           ORDER BY y DESC, m DESC");
    $stmt->bind_param('ss', $from, $to);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($res && ($r = $res->fetch_assoc())) {
        $monthly[] = $r;
    }
    $stmt->close();
}

// --------------------
// YEARLY SALES (by year)
// --------------------
$yearly = [];
if ($db) {
    $stmt = $db->prepare("SELECT YEAR(created_at) y, SUM(total_amount) total
                           FROM orders
                           WHERE payment_status='paid'
                             AND DATE(created_at) BETWEEN ? AND ?
                           GROUP BY YEAR(created_at)
                           ORDER BY y DESC");
    $stmt->bind_param('ss', $from, $to);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($res && ($r = $res->fetch_assoc())) {
        $yearly[] = $r;
    }
    $stmt->close();
}

// -------------------------------
// PRODUCT SALES SUMMARY (qty & ₱)
// -------------------------------
$productSummary = [];
if ($db) {
    $sql = "SELECT p.name, SUM(oi.qty) qty, SUM(oi.qty * oi.price) amount
            FROM order_items oi
            JOIN orders o   ON o.id = oi.order_id
            LEFT JOIN products p ON p.id = oi.product_id
            WHERE o.payment_status='paid'
              AND DATE(o.created_at) BETWEEN ? AND ?
            GROUP BY p.name
            ORDER BY qty DESC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('ss', $from, $to);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($res && ($r = $res->fetch_assoc())) {
        $productSummary[] = $r;
    }
    $stmt->close();
}

// -------------------
// TRANSACTION HISTORY
// -------------------
$transactions = [];
if ($db) {
    $stmt = $db->prepare("SELECT id, code, customer_name, total_amount, payment_method, created_at
                           FROM orders
                           WHERE payment_status='paid'
                             AND DATE(created_at) BETWEEN ? AND ?
                           ORDER BY created_at DESC");
    $stmt->bind_param('ss', $from, $to);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($res && ($r = $res->fetch_assoc())) {
        $transactions[] = $r;
    }
    $stmt->close();
}

// -------------------
// REDEMPTIONS SUMMARY
// -------------------
$redemptionsSummary = [
    'total_redemptions' => 0,
    'total_points_redeemed' => 0,
    'approved_count' => 0,
    'pending_count' => 0,
    'unique_users' => 0,
];
if ($db) {
    $stmt = $db->prepare("SELECT COUNT(*) as total,
                                 COALESCE(SUM(points_spent), 0) as points,
                                 SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                                 SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                                 COUNT(DISTINCT user_id) as users
                          FROM redemptions
                          WHERE DATE(created_at) BETWEEN ? AND ?");
    $stmt->bind_param('ss', $from, $to);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && ($row = $res->fetch_assoc())) {
        $redemptionsSummary['total_redemptions'] = (int)($row['total'] ?? 0);
        $redemptionsSummary['total_points_redeemed'] = (int)($row['points'] ?? 0);
        $redemptionsSummary['approved_count'] = (int)($row['approved'] ?? 0);
        $redemptionsSummary['pending_count'] = (int)($row['pending'] ?? 0);
        $redemptionsSummary['unique_users'] = (int)($row['users'] ?? 0);
    }
    $stmt->close();
}

// -------------------
// DAILY REDEMPTIONS
// -------------------
$dailyRedemptions = [];
if ($db) {
    $stmt = $db->prepare("SELECT DATE(created_at) d, COUNT(*) as count, SUM(points_spent) as points
                           FROM redemptions
                           WHERE DATE(created_at) BETWEEN ? AND ?
                           GROUP BY DATE(created_at)
                           ORDER BY d DESC");
    $stmt->bind_param('ss', $from, $to);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($res && ($r = $res->fetch_assoc())) {
        $dailyRedemptions[] = $r;
    }
    $stmt->close();
}

// -------------------
// REDEMPTIONS BY REWARD
// -------------------
$redemptionsByReward = [];
if ($db) {
    $sql = "SELECT COALESCE(rw.name, rc.name) as reward_name,
                   COUNT(*) as count,
                   SUM(r.points_spent) as total_points
            FROM redemptions r
            LEFT JOIN rewards rw ON r.reward_id = rw.id
            LEFT JOIN reward_catalog rc ON r.reward_id = rc.id
            WHERE DATE(r.created_at) BETWEEN ? AND ?
            GROUP BY COALESCE(rw.name, rc.name)
            ORDER BY count DESC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('ss', $from, $to);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($res && ($r = $res->fetch_assoc())) {
        $redemptionsByReward[] = $r;
    }
    $stmt->close();
}

// -------------------
// REDEMPTIONS BY STATUS
// -------------------
$redemptionsByStatus = [];
if ($db) {
    $stmt = $db->prepare("SELECT status, COUNT(*) as count, SUM(points_spent) as total_points
                           FROM redemptions
                           WHERE DATE(created_at) BETWEEN ? AND ?
                           GROUP BY status
                           ORDER BY count DESC");
    $stmt->bind_param('ss', $from, $to);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($res && ($r = $res->fetch_assoc())) {
        $redemptionsByStatus[] = $r;
    }
    $stmt->close();
}

// -------------------
// REDEMPTION HISTORY
// -------------------
$redemptionHistory = [];
if ($db) {
    $sql = "SELECT r.id, r.voucher_code, r.points_spent, r.status, r.created_at,
                   u.name as user_name, u.email as user_email,
                   COALESCE(rw.name, rc.name) as reward_name
            FROM redemptions r
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN rewards rw ON r.reward_id = rw.id
            LEFT JOIN reward_catalog rc ON r.reward_id = rc.id
            WHERE DATE(r.created_at) BETWEEN ? AND ?
            ORDER BY r.created_at DESC
            LIMIT 100";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('ss', $from, $to);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($res && ($r = $res->fetch_assoc())) {
        $redemptionHistory[] = $r;
    }
    $stmt->close();
}
?>

<div class="card">
  <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
    <div>
      <div class="title">Sales &amp; Reports</div>
      <div class="small text-muted">Unified view of sales performance and transaction history</div>
    </div>
  </div>
  <div class="card-body">
    <form method="get" class="mb-3" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
      <div>
        <label class="label">From</label>
        <input class="input" type="date" name="from" value="<?= e($from) ?>">
      </div>
      <div>
        <label class="label">To</label>
        <input class="input" type="date" name="to" value="<?= e($to) ?>">
      </div>
      <div>
        <button class="btn primary" type="submit"><i class="bi bi-filter"></i> Apply Range</button>
      </div>
    </form>

    <!-- Summary KPIs for selected range -->
    <div class="grid cols-4" style="margin-bottom:16px;">
      <div class="card">
        <div class="card-body stat">
          <div class="ring"><i class="bi bi-cash-coin"></i></div>
          <div class="kpi">
            <div class="label">Total Sales (Range)</div>
            <div class="value"><?= money($summary['total_sales']) ?></div>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-body stat">
          <div class="ring"><i class="bi bi-receipt"></i></div>
          <div class="kpi">
            <div class="label">Orders</div>
            <div class="value"><?= (int)$summary['orders_count'] ?></div>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-body stat">
          <div class="ring"><i class="bi bi-graph-up"></i></div>
          <div class="kpi">
            <div class="label">Average Order Value</div>
            <div class="value"><?= money($summary['avg_order']) ?></div>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-body stat">
          <div class="ring"><i class="bi bi-people"></i></div>
          <div class="kpi">
            <div class="label">Unique Customers</div>
            <div class="value"><?= (int)$summary['customers_cnt'] ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Redemptions Summary KPIs -->
    <h5 class="mt-4">Redemptions Report</h5>
    <div class="grid cols-4" style="margin-bottom:16px;">
      <div class="card">
        <div class="card-body stat">
          <div class="ring"><i class="bi bi-gift"></i></div>
          <div class="kpi">
            <div class="label">Total Redemptions</div>
            <div class="value"><?= number_format($redemptionsSummary['total_redemptions']) ?></div>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-body stat">
          <div class="ring"><i class="bi bi-star"></i></div>
          <div class="kpi">
            <div class="label">Total Points Redeemed</div>
            <div class="value"><?= number_format($redemptionsSummary['total_points_redeemed']) ?></div>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-body stat">
          <div class="ring"><i class="bi bi-check-circle"></i></div>
          <div class="kpi">
            <div class="label">Approved</div>
            <div class="value"><?= number_format($redemptionsSummary['approved_count']) ?></div>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-body stat">
          <div class="ring"><i class="bi bi-clock-history"></i></div>
          <div class="kpi">
            <div class="label">Pending</div>
            <div class="value"><?= number_format($redemptionsSummary['pending_count']) ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- High-level charts -->
    <div class="grid cols-2" style="margin-bottom:16px;">
      <div class="card">
        <div class="card-header">Daily Sales (Chart) - <?= e($from) ?> to <?= e($to) ?></div>
        <div class="card-body" style="position:relative; height:300px;"><canvas id="sr_daily_chart"></canvas></div>
      </div>
      <div class="card">
        <div class="card-header">Monthly Sales (Chart) - <?= e($from) ?> to <?= e($to) ?></div>
        <div class="card-body" style="position:relative; height:300px;"><canvas id="sr_monthly_chart"></canvas></div>
      </div>
    </div>

    <div class="grid cols-3" style="margin-bottom:16px;">
      <div class="card">
        <div class="card-header">Daily Sales</div>
        <div class="card-body small">
          <p class="small text-muted mb-2">Totals per day within the selected range.</p>
          <a class="btn btn-sm" href="export_pdf_download.php?type=daily&from=<?= e($from) ?>&to=<?= e($to) ?>" target="_blank"><i class="bi bi-download"></i> Download PDF</a>
        </div>
      </div>
      <div class="card">
        <div class="card-header">Monthly Sales</div>
        <div class="card-body small">
          <p class="small text-muted mb-2">Aggregated monthly revenue.</p>
          <a class="btn btn-sm" href="export_pdf_download.php?type=monthly&from=<?= e($from) ?>&to=<?= e($to) ?>" target="_blank"><i class="bi bi-download"></i> Download PDF</a>
        </div>
      </div>
      <div class="card">
        <div class="card-header">Product Summary</div>
        <div class="card-body small">
          <p class="small text-muted mb-2">Quantity and revenue by product.</p>
          <a class="btn btn-sm" href="export_pdf_download.php?type=products&from=<?= e($from) ?>&to=<?= e($to) ?>" target="_blank"><i class="bi bi-download"></i> Download PDF</a>
        </div>
      </div>
    </div>

    <h5 id="daily" class="mt-3">Daily Sales</h5>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Date</th>
            <th class="text-end">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($daily as $row): ?>
            <tr>
              <td><?= e($row['d']) ?></td>
              <td class="text-end"><?= money($row['total']) ?></td>
            </tr>
          <?php endforeach; if (!$daily): ?>
            <tr><td colspan="2" class="small">No sales for selected period.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <h5 id="weekly" class="mt-4">Weekly Sales
      <a class="btn btn-sm" href="export_pdf_download.php?type=weekly&from=<?= e($from) ?>&to=<?= e($to) ?>" style="margin-left:8px;" target="_blank"><i class="bi bi-download"></i> Download PDF</a>
    </h5>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Year</th>
            <th>Week</th>
            <th class="text-end">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($weekly as $row): ?>
            <tr>
              <td><?= (int)$row['y'] ?></td>
              <td>W<?= (int)$row['w'] ?></td>
              <td class="text-end"><?= money($row['total']) ?></td>
            </tr>
          <?php endforeach; if (!$weekly): ?>
            <tr><td colspan="3" class="small">No weekly aggregates for selected period.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <h5 id="monthly" class="mt-4">Monthly Sales</h5>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Year</th>
            <th>Month</th>
            <th class="text-end">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($monthly as $row): ?>
            <tr>
              <td><?= (int)$row['y'] ?></td>
              <td><?= date('F', mktime(0,0,0,(int)$row['m'],1)) ?></td>
              <td class="text-end"><?= money($row['total']) ?></td>
            </tr>
          <?php endforeach; if (!$monthly): ?>
            <tr><td colspan="3" class="small">No monthly aggregates for selected period.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <h5 id="yearly" class="mt-4">Yearly Sales
      <a class="btn btn-sm" href="export_pdf_download.php?type=yearly&from=<?= e($from) ?>&to=<?= e($to) ?>" style="margin-left:8px;" target="_blank"><i class="bi bi-download"></i> Download PDF</a>
    </h5>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Year</th>
            <th class="text-end">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($yearly as $row): ?>
            <tr>
              <td><?= (int)$row['y'] ?></td>
              <td class="text-end"><?= money($row['total']) ?></td>
            </tr>
          <?php endforeach; if (!$yearly): ?>
            <tr><td colspan="2" class="small">No yearly aggregates for selected period.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <h5 id="products" class="mt-4">Product Sales Summary</h5>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Product</th>
            <th class="text-end">Quantity Sold</th>
            <th class="text-end">Revenue</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($productSummary as $row): ?>
            <tr>
              <td><?= e($row['name']) ?></td>
              <td class="text-end"><?= (int)$row['qty'] ?></td>
              <td class="text-end"><?= money($row['amount']) ?></td>
            </tr>
          <?php endforeach; if (!$productSummary): ?>
            <tr><td colspan="3" class="small">No product sales in selected period.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
      <a class="btn btn-sm" href="export_pdf_download.php?type=products&from=<?= e($from) ?>&to=<?= e($to) ?>" target="_blank"><i class="bi bi-download"></i> Download Product Summary PDF</a>
    </div>

    <h5 id="transactions" class="mt-4">Transaction History</h5>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Code</th>
            <th>Customer</th>
            <th class="text-end">Amount</th>
            <th>Method</th>
            <th>Created</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($transactions as $r): ?>
            <tr>
              <td><?= (int)$r['id'] ?></td>
              <td><?= e($r['code']) ?></td>
              <td><?= e($r['customer_name'] ?: '—') ?></td>
              <td class="text-end"><?= money($r['total_amount']) ?></td>
              <td><?= e(strtoupper($r['payment_method'])) ?></td>
              <td><?= e($r['created_at']) ?></td>
            </tr>
          <?php endforeach; if (!$transactions): ?>
            <tr><td colspan="6" class="small">No transactions in selected period.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
      <a class="btn btn-sm" href="export_pdf_download.php?type=transactions&from=<?= e($from) ?>&to=<?= e($to) ?>" target="_blank"><i class="bi bi-download"></i> Download Transactions PDF</a>
    </div>

    <h5 id="redemptions" class="mt-4">Redemptions by Reward</h5>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Reward</th>
            <th class="text-end">Redemptions</th>
            <th class="text-end">Total Points</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($redemptionsByReward as $row): ?>
            <tr>
              <td><?= e($row['reward_name'] ?: 'Unknown Reward') ?></td>
              <td class="text-end"><?= number_format((int)$row['count']) ?></td>
              <td class="text-end"><?= number_format((int)$row['total_points']) ?> pts</td>
            </tr>
          <?php endforeach; if (!$redemptionsByReward): ?>
            <tr><td colspan="3" class="small">No redemptions in selected period.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <h5 id="redemptions-daily" class="mt-4">Daily Redemptions</h5>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Date</th>
            <th class="text-end">Count</th>
            <th class="text-end">Points Redeemed</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($dailyRedemptions as $row): ?>
            <tr>
              <td><?= e($row['d']) ?></td>
              <td class="text-end"><?= number_format((int)$row['count']) ?></td>
              <td class="text-end"><?= number_format((int)$row['points']) ?> pts</td>
            </tr>
          <?php endforeach; if (!$dailyRedemptions): ?>
            <tr><td colspan="3" class="small">No redemptions for selected period.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<?php
// Prepare data for charts - ensure all dates in range are included
$dailyLabels = [];
$dailyData   = [];

// Create a map of existing daily data
$dailyMap = [];
foreach ($daily as $row) {
    $dailyMap[$row['d']] = (float)$row['total'];
}

// Fill in all days in the date range (including days with zero sales)
$startDate = new DateTime($from);
$endDate = new DateTime($to);
$currentDate = clone $startDate;

while ($currentDate <= $endDate) {
    $dateStr = $currentDate->format('Y-m-d');
    $dailyLabels[] = $currentDate->format('M j');
    $dailyData[] = isset($dailyMap[$dateStr]) ? $dailyMap[$dateStr] : 0.0;
    $currentDate->modify('+1 day');
}

// Prepare monthly chart data - show all 12 months of the year
$monthlyLabels = [];
$monthlyData   = [];

// Create a map of existing monthly data
$monthlyMap = [];
foreach ($monthly as $row) {
    $year = (int)$row['y'];
    $month = (int)$row['m'];
    $key = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
    $monthlyMap[$key] = (float)$row['total'];
}

// Determine which year to show (use the year from the 'to' date, or current year)
$chartYear = (int)date('Y', strtotime($to));
if (!$chartYear) {
    $chartYear = (int)date('Y');
}

// Show all 12 months of the selected year
$monthNames = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
for ($m = 1; $m <= 12; $m++) {
    $monthKey = $chartYear . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
    $monthlyLabels[] = $monthNames[$m - 1];
    $monthlyData[] = isset($monthlyMap[$monthKey]) ? $monthlyMap[$monthKey] : 0.0;
}
?>
<script>
  const srDailyLabels   = <?= json_encode($dailyLabels) ?>;
  const srDailyData     = <?= json_encode($dailyData) ?>;
  const srMonthlyLabels = <?= json_encode($monthlyLabels) ?>;
  const srMonthlyData   = <?= json_encode($monthlyData) ?>;

  // Store chart instances for potential updates
  let srDailyChart = null;
  let srMonthlyChart = null;

  if (document.getElementById('sr_daily_chart')) {
    srDailyChart = new Chart(document.getElementById('sr_daily_chart'), {
      type: 'line',
      data: {
        labels: srDailyLabels,
        datasets: [{
          label: 'Daily Sales (₱)',
          data: srDailyData,
          borderColor: '#2A5618',
          backgroundColor: 'rgba(42,86,24,.25)',
          tension: 0.35,
          fill: true,
          pointRadius: 3,
          pointHoverRadius: 5
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { 
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                return '₱' + parseFloat(context.parsed.y).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
              }
            }
          }
        },
        scales: {
          x: { 
            grid: { display: false },
            ticks: {
              maxRotation: 45,
              minRotation: 0
            }
          },
          y: { 
            ticks: { 
              color: '#a8b7ad',
              callback: function(value) {
                return '₱' + value.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0});
              }
            },
            beginAtZero: true
          }
        }
      }
    });
  }

  if (document.getElementById('sr_monthly_chart')) {
    // Always use bar chart for monthly data to show clear monthly breakdown
    srMonthlyChart = new Chart(document.getElementById('sr_monthly_chart'), {
      type: 'bar',
      data: {
        labels: srMonthlyLabels,
        datasets: [{
          label: 'Monthly Sales (₱)',
          data: srMonthlyData,
          backgroundColor: 'rgba(42,86,24,0.7)',
          borderColor: '#2A5618',
          borderWidth: 1,
          borderRadius: 4,
          borderSkipped: false
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          intersect: false,
          mode: 'index'
        },
        plugins: { 
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                const value = parseFloat(context.parsed.y);
                return '₱' + value.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
              },
              title: function(context) {
                return context[0].label;
              }
            }
          }
        },
        scales: {
          x: { 
            grid: { 
              display: false,
              drawBorder: true
            },
            ticks: {
              maxRotation: 0,
              minRotation: 0,
              autoSkip: false,
              color: '#666',
              font: {
                size: 11
              }
            },
            barPercentage: 0.5,
            categoryPercentage: 0.6
          },
          y: { 
            ticks: { 
              color: '#a8b7ad',
              callback: function(value) {
                return '₱' + value.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0});
              }
            },
            beginAtZero: true,
            grid: {
              color: 'rgba(0,0,0,0.05)',
              drawBorder: true
            }
          }
        },
        layout: {
          padding: {
            left: 10,
            right: 10,
            top: 10,
            bottom: 10
          }
        }
      }
    });
  }

  // Auto-refresh charts when date range form is submitted
  const dateForm = document.querySelector('form[method="get"]');
  if (dateForm) {
    dateForm.addEventListener('submit', function() {
      // Charts will be recreated on page reload with new data
      // This ensures real-time data when date range changes
    });
  }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
