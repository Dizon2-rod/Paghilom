<?php require __DIR__.'/../config.php'; require_pos();
$id=(int)($_POST['id']??0); $action=$_POST['action']??''; $status=$_POST['status']??''; $payment=$_POST['payment_status']??'';

if($id){
  $mysqli->begin_transaction();
  try {
    // Get current state
    $cur=$mysqli->prepare('SELECT user_id, payment_status, status, total_amount FROM orders WHERE id=?');
    $cur->bind_param('i',$id); $cur->execute(); $curRes=$cur->get_result()->fetch_assoc(); $cur->close();
    
    if($curRes){
      // Prevent changes on paid orders unless admin
      $role = $_SESSION['user']['role'] ?? '';
      $isPriv = ($role === 'admin');
      if (($curRes['payment_status'] ?? '') === 'successful' && !$isPriv) {
        $mysqli->rollback();
        header('Location: '.($_SERVER['HTTP_REFERER']??'index.php'));
        exit;
      }
      $updates = ['updated_at=NOW()'];
      $params = [];
      $types = '';
      
      // Handle specific actions for unified flow
      if($action==='mark_paid'){
        $updates[] = 'status="paid"';
        $updates[] = 'payment_status="successful"';
        $updates[] = 'paid_at=NOW()';
        $newStatus = 'paid';
        $newPayment = 'successful';
      } elseif($action==='mark_preparing'){
        $updates[] = 'status="preparing"';
        $newStatus = 'preparing';
      } elseif($action==='mark_ready'){
        $updates[] = 'status="ready"';
        $newStatus = 'ready';
      } elseif($action==='mark_completed'){
        $updates[] = 'status="completed"';
        $updates[] = 'claimed_at=NOW()';
        $newStatus = 'completed';
      } else {
        // Legacy support: direct status/payment update
        if($status && in_array($status,['unpaid','paid','preparing','ready','completed','cancelled'])){
          $updates[] = 'status=?';
          $params[] = $status;
          $types .= 's';
          $newStatus = $status;
        }
        if($payment && in_array($payment,['pending','successful','failed','unpaid','paid','refunded'])){
          $pm = ($payment==='paid') ? 'successful' : (($payment==='unpaid') ? 'pending' : $payment);
          $updates[] = 'payment_status=?';
          $params[] = $pm;
          $types .= 's';
          $newPayment = $pm;
          if($pm==='successful' && ($curRes['payment_status']??'')!=='successful'){
            $updates[] = 'paid_at=NOW()';
          }
        }
      }
      
      // Execute update
      $sql = 'UPDATE orders SET '.implode(', ',$updates).' WHERE id=?';
      $params[] = $id;
      $types .= 'i';
      $stmt = $mysqli->prepare($sql);
      if($params) $stmt->bind_param($types, ...$params);
      $stmt->execute(); $stmt->close();
      
      // Award points logic: Only on completion now (not payment)
      $shouldAwardPoints = false;
      if(isset($newStatus) && $newStatus==='completed' && ($curRes['status']??'')!=='completed'){
        $shouldAwardPoints = true;
      } elseif(!isset($newStatus) && isset($newPayment) && $newPayment==='successful' && ($curRes['payment_status']??'')!=='successful'){
        // Legacy: still award on payment transition for backward compatibility
        $shouldAwardPoints = true;
      }
      
      if($shouldAwardPoints){
        $user_id = (int)($curRes['user_id'] ?? 0);
        $total = (float)($curRes['total_amount'] ?? 0);
        if ($user_id>0 && $total>0){
          $points = (int)floor($total/2); // ₱10 = 5 points (₱2 = 1 point)
          if ($points>0){
            // Ensure not already awarded
            $chk=$mysqli->prepare("SELECT id FROM point_transactions WHERE user_id=? AND ref_type='order' AND ref_id=? LIMIT 1");
            $chk->bind_param('ii',$user_id,$id); $chk->execute(); $exists=$chk->get_result()->fetch_assoc(); $chk->close();
            if (!$exists){
              $ins=$mysqli->prepare("INSERT INTO point_transactions (user_id, points, type, ref_type, ref_id, note, created_at) VALUES (?,?,'earn','order',?, 'Points earned from order completion', NOW())");
              $ins->bind_param('iii',$user_id,$points,$id); $ins->execute(); $ins->close();
              $upd=$mysqli->prepare('UPDATE orders SET points_awarded = COALESCE(points_awarded,0) + ? WHERE id=?');
              $upd->bind_param('ii',$points,$id); $upd->execute(); $upd->close();
            }
          }
        }
      }
    }
    
    $mysqli->commit();
  } catch (Throwable $e) {
    $mysqli->rollback();
  }
}
header('Location: '.($_SERVER['HTTP_REFERER']??'index.php')); ?>
