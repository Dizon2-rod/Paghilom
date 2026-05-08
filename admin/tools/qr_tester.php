<?php
/**
 * QR Code Testing & Validation Tool
 * Test QR code generation and scannability
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/qr_unified.php';

// Require admin access
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$action = $_GET['action'] ?? 'test';
$test_result = null;
$generated_qr = null;

// Handle test actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'generate') {
        $type = $_POST['type'] ?? 'order';
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id > 0) {
            $generated_qr = generate_secure_qr_code($mysqli, $type, $id);
        }
    } elseif ($action === 'validate') {
        $qr_code = $_POST['qr_code'] ?? '';
        if (!empty($qr_code)) {
            $test_result = validate_unified_qr($mysqli, $qr_code);
        }
    }
}

include __DIR__ . '/../../partials/header.php';
?>

<style>
    .tool-container { max-width: 900px; margin: 2rem auto; padding: 2rem; }
    .test-section { background: white; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .test-section h3 { color: #2A5618; margin-bottom: 1.5rem; }
    .qr-display { text-align: center; padding: 2rem; background: #f8f9fa; border-radius: 8px; margin: 1rem 0; }
    .qr-display img { max-width: 300px; height: auto; }
    .result-box { padding: 1.5rem; border-radius: 8px; margin: 1rem 0; }
    .result-box.success { background: #d1f2eb; border: 2px solid #28a745; }
    .result-box.error { background: #f8d7da; border: 2px solid #dc3545; }
    .code-display { font-family: monospace; background: #f4f4f4; padding: 1rem; border-radius: 4px; overflow-x: auto; }
</style>

<div class="tool-container">
    <h1><i class="bi bi-qr-code-scan me-2"></i>QR Code Tester</h1>
    <p class="text-muted mb-4">Test QR code generation, validation, and scannability</p>
    
    <!-- Generate QR Code Section -->
    <div class="test-section">
        <h3><i class="bi bi-plus-circle me-2"></i>Generate QR Code</h3>
        <form method="post" action="?action=generate">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select" required>
                        <option value="order">Order</option>
                        <option value="reward">Reward/Voucher</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">ID</label>
                    <input type="number" name="id" class="form-control" placeholder="Enter order or voucher ID" required>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-qr-code me-2"></i>Generate QR Code
                    </button>
                </div>
            </div>
        </form>
        
        <?php if ($generated_qr): ?>
        <div class="result-box <?= $generated_qr['success'] ? 'success' : 'error' ?>">
            <?php if ($generated_qr['success']): ?>
                <h5><i class="bi bi-check-circle me-2"></i>QR Code Generated Successfully!</h5>
                
                <div class="qr-display">
                    <img src="<?= htmlspecialchars($generated_qr['qr_image']) ?>" alt="QR Code">
                    <p class="mt-3 mb-0"><strong>Code:</strong> <code><?= htmlspecialchars($generated_qr['code']) ?></code></p>
                </div>
                
                <div class="mt-3">
                    <h6>QR Payload:</h6>
                    <div class="code-display"><?= htmlspecialchars($generated_qr['qr_payload']) ?></div>
                </div>
                
                <div class="mt-3">
                    <h6>Details:</h6>
                    <ul>
                        <li><strong>Type:</strong> <?= htmlspecialchars($generated_qr['type']) ?></li>
                        <li><strong>ID:</strong> <?= htmlspecialchars($generated_qr['id']) ?></li>
                        <li><strong>Token:</strong> <code><?= htmlspecialchars($generated_qr['token']) ?></code></li>
                    </ul>
                </div>
            <?php else: ?>
                <h5><i class="bi bi-x-circle me-2"></i>Generation Failed</h5>
                <p class="mb-0"><?= htmlspecialchars($generated_qr['error'] ?? 'Unknown error') ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Validate QR Code Section -->
    <div class="test-section">
        <h3><i class="bi bi-shield-check me-2"></i>Validate QR Code</h3>
        <form method="post" action="?action=validate">
            <div class="mb-3">
                <label class="form-label">QR Code Data</label>
                <textarea name="qr_code" class="form-control" rows="4" placeholder="Paste QR code data (plain code like ORD12345 or JSON)" required></textarea>
                <small class="text-muted">Examples: ORD12345, PHC-ABC123, or JSON format</small>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2-square me-2"></i>Validate QR Code
            </button>
        </form>
        
        <?php if ($test_result): ?>
        <div class="result-box <?= $test_result['success'] ? 'success' : 'error' ?>">
            <?php if ($test_result['success']): ?>
                <h5><i class="bi bi-check-circle me-2"></i>✅ Valid QR Code!</h5>
                <ul>
                    <li><strong>Type:</strong> <?= htmlspecialchars($test_result['type']) ?></li>
                    <li><strong>Code:</strong> <code><?= htmlspecialchars($test_result['code']) ?></code></li>
                    <li><strong>ID:</strong> <?= htmlspecialchars($test_result['id']) ?></li>
                    <li><strong>Redirect URL:</strong> <code><?= htmlspecialchars($test_result['redirect_url']) ?></code></li>
                    <?php if (isset($test_result['amount'])): ?>
                    <li><strong>Amount:</strong> ₱<?= number_format($test_result['amount'], 2) ?></li>
                    <?php endif; ?>
                    <?php if (isset($test_result['points'])): ?>
                    <li><strong>Points:</strong> <?= $test_result['points'] ?></li>
                    <?php endif; ?>
                </ul>
                <p class="mb-0"><em><?= htmlspecialchars($test_result['message']) ?></em></p>
            <?php else: ?>
                <h5><i class="bi bi-x-circle me-2"></i>❌ Invalid QR Code</h5>
                <p><strong>Error:</strong> <?= htmlspecialchars($test_result['error']) ?></p>
                <p class="mb-0"><em><?= htmlspecialchars($test_result['message']) ?></em></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Quick Reference -->
    <div class="test-section">
        <h3><i class="bi bi-info-circle me-2"></i>QR Code Format Reference</h3>
        
        <h6>Order QR Codes</h6>
        <ul>
            <li><strong>Format:</strong> <code>ORD + 8 alphanumeric characters</code></li>
            <li><strong>Example:</strong> <code>ORDABC12345</code></li>
            <li><strong>Validity:</strong> 3 hours from creation</li>
            <li><strong>JSON Format:</strong></li>
            <div class="code-display">{"type":"order","code":"ORDABC12345","token":"abc123","id":1,"timestamp":1234567890}</div>
        </ul>
        
        <h6 class="mt-4">Reward QR Codes</h6>
        <ul>
            <li><strong>Format:</strong> <code>PHC- + 8 alphanumeric characters</code></li>
            <li><strong>Example:</strong> <code>PHC-XYZ78901</code></li>
            <li><strong>Validity:</strong> 30 minutes from creation</li>
            <li><strong>JSON Format:</strong></li>
            <div class="code-display">{"type":"reward","code":"PHC-XYZ78901","token":"xyz789","id":1,"timestamp":1234567890}</div>
        </ul>
        
        <h6 class="mt-4">Scanner Behavior</h6>
        <ul>
            <li>✅ Accepts both plain code and JSON format</li>
            <li>✅ Validates expiry time automatically</li>
            <li>✅ Checks payment/redemption status</li>
            <li>✅ Returns redirect URL on success</li>
            <li>❌ Rejects tampered or invalid codes</li>
        </ul>
    </div>
    
    <div class="text-center">
        <a href="../index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Admin
        </a>
    </div>
</div>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
