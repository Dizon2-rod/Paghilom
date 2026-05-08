<?php include dirname(__DIR__).'/includes/header.php';
$db=db(); $id=(int)($_GET['id'] ?? 0); $msg=null; $err=null;
$r=$db->query("SELECT * FROM products WHERE id=".$id); $row=$r?$r->fetch_assoc():null;
if(!$row){ echo '<div class="alert warn">Product not found.</div>'; include dirname(__DIR__).'/includes/footer.php'; exit; }

if($_SERVER['REQUEST_METHOD']==='POST'){
  $name = safe('name','POST');
  $price = (float)safe('price','POST',0);
  $cat = (int)safe('category_id','POST',0);
  $active = (int)(safe('is_active','POST',1)?1:0);
  $feat = (int)(safe('is_featured','POST',0)?1:0);
  $desc = safe('description','POST');
  if($name && $price>0){
$stmt=$db->prepare("UPDATE products SET name=?, description=?, price=?, category_id=?, is_active=?, is_featured=?, updated_at=NOW() WHERE id=?");
    $stmt->bind_param('ssdiiii',$name,$desc,$price,$cat,$active,$feat,$id);
    if($stmt->execute()){
      if(!empty($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name'])){
        $dir = dirname(__DIR__,2).'/uploads/products/'; if(!is_dir($dir)) @mkdir($dir,0777,true);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION) ?: 'jpg';
        $fn = time().'_'.rand(1000,9999).'.'.$ext;
        move_uploaded_file($_FILES['image']['tmp_name'],$dir.$fn);
        // Set current cover to 0 and add new cover
        $db->query("UPDATE product_images SET is_cover=0 WHERE product_id=".$id);
        $db->query("INSERT INTO product_images (product_id, filename, is_cover) VALUES (".$id.", '".$db->real_escape_string($fn)."', 1)");
      }
      $msg='Product updated.';
      // refresh row
      $r=$db->query("SELECT * FROM products WHERE id=".$id); $row=$r?$r->fetch_assoc():$row;
    } else { $err='Failed to update product.'; }
  } else { $err='Name and valid price are required.'; }
}

$cats=[]; $rc=$db->query("SELECT id,name FROM categories WHERE is_active=1 ORDER BY sort_order,name"); while($rc && ($c=$rc->fetch_assoc())) $cats[]=$c;
$imgRow = $db->query("SELECT filename FROM product_images WHERE product_id=".$id." ORDER BY is_cover DESC,id DESC LIMIT 1");
$img = ($imgRow && ($ir=$imgRow->fetch_assoc())) ? (APP_URL.'uploads/products/'.$ir['filename']) : (APP_URL.'assets/img/placeholder.php?w=400&h=400&text=No+Image');
?>
<div class="topbar"><div class="title">Edit Product</div></div>
<?php if($msg): ?><div class="alert"><?= e($msg) ?></div><?php endif; ?>
<?php if($err): ?><div class="alert warn"><?= e($err) ?></div><?php endif; ?>
<div class="card"><div class="card-body">
  <form method="post" enctype="multipart/form-data" style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;align-items:start;">
    <div><label class="label">Product Name</label><input class="input" name="name" value="<?= e($row['name']) ?>" required></div>
    <div><label class="label">Category</label><select class="input" name="category_id"><option value="0">Uncategorized</option><?php foreach($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= ((int)$row['category_id']===(int)$c['id'])?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
    <div><label class="label">Price (₱)</label><input class="input" type="number" step="0.01" min="0" name="price" value="<?= e($row['price']) ?>" required></div>
    <div><label class="label">Availability</label><select class="input" name="is_active"><option value="1" <?= $row['is_active']?'selected':'' ?>>Available</option><option value="0" <?= !$row['is_active']?'selected':'' ?>>Not Available</option></select></div>
    <div><label class="label">Featured</label><label style="display:flex;gap:8px;align-items:center;"><input type="checkbox" name="is_featured" value="1" <?= ((int)$row['is_featured'])?'checked':'' ?>> <span>Show on homepage (Featured)</span></label></div>
    <div style="grid-column: span 2;"><label class="label">Description / Recipe / BOM Notes</label><textarea class="input" name="description" rows="4"><?= e($row['description']) ?></textarea></div>
    <div><label class="label">Change Image</label><img src="<?= e($img) ?>" alt="cover" style="display:block;width:120px;height:120px;object-fit:cover;border-radius:8px;margin-bottom:8px;"><input class="input" type="file" name="image" accept="image/*"></div>
    <div style="align-self:end"><button class="btn primary" type="submit"><i class="bi bi-save"></i> Update</button> <a class="btn" href="index.php">Back</a> <a class="btn" href="view_recipe.php?id=<?= (int)$id ?>">Edit Recipe</a></div>
  </form>
</div></div>
<?php include dirname(__DIR__).'/includes/footer.php'; ?>


