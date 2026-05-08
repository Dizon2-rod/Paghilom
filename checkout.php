<?php 
require __DIR__.'/config.php'; 
require_login(); 

// Only customer accounts may place online orders (no admin/staff checkout)
// Exception: Allow staff access when coming from kiosk
if (!is_customer() && !isset($_SESSION['from_kiosk'])) {
    header('Location: ' . APP_URL . 'index.php');
    exit;
}

if(!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// Support "Buy Now" flow: add item to cart and preselect it
$selected = array_map('intval', $_POST['selected'] ?? []);
$qtyOverride = [];
if (empty($selected) && isset($_GET['buy'])) {
    $pid = max(0, (int)$_GET['buy']);
    $qty = max(1, (int)($_GET['qty'] ?? 1));
    if ($pid > 0) {
        // Put into cart and remember requested qty to avoid race with session writes
        $_SESSION['cart'][$pid] = ($_SESSION['cart'][$pid] ?? 0) + $qty;
        $qtyOverride[$pid] = $qty;
        $selected = [$pid];
    }
}

// If cart is empty, go to menu
if(empty($_SESSION['cart'])) { 
    header('Location: ' . APP_URL . 'menu.php?error=cart_empty');
    exit;
}

// Determine selected items (if provided)
$cart_ids = array_keys($_SESSION['cart']);
$ids = !empty($selected) ? array_values(array_intersect($cart_ids, $selected)) : [];

// Fallback for Buy Now: if still empty, force-select the buy item
if (empty($ids) && isset($_GET['buy'])) {
    $ids = [max(0, (int)$_GET['buy'])];
}

$ignore_stock = isset($_GET['buy']) || isset($_GET['ignore_stock']);

$items = []; 
$subtotal = 0;
$cart_errors = [];

if (!empty($ids)) {
    $in = implode(',', array_fill(0, count($ids), '?')); 
    $types = str_repeat('i', count($ids));
    
    // First, check if products exist and are active
    $stmt = $mysqli->prepare("SELECT id, name, price, stock_qty, is_active FROM products WHERE id IN ($in)");
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $res = $stmt->get_result();
    $found_products = [];
    
    while($p = $res->fetch_assoc()) {
        $found_products[$p['id']] = $p;
    }
    $stmt->close();
    
// Process each selected cart item only
foreach ($ids as $product_id) {
$qty = (int)($qtyOverride[$product_id] ?? ($_SESSION['cart'][$product_id] ?? 0));
if ($qty <= 0) { continue; }
    
    // Check if product exists
    if (!isset($found_products[$product_id])) {
        $cart_errors[] = "Product ID #{$product_id} no longer exists and was removed from selection";
        unset($_SESSION['cart'][$product_id]);
        continue;
    }
    
    $p = $found_products[$product_id];
        
// Check if product is active
if (!$p['is_active']) {
    $cart_errors[] = $p['name'] . ' is currently unavailable and was removed from cart';
    unset($_SESSION['cart'][$product_id]);
    continue;
}
        
// Stock handling (allow Buy Now to proceed even if stock is low)
if (!$ignore_stock) {
    if ($p['stock_qty'] < $qty) {
        if ($p['stock_qty'] == 0) {
            $cart_errors[] = $p['name'] . ' is out of stock and was removed from cart';
            unset($_SESSION['cart'][$product_id]);
            continue;
        } else {
            $cart_errors[] = $p['name'] . ' only has ' . $p['stock_qty'] . ' in stock. Quantity adjusted.';
            $_SESSION['cart'][$product_id] = $p['stock_qty'];
            $qty = $p['stock_qty'];
        }
    }
}
        
// Add valid item
$line = $p['price'] * $qty;
$subtotal += $line;
$items[] = $p + ['qty' => $qty, 'line' => $line];
    }
}

// Fetch stores
$stores = $mysqli->query('SELECT id, name, city, address FROM stores WHERE is_active = 1 ORDER BY city, name');
$store_opts = $stores ? $stores->fetch_all(MYSQLI_ASSOC) : [];
$default_store_id = (int)($_POST['pickup_store_id'] ?? ($store_opts[0]['id'] ?? 0));
# Fetch allowed add-ons per product
$allowed_addons=[];
if($ids){
  $in = implode(',', array_fill(0,count($ids),'?')); $types=str_repeat('i',count($ids));
  $st=$mysqli->prepare("SELECT pa.product_id, a.id aid, a.name, a.price FROM product_addons pa JOIN addons a ON a.id=pa.addon_id WHERE pa.product_id IN ($in) ORDER BY a.name");
  $st->bind_param($types, ...$ids); $st->execute(); $rs=$st->get_result();
  while($r=$rs->fetch_assoc()){ $allowed_addons[$r['product_id']][]=$r; }
  $st->close();
}
$msg = $err = '';
$errors = [];

// Never show empty-cart UI: redirect before any output
if (empty($items)) {
    if (empty($_SESSION['cart'])) {
        header('Location: ' . APP_URL . 'menu.php');
        exit;
    } else {
        header('Location: ' . APP_URL . 'cart.php?error=no_selection');
        exit;
    }
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'place')) {
    // Sanitize input
    $cust_name = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $pickup_store_id = (int)($_POST['pickup_store_id'] ?? 0);
    $payment_method = trim($_POST['payment_method'] ?? 'cash');
    $notes = trim($_POST['notes'] ?? '');
    
    // Validate inputs
    if (empty($cust_name)) {
        $errors[] = 'Customer name is required';
    } elseif (strlen($cust_name) < 2) {
        $errors[] = 'Customer name must be at least 2 characters';
    }
    
    if (!empty($phone) && !preg_match('/^09\d{9}$/', preg_replace('/[^0-9]/', '', $phone))) {
        $errors[] = 'Invalid Philippine phone number format (09XXXXXXXXX)';
    }
    
    if (!$pickup_store_id) {
        $errors[] = 'Please select a pickup store';
    }
    
    // Ensure there are items selected to place order
    if (empty($items)) {
        $errors[] = 'Please select items in your cart to checkout.';
    }
    
    if (empty($errors)) {
        $mysqli->begin_transaction();
        try {
            // Create order (self-pickup; immediate flow, no schedule)
            $ts = date('Y-m-d H:i:s');
            // Unified statuses
            $otype = 'pickup';
            $uid = $_SESSION['user']['id'];
            $code = 'ORD' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
            $total = (float)$subtotal;
            $status = ($payment_method === 'online') ? 'paid' : 'unpaid';
            $payment_status = ($payment_method === 'online') ? 'pending' : 'pending';
            
            $stmt = $mysqli->prepare(
                'INSERT INTO orders(code, user_id, order_type, pickup_store_id, pickup_time, status, payment_status, customer_name, phone, notes, total_amount, created_at) 
                 VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->bind_param(
                'sisissssssd',
                $code, $uid, $otype, $pickup_store_id, $ts, $status, $payment_status, $cust_name, $phone, $notes, $total
            );
            $stmt->execute();
            $order_id = $stmt->insert_id;
            $stmt->close();
      // Insert items + add-ons + ingredient deductions
      $st=$mysqli->prepare("SELECT id,name,price FROM products WHERE id IN ($in)"); $st->bind_param($types,...$ids); $st->execute(); $rs=$st->get_result();
      while($p=$rs->fetch_assoc()){
        $qty=(int)$_SESSION['cart'][$p['id']]; $price=$p['price']; $line=$price*$qty;
        $stmt2=$mysqli->prepare('INSERT INTO order_items(order_id,product_id,name,qty,price,subtotal) VALUES(?,?,?,?,?,?)'); $stmt2->bind_param('isisdd',$order_id,$p['id'],$p['name'],$qty,$price,$line); $stmt2->execute(); $oi=$stmt2->insert_id; $stmt2->close();
        // base recipe ingredients
        $r=$mysqli->prepare('SELECT ingredient_id, qty_per_unit FROM product_recipes WHERE product_id=?'); $r->bind_param('i',$p['id']); $r->execute(); $rr=$r->get_result();
        while($ri=$rr->fetch_assoc()){ $change = -1 * $ri['qty_per_unit'] * $qty; $im=$mysqli->prepare('INSERT INTO ingredient_movements(ingredient_id,change_qty,reason,ref_id) VALUES(?,?,"order",?)'); $im->bind_param('iii',$ri['ingredient_id'],$change,$order_id); $im->execute(); $im->close(); }
        $r->close();
        // add-ons posted: addon_qty[productId][addonId]
        foreach(($_POST['addon_qty'][$p['id']] ?? []) as $aid=>$aqty){
          $aqty = max(0, (int)$aqty);
          if($aqty>0){
            // price lookup
            $a=$mysqli->prepare('SELECT id,price,name FROM addons WHERE id=?'); $a->bind_param('i',$aid); $a->execute(); $ad=$a->get_result()->fetch_assoc(); $a->close();
            $oa=$mysqli->prepare('INSERT INTO order_item_options(order_item_id,addon_name,price) VALUES(?,?,?)'); $oa->bind_param('isd',$oi,$ad['name'],$ad['price']); $oa->execute(); $oa->close();
            // ingredient map by name
            $map=['Espresso'=>1,'Syrup'=>3,'Sauce'=>4,'Milk'=>2];
            foreach($map as $n=>$ing){
              if(stripos($ad['name'],$n)!==false){
                $chg = -1 * $aqty; $im=$mysqli->prepare('INSERT INTO ingredient_movements(ingredient_id,change_qty,reason,ref_id) VALUES(?,?,"order_addon",?)'); $im->bind_param('iii',$ing,$chg,$order_id); $im->execute(); $im->close();
                break;
              }
            }
          }
        }
      }
      $st->close();
            
            // Do NOT award points here; only after payment is confirmed.
            
            // Commit transaction
            $mysqli->commit();
            
            // Handle payment method
            if($payment_method === 'online') {
                // Clear kiosk session flag before redirecting to payment
                if (isset($_SESSION['from_kiosk'])) {
                    unset($_SESSION['from_kiosk']);
                }
                // Redirect to payment gateway
                $_SESSION['pending_payment'] = [
                    'order_id' => $order_id,
                    'order_code' => $code,
                    'amount' => $total,
                    'customer_name' => $cust_name
                ];
                header('Location: payment_gateway.php');
                exit;
            } else {
                // For onsite cash, award points immediately since payment is confirmed
                if (!empty($order_id) && !empty($uid)) {
                    require_once __DIR__ . '/includes/points.php';
                    // Check if points already awarded for this order
                    $chk = $mysqli->prepare("SELECT id FROM point_transactions WHERE user_id = ? AND ref_type = 'order' AND ref_id = ? LIMIT 1");
                    $chk->bind_param('ii', $uid, $order_id);
                    $chk->execute();
                    $exists = $chk->get_result()->fetch_assoc();
                    $chk->close();
                    
                    if (!$exists) {
                        // Calculate points: ₱10 = 5 points (₱2 = 1 point)
                        $points_earned = (int)floor($total / 2);
                        if ($points_earned > 0) {
                            $ins = $mysqli->prepare("INSERT INTO point_transactions (user_id, points, type, ref_type, ref_id, note, created_at) VALUES (?, ?, 'earn', 'order', ?, 'Points earned from order payment', NOW())");
                            $ins->bind_param('iii', $uid, $points_earned, $order_id);
                            $ins->execute();
                            $ins->close();
                            
                            // Update orders table if column exists
                            if ($mysqli->query("SHOW COLUMNS FROM orders LIKE 'points_awarded'")) {
                                $upd = $mysqli->prepare("UPDATE orders SET points_awarded = COALESCE(points_awarded,0) + ? WHERE id = ?");
                                $upd->bind_param('ii', $points_earned, $order_id);
                                $upd->execute();
                                $upd->close();
                            }
                        }
                    }
                }
                
                // For onsite cash, clear selected items now (checkout complete at cashier flow)
                if (!empty($ids)) {
                    foreach ($ids as $pid) { unset($_SESSION['cart'][$pid]); }
                    if (isset($_SESSION['user']['id'])) {
                      $uid = (int)$_SESSION['user']['id'];
                      $in = implode(',', array_fill(0, count($ids), '?'));
                      $types = str_repeat('i', count($ids)+1);
                      $params = array_merge([$uid], $ids);
                      $placeholders = 'user_id=? AND product_id IN ('.implode(',', array_fill(0,count($ids),'?')).')';
                      $stmtDel = $mysqli->prepare('DELETE FROM user_carts WHERE '.$placeholders);
                      $stmtDel->bind_param($types, ...$params);
                      $stmtDel->execute();
                      $stmtDel->close();
                    }
                }
                // Clear kiosk session flag after successful checkout
                if (isset($_SESSION['from_kiosk'])) {
                    unset($_SESSION['from_kiosk']);
                }
                // For onsite cash, take user straight to the receipt page
                header('Location: payment_success.php?order=' . urlencode($code));
                exit;
            }
            
        } catch (Exception $e) {
            $mysqli->rollback();
            $errors[] = 'An error occurred during checkout: ' . $e->getMessage();
            error_log('Checkout error: ' . $e->getMessage());
        }
    }
}
include __DIR__.'/partials/header.php';
?>

<style>
/***** Modern Checkout Styling *****/
.checkout-steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; margin-bottom: 1rem; }
.checkout-steps .step { background: #f8faf9; border: 1px solid rgba(42,86,24,.12); color:#2A5618; border-radius: 12px; padding: .75rem; display:flex; align-items:center; gap:.5rem; font-weight:600; text-decoration:none; }
.checkout-steps .step i { color:#2A5618; }
.checkout-steps .step.active { background:#e9f5ec; border-color:#2A5618; box-shadow:0 0 0 2px rgba(42,86,24,.1) inset; }
.card-elevated { border: 1px solid rgba(16,24,40,0.08); border-radius: 16px; box-shadow: 0 6px 24px rgba(16,24,40,0.08); }
.badge-soft { background: rgba(42,86,24,.08); color:#2A5618; border:1px solid rgba(42,86,24,.15); font-weight:600; }
@media (max-width: 991px){ .sticky-top { position: static !important; } }
</style>

<section class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
          <h1 class="h3 mb-1" style="letter-spacing:-.01em;">Checkout</h1>
          <nav class="checkout-steps" aria-label="Checkout steps">
            <a href="#step-review" class="step" data-step="review"><i class="bi bi-cart-check"></i> Review</a>
            <a href="#step-schedule" class="step" data-step="schedule"><i class="bi bi-calendar-check"></i> Schedule</a>
            <a href="#step-payment" class="step" data-step="payment"><i class="bi bi-cash-coin"></i> Payment</a>
          </nav>
        </div>
        <a href="<?= APP_URL ?>menu.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Menu</a>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="bi bi-exclamation-triangle me-2"></i><strong>Fix the following:</strong>
          <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?>
          </ul>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($msg) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>


      <?php if (!$msg && !empty($items)): ?>
      <form method="post" class="needs-validation" novalidate>
        <input type="hidden" name="action" value="place">
        <?php foreach($ids as $hid): ?><input type="hidden" name="selected[]" value="<?= (int)$hid ?>"><?php endforeach; ?>
        <div class="row g-4">
          <div class="col-lg-7">
            <div id="step-review" class="card card-elevated">
              <div class="card-header bg-white d-flex align-items-center justify-content-between">
                <h5 class="mb-0"><i class="bi bi-cart3"></i> Order Summary</h5>
                <span class="badge badge-soft">Items: <?= count($items) ?></span>
              </div>
              <div class="card-body">
                <?php foreach($items as $it): ?>
                  <div class="mb-3 pb-2 border-bottom">
                    <div class="d-flex justify-content-between flex-wrap">
                      <strong><?=htmlspecialchars($it['name'])?></strong>
                      <span>Qty <?=$it['qty']?> • ₱<?=number_format($it['line'],2)?></span>
                    </div>
                    <?php if(!empty($allowed_addons[$it['id']])): ?>
                      <div class="row g-2 mt-2">
                        <?php foreach($allowed_addons[$it['id']] as $ad): ?>
                          <div class="col-6">
                            <label class="form-label small"><?=$ad['name']?> (+₱<?=number_format($ad['price'],2)?> each)</label>
                            <input class="form-control form-control-sm" type="number" name="addon_qty[<?=$it['id']?>][<?=$ad['aid']?>]" min="0" value="0">
                          </div>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
                <div class="d-flex justify-content-between"><span class="text-muted">Subtotal:</span><strong>₱<?=number_format($subtotal,2)?></strong></div>
              </div>
            </div>
          </div>
          <div class="col-lg-5">
            <div id="step-schedule" class="card card-elevated sticky-top" style="top:96px;">
              <div class="card-header bg-white"><h5 class="mb-0"><i class="bi bi-bag-check"></i> Pickup Details</h5></div>
              <div class="card-body">
                <div class="mb-3">
                  <label class="form-label fw-semibold">Customer Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="customer_name" placeholder="Enter your full name" required value="<?= e($_SESSION['user']['name'] ?? '') ?>">
                  <div class="invalid-feedback">Please enter your name</div>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Phone Number <small class="text-muted">(Optional)</small></label>
                  <input type="tel" class="form-control" name="phone" placeholder="09XXXXXXXXX" pattern="^09\d{9}$">
                  <small class="form-text text-muted">Format: 09XXXXXXXXX</small>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Pickup Store <span class="text-danger">*</span></label>
                  <select class="form-select" name="pickup_store_id" id="pickup_store_id" required>
                    <?php foreach($store_opts as $s): ?>
                      <option value="<?= $s['id'] ?>" data-address="<?= htmlspecialchars($s['address']) ?>" <?= ($s['id']==$default_store_id?'selected':'') ?>><?= htmlspecialchars($s['name']) ?> - <?= htmlspecialchars($s['city']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div id="step-payment" class="mb-3">
                  <label class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                  <div class="payment-options">
                    <div class="form-check mb-2 p-3 border rounded-3">
                      <input class="form-check-input" type="radio" name="payment_method" id="pay_cashier" value="cash" checked required>
                      <label class="form-check-label w-100" for="pay_cashier"><i class="fas fa-cash-register me-2 text-success"></i>Pay at Cashier</label>
                    </div>
                    <div class="form-check p-3 border rounded-3">
                      <input class="form-check-input" type="radio" name="payment_method" id="pay_online" value="online" required>
                      <label class="form-check-label w-100" for="pay_online"><i class="fas fa-credit-card me-2 text-primary"></i>Pay Online</label>
                    </div>
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Order Notes <small class="text-muted">(Optional)</small></label>
                  <textarea class="form-control" name="notes" rows="2" placeholder="Any special instructions?"></textarea>
                </div>
                <div class="d-grid gap-2">
                  <button type="submit" class="btn btn-success btn-lg"><i class="bi bi-check-circle"></i> Place Order</button>
                  <small class="text-muted text-center" id="payment-note"><i class="bi bi-info-circle"></i> Please pay at the counter when picking up</small>
                </div>
                <div class="mt-4 p-3 bg-light rounded-3">
                  <div class="d-flex justify-content-between mb-2"><span class="text-muted">Subtotal:</span><span class="fw-semibold">₱<?= number_format($subtotal, 2) ?></span></div>
                  <div class="d-flex justify-content-between border-top pt-2"><span class="fw-bold">Total:</span><span class="fw-bold text-success fs-5">₱<?= number_format($subtotal, 2) ?></span></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
      <?php endif; ?>
    </div>
  </div>
</section>

<script>

// Form validation
(function() {
  'use strict';
  const forms = document.querySelectorAll('.needs-validation');
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', event => {
      if (!form.checkValidity()) { event.preventDefault(); event.stopPropagation(); }
      form.classList.add('was-validated');
    }, false);
  });
})();

// Payment method note
const payCashier = document.getElementById('pay_cashier');
const payOnline = document.getElementById('pay_online');
const paymentNote = document.getElementById('payment-note');
if(payCashier && payOnline && paymentNote){
  payCashier.addEventListener('change',()=>{ if(payCashier.checked) paymentNote.innerHTML='<i class="bi bi-info-circle"></i> Please pay at the counter when picking up';});
  payOnline.addEventListener('change',()=>{ if(payOnline.checked) paymentNote.innerHTML='<i class="bi bi-credit-card"></i> You will be redirected to payment gateway';});
}

// Checkout step navigation
(function(){
  const steps = document.querySelectorAll('.checkout-steps .step');
  const targets = {
    review: document.getElementById('step-review'),
    schedule: document.getElementById('step-schedule'),
    payment: document.getElementById('step-payment')
  };
  function setActive(key){ steps.forEach(s=>s.classList.toggle('active', s.dataset.step===key)); }
  steps.forEach(a=>{
    a.addEventListener('click', (e)=>{
      e.preventDefault();
      const key = a.dataset.step;
      const el = targets[key];
      if(el){ el.scrollIntoView({behavior:'smooth', block:'start'}); }
      setActive(key);
    });
  });
  setActive('review');
  // Observe sections to update active state on scroll
  try{
    const io = new IntersectionObserver((entries)=>{
      const visible = entries.filter(en=>en.isIntersecting).sort((a,b)=>a.target.offsetTop-b.target.offsetTop);
      if(visible[0]){ const id = visible[0].target.id.replace('step-',''); setActive(id); }
    }, { threshold: 0.3 });
    Object.values(targets).forEach(el=>{ if(el) io.observe(el); });
  }catch(err){ /* noop for older browsers */ }
})();
</script>
<?php include __DIR__.'/partials/footer.php'; ?>
