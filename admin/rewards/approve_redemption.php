<?php include dirname(__DIR__).'/includes/header.php';
$db = db();
$id = (int)(safe('id') ?: 0);

if(!$id){
  header('Location: redemptions.php');
  exit;
}

// Get redemption
$redemption = null;
if($db){
  $stmt = $db->prepare("SELECT * FROM redemptions WHERE id=? LIMIT 1");
  $stmt->bind_param('i', $id);
  $stmt->execute();
  $res = $stmt->get_result();
  $redemption = $res ? $res->fetch_assoc() : null;
  $stmt->close();
}

if($redemption && strtolower($redemption['status'] ?? '') === 'pending'){
  // Update status to approved
  $stmt = $db->prepare("UPDATE redemptions SET status='approved', updated_at=NOW() WHERE id=?");
  $stmt->bind_param('i', $id);
  $stmt->execute();
  $stmt->close();
  
  // Also update vouchers table if it exists
  if($redemption['voucher_code']){
    $voucher_check = $db->query("SHOW TABLES LIKE 'vouchers'");
    if($voucher_check && $voucher_check->num_rows > 0){
      $voucher_code_col = 'code';
      $voucher_check_col = $db->query("SHOW COLUMNS FROM vouchers LIKE 'voucher_code'");
      if($voucher_check_col && $voucher_check_col->num_rows > 0){
        $voucher_code_col = 'voucher_code';
      }
      $voucher_stmt = $db->prepare("UPDATE vouchers SET status='approved' WHERE `{$voucher_code_col}`=?");
      if($voucher_stmt){
        $voucher_stmt->bind_param('s', $redemption['voucher_code']);
        $voucher_stmt->execute();
        $voucher_stmt->close();
      }
    }
  }
  
  header('Location: redemptions.php?msg='.urlencode('Redemption approved successfully.'));
} else {
  header('Location: redemptions.php?err='.urlencode('Redemption not found or already processed.'));
}
exit;
?>

