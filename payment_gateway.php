<?php
require __DIR__.'/config.php';
require_login();

// Check if there's a pending payment
if(!isset($_SESSION['pending_payment'])) {
    header('Location: cart.php');
    exit;
}

$payment_data = $_SESSION['pending_payment'];
$order_id = $payment_data['order_id'];
$order_code = $payment_data['order_code'];
$amount = $payment_data['amount'];
$customer_name = $payment_data['customer_name'];

// Get order details
$order_query = $mysqli->prepare("SELECT * FROM orders WHERE id = ?");
$order_query->bind_param('i', $order_id);
$order_query->execute();
$order = $order_query->get_result()->fetch_assoc();
$order_query->close();

if(!$order) {
    header('Location: cart.php');
    exit;
}

// Handle payment submission
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment'])) {
    csrf_check();
    
    $payment_method = $_POST['payment_method'] ?? '';
    $reference_number = trim($_POST['reference_number'] ?? '');
    
    if(empty($reference_number)) {
        $error = 'Please enter your payment reference number';
    } else {
        // Update order payment status
        $update = $mysqli->prepare("
            UPDATE orders 
            SET payment_status = 'successful', 
                status = 'paid',
                paid_at = NOW(),
                payment_method = ?,
                payment_reference = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $update->bind_param('ssi', $payment_method, $reference_number, $order_id);
        
        if($update->execute()) {
            // After successful payment, clear the ordered items from cart/session
            // Fetch item product_ids for this order
            $pidRes = $mysqli->prepare('SELECT product_id FROM order_items WHERE order_id=?');
            $pidRes->bind_param('i',$order_id); $pidRes->execute();
            $pids = [];
            $rs = $pidRes->get_result();
            while($row = $rs->fetch_assoc()){ $pids[] = (int)$row['product_id']; }
            $pidRes->close();
            if($pids){
              foreach($pids as $pid){ unset($_SESSION['cart'][$pid]); }
              // Remove from persistent cart as well
              if(isset($_SESSION['user']['id'])){
                $uid=(int)$_SESSION['user']['id'];
                $types = str_repeat('i', count($pids)+1);
                $params = array_merge([$uid], $pids);
                $stmtDel = $mysqli->prepare('DELETE FROM user_carts WHERE user_id=? AND product_id IN ('.implode(',', array_fill(0,count($pids),'?')).')');
                $stmtDel->bind_param($types, ...$params);
                $stmtDel->execute();
                $stmtDel->close();
              }
            }

            // Clear pending payment
            unset($_SESSION['pending_payment']);
            
            // Redirect to success page
            header('Location: payment_success.php?order=' . $order_code);
            exit;
        } else {
            $error = 'Payment update failed. Please contact support.';
        }
        $update->close();
    }
}

include __DIR__.'/partials/header.php';
?>

<style>
.payment-card {
    border: 2px solid var(--gray-300);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    cursor: pointer;
    transition: all var(--transition-base);
}

.payment-card:hover {
    border-color: var(--primary);
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.payment-card.selected {
    border-color: var(--primary);
    background-color: rgba(42, 86, 24, 0.05);
}

.qr-code-box {
    background: white;
    padding: 2rem;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    text-align: center;
}
</style>

<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="text-center mb-4">
                <h1 class="h3 mb-2">Complete Your Payment</h1>
                <p class="text-muted">Choose your preferred online payment method</p>
            </div>
            
            <!-- Order Summary Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Order Code:</span>
                        <strong><?= htmlspecialchars($order_code) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Customer:</span>
                        <strong><?= htmlspecialchars($customer_name) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                        <span class="text-muted">Order Date & Time:</span>
                        <strong><?= date('M d, Y g:i A', strtotime($order['created_at'])) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="h5 mb-0">Total Amount:</span>
                        <span class="h4 mb-0 text-success">₱<?= number_format($amount, 2) ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Payment Form -->
            <form method="post" id="paymentForm">
                <?= csrf_field() ?>
                
                <!-- Payment Method Selection -->
                <div class="mb-4">
                    <h5 class="mb-3">Select Payment Method</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="payment-card" onclick="selectPayment('gcash')">
                                <input type="radio" name="payment_method" value="gcash" id="gcash" required hidden>
                                <label for="gcash" class="d-flex align-items-center mb-0" style="cursor: pointer;">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5c/Gcash_logo.svg/200px-Gcash_logo.svg.png" 
                                         alt="GCash" style="height: 40px; margin-right: 1rem;">
                                    <div>
                                        <strong>GCash</strong>
                                        <small class="d-block text-muted">Instant transfer</small>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="payment-card" onclick="selectPayment('paymaya')">
                                <input type="radio" name="payment_method" value="paymaya" id="paymaya" required hidden>
                                <label for="paymaya" class="d-flex align-items-center mb-0" style="cursor: pointer;">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/e/e9/Maya_%28payments%29_logo.svg/200px-Maya_%28payments%29_logo.svg.png" 
                                         alt="PayMaya" style="height: 40px; margin-right: 1rem;">
                                    <div>
                                        <strong>PayMaya</strong>
                                        <small class="d-block text-muted">Fast & secure</small>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Instructions -->
                <div id="payment-instructions" class="card mb-4" style="display: none;">
                    <div class="card-body">
                        <h5 class="mb-3"><i class="fas fa-info-circle me-2 text-info"></i>Payment Instructions</h5>
                        
                        <div id="gcash-instructions" style="display: none;">
                            <div class="qr-code-box mb-3">
                                <h6 class="mb-3">Scan GCash QR Code</h6>
                                <img src="assets/img/gcash-qr-placeholder.png" alt="GCash QR" style="max-width: 250px;" 
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                <div style="display: none;" class="p-4 bg-light rounded">
                                    <i class="fas fa-qrcode fa-5x text-muted mb-3"></i>
                                    <p class="mb-0 text-muted">GCash QR Code</p>
                                </div>
                            </div>
                            <div class="alert alert-info">
                                <strong>Steps:</strong>
                                <ol class="mb-0 mt-2">
                                    <li>Open your GCash app</li>
                                    <li>Scan the QR code above or send to: <strong>0928-719-7722</strong></li>
                                    <li>Enter amount: <strong>₱<?= number_format($amount, 2) ?></strong></li>
                                    <li>Complete payment</li>
                                    <li>Enter the reference number below</li>
                                </ol>
                            </div>
                        </div>
                        
                        <div id="paymaya-instructions" style="display: none;">
                            <div class="qr-code-box mb-3">
                                <h6 class="mb-3">Scan PayMaya QR Code</h6>
                                <img src="assets/img/paymaya-qr-placeholder.png" alt="PayMaya QR" style="max-width: 250px;"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                <div style="display: none;" class="p-4 bg-light rounded">
                                    <i class="fas fa-qrcode fa-5x text-muted mb-3"></i>
                                    <p class="mb-0 text-muted">PayMaya QR Code</p>
                                </div>
                            </div>
                            <div class="alert alert-info">
                                <strong>Steps:</strong>
                                <ol class="mb-0 mt-2">
                                    <li>Open your Maya app</li>
                                    <li>Scan the QR code above or send to: <strong>0928-719-7722</strong></li>
                                    <li>Enter amount: <strong>₱<?= number_format($amount, 2) ?></strong></li>
                                    <li>Complete payment</li>
                                    <li>Enter the reference number below</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Reference Number Input -->
                <div id="reference-input" class="card mb-4" style="display: none;">
                    <div class="card-body">
                        <h5 class="mb-3">Enter Payment Reference Number</h5>
                        <div class="mb-3">
                            <label class="form-label">Reference Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg" name="reference_number" 
                                   placeholder="Enter 13-digit reference number" 
                                   pattern="[0-9]{13}" 
                                   maxlength="13"
                                   required>
                            <small class="form-text text-muted">You'll find this in your payment receipt</small>
                        </div>
                    </div>
                </div>
                
                <?php if(isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>
                
                <!-- Submit Button -->
                <div class="d-grid gap-2">
                    <button type="submit" name="submit_payment" class="btn btn-success btn-lg" id="submitBtn" disabled>
                        <i class="fas fa-check-circle me-2"></i>Confirm Payment
                    </button>
                    <a href="checkout.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Checkout
                    </a>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
function selectPayment(method) {
    // Remove all selected states
    document.querySelectorAll('.payment-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Select the payment method
    const radio = document.getElementById(method);
    radio.checked = true;
    radio.closest('.payment-card').classList.add('selected');
    
    // Show instructions
    document.getElementById('payment-instructions').style.display = 'block';
    document.getElementById('reference-input').style.display = 'block';
    
    // Hide all instructions first
    document.getElementById('gcash-instructions').style.display = 'none';
    document.getElementById('paymaya-instructions').style.display = 'none';
    
    // Show selected method instructions
    document.getElementById(method + '-instructions').style.display = 'block';
    
    // Enable submit button
    document.getElementById('submitBtn').disabled = false;
}

// Pre-select if already checked
window.addEventListener('DOMContentLoaded', function() {
    const checked = document.querySelector('input[name="payment_method"]:checked');
    if(checked) {
        selectPayment(checked.value);
    }
});
</script>

<?php include __DIR__.'/partials/footer.php'; ?>
