
<?php
require __DIR__.'/../config.php';
$secret = $_GET['secret'] ?? '';
$cfg = $mysqli->query("SELECT `value` v FROM settings WHERE `key`='cron_secret'")->fetch_assoc();
if(!$cfg || $secret==='' || $secret!==$cfg['v']){ http_response_code(403); echo 'forbidden'; exit; }
$to = $mysqli->query("SELECT `value` v FROM settings WHERE `key`='alert_email'")->fetch_assoc()['v'] ?? 'admin@paghilom.local';
$day = $_GET['date'] ?? date('Y-m-d');
// paid + fulfilled
$q = $mysqli->query("SELECT oi.name, SUM(oi.qty) qty FROM order_items oi INNER JOIN orders o ON o.id=oi.order_id WHERE DATE(o.created_at)='".$mysqli->real_escape_string($day)."' AND o.status IN ('paid','fulfilled') GROUP BY oi.name ORDER BY oi.name");
$list = [];
while($r=$q->fetch_assoc()){ $list[] = $r['name'].': '.$r['qty']; }
$body = "Pick-list for $day (".count($list)." items)\n".implode("\n", $list);
@mail($to, "DAILY PICK-LIST ($day)", $body, "From: no-reply@paghilom.local\r\n");
echo "ok";
