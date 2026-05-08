<?php
require __DIR__.'/../config/auth.php';
require_owner();
// Simple placeholder if no dedicated security page exists
include __DIR__.'/../partials/header.php';
?>
<section class="container py-5">
  <h1 class="h4 mb-3">Owner Security & Maintenance</h1>
  <p class="text-muted">Backups, restore, and security logs. Hook your backup scripts here.</p>
  <ul>
    <li><a href="../scripts/backups/login_backup.php">Backup: Login Page</a></li>
  </ul>
</section>
<?php include __DIR__.'/../partials/footer.php'; ?>


