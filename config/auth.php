<?php
// Centralized role helpers
require_once __DIR__.'/../config.php';

// Prevent caching for authenticated pages
if (!headers_sent()) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
}

if (!function_exists('current_role')) {
  function current_role(){ return strtolower($_SESSION['user']['role'] ?? ''); }
}

// Owner role has been removed; old is_owner() now simply aliases to admin.
// Primary admin home is the owner dashboard: /owner/index.php
if (!function_exists('is_owner')) {
  function is_owner(){ return is_admin_role(); }
}

// Admin role helper – only real admins, not staff/cashier
if (!function_exists('is_admin_role')) {
  function is_admin_role(){ return current_role()==='admin'; }
}

if (!function_exists('require_owner')) {
  function require_owner(){ 
    require_login(); 
    // Always send admins to the owner dashboard; others go to public home
    header('Location: '.APP_URL.(is_admin_role() ? 'owner/index.php' : 'index.php'));
    exit; 
  }
}

if (!function_exists('require_admin')) {
  function require_admin(){ 
    require_login(); 
    if(!is_admin_role()){ 
      header('Location: '.APP_URL.'index.php'); 
      exit; 
    } 
  }
}
