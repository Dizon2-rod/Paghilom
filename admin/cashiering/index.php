<?php
require_once __DIR__.'/../../config/auth.php';
require_login();
if (!is_admin_role()) { header('Location: '.APP_URL.(is_owner()? 'owner/owner_dashboard.php' : 'index.php')); exit; }
include __DIR__.'/../../partials/header.php';
?>
<link rel="stylesheet" href="<?= APP_URL ?>admin/assets/css/admin.css">
<section class="container py-4">
  <h3 class="mb-4">Cashiering</h3>
  <div class="list-group">
    <a class="list-group-item list-group-item-action" href="<?= APP_URL ?>pos/" target="_blank">Open POS Terminal</a>
    <a class="list-group-item list-group-item-action" href="<?= APP_URL ?>kiosk.php" target="_blank">Open Kiosk Screen</a>
  </div>
  <div class="alert alert-info mt-3">Use the POS terminal for taking payments and printing receipts. Shift reports can be generated from POS.</div>
  <a class="btn btn-secondary mt-3" href="<?= APP_URL ?>admin/dashboard.php">Back to Admin Dashboard</a>
</section>
<?php include __DIR__.'/../../partials/footer.php'; ?>
