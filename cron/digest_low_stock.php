
<?php
require __DIR__.'/../config.php';
$secret = $_GET['secret'] ?? '';
$cfg = $mysqli->query("SELECT `value` v FROM settings WHERE `key`='cron_secret'")->fetch_assoc();
if(!$cfg || $secret==='' || $secret!==$cfg['v']){ http_response_code(403); echo 'forbidden'; exit; }
$to = $mysqli->query("SELECT `value` v FROM settings WHERE `key`='alert_email'")->fetch_assoc()['v'] ?? 'admin@paghilom.local';
$list = [];
$q = $mysqli->query("SELECT p.name,c.name cat,p.stock_qty,COALESCE(p.low_stock_threshold,c.low_stock_threshold,5) thr FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.stock_qty<=COALESCE(p.low_stock_threshold,c.low_stock_threshold,5) ORDER BY c.name,p.name");
while($r=$q->fetch_assoc()){ $list[] = ($r['cat']? ('['.$r['cat'].'] ') : '').$r['name'].' — stock '.$r['stock_qty'].' ≤ thr '.$r['thr']; }
$body = "Low stock items (".count($list)."):\n".implode("\n",$list);
@mail($to, "DAILY LOW STOCK DIGEST (".count($list).")", $body, "From: no-reply@paghilom.local\r\n");
echo "ok";
