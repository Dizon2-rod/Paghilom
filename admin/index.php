<?php include __DIR__.'/includes/header.php';
// KPIs
$db = db();
$today = date('Y-m-d');

$todaySales = 0; $totalIncome = 0; $activeOrders = 0; $topProduct = null;
if ($db) {
  // Today sales via paid orders
  $r = $db->query("SELECT COALESCE(SUM(total_amount),0) s FROM orders WHERE DATE(created_at)=CURDATE() AND payment_status='paid'");
  if($r){ $todaySales = (float)($r->fetch_assoc()['s'] ?? 0); }
  $r = $db->query("SELECT COALESCE(SUM(total_amount),0) s FROM orders WHERE payment_status='paid'");
  if($r){ $totalIncome = (float)($r->fetch_assoc()['s'] ?? 0); }
  // Active orders (not completed/cancelled)
  $r = $db->query("SELECT COUNT(*) c FROM orders WHERE status NOT IN ('completed','cancelled')");
  if($r){ $activeOrders = (int)($r->fetch_assoc()['c'] ?? 0); }
  // Top product
  $r = $db->query("SELECT oi.product_id, p.name, SUM(oi.qty) qty FROM order_items oi LEFT JOIN products p ON p.id=oi.product_id GROUP BY oi.product_id,p.name ORDER BY qty DESC LIMIT 1");
  if($r){ $row = $r->fetch_assoc(); $topProduct = $row ? ($row['name'] . ' (' . (int)$row['qty'] . ')') : null; }
}
?>
<div class="dashboard-grid">
  <div class="card kpi-card">
    <div class="card-body stat">
      <div class="ring"><i class="bi bi-cash-coin"></i></div>
      <div class="kpi">
        <div class="label">Today's Sales</div>
        <div class="value"><?= money($todaySales) ?></div>
      </div>
    </div>
  </div>
  <div class="card kpi-card">
    <div class="card-body stat">
      <div class="ring"><i class="bi bi-bank"></i></div>
      <div class="kpi">
        <div class="label">Total Income</div>
        <div class="value"><?= money($totalIncome) ?></div>
      </div>
    </div>
  </div>
  <div class="card kpi-card">
    <div class="card-body stat">
      <div class="ring"><i class="bi bi-bag-check"></i></div>
      <div class="kpi">
        <div class="label">Active Orders</div>
        <div class="value"><?= (int)$activeOrders ?></div>
      </div>
    </div>
  </div>
  <div class="card kpi-card">
    <div class="card-body stat">
      <div class="ring"><i class="bi bi-stars"></i></div>
      <div class="kpi">
        <div class="label">Top Product</div>
        <div class="value"><?= e($topProduct ?? '—') ?></div>
      </div>
    </div>
  </div>
</div>

<div class="dashboard-grid chart-grid">
  <div class="card kpi-card">
    <div class="card-header">Sales (Last 7 days)</div>
    <div class="card-body"><canvas id="chart7"></canvas></div>
  </div>
  <div class="card kpi-card">
    <div class="card-header">Monthly Revenue (This Year)</div>
    <div class="card-body"><canvas id="chartMonth"></canvas></div>
  </div>
  <div class="card kpi-card">
    <div class="card-header">Key Insights</div>
    <div class="card-body">
      <p class="small text-muted mb-2">Quick snapshot of your café performance.</p>
      <ul class="small" style="margin:0;padding-left:18px;">
        <li>Total income is based on all paid orders.</li>
        <li>Active orders are those not completed or cancelled.</li>
        <li>Top product is computed from recent order items.</li>
      </ul>
    </div>
  </div>
</div>

<div class="card quick-actions-card">
  <div class="card-header">Quick Actions</div>
  <div class="card-body quick-actions">
    <a class="btn" href="products/index.php"><i class="bi bi-pen"></i>Add/Edit Product</a>
    <a class="btn" href="sales_reports/index.php"><i class="bi bi-bar-chart-line"></i>View Sales &amp; Reports</a>
  </div>
</div>

<div class="card quick-actions-card">
  <div class="card-header">Quick Links (Admin Modules)</div>
  <div class="card-body">
    <div class="quick-links">
    <?php
      $modules = [
        ['dir'=>'orders','label'=>'Manage Orders','icon'=>'bi bi-card-checklist'],
        ['dir'=>'sales_reports','label'=>'Sales & Reports','icon'=>'bi bi-bar-chart-line'],
        ['dir'=>'pos','label'=>'POS','icon'=>'bi bi-cash'],
        ['dir'=>'products','label'=>'Products','icon'=>'bi bi-cup-hot'],
        ['dir'=>'rewards','label'=>'Rewards','icon'=>'bi bi-gift'],
        ['dir'=>'users','label'=>'Users','icon'=>'bi bi-people-fill'],
        ['dir'=>'settings','label'=>'Settings','icon'=>'bi bi-gear'],
      ];
      foreach($modules as $m):
        $path = __DIR__ . '/' . $m['dir'];
        if (is_dir($path)):
    ?>
      <a class="tile" href="<?= e(APP_URL.'admin/'.$m['dir'].'/') ?>">
        <i class="<?= e($m['icon']) ?>"></i>
        <span class="label"><?= e($m['label']) ?></span>
      </a>
    <?php endif; endforeach; ?>
    </div>
  </div>
</div>

<style>
/* Dashboard Grid */
.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
  margin-bottom: 14px;
}

/* KPI Cards */
.kpi-card {
  min-height: 100%;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.kpi-card .card-body {
  padding: 16px;
  height: 100%;
  box-sizing: border-box;
}

.stat {
  display: flex;
  align-items: center;
  gap: 12px;
  height: 100%;
}

.stat .ring {
  min-width: 46px;
  height: 46px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #eaf7ec;
  box-shadow: inset 0 0 0 2px rgba(42, 86, 24, 0.2);
  flex-shrink: 0;
}

.stat .ring i {
  font-size: 1.5rem;
  color: var(--owner-accent);
}

.stat .kpi {
  flex-grow: 1;
  overflow: hidden;
}

.stat .label {
  font-size: 0.85rem;
  color: var(--owner-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.stat .value {
  font-size: 1.35rem;
  font-weight: 800;
  color: #101828;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Chart Grid */
.chart-grid {
  grid-template-columns: repeat(3, 1fr);
  margin-bottom: 14px;
}

.chart-grid .card {
  min-height: 300px;
  display: flex;
  flex-direction: column;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.chart-grid .card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.chart-grid .card-body {
  flex: 1;
  min-height: 250px;
  padding: 10px;
  position: relative;
}

/* Quick Actions */
.quick-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.quick-actions .btn {
  flex: 1;
  min-width: 200px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 16px;
  border-radius: 8px;
  background: var(--owner-accent);
  color: white;
  text-decoration: none;
  transition: all 0.2s ease;
  border: none;
  font-weight: 500;
}

.quick-actions .btn:hover {
  background: var(--owner-accent-2);
  transform: translateY(-1px);
}

/* Quick Links */
.quick-links {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 12px;
}

.quick-links .tile {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  background: #f8f9fa;
  border-radius: 8px;
  color: var(--owner-text);
  text-decoration: none;
  transition: all 0.2s ease;
  border: 1px solid #e9ecef;
}

.quick-links .tile:hover {
  background: #fff;
  border-color: var(--owner-accent);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.quick-links .tile i {
  margin-right: 10px;
  font-size: 1.2rem;
  color: var(--owner-accent);
}

.quick-links .tile .label {
  font-weight: 500;
  font-size: 0.95rem;
}

/* Responsive Adjustments */
@media (max-width: 1200px) {
  .dashboard-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .chart-grid {
    grid-template-columns: 1fr 1fr;
  }
  
  .chart-grid .kpi-card:last-child {
    grid-column: span 2;
  }
}

@media (max-width: 768px) {
  .dashboard-grid,
  .chart-grid {
    grid-template-columns: 1fr;
  }
  
  .chart-grid .kpi-card:last-child {
    grid-column: auto;
  }
  
  .quick-links {
    grid-template-columns: 1fr 1fr;
  }
  
  .quick-actions .btn {
    min-width: 100%;
  }
}

@media (max-width: 480px) {
  .quick-links {
    grid-template-columns: 1fr;
  }
  
  .stat .ring {
    min-width: 40px;
    height: 40px;
  }
  
  .stat .ring i {
    font-size: 1.2rem;
  }
  
  .stat .value {
    font-size: 1.2rem;
  }
  
  .stat .label {
    font-size: 0.8rem;
  }
}

/* Quick Actions */
.quick-actions-card {
  margin-bottom: 14px;
}

.quick-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  padding: 16px !important;
}

.quick-actions .btn {
  margin: 0;
  white-space: nowrap;
}

/* Quick Links */
.quick-links {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 12px;
  padding: 0 !important;
}

.quick-links .tile {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  background: #fff;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  color: #333;
  text-decoration: none;
  transition: all 0.2s ease;
}

.quick-links .tile:hover {
  border-color: var(--owner-accent);
  background: #f8f9fa;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.quick-links .tile i {
  font-size: 1.25rem;
  margin-right: 10px;
  color: var(--owner-accent);
}

/* Responsive Adjustments */
@media (max-width: 1200px) {
  .dashboard-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .chart-grid {
    grid-template-columns: 1fr 1fr;
  }
  
  .chart-grid .card:last-child {
    grid-column: 1 / -1;
  }
}

@media (max-width: 768px) {
  .dashboard-grid,
  .chart-grid {
    grid-template-columns: 1fr;
  }
  
  .quick-links {
    grid-template-columns: 1fr 1fr;
  }
  
  .quick-actions {
    flex-direction: column;
  }
  
  .quick-actions .btn {
    width: 100%;
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .quick-links {
    grid-template-columns: 1fr;
  }
  
  .stat {
    flex-direction: column;
    text-align: center;
  }
  
  .stat .kpi {
    text-align: center;
  }
  
  .stat .ring {
    margin-bottom: 8px;
  }
}

/* Ensure charts are responsive */
canvas {
  max-width: 100%;
  height: auto !important;
}
</style>

<script>
// Prepare data from backend
let labels7 = []; let data7 = []; let labelsM = []; let dataM = [];
<?php
// Last 7 days sales from orders
$labels7=[];$data7=[];
if ($db){
  $res=$db->query("SELECT DATE(created_at) d, SUM(total_amount) s FROM orders WHERE payment_status='paid' AND created_at>=DATE_SUB(CURDATE(),INTERVAL 6 DAY) GROUP BY DATE(created_at) ORDER BY d");
  $map=[];while($res && ($r=$res->fetch_assoc())){$map[$r['d']] = (float)$r['s'];}
  for($i=6;$i>=0;$i--){ $d=date('Y-m-d',strtotime("-$i day")); $labels7[]=date('D',strtotime($d)); $data7[] = $map[$d] ?? 0; }
  // Monthly
  $labelsM=[];$dataM=[]; $res=$db->query("SELECT MONTH(created_at) m, SUM(total_amount) s FROM orders WHERE payment_status='paid' AND YEAR(created_at)=YEAR(CURDATE()) GROUP BY MONTH(created_at)");
  $mm=[1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0,8=>0,9=>0,10=>0,11=>0,12=>0];
  while($res && ($r=$res->fetch_assoc())){ $mm[(int)$r['m']] = (float)$r['s']; }
  echo 'labels7='.json_encode($labels7).'; data7='.json_encode($data7).';';
  echo 'labelsM='.json_encode(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']).'; dataM='.json_encode(array_values($mm)).';';
}
?>
if (document.getElementById('chart7')) {
  new Chart(document.getElementById('chart7'), {
    type: 'line',
    data: {
      labels: labels7,
      datasets: [{
        label: 'Sales',
        data: data7,
        borderColor: '#2A5618',
        backgroundColor: 'rgba(42,86,24,.25)',
        tension: 0.35,
        fill: true
      }]
    },
    options: {
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false } },
        y: { ticks: { color: '#a8b7ad' } }
      }
    }
  });
}
if (document.getElementById('chartMonth')) {
  new Chart(document.getElementById('chartMonth'), {
    type: 'bar',
    data: {
      labels: labelsM,
      datasets: [{
        label: 'Revenue',
        data: dataM,
        backgroundColor: 'rgba(42,86,24,.65)'
      }]
    },
    options: {
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false } },
        y: { ticks: { color: '#a8b7ad' } }
      }
    }
  });
}
</script>
<?php include __DIR__.'/includes/footer.php'; ?>


