<?php include dirname(__DIR__).'/includes/header.php';
$db = db(); $msg=null; $err=null;

// Handle upload (multiple)
if($_SERVER['REQUEST_METHOD']==='POST' && !empty($_FILES['photos'])){
  $dir = dirname(__DIR__,2).'/uploads/gallery/'; if(!is_dir($dir)) @mkdir($dir,0777,true);
  $count = is_array($_FILES['photos']['name']) ? count($_FILES['photos']['name']) : 0;
  $added=0; $failed=0; $caption = safe('caption','POST');
  for($i=0;$i<$count;$i++){
    if(!empty($_FILES['photos']['tmp_name'][$i]) && is_uploaded_file($_FILES['photos']['tmp_name'][$i])){
      $name = $_FILES['photos']['name'][$i];
      $ext = pathinfo($name, PATHINFO_EXTENSION) ?: 'jpg';
      $fn = time().'_'.rand(1000,9999).'.'.$ext;
      if(move_uploaded_file($_FILES['photos']['tmp_name'][$i],$dir.$fn)){
        $stmt = $db->prepare("INSERT INTO gallery (filename, caption, sort_order, is_active, created_at) VALUES (?,?,0,1,NOW())");
        $stmt->bind_param('ss',$fn,$caption);
        $stmt->execute();
        $added++;
      } else { $failed++; }
    }
  }
  if($added>0) $msg = "$added photo(s) uploaded" . ($failed? ", $failed failed":"");
  elseif($failed>0) $err='Upload failed for all files.';
}

// Handle delete
if(isset($_GET['delete'])){
  $id=(int)$_GET['delete'];
  $row = $db->query("SELECT filename FROM gallery WHERE id=".$id)->fetch_assoc();
  if($row){
    $db->query("DELETE FROM gallery WHERE id=".$id);
    @unlink(dirname(__DIR__,2).'/uploads/gallery/'.$row['filename']);
    $msg='Photo deleted.';
  }
}

$items=[]; $r=$db->query("SELECT * FROM gallery ORDER BY sort_order,id DESC"); while($r && ($x=$r->fetch_assoc())) $items[]=$x;
?>
<div class="topbar"><div class="title">Gallery</div></div>
<?php if($msg): ?><div class="alert"><?= e($msg) ?></div><?php endif; ?>
<?php if($err): ?><div class="alert warn"><?= e($err) ?></div><?php endif; ?>
<div class="card"><div class="card-header">Add Photo</div><div class="card-body">
  <form method="post" enctype="multipart/form-data" style="display:grid;grid-template-columns:2fr 3fr auto;gap:10px;align-items:end;">
    <div>
      <label class="label">Photos</label>
      <input class="input" type="file" name="photos[]" accept="image/*" multiple required>
    </div>
    <div>
      <label class="label">Caption (optional)</label>
      <input class="input" name="caption" placeholder="Caption for all uploads">
    </div>
    <div>
      <button class="btn primary" type="submit"><i class="bi bi-upload"></i> Upload</button>
    </div>
  </form>
</div></div>

<div class="card" style="margin-top:12px;"><div class="card-header">Your Photos</div><div class="card-body">
  <?php if(!$items): ?><div class="small">No photos yet.</div><?php else: ?>
  <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:10px;" class="gallery-grid">
    <?php foreach($items as $it): $src=APP_URL.'uploads/gallery/'.$it['filename']; ?>
      <div class="card" style="overflow:hidden;">
        <img src="<?= e($src) ?>" alt="" style="width:100%;height:120px;object-fit:cover;">
        <div class="card-body">
          <div class="small" style="min-height:2.4em;"><?= e($it['caption']) ?></div>
          <a class="btn danger" href="?delete=<?= (int)$it['id'] ?>" onclick="return confirm('Delete this photo?');"><i class="bi bi-trash"></i> Delete</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div></div>
<?php include dirname(__DIR__).'/includes/footer.php'; ?>


