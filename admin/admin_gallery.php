<?php
require __DIR__.'/../config/auth.php';
require_owner();
header('Location: '.APP_URL.'staff/gallery.php');
exit;

