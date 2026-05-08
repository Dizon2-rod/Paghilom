
<?php
require __DIR__.'/../config.php';
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

function send($data){ echo "data: ".json_encode($data)."\n\n"; @ob_flush(); @flush(); }

$since_id = 0;
$start = time();

function get_snapshot($mysqli){
  $today = date('Y-m-d');
  $list = [];
  $q = $mysqli->query("SELECT code, customer_name, total_amount FROM orders WHERE status='paid' AND DATE(created_at)='$today' ORDER BY id DESC LIMIT 12");
  while($r=$q->fetch_assoc()){
    $list[] = ["code"=>$r['code'], "customer"=>$r['customer_name'], "total"=>$r['total_amount']];
  }
  $sum = ["total_orders"=>0,"paid"=>0,"fulfilled"=>0,"revenue"=>0];
  $t = $mysqli->query("SELECT COUNT(*) c FROM orders WHERE DATE(created_at)='$today'")->fetch_assoc(); $sum['total_orders']=(int)$t['c'];
  $p = $mysqli->query("SELECT COUNT(*) c, COALESCE(SUM(total_amount),0) s FROM orders WHERE status='paid' AND DATE(created_at)='$today'")->fetch_assoc();
  $sum['paid']=(int)$p['c']; $sum['revenue']=(float)$p['s'];
  $f = $mysqli->query("SELECT COUNT(*) c FROM orders WHERE status='fulfilled' AND DATE(created_at)='$today'")->fetch_assoc(); $sum['fulfilled']=(int)$f['c'];
  return [$list,$sum];
}

list($list,$sum)=get_snapshot($mysqli);
send(["type"=>"snapshot","list"=>$list,"summary"=>$sum]);

while(true){
  $q=$mysqli->query("SELECT id, code, customer_name, total_amount FROM orders WHERE status='paid' ORDER BY id DESC LIMIT 1");
  $r=$q->fetch_assoc();
  $latest_id = $r ? (int)$r['id'] : 0;
  if($latest_id > $since_id){
    $since_id = $latest_id;
    list($list,$sum)=get_snapshot($mysqli);
    send(["type"=>"new_paid","latest"=>["code"=>$r['code']??''], "list"=>$list, "summary"=>$sum]);
  }
  if (connection_aborted()) break;
  if (time() - $start > 55) break;
  sleep(3);
}
