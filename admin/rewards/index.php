<?php include __DIR__.'/../includes/header.php';
$db=db();

// Filters
$search = safe('q');

// List rewards
$rows=[];
if ($db){
  $where = '1=1';
  if ($search) { $s=$db->real_escape_string($search); $where .= " AND name LIKE '%$s%'"; }
  $sql = "SELECT * FROM rewards WHERE $where ORDER BY sort_order, name LIMIT 1000";
  $res=$db->query($sql);
  while($res && ($r=$res->fetch_assoc())) $rows[]=$r;
}
?>
<div class="topbar">
  <div class="title">Rewards</div>
  <div style="display:flex;gap:10px;">
    <a class="btn" href="redemptions.php"><i class="bi bi-list-check"></i> View Redemptions</a>
    <a class="btn primary" href="add_reward.php"><i class="bi bi-plus-circle"></i> Add Reward</a>
  </div>
</div>
<div class="card"><div class="card-body">
  <form method="get" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;margin-bottom:12px;">
    <div><label class="label">Search</label><input class="input" name="q" value="<?= e($search) ?>" placeholder="Search rewards..."></div>
    <div><button class="btn" type="submit"><i class="bi bi-filter"></i> Filter</button></div>
  </form>
  <div class="table-responsive-sm">
    <table class="table">
      <thead><tr><th>ID</th><th>Name</th><th>Description</th><th>Points Required</th><th>Type</th><th>Value</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($rows as $r): ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>
            <td>
              <?php if($r['thumb']): ?>
                <img src="<?= e(APP_URL) ?>uploads/rewards/<?= e($r['thumb']) ?>" alt="<?= e($r['name']) ?>" style="width:40px;height:40px;object-fit:cover;border-radius:4px;margin-right:8px;vertical-align:middle;">
              <?php endif; ?>
              <?= e($r['name']) ?>
            </td>
            <td><?= e(substr($r['description'] ?? '', 0, 50)) ?><?= strlen($r['description'] ?? '') > 50 ? '...' : '' ?></td>
            <td><strong><?= number_format((int)$r['required_points']) ?> pts</strong></td>
            <td><?= e(ucfirst(str_replace('_', ' ', $r['reward_type'] ?? 'free_item'))) ?></td>
            <td><?= e($r['value'] ?? '—') ?></td>
            <td><?= ((int)$r['is_active'])? '<span style="color:green;">Active</span>' : '<span style="color:red;">Inactive</span>' ?></td>
            <td>
              <a class="btn" href="edit_reward.php?id=<?= (int)$r['id'] ?>"><i class="bi bi-pencil"></i> Edit</a>
              <a class="btn danger" href="delete_reward.php?id=<?= (int)$r['id'] ?>" onclick="return confirm('Delete this reward? This will not affect past redemptions.');"><i class="bi bi-trash"></i> Delete</a>
            </td>
          </tr>
        <?php endforeach; if(!$rows): ?>
          <tr><td colspan="8" class="small">No rewards found. <a href="add_reward.php">Add your first reward</a></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>

