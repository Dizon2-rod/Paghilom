<?php include dirname(__DIR__).'/includes/header.php';
$db = db();
$id = (int)(safe('id') ?: 0);

if(!$id){
  header('Location: index.php');
  exit;
}

// Check if reward exists
$reward = null;
if($db){
  $stmt = $db->prepare("SELECT * FROM rewards WHERE id=? LIMIT 1");
  $stmt->bind_param('i', $id);
  $stmt->execute();
  $res = $stmt->get_result();
  $reward = $res ? $res->fetch_assoc() : null;
  $stmt->close();
}

if($reward){
  // Delete reward (soft delete by setting is_active=0, or hard delete)
  // For safety, we'll just set it to inactive instead of deleting
  $stmt = $db->prepare("UPDATE rewards SET is_active=0 WHERE id=?");
  $stmt->bind_param('i', $id);
  $stmt->execute();
  $stmt->close();
  
  // Or if you want to hard delete:
  // $stmt = $db->prepare("DELETE FROM rewards WHERE id=?");
  // $stmt->bind_param('i', $id);
  // $stmt->execute();
  // $stmt->close();
  
  header('Location: index.php?msg='.urlencode('Reward deleted.'));
} else {
  header('Location: index.php?err='.urlencode('Reward not found.'));
}
exit;
?>

