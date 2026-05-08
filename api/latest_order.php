
<?php require __DIR__.'/../config.php'; header('Content-Type: application/json');
$phone = $_GET['phone'] ?? ($_COOKIE['client_phone'] ?? '');
$resp = ["ok"=>true, "order"=>null];
if($phone){
  $stmt=$mysqli->prepare("SELECT code,status,total_amount,created_at FROM orders WHERE phone=? ORDER BY id DESC LIMIT 1");
  $stmt->bind_param('s',$phone); $stmt->execute(); $o=$stmt->get_result()->fetch_assoc();
  if($o){ $resp["order"]=$o; }
}
echo json_encode($resp);
