<?php
require_once __DIR__.'/../config/auth.php';
require_login();
if (!is_admin()) { header('Location: '.APP_URL.(is_owner()? 'owner/owner_dashboard.php' : 'index.php')); exit; }
require_once __DIR__.'/../includes/db_helper.php';

// Prevent caching of dashboard - forces fresh check on back button
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
include __DIR__.'/../partials/header.php';

// KPIs (operations-focused)
$today = date('Y-m-d');
$kpi = [
  'sales_today' => 0,
  'active_orders' => 0,
  'low_stock' => 0,
  'total_products' => 0,
];

if ($row = db_query_single($mysqli, "SELECT SUM(total_amount) AS t FROM orders WHERE DATE(created_at)=? AND payment_status='paid'", 's', [$today])) {
  $kpi['sales_today'] = (float)($row['t'] ?? 0);
}
if ($row = db_query_single($mysqli, "SELECT COUNT(*) AS c FROM orders WHERE status IN ('pending','queued','in_progress','ready')")) {
  $kpi['active_orders'] = (int)($row['c'] ?? 0);
}
if ($row = db_query_single($mysqli, "SELECT COUNT(*) AS c FROM products WHERE stock_qty <= low_stock_threshold")) {
  $kpi['low_stock'] = (int)($row['c'] ?? 0);
}
if ($row = db_query_single($mysqli, "SELECT COUNT(*) AS c FROM products WHERE is_active=1")) {
  $kpi['total_products'] = (int)($row['c'] ?? 0);
}
?>

<link rel="stylesheet" href="<?= APP_URL ?>admin/assets/css/admin.css">

<!-- Hero Header -->
<div class="admin-hero" style="background: linear-gradient(135deg, #2A5618 0%, #1e3d10 100%); color: white; padding: 3rem 0; margin-bottom: 2rem; box-shadow: 0 4px 20px rgba(42, 86, 24, 0.15);">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-8">
        <h1 class="display-5 fw-bold mb-2">Admin Dashboard</h1>
        <p class="lead mb-0 opacity-90">Manage operations and monitor business performance</p>
      </div>
      <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
        <div class="text-white opacity-75 small">Welcome back, <strong><?= e($_SESSION['user']['name'] ?? 'Admin') ?></strong></div>
        <div class="text-white opacity-75 small"><?= date('l, F j, Y') ?></div>
      </div>
    </div>
  </div>
</div>

<section class="container pb-5">
  <!-- KPI Cards -->
  <div class="row g-4 mb-5">
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="kpi-card" style="background: linear-gradient(135deg, #ffffff 0%, #f8fffe 100%); border-left: 4px solid #2A5618;">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div class="kpi-icon" style="background: rgba(42, 86, 24, 0.1); color: #2A5618; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
            <i class="bi bi-cash-coin" style="font-size: 1.5rem;"></i>
          </div>
          <span class="badge bg-success bg-opacity-10 text-success">Today</span>
        </div>
        <div class="text-muted small mb-1">Sales Today</div>
        <div class="kpi-value" style="font-size: 1.75rem; font-weight: 700; color: #0f172a;">₱<?= number_format($kpi['sales_today'],2) ?></div>
      </div>
    </div>
    
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="kpi-card" style="background: linear-gradient(135deg, #ffffff 0%, #fffef8 100%); border-left: 4px solid #f59e0b;">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div class="kpi-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
            <i class="bi bi-hourglass-split" style="font-size: 1.5rem;"></i>
          </div>
          <span class="badge bg-warning bg-opacity-10 text-warning">Active</span>
        </div>
        <div class="text-muted small mb-1">Active Orders</div>
        <div class="kpi-value" style="font-size: 1.75rem; font-weight: 700; color: #0f172a;"><?= (int)$kpi['active_orders'] ?></div>
      </div>
    </div>
    
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="kpi-card" style="background: linear-gradient(135deg, #ffffff 0%, #fef8f8 100%); border-left: 4px solid #dc2626;">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div class="kpi-icon" style="background: rgba(220, 38, 38, 0.1); color: #dc2626; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
            <i class="bi bi-exclamation-triangle" style="font-size: 1.5rem;"></i>
          </div>
          <span class="badge bg-danger bg-opacity-10 text-danger">Alert</span>
        </div>
        <div class="text-muted small mb-1">Low Stock Items</div>
        <div class="kpi-value" style="font-size: 1.75rem; font-weight: 700; color: #0f172a;"><?= (int)$kpi['low_stock'] ?></div>
      </div>
    </div>
    
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="kpi-card" style="background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%); border-left: 4px solid #3b82f6;">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div class="kpi-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
            <i class="bi bi-box-seam" style="font-size: 1.5rem;"></i>
          </div>
          <span class="badge bg-primary bg-opacity-10 text-primary">Total</span>
        </div>
        <div class="text-muted small mb-1">Active Products</div>
        <div class="kpi-value" style="font-size: 1.75rem; font-weight: 700; color: #0f172a;"><?= (int)$kpi['total_products'] ?></div>
      </div>
    </div>
  </div>

  <!-- Quick Actions -->
  <h5 class="fw-bold mb-3" style="color: #0f172a;">Quick Actions</h5>
  <div class="row g-4 mb-5">
    <div class="col-12 col-md-6 col-xl-4">
      <a class="action-tile" href="<?= APP_URL ?>admin/pos/">
        <div class="action-icon" style="background: linear-gradient(135deg, #2A5618 0%, #1e3d10 100%);">
          <i class="bi bi-cash-stack"></i>
        </div>
        <div class="flex-grow-1">
          <div class="fw-bold mb-1">Point of Sale</div>
          <div class="small text-muted">Open cashier-side POS terminal</div>
        </div>
        <i class="bi bi-chevron-right text-muted"></i>
      </a>
    </div>
    
    <div class="col-12 col-md-6 col-xl-4">
      <a class="action-tile" href="<?= APP_URL ?>admin/kiosk/">
        <div class="action-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
          <i class="bi bi-tablet"></i>
        </div>
        <div class="flex-grow-1">
          <div class="fw-bold mb-1">Kiosk Monitoring</div>
          <div class="small text-muted">Manage self-service kiosk</div>
        </div>
        <i class="bi bi-chevron-right text-muted"></i>
      </a>
    </div>
    
    <div class="col-12 col-md-6 col-xl-4">
      <a class="action-tile" href="<?= APP_URL ?>admin/cashiering/">
        <div class="action-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
          <i class="bi bi-receipt-cutoff"></i>
        </div>
        <div class="flex-grow-1">
          <div class="fw-bold mb-1">Cashiering</div>
          <div class="small text-muted">Payments, receipts, shift reports</div>
        </div>
        <i class="bi bi-chevron-right text-muted"></i>
      </a>
    </div>
  </div>
</section>

<script>
// Immediate back button prevention
(function(){
    history.pushState(null, null, location.href);
    window.onpopstate = function() { history.go(1); };
})();
</script>

<?php include __DIR__.'/../partials/footer.php'; ?>
