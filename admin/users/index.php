<?php include dirname(__DIR__).'/includes/header.php';
$db = db();
$search = safe('q');
$role = safe('role');
$rows=[];
if($db){
  $where='1=1';
  if($search){ $s=$db->real_escape_string($search); $where .= " AND (name LIKE '%$s%' OR email LIKE '%$s%')"; }
  if($role){ $r=$db->real_escape_string($role); $where .= " AND role='".$r."'"; }
  $sql="SELECT id, name, email, role, is_active, last_login, created_at FROM users WHERE $where ORDER BY id DESC LIMIT 500";
  $res=$db->query($sql);
  while($res && ($x=$res->fetch_assoc())) $rows[]=$x;
}
?>
<div class="topbar"><div class="title">User Management</div></div>
<div class="card"><div class="card-header">All Users</div><div class="card-body">
  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;margin-bottom:12px;">
    <div>
      <label class="label">Search</label>
      <input class="input" name="q" value="<?= e($search) ?>" placeholder="Name or Email">
    </div>
    <div>
      <label class="label">Role</label>
      <select class="input" name="role">
        <option value="">All</option>
        <?php foreach(['owner','admin','staff','cashier','customer'] as $r): ?>
          <option value="<?= e($r) ?>" <?= ($role===$r)?'selected':'' ?>><?= ucfirst($r) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <button class="btn" type="submit"><i class="bi bi-filter"></i> Filter</button>
    </div>
  </form>
  <div class="table-responsive-sm">
    <table class="table">
      <thead><tr>
        <th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th>Joined</th><th>Actions</th>
      </tr></thead>
      <tbody>
        <?php foreach($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= e($r['name']) ?></td>
          <td><?= e($r['email']) ?></td>
          <td><?= e($r['role']) ?></td>
          <td><?= (int)$r['is_active'] ? '<span class="badge">Active</span>':'<span class="badge">Inactive</span>' ?></td>
          <td><?= e($r['last_login'] ?: '—') ?></td>
          <td><?= e($r['created_at']) ?></td>
          <td>
            <?php if ($r['role']!=='owner'): ?>
              <a class="btn" href="toggle.php?id=<?= (int)$r['id'] ?>&active=<?= (int)!$r['is_active'] ?>"><?= ((int)$r['is_active'])? 'Deactivate':'Activate' ?></a>
              <a class="btn danger" href="delete.php?id=<?= (int)$r['id'] ?>" onclick="return confirm('Delete user? This cannot be undone.');">Delete</a>
            <?php else: ?>
              <span class="small">Protected</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; if(!$rows): ?>
          <tr><td colspan="8" class="small">No users found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include dirname(__DIR__).'/includes/footer.php'; ?>


