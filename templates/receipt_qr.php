<?php
/**
 * Universal Receipt Template with QR Code
 * Works for both orders and rewards
 * Includes high-quality, scannable QR code
 */

// Required parameters:
// $type: 'order' or 'reward'
// $transaction_id: Order ID or Reward ID
// $qr_code: The generated QR code string
// $transaction_data: Array with transaction details

if (!isset($type, $transaction_id, $qr_code, $transaction_data)) {
    die('Missing required receipt parameters');
}

require_once __DIR__ . '/../includes/qr_helper.php';

// Generate high-quality QR image
$qr_image = generate_qr_code_image($qr_code, 300);

// Get site info
$site_name = function_exists('get_setting') ? get_setting('site_name', 'Paghilom Café') : 'Paghilom Café';
$site_address = function_exists('get_setting') ? get_setting('address', 'Sta. Cruz, Laguna') : 'Sta. Cruz, Laguna';
$site_phone = function_exists('get_setting') ? get_setting('contact_phone', '0928 719 7722') : '0928 719 7722';

// Format date
$date = date('F j, Y g:i A');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $type === 'order' ? 'Order' : 'Reward' ?> Receipt - <?= htmlspecialchars($site_name) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .receipt-container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .receipt-header {
            background: linear-gradient(135deg, #2A5618 0%, #1e3d10 100%);
            color: white;
            padding: 24px;
            text-align: center;
        }
        .receipt-header h1 {
            font-size: 1.5rem;
            margin-bottom: 4px;
        }
        .receipt-header p {
            font-size: 0.85rem;
            opacity: 0.9;
            margin: 2px 0;
        }
        .receipt-body {
            padding: 24px;
        }
        .section-title {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 12px;
            padding-bottom: 6px;
            border-bottom: 2px solid #e0e0e0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 0.9rem;
        }
        .info-label {
            color: #666;
        }
        .info-value {
            font-weight: 600;
            color: #222;
        }
        .qr-section {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 3px dashed #2A5618;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            margin: 24px 0;
        }
        .qr-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #2A5618;
            margin-bottom: 12px;
        }
        .qr-code-image {
            width: 250px;
            height: 250px;
            margin: 12px auto;
            background: white;
            padding: 12px;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
        }
        .qr-code-text {
            font-family: 'Courier New', monospace;
            font-size: 1rem;
            font-weight: 700;
            color: #2A5618;
            margin: 12px 0;
            letter-spacing: 1px;
        }
        .qr-instructions {
            font-size: 0.85rem;
            color: #666;
            line-height: 1.5;
            margin-top: 12px;
        }
        .qr-instructions strong {
            color: #2A5618;
        }
        .total-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 1.2rem;
            font-weight: 700;
            color: #2A5618;
        }
        .receipt-footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-top: 2px dashed #e0e0e0;
        }
        .footer-text {
            font-size: 0.8rem;
            color: #666;
            margin: 4px 0;
        }
        .print-button {
            background: #2A5618;
            color: white;
            border: none;
            padding: 12px 32px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin: 16px auto;
            display: block;
        }
        .print-button:hover {
            background: #1e3d10;
        }
        @media print {
            body { background: white; padding: 0; }
            .receipt-container { box-shadow: none; max-width: 100%; }
            .print-button { display: none; }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Header -->
        <div class="receipt-header">
            <h1><?= htmlspecialchars($site_name) ?></h1>
            <p><?= htmlspecialchars($site_address) ?></p>
            <p><?= htmlspecialchars($site_phone) ?></p>
            <p style="margin-top: 8px; font-size: 0.75rem; opacity: 0.8;"><?= $date ?></p>
        </div>

        <!-- Body -->
        <div class="receipt-body">
            <!-- Transaction Info -->
            <div class="section-title">
                <?= $type === 'order' ? '📋 Order Details' : '🎁 Reward Details' ?>
            </div>
            
            <div class="info-row">
                <span class="info-label"><?= $type === 'order' ? 'Order ID' : 'Reward ID' ?>:</span>
                <span class="info-value">#<?= $transaction_id ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Reference Code:</span>
                <span class="info-value"><?= htmlspecialchars($qr_code) ?></span>
            </div>
            
            <?php if (isset($transaction_data['customer_name'])): ?>
            <div class="info-row">
                <span class="info-label">Customer:</span>
                <span class="info-value"><?= htmlspecialchars($transaction_data['customer_name']) ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (isset($transaction_data['status'])): ?>
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="info-value" style="text-transform: capitalize;">
                    <?= htmlspecialchars($transaction_data['status']) ?>
                </span>
            </div>
            <?php endif; ?>

            <!-- Amount Section (for orders) -->
            <?php if ($type === 'order' && isset($transaction_data['total_amount'])): ?>
            <div class="total-section">
                <div class="total-row">
                    <span>Total Amount:</span>
                    <span>₱<?= number_format($transaction_data['total_amount'], 2) ?></span>
                </div>
            </div>
            <?php endif; ?>

            <!-- QR Code Section -->
            <div class="qr-section">
                <div class="qr-title">
                    🎫 Scan to <?= $type === 'order' ? 'Pay' : 'Redeem' ?>
                </div>
                
                <!-- Timer Badge -->
                <div id="qr-timer" class="mb-2" style="padding: 10px; border-radius: 8px; font-weight: 700; font-size: 1.1rem;">
                    <i class="fas fa-clock"></i> <span id="timer-text">Loading...</span>
                </div>
                
                <img src="<?= htmlspecialchars($qr_image) ?>" 
                     alt="QR Code" 
                     class="qr-code-image">
                
                <div class="qr-code-text"><?= htmlspecialchars($qr_code) ?></div>
                
                <div class="qr-instructions">
                    <strong>How to use:</strong><br>
                    1. Show this QR code at the <strong>POS</strong> or <strong>Kiosk</strong><br>
                    2. Scanner will automatically detect it<br>
                    3. Complete your <?= $type === 'order' ? 'payment' : 'redemption' ?><br>
                    <br>
                    <em>Valid for <?= $type === 'order' ? '3 hours' : '30 minutes' ?> from creation</em>
                </div>
            </div>

            <!-- Items List (for orders) -->
            <?php if ($type === 'order' && isset($transaction_data['items'])): ?>
            <div class="section-title">🛒 Order Items</div>
            <?php foreach ($transaction_data['items'] as $item): ?>
            <div class="info-row">
                <span class="info-label">
                    <?= htmlspecialchars($item['name']) ?> 
                    <?php if (isset($item['quantity']) && $item['quantity'] > 1): ?>
                        × <?= $item['quantity'] ?>
                    <?php endif; ?>
                </span>
                <span class="info-value">
                    <?php if (isset($item['price'])): ?>
                        ₱<?= number_format($item['price'], 2) ?>
                    <?php endif; ?>
                </span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="receipt-footer">
            <p class="footer-text" style="font-weight: 600; color: #2A5618;">
                Thank you for choosing <?= htmlspecialchars($site_name) ?>!
            </p>
            <p class="footer-text">
                Keep this receipt for your records
            </p>
            <p class="footer-text" style="margin-top: 12px; font-size: 0.7rem; opacity: 0.7;">
                Receipt generated on <?= $date ?>
            </p>
        </div>
    </div>

    <button class="print-button" onclick="window.print()">🖨️ Print Receipt</button>

    <script>
        // Auto-focus QR code for easy access
        window.addEventListener('load', function() {
            console.log('Receipt loaded with QR code:', '<?= htmlspecialchars($qr_code, ENT_QUOTES) ?>');
        });
        
        // Countdown Timer
        (function() {
            var qrType = '<?= $type ?>';
            var createdAt = new Date('<?= $transaction_data['created_at'] ?? date('Y-m-d H:i:s') ?>');
            var validityMinutes = qrType === 'order' ? 180 : 30; // 3 hours or 30 minutes
            var expiresAt = new Date(createdAt.getTime() + (validityMinutes * 60 * 1000));
            
            function updateTimer() {
                var now = new Date();
                var remaining = expiresAt - now;
                
                var timerEl = document.getElementById('timer-text');
                var badgeEl = document.getElementById('qr-timer');
                
                if (remaining <= 0) {
                    timerEl.textContent = 'QR Code Expired';
                    badgeEl.style.backgroundColor = '#dc3545';
                    badgeEl.style.color = 'white';
                    return;
                }
                
                var hours = Math.floor(remaining / (1000 * 60 * 60));
                var minutes = Math.floor((remaining % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((remaining % (1000 * 60)) / 1000);
                
                var timeStr = '';
                if (hours > 0) {
                    timeStr = hours + 'h ' + (minutes < 10 ? '0' : '') + minutes + 'm ' + (seconds < 10 ? '0' : '') + seconds + 's';
                } else {
                    timeStr = (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
                }
                
                timerEl.textContent = 'Expires in ' + timeStr;
                
                // Color based on remaining time
                var halfTime = (validityMinutes * 60 * 1000) / 2;
                var quarterTime = (validityMinutes * 60 * 1000) / 4;
                
                if (remaining < quarterTime) {
                    badgeEl.style.backgroundColor = '#dc3545'; // Red
                    badgeEl.style.color = 'white';
                } else if (remaining < halfTime) {
                    badgeEl.style.backgroundColor = '#ffc107'; // Yellow
                    badgeEl.style.color = '#000';
                } else {
                    badgeEl.style.backgroundColor = '#28a745'; // Green
                    badgeEl.style.color = 'white';
                }
                
                setTimeout(updateTimer, 1000);
            }
            
            updateTimer();
        })();
    </script>
</body>
</html>
