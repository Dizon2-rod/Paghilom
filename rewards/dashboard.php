<?php 
require __DIR__.'/../config.php'; 
require_login(); 
if (file_exists(__DIR__.'/../points.php')) {
    require_once __DIR__.'/../points.php';
}
include __DIR__.'/../partials/header.php';
$uid=$_SESSION['user']['id']; $bal=points_balance($mysqli,$uid);
$tx=$mysqli->prepare('SELECT points,type,ref_type,ref_id,note,created_at FROM point_transactions WHERE user_id=? ORDER BY id DESC LIMIT 50'); $tx->bind_param('i',$uid); $tx->execute(); $hist=$tx->get_result();
$reds=$mysqli->prepare('SELECT r.id, rc.name reward_name, r.points_spent, r.status, r.created_at FROM redemptions r JOIN reward_catalog rc ON rc.id=r.reward_id WHERE r.user_id=? ORDER BY r.id DESC'); $reds->bind_param('i',$uid); $reds->execute(); $redhist=$reds->get_result();
$catalog=$mysqli->query('SELECT id,name,description,points_cost,reward_type,value FROM reward_catalog WHERE is_active=1 ORDER BY points_cost');
?>

<section class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12">
      <!-- Header -->
      <div class="mb-4">
        <h1 class="h3 mb-1"><i class="bi bi-award"></i> My Rewards</h1>
        <p class="text-muted">Earn points with every purchase and redeem amazing rewards!</p>
      </div>
      
      <!-- Points Balance Card -->
      <div class="card shadow-sm border-0 mb-4" style="background: linear-gradient(135deg, #1e3932 0%, #2d5a4d 100%); color: white;">
        <div class="card-body p-4">
          <div class="row align-items-center">
            <div class="col-md-8">
              <h2 class="h1 mb-2"><i class="bi bi-star-fill text-warning"></i> <?= number_format($bal) ?> Points</h2>
              <p class="mb-0 opacity-75">
                <i class="bi bi-info-circle"></i> Earning rule: <strong>₱5 = 1 point</strong> | Redeem rewards below
              </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
              <div class="bg-white bg-opacity-10 rounded p-3">
                <div class="small opacity-75 mb-1">Next Reward at</div>
                <div class="h5 mb-0"><?php 
                  $next_reward = $mysqli->query('SELECT MIN(points_cost) as min_cost FROM reward_catalog WHERE is_active=1 AND points_cost > ' . (int)$bal);
                  $next = $next_reward->fetch_assoc();
                  echo $next && $next['min_cost'] ? number_format($next['min_cost']) . ' pts' : 'N/A';
                ?></div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- Available Rewards -->
      <div class="mb-4">
        <h4 class="mb-3"><i class="bi bi-gift"></i> Available Rewards</h4>
        <div class="row g-3">
          <?php 
          $catalog_result = $mysqli->query('SELECT id,name,description,points_cost,reward_type,value FROM reward_catalog WHERE is_active=1 ORDER BY points_cost');
          if ($catalog_result && $catalog_result->num_rows > 0):
            while($r = $catalog_result->fetch_assoc()): 
              $can_redeem = $bal >= $r['points_cost'];
          ?>
            <div class="col-md-6 col-lg-4">
              <div class="card shadow-sm border-0 h-100 <?= !$can_redeem ? 'opacity-75' : '' ?>">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="card-title mb-0"><?= htmlspecialchars($r['name']) ?></h5>
                    <?php if ($can_redeem): ?>
                      <span class="badge bg-success">Available</span>
                    <?php else: ?>
                      <span class="badge bg-secondary">Locked</span>
                    <?php endif; ?>
                  </div>
                  <p class="card-text text-muted small mb-3"><?= htmlspecialchars($r['description']) ?></p>
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <div class="h5 mb-0 text-primary"><?= number_format($r['points_cost']) ?> pts</div>
                      <?php if (!$can_redeem): ?>
                        <small class="text-muted">Need <?= number_format($r['points_cost'] - $bal) ?> more</small>
                      <?php endif; ?>
                    </div>
                    <form method="post" action="redeem.php">
                      <input type="hidden" name="reward_id" value="<?= $r['id'] ?>">
                      <button type="submit" class="btn btn-<?= $can_redeem ? 'success' : 'outline-secondary' ?>" 
                              <?= !$can_redeem ? 'disabled' : '' ?>>
                        <i class="bi bi-<?= $can_redeem ? 'check-circle' : 'lock' ?>"></i> 
                        <?= $can_redeem ? 'Redeem' : 'Locked' ?>
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          <?php 
            endwhile;
          else:
          ?>
            <div class="col-12">
              <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No rewards available at the moment. Check back later!
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <!-- Transaction History -->
      <div class="row g-4">
        <div class="col-lg-7">
          <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom">
              <h5 class="mb-0"><i class="bi bi-clock-history"></i> Transaction History</h5>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Date</th>
                      <th>Type</th>
                      <th class="text-end">Points</th>
                      <th>Note</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php 
                    $tx_result = $mysqli->prepare('SELECT points,type,ref_type,ref_id,note,created_at FROM point_transactions WHERE user_id=? ORDER BY id DESC LIMIT 20');
                    $tx_result->bind_param('i', $uid);
                    $tx_result->execute();
                    $transactions = $tx_result->get_result();
                    
                    if ($transactions->num_rows > 0):
                      while($t = $transactions->fetch_assoc()): 
                    ?>
                      <tr>
                        <td class="small"><?= date('M j, Y g:i A', strtotime($t['created_at'])) ?></td>
                        <td>
                          <?php if ($t['type'] == 'earn'): ?>
                            <span class="badge bg-success"><i class="bi bi-plus-circle"></i> Earned</span>
                          <?php else: ?>
                            <span class="badge bg-warning"><i class="bi bi-dash-circle"></i> Redeemed</span>
                          <?php endif; ?>
                        </td>
                        <td class="text-end">
                          <span class="fw-semibold <?= $t['points'] > 0 ? 'text-success' : 'text-danger' ?>">
                            <?= $t['points'] > 0 ? '+' : '' ?><?= number_format($t['points']) ?>
                          </span>
                        </td>
                        <td class="small text-muted"><?= htmlspecialchars($t['note']) ?></td>
                      </tr>
                    <?php 
                      endwhile;
                    else:
                    ?>
                      <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                          <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                          No transactions yet. Start shopping to earn points!
                        </td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Redemption History -->
        <div class="col-lg-5">
          <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom">
              <h5 class="mb-0"><i class="bi bi-box-seam"></i> Redemption History</h5>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Reward</th>
                      <th class="text-end">Points</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php 
                    $red_result = $mysqli->prepare('SELECT r.id, rc.name reward_name, r.points_spent, r.status, r.created_at FROM redemptions r JOIN reward_catalog rc ON rc.id=r.reward_id WHERE r.user_id=? ORDER BY r.id DESC LIMIT 20');
                    $red_result->bind_param('i', $uid);
                    $red_result->execute();
                    $redemptions = $red_result->get_result();
                    
                    if ($redemptions->num_rows > 0):
                      while($r = $redemptions->fetch_assoc()): 
                    ?>
                      <tr>
                        <td>
                          <div class="fw-semibold"><?= htmlspecialchars($r['reward_name']) ?></div>
                          <div class="small text-muted"><?= date('M j, Y', strtotime($r['created_at'])) ?></div>
                        </td>
                        <td class="text-end text-danger">-<?= number_format($r['points_spent']) ?></td>
                        <td>
                          <?php 
                          $status_class = [
                            'pending' => 'warning',
                            'approved' => 'success',
                            'claimed' => 'info',
                            'rejected' => 'danger'
                          ];
                          $badge_class = $status_class[$r['status']] ?? 'secondary';
                          ?>
                          <span class="badge bg-<?= $badge_class ?>"><?= ucfirst($r['status']) ?></span>
                        </td>
                      </tr>
                    <?php 
                      endwhile;
                    else:
                    ?>
                      <tr>
                        <td colspan="3" class="text-center text-muted py-4">
                          <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                          No redemptions yet
                        </td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__.'/../partials/footer.php'; ?>
