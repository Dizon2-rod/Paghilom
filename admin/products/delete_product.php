<?php
require_once dirname(__DIR__).'/includes/header.php';
$db=db(); $id=(int)($_GET['id'] ?? 0);
if($id>0){
  // Soft delete: set is_active=0 or hard delete? We'll hard delete but sales history remains due to FK set null
  $db->query("DELETE FROM products WHERE id=".$id);
  echo '<div class="alert">Product deleted (if existed). <a class="btn" href="index.php">Back to products</a></div>';
} else {
  echo '<div class="alert warn">Invalid product id.</div>';
}
include dirname(__DIR__).'/includes/footer.php';


