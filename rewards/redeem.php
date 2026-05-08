<?php require __DIR__.'/../config.php'; require_login(); require_once __DIR__.'/../points.php';
$uid=$_SESSION['user']['id']; $rid=(int)($_POST['reward_id']??0);
$msg='';
$r=$mysqli->prepare('SELECT id,name,points_cost FROM reward_catalog WHERE id=? AND is_active=1'); $r->bind_param('i',$rid); $r->execute(); $rw=$r->get_result()->fetch_assoc(); $r->close();
if($rw){
  $cost=(int)$rw['points_cost']; $bal=points_balance($mysqli,$uid);
  if($bal>=$cost){
    $mysqli->begin_transaction();
    try{
      $ins=$mysqli->prepare('INSERT INTO redemptions(user_id,reward_id,points_spent,status) VALUES(?,? ,?, "pending")'); $ins->bind_param('iii',$uid,$rid,$cost); $ins->execute(); $red_id=$ins->insert_id; $ins->close();
      $pt=$mysqli->prepare('INSERT INTO point_transactions(user_id,points,type,ref_type,ref_id,note) VALUES(?,-?,"redeem","redemption",?,?)');
      $note='Redeem '.$rw['name']; $pt->bind_param('iiis',$uid,$cost,$red_id,$note); $pt->execute(); $pt->close();
      $mysqli->commit(); $msg='Redemption created. Show this at counter: #'.$red_id;
    }catch(Exception $e){ $mysqli->rollback(); $msg='Error creating redemption.'; }
  } else { $msg='Not enough points.'; }
} else { $msg='Reward not available.'; }
header('Location: dashboard.php'); exit; ?>