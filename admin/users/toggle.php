<?php
require_once dirname(__DIR__).'/includes/header.php';
$db=db();
$id=(int)($_GET['id']??0); $active=(int)($_GET['active']??0);
if($id>0){
  $stmt=$db->prepare("UPDATE users SET is_active=? WHERE id=? AND role<>'owner'");
  $stmt->bind_param('ii',$active,$id);
  $stmt->execute();
}
header('Location: index.php');
exit;

