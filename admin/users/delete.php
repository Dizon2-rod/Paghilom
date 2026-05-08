<?php
require_once dirname(__DIR__).'/includes/header.php';
$db=db();
$id=(int)($_GET['id']??0);
if($id>0){
  $stmt=$db->prepare("DELETE FROM users WHERE id=? AND role<>'owner'");
  $stmt->bind_param('i',$id);
  $stmt->execute();
}
header('Location: index.php');
exit;

