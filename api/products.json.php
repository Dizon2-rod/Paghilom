
<?php require __DIR__.'/../config.php'; header('Content-Type: application/json');
$res = $mysqli->query("SELECT p.id,p.name,p.description,p.price,
(SELECT filename FROM product_images WHERE product_id=p.id AND is_cover=1 ORDER BY id DESC LIMIT 1) img
FROM products p WHERE p.is_active=1 ORDER BY p.sort_order,p.id");
$out=[]; while($r=$res->fetch_assoc()){
  $r['image_url'] = $r['img'] ? (APP_URL.'uploads/products/'.$r['img']) : (APP_URL.'assets/img/placeholder.jpg');
  unset($r['img']); $out[]=$r;
}
echo json_encode(['products'=>$out, 'count'=>count($out)], JSON_PRETTY_PRINT);
