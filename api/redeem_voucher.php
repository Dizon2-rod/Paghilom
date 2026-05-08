<?php
require_once __DIR__.'/../config.php';
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get the raw POST data
$input = json_decode(file_get_contents('php://input'), true);
$code = $input['code'] ?? '';
$staff_id = (int)($input['staff_id'] ?? 0);

if (empty($code)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Voucher code is required']);
    exit;
}

$mysqli->begin_transaction();
try {
    // Get the voucher with related data
    $stmt = $mysqli->prepare("
        SELECT v.*, c.name as client_name, c.phone, r.name as reward_name, r.points_cost
        FROM vouchers v
        LEFT JOIN clients c ON c.id = v.client_id
        LEFT JOIN rewards r ON r.id = v.reward_id
        WHERE v.code = ? AND v.status = 'pending'
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $voucher = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$voucher) {
        throw new Exception('Voucher not found or already redeemed');
    }

    // Update voucher status to redeemed
    $now = date('Y-m-d H:i:s');
    $stmt = $mysqli->prepare("UPDATE vouchers SET status = 'redeemed', claimed_at = ?, claimed_by = ? WHERE code = ? AND status = 'pending'");
    $stmt->bind_param('sis', $now, $staff_id, $code);
    $stmt->execute();
    
    if ($mysqli->affected_rows === 0) {
        throw new Exception('Failed to update voucher status');
    }

    // Update redemptions table if it exists
    $tables = $mysqli->query("SHOW TABLES LIKE 'redemptions'")->num_rows > 0;
    if ($tables) {
        $stmt = $mysqli->prepare("
            UPDATE redemptions 
            SET status = 'approved', 
                updated_at = NOW(),
                claimed_at = ?,
                claimed_by = ?
            WHERE voucher_code = ? 
              AND status = 'pending'
        ");
        $stmt->bind_param('sis', $now, $staff_id, $code);
        $stmt->execute();
    }

    $mysqli->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Voucher redeemed successfully',
        'voucher' => [
            'code' => $voucher['code'],
            'reward_name' => $voucher['reward_name'],
            'client_name' => $voucher['client_name'] ?: ('Customer ' . $voucher['phone']),
            'points_cost' => (int)$voucher['points_cost'],
            'claimed_at' => $now
        ]
    ]);
    
} catch (Exception $e) {
    $mysqli->rollback();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
