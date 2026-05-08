<?php
/**
 * Universal Receipt Page
 * Displays order or reward receipt with scannable QR code
 * 
 * Usage:
 * - Order receipt: receipt.php?type=order&id=123
 * - Reward receipt: receipt.php?type=reward&id=456
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/receipt_helper.php';

// Get parameters
$type = isset($_GET['type']) ? trim($_GET['type']) : 'order';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Validate parameters
if (!in_array($type, ['order', 'reward'])) {
    http_response_code(400);
    die('Invalid receipt type. Use "order" or "reward".');
}

if ($id <= 0) {
    http_response_code(400);
    die('Invalid ID.');
}

// Verify transaction exists
if ($type === 'order') {
    $stmt = $mysqli->prepare("SELECT id FROM orders WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        http_response_code(404);
        die('Order not found.');
    }
    $stmt->close();
} else {
    $stmt = $mysqli->prepare("SELECT id FROM vouchers WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        http_response_code(404);
        die('Reward not found.');
    }
    $stmt->close();
}

// Ensure QR code exists
$qr_code = ensure_qr_code_exists($mysqli, $type, $id);

// Display receipt
display_receipt_with_qr($type, $id, $mysqli);
?>
