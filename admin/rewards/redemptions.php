<?php include __DIR__.'/../includes/header.php';
$db=db();

// Messages
$msg = safe('msg');
$err = safe('err');

// Filters
$search = safe('q');
$status_filter = safe('status');

// Pagination
$page = (int)(safe('page') ?: 1);
$per_page = 20;
$offset = ($page - 1) * $per_page;

// List redemptions with user and reward info
$rows=[];
$total_count = 0;

if ($db){
  $where = '1=1';
  $params = [];
  $types = '';
  
  if ($search) { 
    $where .= " AND (u.name LIKE ? OR u.email LIKE ? OR r.voucher_code LIKE ?)";
    $search_param = '%' . $db->real_escape_string($search) . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
  }
  
  if ($status_filter && in_array($status_filter, ['pending', 'approved', 'rejected', 'cancelled'])) {
    $where .= " AND r.status = ?";
    $params[] = $status_filter;
    $types .= 's';
  }
  
  // Get total count
  $count_sql = "SELECT COUNT(*) as total 
                FROM redemptions r
                LEFT JOIN users u ON r.user_id = u.id
                WHERE $where";
  $count_stmt = $db->prepare($count_sql);
  if ($types) {
    $count_stmt->bind_param($types, ...$params);
  }
  $count_stmt->execute();
  $count_result = $count_stmt->get_result();
  $total_count = $count_result ? (int)$count_result->fetch_assoc()['total'] : 0;
  $count_stmt->close();
  
  // Get redemptions with pagination
  $sql = "SELECT r.*, 
                 u.name as user_name, 
                 u.email as user_email,
                 COALESCE(rw.name, rc.name) as reward_name,
                 COALESCE(rw.description, rc.description) as reward_description
          FROM redemptions r
          LEFT JOIN users u ON r.user_id = u.id
          LEFT JOIN rewards rw ON r.reward_id = rw.id
          LEFT JOIN reward_catalog rc ON r.reward_id = rc.id
          WHERE $where
          ORDER BY r.created_at DESC
          LIMIT ? OFFSET ?";
  
  $stmt = $db->prepare($sql);
  if ($types) {
    $params[] = $per_page;
    $params[] = $offset;
    $types .= 'ii';
    $stmt->bind_param($types, ...$params);
  } else {
    $stmt->bind_param('ii', $per_page, $offset);
  }
  $stmt->execute();
  $res = $stmt->get_result();
  while($res && ($r=$res->fetch_assoc())) $rows[]=$r;
  $stmt->close();
}

$total_pages = max(1, (int)ceil($total_count / $per_page));
?>
<div class="topbar">
  <div class="title">Redemptions</div>
  <div class="small">Total: <?= number_format($total_count) ?> redemption<?= $total_count != 1 ? 's' : '' ?></div>
</div>
<?php if($msg): ?><div class="alert"><?= e($msg) ?></div><?php endif; ?>
<?php if($err): ?><div class="alert warn"><?= e($err) ?></div><?php endif; ?>
<div class="card"><div class="card-body">
  <form method="get" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;margin-bottom:12px;">
    <div><label class="label">Search</label><input class="input" name="q" value="<?= e($search) ?>" placeholder="User name, email, or voucher code..."></div>
    <div><label class="label">Status</label>
      <select class="input" name="status">
        <option value="">All Status</option>
        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
        <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
        <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
      </select>
    </div>
    <div><button class="btn" type="submit"><i class="bi bi-filter"></i> Filter</button></div>
    <?php if($search || $status_filter): ?>
      <div><a class="btn" href="redemptions.php">Clear</a></div>
    <?php endif; ?>
  </form>
  <div class="table-responsive-sm">
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>User</th>
          <th>Reward</th>
          <th>Points</th>
          <th>Voucher Code</th>
          <th>Status</th>
          <th>Date/Time</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($rows as $r): ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>
            <td>
              <div><strong><?= e($r['user_name'] ?? 'Unknown User') ?></strong></div>
              <div class="small text-muted"><?= e($r['user_email'] ?? '') ?></div>
            </td>
            <td>
              <div><strong><?= e($r['reward_name'] ?? 'Unknown Reward') ?></strong></div>
              <?php if($r['reward_description']): ?>
                <div class="small text-muted"><?= e(substr($r['reward_description'], 0, 40)) ?><?= strlen($r['reward_description']) > 40 ? '...' : '' ?></div>
              <?php endif; ?>
            </td>
            <td><strong><?= number_format((int)$r['points_spent']) ?> pts</strong></td>
            <td><code><?= e($r['voucher_code'] ?? '—') ?></code></td>
            <td>
              <?php 
              $status = strtolower($r['status'] ?? 'pending');
              $status_color = 'secondary';
              $status_text = 'Unknown';
              if ($status === 'approved') {
                $status_color = 'success';
                $status_text = 'Approved';
              } elseif ($status === 'pending') {
                $status_color = 'warning';
                $status_text = 'Pending';
              } elseif ($status === 'rejected' || $status === 'cancelled') {
                $status_color = 'danger';
                $status_text = ucfirst($status);
              } else {
                $status_text = ucfirst($status);
              }
              ?>
              <span class="badge bg-<?= $status_color ?>"><?= htmlspecialchars($status_text) ?></span>
            </td>
            <td>
              <div><?= date('M d, Y', strtotime($r['created_at'] ?? 'now')) ?></div>
              <div class="small text-muted"><?= date('g:i A', strtotime($r['created_at'] ?? 'now')) ?></div>
              <?php if($r['claimed_at']): ?>
                <div class="small" style="color:green;">Claimed: <?= date('M d, Y g:i A', strtotime($r['claimed_at'])) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <a class="btn" href="view_redemption.php?id=<?= (int)$r['id'] ?>" title="View Details"><i class="bi bi-eye"></i> View</a>
            </td>
          </tr>
        <?php endforeach; if(!$rows): ?>
          <tr><td colspan="8" class="small text-center py-4">No redemptions found.<?= ($search || $status_filter) ? ' <a href="redemptions.php">Clear filters</a>' : '' ?></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  
  <?php if($total_pages > 1): ?>
    <div style="margin-top:12px;display:flex;justify-content:center;gap:8px;align-items:center;">
      <?php if($page > 1): ?>
        <a class="btn" href="?page=<?= $page-1 ?><?= $search ? '&q='.urlencode($search) : '' ?><?= $status_filter ? '&status='.urlencode($status_filter) : '' ?>">Previous</a>
      <?php endif; ?>
      <span class="small">Page <?= $page ?> of <?= $total_pages ?></span>
      <?php if($page < $total_pages): ?>
        <a class="btn" href="?page=<?= $page+1 ?><?= $search ? '&q='.urlencode($search) : '' ?><?= $status_filter ? '&status='.urlencode($status_filter) : '' ?>">Next</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>

