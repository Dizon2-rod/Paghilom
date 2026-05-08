<?php
// Backward-compatibility shim: redirect old owner_dashboard to the new Owner Dashboard
require_once __DIR__.'/includes/bootstrap.php';

// Ensure no output before headers, then redirect
$target = APP_URL . 'admin/index.php';
header('Location: ' . $target);
// Fallback for non-compliant clients
echo '<!doctype html><meta http-equiv="refresh" content="0;url='.e($target).'">';
exit;


