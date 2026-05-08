<?php 
require __DIR__.'/../config.php'; 
require_pos(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paghilom Cafe - POS System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1a4d2e;
            --primary-light: #2a6e3d;
            --primary-lightest: #e8f5e9;
            --secondary: #f8f9fa;
            --text: #2c3e50;
            --text-light: #6c757d;
            --text-lighter: #95a5a6;
            --border: #e9ecef;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --transition: all 0.2s ease-in-out;
        }
        
        body {
            background-color: #f5f7fa;
            color: var(--text);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .staff-header {
            background: #ffffff;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.04);
            padding: 0.8rem 2.5rem;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            transition: var(--transition);
        }
        
        .staff-header.scrolled {
            padding: 0.6rem 2.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        .logo {
            font-weight: 700;
            font: size 90px;; /* Base size for desktop */
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            letter-spacing: -0.3px;
            transition: var(--transition);
            font-family: 'Poppins', sans-serif;
            white-space: nowrap;
        }
        
        /* Responsive header for mobile */
        @media (max-width: 768px) {
            .staff-header {
                padding: 0.5rem 0.75rem !important;
                height: 60px;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                z-index: 1030;
                background: #fff;
                box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            }
            
            .staff-header > div:first-child {
                flex: 1;
                min-width: 0;
                overflow: hidden;
            }
            
            .logo-link {
                display: flex;
                align-items: center;
                text-decoration: none;
            }
            
            .logo-link img.main-logo {
                height: 55px !important;  /* Increased from 45px */
                width: auto !important;
                margin-right: 10px !important;
                object-fit: contain;
            }
            
            .staff-header .d-flex.flex-column {
                display: flex !important;
                margin-left: 0.5rem;
                line-height: 1.2;
            }
            
            .staff-header .fw-bold {
                font-size: 1.1rem !important;
                color: var(--primary);
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            .staff-header small {
                font-size: 0.7rem !important;
                display: block;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            .staff-actions {
                margin-left: auto;
                gap: 0.5rem;
                align-items: center;
            }
            
            .staff-actions .d-flex.align-items-center {
                display: flex !important;
                margin-right: 0.5rem;
            }
            
            .staff-actions .bi-person-circle {
                font-size: 1.5rem !important;
                color: var(--primary);
                margin-right: 0.25rem;
            }
            
            .staff-actions .d-flex.flex-column {
                display: none !important; /* Hide user info text on mobile */
            }
            
            .staff-actions .btn {
                padding: 0.4rem 0.6rem !important;
                font-size: 0.85rem !important;
                white-space: nowrap;
            }
            
            .staff-actions .btn i {
                margin-right: 0.25rem !important;
            }
            
            .staff-actions .btn span {
                display: none; /* Hide text, show only icon on mobile */
            }
        }
        
        /* Extra small devices */
        @media (max-width: 480px) {
            .staff-header {
                padding: 0.4rem 0.5rem !important;
                height: 55px;
            }
            
            .logo-link img.main-logo {
                height: 50px !important;  /* Increased from 40px */
                margin-right: 8px !important;
            }
            
            .staff-header .fw-bold {
                font-size: 1.1rem !important;  /* Slightly increased */
            }
            
            .staff-header small {
                font-size: 0.65rem !important;
            }
            
            .staff-actions .btn {
                padding: 0.3rem 0.5rem !important;
                font-size: 0.8rem !important;
            }
            
            .staff-actions .bi-person-circle {
                font-size: 1.3rem !important;
            }
            
            .vr {
                height: 30px !important;
                margin: 0 0.25rem !important;
            }
        }
        
        .logo:hover {
            color: var(--primary-light);
            transform: translateY(-1px);
        }
        
        .staff-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .staff-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .pos-container {
            margin-top: 80px;
            padding: 0 1rem 2rem;
            min-height: calc(100vh - 180px);
        }
        
        @media (max-width: 768px) {
            .pos-container {
                margin-top: 70px;
                padding: 0 0.75rem 1.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .pos-container {
                margin-top: 65px;
                padding: 0 0.5rem 1rem;
            }
        }
        
        .staff-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            height: 70px;
            padding: 0.5rem 1rem;
        }
        
        @media (max-width: 768px) {
            .staff-header {
                height: 60px;
                padding: 0.25rem 0.75rem;
            }
            .staff-header img {
                height: 35px !important;
                margin-right: 8px !important;
            }
            .staff-header .fw-bold {
                font-size: 1.1rem !important;
            }
            .staff-header small {
                display: none;
            }
            .staff-actions .btn {
                padding: 0.3rem 0.75rem !important;
                font-size: 0.9rem !important;
            }
            .staff-actions .bi {
                font-size: 1.2rem !important;
                margin-right: 0.25rem !important;
            }
            .staff-actions .vr {
                height: 30px !important;
                margin: 0 0.5rem;
            }
            .pos-container {
                margin-top: 70px !important;
                padding: 0 1rem 3rem !important;
            }
        }
        
        /* Ensure footer stays at bottom */
        html, body {
            height: 100%;
            margin: 0;
        }
        
        body {
            display: flex;
            flex-direction: column;
        }
        
        main {
            flex: 1 0 auto;
        }
        
        /* Override footer styles for POS */
        .site-footer {
            background-color: #2A5618 !important;
            border-top: 1px solid #333 !important;
            padding: 1.5rem 0 !important;
        }
        
        .site-footer .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .pos-hero {
          background: linear-gradient(135deg, #2A5618 0%, #1e3d10 100%);
          color: white;
          padding: 2.5rem 0;
          margin-bottom: 2rem;
          box-shadow: 0 4px 20px rgba(42, 86, 24, 0.15);
        }
        .pos-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 1.75rem;
            overflow: hidden;
            transition: var(--transition);
        }
        
        .pos-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .pos-card-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 1.1rem 1.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 1.05rem;
            letter-spacing: 0.3px;
        }
        
        .pos-card-header i {
            font-size: 1.2em;
            margin-right: 0.25rem;
        }
        
        .order-item {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            transition: background 0.2s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-item:hover {
            background: var(--secondary);
        }
        
        .badge-status {
            font-size: 0.7rem;
            padding: 0.3rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-action {
            border-radius: 8px;
            font-size: 0.8rem;
            padding: 0.4rem 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        
        .btn-action i {
            font-size: 0.9em;
        }
        
        .btn-action:hover {
            transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
.scanner-container {
  border: 2px dashed #cbd5e1;
  border-radius: 12px;
  padding: 1rem;
  background: #f8f9fa;
}
#lowStockBanner {
  position: fixed;
  top: 80px;
  right: 20px;
  max-width: 400px;
  z-index: 1000;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  animation: slideIn 0.3s ease;
}
@keyframes slideIn {
  from { transform: translateX(100%); opacity: 0; }
  to { transform: translateX(0); opacity: 1; }
}
main {
  padding-bottom: 4rem !important;
  min-height: auto !important;
  display: block !important;
}
footer {
  margin-top: 0 !important;
  position: relative !important;
}
.pos-section-wrapper {
  position: relative;
  z-index: 1;
}
</style>

<script>
// Real-time clock update
function updateClock() {
  const now = new Date();
  const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
  const dateStr = now.toLocaleDateString('en-US', options);
  const timeStr = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
  
  const dateEl = document.getElementById('currentDate');
  const timeEl = document.getElementById('currentTime');
  if (dateEl) dateEl.textContent = dateStr;
  if (timeEl) timeEl.textContent = timeStr;
}

// Update clock every second
setInterval(updateClock, 1000);
window.addEventListener('load', updateClock);

// Low stock check
async function checkLow(){ try{
  const r = await fetch('../admin/low_stock_api.php'); const j = await r.json();
  const el = document.getElementById('lowStockBanner');
  if(j.count>0){ el.innerHTML = '<strong><i class="bi bi-exclamation-triangle me-2"></i>Low Stock Alert</strong><br>' + j.items.map(i=>i.name+' ('+i.onhand+' '+i.unit+')').join(', '); el.style.display='block'; }
  else { el.style.display='none'; }
}catch(e){} }
setInterval(checkLow, 15000); window.addEventListener('load', checkLow);
</script>
<div id="lowStockBanner" class="alert alert-warning" style="display:none"></div>

<!-- Hero Header -->
<div class="pos-hero">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-8">
        <h1 class="h3 fw-bold mb-2"><i class="bi bi-cash-register me-2"></i>Point of Sale</h1>
        <p class="mb-0 opacity-90">Manage orders, process payments, and quick sales</p>
      </div>
      <div class="col-lg-4 text-lg-end mt-1 mt-lg-0">
    <div class="d-flex align-items-center justify-content-end gap-3">
        <div class="text-white fw-bold" id="currentDate" style="font-size: 1rem;"><?= date('M j, Y') ?></div>
        <div class="vr bg-white" style="height: 1.5rem; opacity: 0.8;"></div>
        <div class="d-flex align-items-center">
            <i class="bi bi-clock-fill me-2 text-white" style="font-size: 1.1rem;"></i>
            <span id="currentTime" class="text-white fw-bold" style="font-size: 1.1rem;"><?= date('g:i A') ?></span>
        </div>
    </div>
</div>
      </div>
    </div>
  </div>
</div>

    </style>
</head>
<body>
    <!-- Staff Header -->
    <header class="staff-header">
        <div class="d-flex align-items-center">
            <a href="index.php" class="text-decoration-none d-flex align-items-center logo-link">
                <img src="../logo.png" alt="Paghilom Logo" class="main-logo" style="height: 90px; width: auto; margin-right: 15px;">
            </a>
            <div class="d-flex flex-column">
                <span class="fw-bold" style="font-size: 1.5rem; color: #2A5618;">Paghilom Cafe</span>
                <small class="text-muted">Point of Sale System</small>
            </div>
        </div>
        
        <div class="d-flex align-items-center gap-4">
            <div class="d-flex align-items-center text-dark">
                <i class="bi bi-person-circle me-2" style="font-size: 1.8rem; color: #2A5618;"></i>
                <div class="d-flex flex-column">
                    <span class="small text-muted">Logged in as</span>
                    <span class="fw-bold"><?php echo $_SESSION['user_name'] ?? 'Staff'; ?></span>
                </div>
            </div>
            <div class="vr" style="height: 40px;"></div>
            <a href="../logout.php" class="btn btn-danger d-flex align-items-center" style="padding: 0.5rem 1.25rem; font-size: 1rem;">
                <i class="bi bi-box-arrow-right me-2"></i>
                <span>Logout</span>
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="pos-container">
  <div class="row g-4">
    <div class="col-lg-7">
              <div class="pos-card">
        <div class="pos-card-header d-flex justify-content-between align-items-center">
          <span><i class="bi bi-list-check me-2"></i>Open Orders</span>
          <form class="d-flex gap-2" method="get">
            <input class="form-control form-control-sm" name="q" placeholder="Search name / order #" style="border-radius: 8px;">
            <button class="btn btn-sm btn-primary" style="border-radius: 8px;"><i class="bi bi-search"></i></button>
          </form>
        </div>
        <div style="max-height: 600px; overflow-y: auto;">
          <?php
          $q = trim($_GET['q'] ?? '');
          if($q!==''){ $like='%'.$q.'%'; $stmt=$mysqli->prepare('SELECT * FROM orders WHERE (customer_name LIKE ? OR id=?) AND status IN ("queued","in_progress","ready") ORDER BY id DESC LIMIT 50'); $idnum=intval($q); $stmt->bind_param('si',$like,$idnum); }
          else { $stmt=$mysqli->prepare('SELECT * FROM orders WHERE status IN ("queued","in_progress","ready") ORDER BY id DESC LIMIT 50'); }
          $stmt->execute(); $orders=$stmt->get_result();
          while($o=$orders->fetch_assoc()): ?>
            <div class="order-item">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                  <div class="mb-2">
                    <span class="badge bg-dark me-2" style="font-size: 0.9rem;">#<?=$o['id']?></span>
                    <strong><?=htmlspecialchars($o['customer_name']?:'Guest')?></strong>
                  </div>
                  <div class="d-flex gap-2 mb-2">
                    <span class="badge badge-status <?=$o['status']=='ready'?'bg-success':($o['status']=='in_progress'?'bg-warning text-dark':'bg-secondary')?>"><?=ucfirst($o['status'])?></span>
                    <span class="badge badge-status <?=$o['payment_status']=='paid'?'bg-success':'bg-warning text-dark'?>"><?=ucfirst($o['payment_status'])?></span>
                  </div>
                  <div class="small text-muted"><i class="bi bi-clock me-1"></i>Pickup: <?=$o['pickup_time']?> <i class="bi bi-shop ms-2 me-1"></i>Store <?=$o['pickup_store_id']?></div>
                </div>
                <div class="ms-3">
                  <a class="btn btn-sm btn-outline-primary btn-action mb-2" href="edit_order.php?id=<?=$o['id']?>"><i class="bi bi-pencil me-1"></i>Edit</a>
                </div>
              </div>
              <form class="d-flex gap-2 flex-wrap" method="post" action="order_update.php">
                <input type="hidden" name="id" value="<?=$o['id']?>">
                <select class="form-select form-select-sm" name="status" style="max-width: 150px; border-radius: 8px;">
                  <?php foreach(['queued','in_progress','ready','completed','cancelled'] as $s): ?><option <?=$o['status']==$s?'selected':''?>><?=$s?></option><?php endforeach; ?>
                </select>
                <select class="form-select form-select-sm" name="payment_status" style="max-width: 120px; border-radius: 8px;">
                  <?php foreach(['unpaid','paid','refunded'] as $p): ?><option <?=$o['payment_status']==$p?'selected':''?>><?=$p?></option><?php endforeach; ?>
                </select>
                <button class="btn btn-sm btn-success btn-action"><i class="bi bi-check-circle me-1"></i>Update</button>
                <a class="btn btn-sm btn-outline-secondary btn-action" target="_blank" href="receipt.php?id=<?=$o['id']?>&size=58"><i class="bi bi-printer me-1"></i>58mm</a>
                <a class="btn btn-sm btn-outline-secondary btn-action" target="_blank" href="kitchen.php?id=<?=$o['id']?>"><i class="bi bi-receipt me-1"></i>Kitchen</a>
              </form>
            </div>
          <?php endwhile; $stmt->close(); ?>
        </div>
      </div>
    </div>
    <div class="col-lg-5">
      <!-- QR Scanner -->
              <div class="pos-card mb-4">
        <div class="pos-card-header">
          <i class="bi bi-qr-code-scan me-2"></i>QR Scanner
          <small class="text-muted ms-2">Order or Voucher</small>
        </div>
        <div class="p-4">
          <!-- Manual Code Input -->
          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">Enter Code Manually</label>
            <div class="input-group">
              <input type="text" id="manualQrCode" class="form-control" placeholder="Enter order or voucher code" style="border-radius: 10px 0 0 10px;">
              <button class="btn btn-success" type="button" id="btnManualSubmit" style="border-radius: 0 10px 10px 0;">
                <i class="bi bi-arrow-right"></i>
              </button>
            </div>
            <small class="text-muted d-block mt-1">Or scan with camera below</small>
          </div>
          
          <!-- Camera Scanner -->
          <div class="scanner-container">
            <div id="posScanner" style="max-width:100%"></div>
          </div>
          <div class="alert alert-info mt-3 mb-0" style="border-radius: 10px;">
            <i class="bi bi-info-circle me-2"></i>
            <small>Auto-detects Order vs Voucher QR codes</small>
          </div>
        </div>
      </div>
      
      <!-- Quick Sale -->
      <div class="pos-card" style="margin-bottom: 0;">
        <div class="pos-card-header">
          <i class="bi bi-lightning-charge-fill me-2"></i>Quick Sale
        </div>
        <div class="p-4">
          <form method="post" action="quick_sale.php" class="row g-3">
            <input type="hidden" name="payment_method" value="cash">
            <div class="col-12">
              <label class="form-label small fw-semibold text-muted">Product</label>
              <select class="form-select" name="product_id" style="border-radius: 10px;">
                <?php $p=$mysqli->query('SELECT id,name,price FROM products ORDER BY name'); while($r=$p->fetch_assoc()): ?>
                  <option value="<?=$r['id']?>"><?=htmlspecialchars($r['name'])?> — ₱<?=number_format($r['price'],2)?></option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="col-4">
              <label class="form-label small fw-semibold text-muted">Quantity</label>
              <input class="form-control" type="number" name="qty" min="1" value="1" style="border-radius: 10px;">
            </div>
            <div class="col-8">
              <label class="form-label small fw-semibold text-muted">Customer Name</label>
              <input class="form-control" name="customer_name" placeholder="Optional" style="border-radius: 10px;">
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-success w-100 btn-action" style="padding: 0.75rem;"><i class="bi bi-check-circle me-2"></i>Place Order</button>
            </div>
            <div class="col-12">
              <a class="btn btn-outline-secondary w-100 btn-action" href="../kiosk.php"><i class="bi bi-tablet me-2"></i>Open Kiosk</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
    </main>
    <footer class="pos-footer mt-auto py-3 bg-light border-top">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <span class="text-muted"> <?= date('Y') ?> Paghilom POS System. All rights reserved.</span>
                </div>
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
<script>
(function(){
  const beep=()=>{try{const c=new (window.AudioContext||window.webkitAudioContext)();const o=c.createOscillator(),g=c.createGain();o.type='sine';o.frequency.value=880;o.connect(g);g.connect(c.destination);g.gain.setValueAtTime(0.001,c.currentTime);g.gain.exponentialRampToValueAtTime(0.1,c.currentTime+0.01);o.start();setTimeout(()=>{g.gain.exponentialRampToValueAtTime(0.001,c.currentTime+0.05);o.stop(c.currentTime+0.06)},60)}catch(e){}};
  
  // Show loading overlay
  const showLoading = (msg) => {
    let overlay = document.getElementById('scanLoadingOverlay');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.id = 'scanLoadingOverlay';
      overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.8);display:flex;align-items:center;justify-content:center;z-index:9999;color:white;font-size:1.2rem;';
      document.body.appendChild(overlay);
    }
    overlay.innerHTML = '<div style="text-align:center;"><div class="spinner-border text-light mb-3" role="status"></div><div>' + msg + '</div></div>';
    overlay.style.display = 'flex';
  };
  
  // Validate QR and redirect to payment
  const validateAndRedirect = async (qrCode) => {
    showLoading('Validating QR Code...');
    
    try {
      const response = await fetch('api/qr_validate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ code: qrCode })
      });
      
      let data;
      try {
        const text = await response.text();
        if (!text) {
          throw new Error('Empty response from server');
        }
        data = JSON.parse(text);
      } catch (e) {
        console.error('Failed to parse response:', e);
        throw new Error('Invalid response from server: ' + (e.message || 'Could not parse JSON'));
      }
      
      if (response.ok && data.success && data.redirect_url) {
        showLoading('Redirecting to payment...');
        beep();
        if(navigator.vibrate) navigator.vibrate([100, 50, 100]);
        // Immediate redirect to payment page
        setTimeout(() => {
          window.location.href = data.redirect_url;
        }, 500);
      } else {
        // Show error and hide loading
        const overlay = document.getElementById('scanLoadingOverlay');
        if (overlay) {
          const errorMsg = data.message || data.error || 'QR code not recognized';
          const statusInfo = !response.ok ? ' (HTTP ' + response.status + ')' : '';
          overlay.innerHTML = '<div style="text-align:center;max-width:400px;padding:30px;background:white;border-radius:12px;color:#dc2626;"><i class="bi bi-x-circle" style="font-size:3rem;"></i><h4 class="mt-3">Invalid QR Code' + statusInfo + '</h4><p>' + errorMsg + '</p><button class="btn btn-primary mt-3" onclick="location.reload()">Scan Again</button></div>';
          setTimeout(() => overlay.style.display = 'none', 5000);
        }
      }
    } catch (error) {
      console.error('QR validation error:', error);
      const overlay = document.getElementById('scanLoadingOverlay');
      if (overlay) {
        const errorDetails = error.message || 'Unknown error';
        overlay.innerHTML = '<div style="text-align:center;max-width:400px;padding:30px;background:white;border-radius:12px;color:#dc2626;"><i class="bi bi-exclamation-triangle" style="font-size:3rem;"></i><h4 class="mt-3">Connection Error</h4><p>' + errorDetails + '</p><small class="text-muted d-block mt-2">Check browser console (F12) for details</small><button class="btn btn-primary mt-3" onclick="location.reload()">Retry</button></div>';
        setTimeout(() => overlay.style.display = 'none', 8000);
      }
    }
  };
  
  const onScanSuccess = (decodedText) => {
    validateAndRedirect(decodedText);
  };
  
  // Manual code input handler
  const manualInput = document.getElementById('manualQrCode');
  const btnManualSubmit = document.getElementById('btnManualSubmit');
  
  const handleManualSubmit = () => {
    const code = manualInput.value.trim();
    if (code) {
      validateAndRedirect(code);
    }
  };
  
  if (btnManualSubmit) {
    btnManualSubmit.addEventListener('click', handleManualSubmit);
  }
  
  if (manualInput) {
    manualInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        handleManualSubmit();
      }
    });
  }
  
  try{ 
    const scanner = new Html5QrcodeScanner('posScanner',{fps:10,qrbox:200}); 
    scanner.render(onScanSuccess);
  }catch(e){
    console.error('Scanner init error:', e);
  }
})();
</script>
<?php include __DIR__.'/../partials/footer.php'; ?>
