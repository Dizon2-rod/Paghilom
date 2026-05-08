<?php include dirname(__DIR__).'/includes/header.php';
$db = db();
$id = (int)(safe('id') ?: 0);

if(!$id){
  header('Location: redemptions.php');
  exit;
}

// Get redemption with user and reward details
$redemption = null;
if($db){
  $stmt = $db->prepare("
    SELECT r.*, 
           u.name as user_name, 
           u.email as user_email,
           u.phone as user_phone,
           COALESCE(rw.name, rc.name) as reward_name,
           COALESCE(rw.description, rc.description) as reward_description,
           COALESCE(rw.reward_type, rc.reward_type) as reward_type,
           COALESCE(rw.value, rc.value) as reward_value
    FROM redemptions r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN rewards rw ON r.reward_id = rw.id
    LEFT JOIN reward_catalog rc ON r.reward_id = rc.id
    WHERE r.id=? 
    LIMIT 1
  ");
  $stmt->bind_param('i', $id);
  $stmt->execute();
  $res = $stmt->get_result();
  $redemption = $res ? $res->fetch_assoc() : null;
  $stmt->close();
}

if(!$redemption){
  echo '<div class="alert warn">Redemption not found.</div>';
  echo '<a class="btn" href="redemptions.php">Back to Redemptions</a>';
  include dirname(__DIR__).'/includes/footer.php';
  exit;
}

$status = strtolower($redemption['status'] ?? 'pending');
$status_color = 'secondary';
if ($status === 'approved') {
  $status_color = 'success';
} elseif ($status === 'pending') {
  $status_color = 'warning';
} elseif ($status === 'rejected' || $status === 'cancelled') {
  $status_color = 'danger';
}
?>
<div class="topbar">
  <div class="title">Redemption Details</div>
  <a class="btn" href="redemptions.php"><i class="bi bi-arrow-left"></i> Back to Redemptions</a>
</div>
<div class="card"><div class="card-body">
  <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:16px;">
    <div>
      <h5 style="margin-bottom:12px;">User Information</h5>
      <div class="row" style="margin-bottom:8px;"><span class="label">Name:</span><span><strong><?= e($redemption['user_name'] ?? 'Unknown') ?></strong></span></div>
      <div class="row" style="margin-bottom:8px;"><span class="label">Email:</span><span><?= e($redemption['user_email'] ?? '—') ?></span></div>
      <?php if($redemption['user_phone']): ?>
        <div class="row" style="margin-bottom:8px;"><span class="label">Phone:</span><span><?= e($redemption['user_phone']) ?></span></div>
      <?php endif; ?>
    </div>
    
    <div>
      <h5 style="margin-bottom:12px;">Reward Information</h5>
      <div class="row" style="margin-bottom:8px;"><span class="label">Reward:</span><span><strong><?= e($redemption['reward_name'] ?? 'Unknown') ?></strong></span></div>
      <?php if($redemption['reward_description']): ?>
        <div class="row" style="margin-bottom:8px;"><span class="label">Description:</span><span><?= e($redemption['reward_description']) ?></span></div>
      <?php endif; ?>
      <div class="row" style="margin-bottom:8px;"><span class="label">Points Spent:</span><span><strong><?= number_format((int)$redemption['points_spent']) ?> pts</strong></span></div>
    </div>
    
    <div>
      <h5 style="margin-bottom:12px;">Redemption Details</h5>
      <div class="row" style="margin-bottom:8px;"><span class="label">Voucher Code:</span><span><code style="font-size:1.1rem;"><?= e($redemption['voucher_code'] ?? '—') ?></code></span></div>
      <div class="row" style="margin-bottom:8px;"><span class="label">Status:</span><span><span class="badge bg-<?= $status_color ?>"><?= ucfirst($redemption['status'] ?? 'pending') ?></span></span></div>
      <div class="row" style="margin-bottom:8px;"><span class="label">Redeemed:</span><span><?= date('M d, Y g:i A', strtotime($redemption['created_at'] ?? 'now')) ?></span></div>
      <?php if($redemption['claimed_at']): ?>
        <div class="row" style="margin-bottom:8px;"><span class="label">Claimed:</span><span><?= date('M d, Y g:i A', strtotime($redemption['claimed_at'])) ?></span></div>
      <?php endif; ?>
      <?php if($redemption['expires_at']): ?>
        <div class="row" style="margin-bottom:8px;"><span class="label">Expires:</span><span><?= date('M d, Y g:i A', strtotime($redemption['expires_at'])) ?></span></div>
      <?php endif; ?>
    </div>
    
    <div>
      <h5 style="margin-bottom:12px;">Note</h5>
      <div class="small text-muted">
        <?php if($status === 'pending'): ?>
          This redemption will be automatically approved when staff scans the QR code at the POS.
        <?php elseif($status === 'approved'): ?>
          This redemption has been approved and processed.
        <?php else: ?>
          This redemption has been <?= htmlspecialchars($status) ?>.
        <?php endif; ?>
      </div>
    </div>
  </div>
</div></div>
<style>
  .row {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    border-bottom: 1px solid var(--line, #e5e7eb);
  }
  .row:last-child {
    border-bottom: none;
  }
  .row .label {
    color: var(--muted, #64748b);
    font-weight: 500;
  }
  @media (max-width: 768px) {
    .card-body > div {
      grid-template-columns: 1fr !important;
    }
  }
</style>
<?php include dirname(__DIR__).'/includes/footer.php'; ?>

