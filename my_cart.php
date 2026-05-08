<?php
require __DIR__.'/config.php';
require_login();
// Simple wrappers to keep routes consistent
header('Location: '.APP_URL.'cart.php');
exit;