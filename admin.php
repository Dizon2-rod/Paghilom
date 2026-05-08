<?php
// Redirect to dedicated Admin Operations dashboard within this app
require_once __DIR__ . '/config.php';
header('Location: ' . APP_URL . 'admin/index.php');
exit;
