<?php /* Owner (Admin) sidebar */ ?>
<aside class="sidebar">
  <div class="brand">
    <span class="logo-wrap"><img src="<?= e(APP_URL) ?>assets/img/logo.png" alt="Paghilom Logo"></span>
    <div>
      <div class="name">Paghilom Admin</div>
      <div class="small">Manage your café</div>
    </div>
  </div>
  <nav>
    <a class="nav-link<?= (strpos($_SERVER['REQUEST_URI'],'/admin/index.php')!==false?' active':'') ?>" href="<?= e(APP_URL) ?>admin/index.php"><i class="bi bi-speedometer2 icon"></i>Dashboard</a>
    <a class="nav-link<?= (strpos($_SERVER['REQUEST_URI'],'/admin/orders')!==false?' active':'') ?>" href="<?= e(APP_URL) ?>admin/orders/index.php"><i class="bi bi-card-checklist icon"></i>Manage Orders</a>
    <a class="nav-link<?= (strpos($_SERVER['REQUEST_URI'],'/admin/sales_reports')!==false?' active':'') ?>" href="<?= e(APP_URL) ?>admin/sales_reports/index.php"><i class="bi bi-bar-chart-line icon"></i>Sales &amp; Reports</a>
    <a class="nav-link<?= (strpos($_SERVER['REQUEST_URI'],'/admin/products')!==false?' active':'') ?>" href="<?= e(APP_URL) ?>admin/products/index.php"><i class="bi bi-cup-hot icon"></i>Products</a>
    <a class="nav-link<?= (strpos($_SERVER['REQUEST_URI'],'/admin/rewards')!==false?' active':'') ?>" href="<?= e(APP_URL) ?>admin/rewards/index.php"><i class="bi bi-gift icon"></i>Rewards</a>
    <a class="nav-link<?= (strpos($_SERVER['REQUEST_URI'],'/admin/gallery')!==false?' active':'') ?>" href="<?= e(APP_URL) ?>admin/gallery/index.php"><i class="bi bi-images icon"></i>Gallery</a>
    <a class="nav-link<?= (strpos($_SERVER['REQUEST_URI'],'/admin/users')!==false?' active':'') ?>" href="<?= e(APP_URL) ?>admin/users/index.php"><i class="bi bi-people-fill icon"></i>Users</a>
    <a class="nav-link<?= (strpos($_SERVER['REQUEST_URI'],'/admin/settings')!==false?' active':'') ?>" href="<?= e(APP_URL) ?>admin/settings/index.php"><i class="bi bi-gear icon"></i>Settings</a>
  </nav>
  <div class="sidebar-footer">
    <a class="btn danger" href="<?= e(APP_URL) ?>admin/logout.php" onclick="return confirm('Log out from owner session?');">
      <i class="bi bi-box-arrow-right"></i> Logout
    </a>
  </div>
</aside>


