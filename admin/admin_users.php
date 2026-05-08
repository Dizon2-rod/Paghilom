<?php
require __DIR__.'/../config/auth.php';
require_owner();
header('Location: '.APP_URL.'staff/users.php');
exit;

