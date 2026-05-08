<?php
require_once __DIR__.'/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

function points_balance(mysqli $db, int $user_id): int {
  $stmt=$db->prepare('SELECT IFNULL(SUM(points),0) FROM point_transactions WHERE user_id=?');
  $stmt->bind_param('i',$user_id); $stmt->execute(); $sum=(int)$stmt->get_result()->fetch_row()[0]; $stmt->close(); return $sum;
}

function active_points_multiplier(mysqli $db): float {
  // Simple fixed multiplier - can be enhanced later by admin
  // For now, always return 1.0 (no multiplier)
  return 1.0;
}

function award_points_for_order(mysqli $db, int $order_id, int $user_id){
  // Get total from order table directly
  $stmt = $db->prepare('SELECT total_amount FROM orders WHERE id=?');
  $stmt->bind_param('i', $order_id);
  $stmt->execute();
  $result = $stmt->get_result()->fetch_assoc();
  $total = $result ? (float)$result['total_amount'] : 0.0;
  $stmt->close();
  
  // Calculate points: ₱10 = 5 points (₱2 = 1 point)
  $points = (int)floor($total / 2);
  
  // Apply multiplier if any (for future promos)
  $points = (int)floor($points * active_points_multiplier($db));
  
  if($points > 0){
    $ins = $db->prepare('INSERT INTO point_transactions(user_id,points,type,ref_type,ref_id,note) VALUES(?,?,"earn","order",?,?)');
    $note = "Earned from Order #$order_id (₱" . number_format($total, 2) . ")"; 
    $ins->bind_param('iiis', $user_id, $points, $order_id, $note); 
    $ins->execute(); 
    $ins->close();
  }
  return $points;
}

function generate_voucher_code(): string {
  // Generate unique voucher code: PHC-XXXXXX (PHC = Paghilom Cafe)
  return 'PHC-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
}

function create_redemption(mysqli $db, int $user_id, int $reward_id){
  $bal = points_balance($db, $user_id);
  $rw = null;
  
  // Try rewards table first, then reward_catalog
  $r = $db->prepare('SELECT id,name,required_points as points_cost FROM rewards WHERE id=? AND is_active=1');
  if ($r) {
    $r->bind_param('i',$reward_id); 
    $r->execute(); 
    $rw=$r->get_result()->fetch_assoc(); 
    $r->close();
  }
  
  // If not found in rewards, try reward_catalog
  if (!$rw) {
    $r = $db->prepare('SELECT id,name,points_cost FROM reward_catalog WHERE id=? AND is_active=1');
    if ($r) {
      $r->bind_param('i',$reward_id); 
      $r->execute(); 
      $rw=$r->get_result()->fetch_assoc(); 
      $r->close();
    }
  }
  
  if(!$rw) return ['ok'=>false,'msg'=>'Reward not available.'];
  $cost=(int)($rw['points_cost'] ?? 0);
  if($bal < $cost) return ['ok'=>false,'msg'=>'Not enough points.'];
  
  // Generate unique voucher code
  $voucher_code = generate_voucher_code();
  
  $db->begin_transaction();
  try{
    // Insert into redemptions table with status "pending" - will be approved when staff processes it
    $red=$db->prepare('INSERT INTO redemptions(user_id,reward_id,points_spent,status,voucher_code) VALUES(?,?,?,"pending",?)');
    if (!$red) {
      throw new Exception('Failed to prepare redemptions insert: ' . $db->error);
    }
    $red->bind_param('iiis',$user_id,$reward_id,$cost,$voucher_code); 
    if (!$red->execute()) {
      throw new Exception('Failed to insert redemption: ' . $red->error);
    }
    $red_id=$db->insert_id; 
    $red->close();
    
    // Also create voucher in vouchers table (exact structure: id, code, client_id, reward_id, points_cost, status, expires_at, created_at, claimed_at)
    $voucher_id = null;
    $tablesCheck = $db->query("SHOW TABLES LIKE 'vouchers'");
    if ($tablesCheck && $tablesCheck->num_rows > 0) {
      $expires_at = date('Y-m-d H:i:s', strtotime('+30 days')); // 30 days validity
      
      // Use exact structure: code, client_id, reward_id, points_cost, status, expires_at, created_at
      // id is auto-increment, claimed_at is NULL initially
      // Status is "pending" - will be updated to "approved" when staff processes it
      $voucher_insert = $db->prepare('INSERT INTO vouchers(code, client_id, reward_id, points_cost, status, expires_at, created_at) VALUES(?, ?, ?, ?, "pending", ?, NOW())');
      
      if ($voucher_insert) {
        $voucher_insert->bind_param('siiis', $voucher_code, $user_id, $reward_id, $cost, $expires_at);
        if ($voucher_insert->execute()) {
          $voucher_id = $db->insert_id;
          error_log("SUCCESS: Voucher created in vouchers table - ID=$voucher_id, Code=$voucher_code, Client=$user_id, Reward=$reward_id, Cost=$cost");
        } else {
          error_log("ERROR: Failed to execute voucher insert - " . $voucher_insert->error);
        }
        $voucher_insert->close();
      } else {
        error_log("ERROR: Failed to prepare voucher insert - " . $db->error);
      }
    } else {
      error_log("WARNING: Vouchers table does not exist");
    }
    
    // Deduct points
    $pt=$db->prepare('INSERT INTO point_transactions(user_id,points,type,ref_type,ref_id,note) VALUES(?,?,"redeem","redemption",?,?)');
    $neg_pts = -$cost; 
    $note="Redeemed: ".$rw['name']; 
    $pt->bind_param('iiis',$user_id,$neg_pts,$red_id,$note); 
    $pt->execute(); 
    $pt->close();
    
    $db->commit();
    
    // Log warning if voucher was not created
    if (!$voucher_id) {
      error_log("WARNING: Redemption created (ID=$red_id) but voucher was NOT created in vouchers table. Code=$voucher_code, User=$user_id, Reward=$reward_id");
    }
    
    return ['ok'=>true,'redemption_id'=>$red_id,'voucher_id'=>$voucher_id,'voucher_code'=>$voucher_code,'msg'=>'Redemption successful!'];
  }catch(Exception $e){ 
    $db->rollback(); 
    error_log("Error creating redemption: " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
    return ['ok'=>false,'msg'=>'Error creating redemption: ' . $e->getMessage()]; 
  }
}
?>