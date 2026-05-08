<?php
// Legacy reports entrypoint  forward to unified Sales & Reports module
require_once dirname(__DIR__) . '/config.php';
if (function_exists('require_admin')) {
    require_admin();
}
header('Location: ' . APP_URL . 'admin/sales_reports/index.php');
exit;
