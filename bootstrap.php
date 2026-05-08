<?php
// Global auto-prepend to apply site header/navbar/footer to most pages
// Skips JSON/API and auth endpoints that render their own full HTML
$script = $_SERVER['SCRIPT_NAME'] ?? '';
$accept = $_SERVER['HTTP_ACCEPT'] ?? '';

$skip = false;
if (php_sapi_name() === 'cli') { $skip = true; }
// Skip API and POS AJAX endpoints
if (preg_match('#/(api|pos)/#i', $script)) { $skip = true; }
// Skip auth/payment standalone pages that already have full HTML
if (preg_match('#/(login|register|forgot_password|reset_password|admin/login|payment_gateway|payment_success)\.php$#i', $script)) { $skip = true; }
// Skip if client expects JSON
if (stripos($accept, 'application/json') !== false) { $skip = true; }

if (!$skip) {
  // Include shared header/navbar and defer footer until shutdown
  require __DIR__ . '/partials/header.php';
  register_shutdown_function(function(){
    // Footer may rely on constants/connection from header/config
    @include __DIR__ . '/partials/footer.php';
  });
}
