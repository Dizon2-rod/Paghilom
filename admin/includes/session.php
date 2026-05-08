<?php
require_once __DIR__.'/db_connect.php';
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (!function_exists('is_owner')) { function is_owner(){ return isset($_SESSION['user']['role']) && $_SESSION['user']['role']==='owner'; } }
if (!function_exists('require_owner')){ function require_owner(){ if(!is_owner()){ header('Location: '.APP_URL.'login.php'); exit; } } }
?>


