
<?php
require __DIR__.'/../config.php';
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
$code = $_GET['code'] ?? '';
if(!$code){ echo "event: error\n"; echo "data: missing code\n\n"; flush(); exit; }
$start = time();
while(true){
  $stmt=$mysqli->prepare("SELECT status FROM orders WHERE code=? LIMIT 1");
  $stmt->bind_param('s',$code); $stmt->execute(); $o=$stmt->get_result()->fetch_assoc();
  $status = $o['status'] ?? '';
  echo "event: status\n";
  echo "data: ".json_encode(['status'=>$status])."\n\n";
  @ob_flush(); @flush();
  if(connection_aborted()) break;
  if($status==='paid' || $status==='fulfilled') break;
  if(time() - $start > 55) break; // keep short to avoid long-running php
  sleep(3);
}
