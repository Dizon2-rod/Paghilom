<?php
// Legacy inventory entrypoint removed  forward to main Owner dashboard
require_once dirname(__DIR__) . '/config.php';
if (function_exists('require_admin')) {
    require_admin();
}
header('Location: ' . APP_URL . 'admin/index.php');
exit;
