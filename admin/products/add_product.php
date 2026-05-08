<?php include dirname(__DIR__).'/includes/header.php';
$db = db();
$msg=null; $err=null;

if($_SERVER['REQUEST_METHOD']==='POST'){
  $name = safe('name','POST');
  $price = (float)safe('price','POST',0);
  $cat = (int)safe('category_id','POST',0);
  $active = (int)(safe('is_active','POST',1)?1:0);
  $feat = (int)(safe('is_featured','POST',0)?1:0);
  $desc = safe('description','POST');
  if($name && $price>0){
$stmt=$db->prepare("INSERT INTO products (name,description,price,category_id,is_active,is_featured,created_at,updated_at) VALUES (?,?,?,?,?,?,NOW(),NOW())");
    $stmt->bind_param('ssdiii',$name,$desc,$price,$cat,$active,$feat);
    if($stmt->execute()){
      $pid = $db->insert_id;
      // handle image
      if(!empty($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name'])){
        $dir = dirname(__DIR__,2).'/uploads/products/'; if(!is_dir($dir)) @mkdir($dir,0777,true);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION) ?: 'jpg';
        $fn = time().'_'.rand(1000,9999).'.'.$ext;
        move_uploaded_file($_FILES['image']['tmp_name'],$dir.$fn);
        $db->query("INSERT INTO product_images (product_id, filename, is_cover) VALUES (".(int)$pid.", '".$db->real_escape_string($fn)."', 1)");
      }
      $msg='Product added.';
    } else { $err='Failed to add product.'; }
  } else { $err='Name and valid price are required.'; }
}

// categories
$cats=[]; $r=$db->query("SELECT id,name FROM categories WHERE is_active=1 ORDER BY sort_order,name"); while($r && ($row=$r->fetch_assoc())) $cats[]=$row;
?>
<div class="topbar"><div class="title">Add Product</div></div>
<?php if($msg): ?><div class="alert"><?= e($msg) ?></div><?php endif; ?>
<?php if($err): ?><div class="alert warn"><?= e($err) ?></div><?php endif; ?>
<div class="card"><div class="card-body">
  <form method="post" enctype="multipart/form-data" style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;">
    <div><label class="label">Product Name</label><input class="input" name="name" required></div>
    <div><label class="label">Category</label><select class="input" name="category_id"><option value="0">Uncategorized</option><?php foreach($cats as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
    <div><label class="label">Price (₱)</label><input class="input" type="number" step="0.01" min="0" name="price" required></div>
    <div><label class="label">Availability</label><select class="input" name="is_active"><option value="1">Available</option><option value="0">Not Available</option></select></div>
    <div style="grid-column: span 2;"><label class="label">Description / Recipe / BOM Notes</label><textarea class="input" name="description" rows="4"></textarea></div>
    <div><label class="label">Product Image</label><input class="input" type="file" name="image" accept="image/*"></div>
    <div><label class="label">Featured</label><label style="display:flex;gap:8px;align-items:center;"><input type="checkbox" name="is_featured" value="1"> <span>Show on homepage (Featured)</span></label></div>
    <div style="align-self:end"><button class="btn primary" type="submit"><i class="bi bi-save"></i> Save</button> <a class="btn" href="index.php">Cancel</a></div>
  </form>
</div></div>
<?php include dirname(__DIR__).'/includes/footer.php'; ?>


