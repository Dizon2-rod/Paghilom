<?php
// Owner bootstrap: load global config from project root
$projectRoot = dirname(__DIR__, 2);
require_once $projectRoot . '/config.php';

if (!function_exists('e')) {
  function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

// APP_URL already provided by root config.php
if (!defined('APP_URL')) {
  // Fallback: derive from current script
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
  $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $base   = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
  define('APP_URL', $scheme.'://'.$host.$base.'/');
}

// DB handle (use $mysqli from root config.php)
function db(){ global $mysqli; return $mysqli; }

function query($sql,$types=null,$params=[]){
  $db = db(); if(!$db) return false;
  if ($types && $params) {
    $stmt = $db->prepare($sql);
    if(!$stmt) return false;
    $stmt->bind_param($types, ...$params);
    if(!$stmt->execute()) return false;
    return $stmt->get_result();
  } else {
    return $db->query($sql);
  }
}

function exec_stmt($sql,$types=null,$params=[]){
  $db = db(); if(!$db) return false;
  $stmt = $db->prepare($sql);
  if(!$stmt) return false;
  if ($types && $params) { $stmt->bind_param($types, ...$params); }
  return $stmt->execute();
}

function safe($key,$method='GET',$default=null){ $src = strtoupper($method)==='POST' ? $_POST : $_GET; return isset($src[$key]) ? (is_string($src[$key])? trim($src[$key]) : $src[$key]) : $default; }

// Simple helpers
function money($n){ return '₱'.number_format((float)$n,2); }
function today(){ return date('Y-m-d'); }
?>


