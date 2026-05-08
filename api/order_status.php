
<?php require __DIR__.'/../config.php'; header('Content-Type: application/json');
$code = $_GET['code'] ?? '';
$out = ['ok'=>true, 'status'=>null];
if($code){
  $stmt=$mysqli->prepare("SELECT status FROM orders WHERE code=? LIMIT 1");
  $stmt->bind_param('s',$code); $stmt->execute(); $o=$stmt->get_result()->fetch_assoc();
  if($o){ $out['status']=$o['status']; }
}
echo json_encode($out);
