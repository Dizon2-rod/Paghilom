<?php
require __DIR__.'/config.php';
require_login();

$order_code = $_GET['order'] ?? '';

if(empty($order_code)) {
    header('Location: index.php');
    exit;
}

// Get order details
$order_query = $mysqli->prepare("
    SELECT o.*, s.name as store_name, s.address as store_address, s.city as store_city
    FROM orders o
    LEFT JOIN stores s ON o.pickup_store_id = s.id
    WHERE o.code = ? AND o.user_id = ?
    LIMIT 1
");
$order_query->bind_param('si', $order_code, $_SESSION['user']['id']);
$order_query->execute();
$order = $order_query->get_result()->fetch_assoc();
$order_query->close();

if(!$order) {
    header('Location: index.php');
    exit;
}

// Ensure store address/name present (fallback to default active store)
if (empty($order['store_address'])) {
    $rs = $mysqli->query("SELECT name, address, city FROM stores WHERE is_active=1 ORDER BY id LIMIT 1");
    if ($rs && $rs->num_rows) {
        $s = $rs->fetch_assoc();
        $order['store_name'] = $order['store_name'] ?: ($s['name'] ?? '');
        $order['store_address'] = trim($s['address'] ?? '');
    }
}

// Fallback: award points if paid and not yet awarded
try {
    if (($order['payment_status'] ?? '') === 'paid' && !empty($order['user_id'])) {
        // Check if already awarded for this order
        $chk = $mysqli->prepare("SELECT id FROM point_transactions WHERE user_id = ? AND ref_type = 'order' AND ref_id = ? LIMIT 1");
        $chk->bind_param('ii', $order['user_id'], $order['id']);
        $chk->execute();
        $exists = $chk->get_result()->fetch_assoc();
        $chk->close();
        
        if (!$exists) {
            $points_earned = (int)floor(($order['total_amount'] ?? 0) / 2); // ₱10 = 5 points (₱2 = 1 point)
            if ($points_earned > 0) {
                $ins = $mysqli->prepare("INSERT INTO point_transactions (user_id, points, type, ref_type, ref_id, note, created_at) VALUES (?, ?, 'earn', 'order', ?, 'Points earned from order payment', NOW())");
                $ins->bind_param('iii', $order['user_id'], $points_earned, $order['id']);
                $ins->execute();
                $ins->close();
                
                $upd = $mysqli->prepare("UPDATE orders SET points_awarded = COALESCE(points_awarded,0) + ? WHERE id = ?");
                $upd->bind_param('ii', $points_earned, $order['id']);
                $upd->execute();
                $upd->close();
            }
        }
    }
} catch (Throwable $e) {}

include __DIR__.'/partials/header.php';
?>

<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 text-center">
            <!-- Success Icon with Paghilom logo -->
            <div class="mb-4 text-center">
                <?php 
                // Try multiple possible logo paths
                $logoPath = '';
                $possiblePaths = [
                    'uploads/paghilom_logo.png',
                    'assets/img/logo.png',
                    'assets/img/logo.jpg'
                ];
                
                foreach ($possiblePaths as $path) {
                    if (file_exists($path)) {
                        $logoPath = $path;
                        break;
                    }
                }
                ?>
                <?php if (!empty($logoPath)): ?>
                    <img src="<?= htmlspecialchars($logoPath) ?>" alt="Paghilom Cafe Logo" 
                         class="img-fluid payment-success-logo" 
                         style="max-width: 200px; height: auto; border-radius: 50%; object-fit: contain; border: 3px solid rgba(255,255,255,.9);">
                <?php else: ?>
                    <div class="bg-success d-inline-flex align-items-center justify-content-center rounded-circle" 
                         style="width: 120px; height: 120px;">
                        <i class="fas fa-check fa-3x text-white"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <style>
                @media (max-width: 767.98px) {
                    .payment-success-logo {
                        max-width: 150px !important;
                    }
                    
                    .card-body {
                        padding: 1rem !important;
                    }
                    
                    .h2 {
                        font-size: 1.5rem !important;
                    }
                    
                    .lead {
                        font-size: 1rem !important;
                    }
                    
                    h5 {
                        font-size: 1rem !important;
                    }
                    
                    .btn {
                        padding: 0.4rem 0.8rem !important;
                        font-size: 0.85rem !important;
                    }
                    
                    .order-item {
                        font-size: 0.9rem !important;
                    }
                    
                    .order-total {
                        font-size: 1rem !important;
                    }
                }
            </style>
            
            <!-- Success Message -->
            <h1 class="h2 mb-3" style="color: var(--primary);">Order Receipt</h1>
            <p class="lead text-muted mb-4">Here are your order details and pickup info</p>
            
            <!-- Order Details Card -->
            <div class="card shadow-sm mb-4 text-start">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Order Confirmation</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3 pb-3 border-bottom">
                        <h6 class="text-muted mb-2">Order Code</h6>
                        <h4 class="mb-0" style="font-family: 'Courier New', monospace;"><?= htmlspecialchars($order['code']) ?></h4>
                        
                        <!-- QR Code Expiry Timer -->
                        <div class="mt-2 mb-3">
                            <div id="qr-timer" class="badge bg-info text-white" style="font-size: 0.9rem; padding: 8px 12px;">
                                <i class="fas fa-clock me-1"></i>
                                <span id="timer-text">Loading...</span>
                            </div>
                            <div class="small text-muted mt-1">QR code valid for 3 hours from order creation</div>
                        </div>
                        
                        <div class="mt-3 d-flex justify-content-center">
                            <div id="order-qr" class="border rounded p-2 bg-light"></div>
                        </div>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-6">
                            <small class="text-muted">Customer</small>
                            <p class="mb-0"><strong><?= htmlspecialchars($order['customer_name']) ?></strong></p>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Amount Paid</small>
                            <p class="mb-0 text-success"><strong>₱<?= number_format($order['total_amount'], 2) ?></strong></p>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Payment Method</small>
                            <p class="mb-0"><strong><?= ucfirst($order['payment_method'] ?? 'N/A') ?></strong></p>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Payment Status</small>
                            <p class="mb-0">
                                <?php 
                                    $ps = strtolower($order['payment_status'] ?? '');
                                    if (in_array($ps, ['paid','successful'])) {
                                        $disp = 'PAID';
                                        $cls = 'success';
                                    } elseif (in_array($ps, ['failed'])) {
                                        $disp = 'FAILED';
                                        $cls = 'danger';
                                    } else {
                                        $disp = 'UNPAID';
                                        $cls = 'warning';
                                    }
                                ?>
                                <span class="badge bg-<?= $cls ?>"><?= $disp ?></span>
                            </p>
                        </div>
                        <div class="col-12">
                            <small class="text-muted">Pickup Location</small>
                            <p class="mb-0"><strong><?= htmlspecialchars($order['store_name']) ?></strong></p>
<p class="mb-0 text-muted small"><?= nl2br(htmlspecialchars(trim($order['store_address'] ?? ''))) ?></p>
                        </div>
                        <div class="col-12">
                            <small class="text-muted">Order Date & Time</small>
                            <?php $dt_display = $order['created_at'] ?? $order['pickup_time'] ?? date('Y-m-d H:i:s'); ?>
                            <p class="mb-0"><strong><?= date('l, F d, Y @ g:i A', strtotime($dt_display)) ?></strong></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- What's Next -->
            <div class="alert alert-info text-start">
                <h6><i class="fas fa-info-circle me-2"></i>What's Next?</h6>
                <ul class="mb-0">
                    <li>We’ll start preparing your order.</li>
                    <li>For onsite cash, present this QR to the cashier to pay.</li>
                    <li>For online payments, we’ll notify you when it’s ready for pickup.</li>
                    <li>Show your order code: <strong><?= htmlspecialchars($order['code']) ?></strong></li>
                </ul>
            </div>
            
            <!-- Action Buttons -->
            <div class="d-grid gap-2">
                <a href="user/orders.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-list me-2"></i>View My Orders
                </a>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-home me-2"></i>Back to Home
                </a>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.1/build/qrcode.min.js"></script>
<script>
  (function(){
    try {
      // Encode plain orders.code so POS/Kiosk can scan directly
      var qrContent = <?= json_encode($order['code']) ?>;
      
      var el = document.getElementById('order-qr');
      if (el && window.QRCode){
        var canvas = document.createElement('canvas');
        el.appendChild(canvas);
        // High error correction (H) for better scannability
        QRCode.toCanvas(canvas, qrContent, { 
          width: 220, 
          margin: 2,
          errorCorrectionLevel: 'H'
        });
      }
      
      // Countdown Timer for QR Code (3 hours)
      var createdAt = new Date(<?= json_encode($order['created_at']) ?>);
      var expiresAt = new Date(createdAt.getTime() + (3 * 60 * 60 * 1000)); // 3 hours
      
      function updateTimer() {
        var now = new Date();
        var remaining = expiresAt - now;
        
        var timerEl = document.getElementById('timer-text');
        var badgeEl = document.getElementById('qr-timer');
        
        if (remaining <= 0) {
          timerEl.textContent = 'QR Code Expired';
          badgeEl.className = 'badge bg-danger text-white';
          return;
        }
        
        var hours = Math.floor(remaining / (1000 * 60 * 60));
        var minutes = Math.floor((remaining % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((remaining % (1000 * 60)) / 1000);
        
        var timeStr = hours + 'h ' + 
                      (minutes < 10 ? '0' : '') + minutes + 'm ' + 
                      (seconds < 10 ? '0' : '') + seconds + 's';
        
        timerEl.textContent = 'Expires in ' + timeStr;
        
        // Change color based on time remaining
        if (remaining < 30 * 60 * 1000) { // Less than 30 minutes
          badgeEl.className = 'badge bg-warning text-dark';
        } else if (remaining < 60 * 60 * 1000) { // Less than 1 hour
          badgeEl.className = 'badge bg-info text-white';
        } else {
          badgeEl.className = 'badge bg-success text-white';
        }
        
        setTimeout(updateTimer, 1000);
      }
      
      updateTimer();
    } catch(e) { console.error('QR Error:', e); }
  })();
</script>

<?php include __DIR__.'/partials/footer.php'; ?>
