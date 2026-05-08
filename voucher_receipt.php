<?php
require_once __DIR__.'/config.php';
require_login();

$voucher_code = $_GET['code'] ?? '';

if(empty($voucher_code)) {
    die('Invalid voucher code');
}

// Get redemption details
$stmt = $mysqli->prepare("
    SELECT r.*, rc.name, rc.description, rc.reward_type, rc.value, rc.terms, u.name as user_name, u.email
    FROM redemptions r
    JOIN reward_catalog rc ON r.reward_id = rc.id
    JOIN users u ON r.user_id = u.id
    WHERE r.voucher_code = ? AND r.user_id = ?
    LIMIT 1
");
$stmt->bind_param('si', $voucher_code, $_SESSION['user']['id']);
$stmt->execute();
$redemption = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$redemption) {
    die('Voucher not found or does not belong to you');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher Receipt - <?= htmlspecialchars($voucher_code) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>assets/css/style.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; margin: 0; }
        }
        
        .receipt-container {
            max-width: 400px;
            margin: 2rem auto;
            padding: 2rem;
            border: 2px dashed var(--gray-400);
            border-radius: var(--radius-xl);
            background: white;
        }
        
        .voucher-code-box {
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            color: white;
            padding: 2rem;
            border-radius: var(--radius-lg);
            text-align: center;
            margin: 1.5rem 0;
            box-shadow: var(--shadow-md);
        }
        
        .qr-placeholder {
            width: 150px;
            height: 150px;
            background: white;
            border: 2px solid var(--gray-300);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 1rem auto;
        }
        
        .receipt-header {
            text-align: center;
            border-bottom: 2px solid var(--gray-300);
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .receipt-logo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: var(--radius-full);
            margin-bottom: 0.5rem;
        }
        
        .receipt-footer {
            text-align: center;
            border-top: 2px solid var(--gray-300);
            padding-top: 1rem;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: var(--gray-600);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="no-print mb-3 text-center">
            <button onclick="window.print()" class="btn btn-primary me-2">
                <i class="fas fa-print me-2"></i>Print Receipt
            </button>
            <button onclick="window.close()" class="btn btn-outline-secondary">
                Close
            </button>
        </div>
        
        <div class="receipt-container shadow-sm">
            <!-- Header -->
            <div class="receipt-header">
                <?php if(file_exists('uploads/paghilom_logo.png')): ?>
                    <img src="uploads/paghilom_logo.png" alt="Logo" class="receipt-logo">
                <?php endif; ?>
                <h4 class="mb-0" style="color: var(--primary);">Paghilom Cafe</h4>
                <small class="text-muted">Reward Voucher</small>
            </div>
            
            <!-- Voucher Details -->
            <div class="text-center mb-3">
                <h5 class="mb-1"><?= htmlspecialchars($redemption['name']) ?></h5>
                <p class="text-muted small mb-0"><?= htmlspecialchars($redemption['description']) ?></p>
            </div>
            
            <!-- Voucher Code -->
            <div class="voucher-code-box">
                <div style="font-size: 0.875rem; opacity: 0.9; margin-bottom: 0.5rem;">VOUCHER CODE</div>
                <div style="font-size: 2rem; font-weight: 700; letter-spacing: 0.1em; font-family: 'Courier New', monospace;">
                    <?= htmlspecialchars($voucher_code) ?>
                </div>
            </div>
            
            <!-- QR Code Placeholder -->
            <div class="qr-placeholder">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($voucher_code) ?>" 
                     alt="QR Code" 
                     style="width: 100%; height: 100%;">
            </div>
            
            <!-- Details -->
            <div class="mb-3">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-muted">Customer:</td>
                        <td class="text-end"><strong><?= htmlspecialchars($redemption['user_name']) ?></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Points Used:</td>
                        <td class="text-end"><strong><?= number_format($redemption['points_spent']) ?></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Reward Type:</td>
                        <td class="text-end"><span class="badge bg-success"><?= ucfirst(str_replace('_', ' ', $redemption['reward_type'])) ?></span></td>
                    </tr>
                    <?php if($redemption['value']): ?>
                    <tr>
                        <td class="text-muted">Value:</td>
                        <td class="text-end"><strong>₱<?= number_format($redemption['value'], 2) ?></strong></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="text-muted">Redeemed:</td>
                        <td class="text-end"><?= date('M d, Y g:i A', strtotime($redemption['created_at'])) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status:</td>
                        <td class="text-end">
                            <span class="badge bg-<?= $redemption['status'] === 'approved' ? 'success' : 'warning' ?>">
                                <?= ucfirst($redemption['status']) ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Terms -->
            <?php if($redemption['terms']): ?>
            <div class="alert alert-warning small mb-3">
                <strong>Terms & Conditions:</strong><br>
                <?= nl2br(htmlspecialchars($redemption['terms'])) ?>
            </div>
            <?php endif; ?>
            
            <!-- Instructions -->
            <div class="alert alert-info small">
                <strong>How to redeem:</strong>
                <ol class="mb-0 ps-3 mt-2">
                    <li>Show this voucher code or QR code to our staff</li>
                    <li>Staff will verify the code in the system</li>
                    <li>Enjoy your reward!</li>
                </ol>
            </div>
            
            <!-- Footer -->
            <div class="receipt-footer">
                <p class="mb-1"><strong>Paghilom Cafe</strong></p>
                <p class="mb-1">4091 Sitio 2 Barangay Bagumbayan</p>
                <p class="mb-1">Sta. Cruz, Laguna, Philippines</p>
                <p class="mb-0">Thank you for being a valued customer! 🍃</p>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
