<?php
// Legacy Reports & Analytics page  forward to unified Sales & Reports module
require_once __DIR__ . '/../includes/bootstrap.php';
if (function_exists('require_admin')) {
    require_admin();
}
$target = APP_URL . 'admin/sales_reports/index.php';
header('Location: ' . $target);
// Fallback meta refresh for older browsers
echo '<!doctype html><meta http-equiv="refresh" content="0;url=' . e($target) . '">';
exit;
