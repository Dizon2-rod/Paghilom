<?php
require __DIR__.'/../config.php';
require_login();
$uid = (int)($_SESSION['user']['id'] ?? 0);
$role = $_SESSION['user']['role'] ?? ($mysqli->query("SELECT role FROM users WHERE id=$uid")->fetch_assoc()['role'] ?? '');
if ($role !== 'owner') {
  http_response_code(403);
  exit('Owner access required.');
}
