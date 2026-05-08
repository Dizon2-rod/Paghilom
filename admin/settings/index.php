<?php include __DIR__.'/../includes/header.php';
$db=db(); $msg=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
  $cafe=safe('cafe_name','POST');
  $logo=safe('logo_url','POST');
  $contact=safe('contact','POST');
  $address=safe('address','POST');
  $fb=safe('facebook_url','POST');
  $ig=safe('instagram_url','POST');
  $open=safe('open_time','POST');
  $close=safe('close_time','POST');
  $days=safe('open_days','POST');
  if($db && $db->query("SHOW TABLES LIKE 'settings'\n")->num_rows){
    exec_stmt("INSERT INTO settings (`key`,`value`) VALUES
      ('cafe_name',?),('logo_url',?),('contact',?),('address',?),('facebook_url',?),('instagram_url',?),('open_time',?),('close_time',?),('open_days',?)
      ON DUPLICATE KEY UPDATE value=VALUES(value)",
      "sssssssss",[$cafe,$logo,$contact,$address,$fb,$ig,$open,$close,$days]);
    $msg='Settings saved.';
  }
}
$conf=[
  'cafe_name'=>'Paghilom Cafe',
  'logo_url'=>APP_URL.'assets/uploads/logo.png',
  'contact'=>'',
  'address'=>'',
  'facebook_url'=>'',
  'instagram_url'=>'',
'open_time'=>'08:00',
  'close_time'=>'21:00',
  'open_days'=>'Mon–Sun'
];
if($db && $db->query("SHOW TABLES LIKE 'settings'\n")->num_rows){ $res=$db->query("SELECT `key`,`value` FROM settings"); while($res && ($r=$res->fetch_assoc())) $conf[$r['key']]=$r['value']; }
?>
<div class="topbar"><div class="title">Settings</div></div>
<?php if($msg): ?><div class="alert"><?= e($msg) ?></div><?php endif; ?>
<style>
  @media (max-width: 768px) {
    .settings-form {
      grid-template-columns: 1fr !important;
    }
  }
</style>
<div class="card"><div class="card-header">Café Configuration</div><div class="card-body">
  <form method="post" class="settings-form" style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;">
    <div><label class="label">Café Name</label><input class="input" name="cafe_name" value="<?= e($conf['cafe_name']) ?>" style="width:100%;box-sizing:border-box;"></div>
    <div><label class="label">Logo URL</label><input class="input" name="logo_url" value="<?= e($conf['logo_url']) ?>" style="width:100%;box-sizing:border-box;"></div>
    <div><label class="label">Contact Number</label><input class="input" name="contact" value="<?= e($conf['contact']) ?>" style="width:100%;box-sizing:border-box;"></div>
    <div><label class="label">Address</label><input class="input" name="address" value="<?= e($conf['address']) ?>" style="width:100%;box-sizing:border-box;"></div>
    <div><label class="label">Facebook URL</label><input class="input" name="facebook_url" value="<?= e($conf['facebook_url']) ?>" placeholder="https://facebook.com/..." style="width:100%;box-sizing:border-box;"></div>
    <div><label class="label">Instagram URL</label><input class="input" name="instagram_url" value="<?= e($conf['instagram_url']) ?>" placeholder="https://instagram.com/..." style="width:100%;box-sizing:border-box;"></div>
    <div><label class="label">Opening Time</label><input class="input" type="time" name="open_time" value="<?= e($conf['open_time']) ?>" style="width:100%;box-sizing:border-box;"></div>
    <div><label class="label">Closing Time</label><input class="input" type="time" name="close_time" value="<?= e($conf['close_time']) ?>" style="width:100%;box-sizing:border-box;"></div>
    <div><label class="label">Days Open</label>
      <select class="input" name="open_days" style="width:100%;box-sizing:border-box;">
        <?php $opts=['Mon–Sun','Mon–Fri','Mon–Sat','Tue–Sun']; foreach($opts as $o): ?>
          <option value="<?= e($o) ?>" <?= ($conf['open_days']??'Mon–Sun')===$o?'selected':'' ?>><?= e($o) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="display:flex;align-items:flex-end;"><button class="btn primary" type="submit" style="white-space:nowrap;">Save</button></div>
  </form>
</div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>


