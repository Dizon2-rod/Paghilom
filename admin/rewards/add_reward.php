<?php include dirname(__DIR__).'/includes/header.php';
$db = db();
$msg=null; $err=null;

if($_SERVER['REQUEST_METHOD']==='POST'){
  $name = safe('name','POST');
  $description = safe('description','POST');
  $required_points = (int)safe('required_points','POST',0);
  $reward_type = 'free_item'; // Default type
  $value = safe('value','POST');
  $active = (int)(safe('is_active','POST',1)?1:0);
  $sort_order = (int)(safe('sort_order','POST',0));
  
  if($name && $required_points > 0){
    $stmt=$db->prepare("INSERT INTO rewards (name,description,required_points,reward_type,value,is_active,sort_order,created_at,updated_at) VALUES (?,?,?,?,?,?,?,NOW(),NOW())");
    $stmt->bind_param('ssissii',$name,$description,$required_points,$reward_type,$value,$active,$sort_order);
    if($stmt->execute()){
      $rid = $db->insert_id;
      // handle image
      if(!empty($_FILES['thumb']['name']) && is_uploaded_file($_FILES['thumb']['tmp_name'])){
        $dir = dirname(__DIR__,2).'/uploads/rewards/'; if(!is_dir($dir)) @mkdir($dir,0777,true);
        $ext = pathinfo($_FILES['thumb']['name'], PATHINFO_EXTENSION) ?: 'jpg';
        $fn = time().'_'.rand(1000,9999).'.'.$ext;
        if(move_uploaded_file($_FILES['thumb']['tmp_name'],$dir.$fn)){
          $db->query("UPDATE rewards SET thumb='".$db->real_escape_string($fn)."' WHERE id=".(int)$rid);
        }
      }
      $msg='Reward added successfully.';
      header('Location: index.php?msg='.urlencode($msg));
      exit;
    } else { $err='Failed to add reward: ' . $db->error; }
  } else { $err='Name and required points are required.'; }
}

?>
<div class="topbar"><div class="title">Add Reward</div></div>
<?php if($msg): ?><div class="alert"><?= e($msg) ?></div><?php endif; ?>
<?php if($err): ?><div class="alert warn"><?= e($err) ?></div><?php endif; ?>
<div class="card"><div class="card-body">
  <form method="post" enctype="multipart/form-data" style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;">
    <div><label class="label">Reward Name *</label><input class="input" name="name" required placeholder="e.g., Cafe Latte" style="width:100%;box-sizing:border-box;"></div>
    <div><label class="label">Points Required *</label><input class="input" type="number" min="1" name="required_points" required placeholder="e.g., 150" style="width:100%;box-sizing:border-box;"></div>
    <div><label class="label">Value</label><input class="input" name="value" placeholder="e.g., Free Drinks or ₱50" style="width:100%;box-sizing:border-box;"></div>
    <div><label class="label">Status</label><select class="input" name="is_active" style="width:100%;box-sizing:border-box;"><option value="1">Active</option><option value="0">Inactive</option></select></div>
    <div style="grid-column: span 2;"><label class="label">Description</label><textarea class="input" name="description" rows="3" placeholder="Describe the reward..." style="width:100%;box-sizing:border-box;"></textarea></div>
    <div><label class="label">Reward Image</label><input class="input" type="file" name="thumb" accept="image/*" style="width:100%;box-sizing:border-box;"></div>
    <div><label class="label">Sort Order</label><input class="input" type="number" min="0" name="sort_order" value="0" placeholder="Display order (lower = first)" style="width:100%;box-sizing:border-box;"></div>
    <div style="grid-column: span 2; align-self:end; display:flex; align-items:flex-end;"><button class="btn primary" type="submit" style="white-space:nowrap;"><i class="bi bi-save"></i> Save</button> <a class="btn" href="index.php">Cancel</a></div>
  </form>
</div></div>
<style>
  @media (max-width: 768px) {
    .card-body form {
      grid-template-columns: 1fr !important;
    }
  }
</style>
<?php include dirname(__DIR__).'/includes/footer.php'; ?>

