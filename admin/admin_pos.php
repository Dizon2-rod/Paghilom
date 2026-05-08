<?php
require __DIR__.'/../config/auth.php';
require_owner();
header('Location: '.APP_URL.'pos/dashboard.php');
exit;

