<?php
// Wrap everything in try-catch to ensure JSON response even on errors
try {
    require_once __DIR__ . '/../../config.php';
    require_pos();

    // Set error handling
    error_reporting(E_ALL);
    ini_set('display_errors', 0); // Don't display errors in JSON response
    ini_set('log_errors', 1);

    header('Content-Type: application/json');

    // Get QR code from request
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON in request: ' . json_last_error_msg());
    }
    $code = isset($input['code']) ? trim($input['code']) : '';

if (empty($code)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No QR code provided', 'message' => 'QR code is required']);
    exit;
}

// Detect QR code type and extract ID
$type = null;
$id = null;

// Check if it's an Order QR (ORD + alphanumeric)
if (preg_match('/^ORD[A-Z0-9]{6,}$/i', $code)) {
    $type = 'order';
    $id = strtoupper($code);
    
    // Validate order exists in database
    $stmt = $mysqli->prepare("SELECT id, code, total_amount, payment_status FROM orders WHERE code = ? LIMIT 1");
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    
    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Order not found', 'code' => $code, 'message' => 'Order code does not exist']);
        exit;
    }
    
    // Check if already paid (handle different payment status values)
    $paid_statuses = ['paid', 'successful', 'completed'];
    if (in_array(strtolower($order['payment_status'] ?? ''), $paid_statuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Order already paid', 'code' => $code, 'message' => 'This order has already been paid']);
        exit;
    }
    
    // Return payment redirect URL
    echo json_encode([
        'success' => true,
        'type' => 'order',
        'id' => $order['id'],
        'code' => $id,
        'amount' => $order['total_amount'],
        'redirect_url' => APP_URL . 'pos/payment.php?mode=order&id=' . urlencode($order['id'])
    ]);
    exit;
}

// Check if it's a Reward/Voucher QR (PHC- prefix)
if (preg_match('/^PHC-[A-Z0-9]{6,}$/i', $code)) {
    $type = 'reward';
    $id = strtoupper($code);
    $reward = null;
    
    // Try vouchers table first
    $tablesCheck = $mysqli->query("SHOW TABLES LIKE 'vouchers'");
    if ($tablesCheck && $tablesCheck->num_rows > 0) {
        $stmt = $mysqli->prepare("SELECT id, voucher_code, points_required, status FROM vouchers WHERE voucher_code = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $reward = $result ? $result->fetch_assoc() : null;
            $stmt->close();
        } else {
            // Check if columns exist, might be different column names
            $colsCheck = $mysqli->query("SHOW COLUMNS FROM vouchers LIKE 'voucher_code'");
            if ($colsCheck && $colsCheck->num_rows > 0) {
                // Try with code column instead
                $stmt = $mysqli->prepare("SELECT id, code as voucher_code, points_required, status FROM vouchers WHERE code = ? LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param('s', $id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $reward = $result ? $result->fetch_assoc() : null;
                    $stmt->close();
                }
            }
        }
    }
    
    // If not found, try redemptions table
    if (!$reward) {
        $tablesCheck = $mysqli->query("SHOW TABLES LIKE 'redemptions'");
        if ($tablesCheck && $tablesCheck->num_rows > 0) {
            // Check which columns exist in redemptions table
            $hasVoucherCode = $mysqli->query("SHOW COLUMNS FROM redemptions LIKE 'voucher_code'");
            $hasPointsSpent = $mysqli->query("SHOW COLUMNS FROM redemptions LIKE 'points_spent'");
            
            if ($hasVoucherCode && $hasVoucherCode->num_rows > 0) {
                $pointsCol = ($hasPointsSpent && $hasPointsSpent->num_rows > 0) ? 'points_spent' : 'points_required';
                $stmt = $mysqli->prepare("SELECT id, voucher_code, {$pointsCol} as points_required, status FROM redemptions WHERE voucher_code = ? LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param('s', $id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $reward = $result ? $result->fetch_assoc() : null;
                    $stmt->close();
                }
            }
        }
    }
    
    if (!$reward) {
        http_response_code(404);
        echo json_encode(['error' => 'Reward voucher not found', 'code' => $code, 'message' => 'Voucher code does not exist in the system']);
        exit;
    }
    
    // Check if already used - allow "pending" status to be processed by staff
    // Status will be updated to "approved" when staff processes it
    $used_statuses = ['redeemed', 'claimed', 'cancelled', 'approved'];
    if (in_array(strtolower($reward['status'] ?? ''), $used_statuses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Voucher already used', 'code' => $code, 'message' => 'This voucher has already been used']);
        exit;
    }
    
    // "pending" status vouchers are allowed - they will be approved when staff processes them
    
    // Determine which table the reward was found in (simplified - we already know from the lookup above)
    $foundInTable = null;
    $tablesCheck = $mysqli->query("SHOW TABLES LIKE 'vouchers'");
    if ($tablesCheck && $tablesCheck->num_rows > 0) {
        $checkStmt = $mysqli->prepare("SELECT id FROM vouchers WHERE voucher_code = ? OR code = ? LIMIT 1");
        if ($checkStmt) {
            $checkStmt->bind_param('ss', $id, $id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            if ($checkResult && $checkResult->fetch_assoc()) {
                $foundInTable = 'vouchers';
            }
            $checkStmt->close();
        }
    }
    if (!$foundInTable) {
        $tablesCheck = $mysqli->query("SHOW TABLES LIKE 'redemptions'");
        if ($tablesCheck && $tablesCheck->num_rows > 0) {
            $checkStmt = $mysqli->prepare("SELECT id FROM redemptions WHERE voucher_code = ? LIMIT 1");
            if ($checkStmt) {
                $checkStmt->bind_param('s', $id);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                if ($checkResult && $checkResult->fetch_assoc()) {
                    $foundInTable = 'redemptions';
                }
                $checkStmt->close();
            }
        }
    }
    
    // Return payment redirect URL - pass both ID and code for lookup
    echo json_encode([
        'success' => true,
        'type' => 'reward',
        'id' => $reward['id'],
        'code' => $id,
        'table' => $foundInTable, // Include which table it was found in
        'points' => $reward['points_required'] ?? 0,
        'redirect_url' => APP_URL . 'pos/payment.php?mode=reward&id=' . urlencode($reward['id']) . '&code=' . urlencode($id)
    ]);
    exit;
}

// Check if it's JSON format with type and code
try {
    $data = json_decode($code, true);
    if (isset($data['type']) && isset($data['code'])) {
        // Recursively call this logic with the extracted code
        $input['code'] = $data['code'];
        // Re-run the checks above (simplified - just redirect)
        if ($data['type'] === 'order') {
            $type = 'order';
            $id = $data['code'];
        } elseif ($data['type'] === 'reward' || $data['type'] === 'voucher') {
            $type = 'reward';
            $id = $data['code'];
        }
    }
} catch (Exception $e) {
    // Not JSON, continue
}

    // If still no match, return error
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid QR code format',
        'code' => $code,
        'message' => 'QR code must be in format ORD##### for orders or PHC-##### for rewards',
        'hint' => 'Order codes start with ORD, voucher codes start with PHC-'
    ]);
    exit;
    
} catch (Exception $e) {
    // Ensure JSON response even on errors
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'message' => 'An error occurred while validating the QR code: ' . $e->getMessage(),
        'debug' => (defined('APP_DEBUG') && APP_DEBUG) ? $e->getTraceAsString() : null
    ]);
    exit;
} catch (Throwable $e) {
    // Catch any other errors
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'message' => 'An unexpected error occurred: ' . $e->getMessage(),
        'debug' => (defined('APP_DEBUG') && APP_DEBUG) ? $e->getTraceAsString() : null
    ]);
    exit;
}
