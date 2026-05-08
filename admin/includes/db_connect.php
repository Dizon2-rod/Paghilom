<?php
// Owner DB bootstrap
require_once __DIR__ . '/../../config.php';
// $mysqli from root config
if (!function_exists('db')) { function db(){ global $mysqli; return $mysqli; } }
?>


