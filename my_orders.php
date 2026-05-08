<?php
require __DIR__.'/config.php';
require_login();
header('Location: '.APP_URL.'user/orders.php');
include __DIR__.'/partials/footer.php';
exit;