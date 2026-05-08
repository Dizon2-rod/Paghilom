<?php
/**
 * Universal QR Code Validation API
 * Single endpoint for validating ALL QR codes (Order & Reward)
 * Used by both POS and Kiosk scanners
 * 
 * Request: POST { "code": "QR_CODE_DATA" }
 * Response: { "success": true, "redirect_url": "payment.php?code=XXX" }
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/qr_unified.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed',
        'message' => 'Only POST requests are accepted'
    ]);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);
$qr_code = isset($input['code']) ? trim($input['code']) : '';

if (empty($qr_code)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'No code provided',
        'message' => 'QR code data is required'
    ]);
    exit;
}

// Log scan attempt
$log_file = __DIR__ . '/../logs/qr_scans.log';
$log_dir = dirname($log_file);
if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0755, true);
}

$log_entry = sprintf(
    "[%s] Scan attempt: %s (IP: %s)\n",
    date('Y-m-d H:i:s'),
    substr($qr_code, 0, 50),
    $_SERVER['REMOTE_ADDR'] ?? 'unknown'
);
@file_put_contents($log_file, $log_entry, FILE_APPEND);

// Validate QR code
try {
    // Debug: detect format
    $is_url = filter_var($qr_code, FILTER_VALIDATE_URL) ? 'yes' : 'no';
    $is_json = 'no';
    $tmp = json_decode($qr_code, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) { $is_json = 'yes'; }
    $log_entry = sprintf("[%s] Details: url=%s json=%s raw_len=%d\n", date('Y-m-d H:i:s'), $is_url, $is_json, strlen($qr_code));
    @file_put_contents($log_file, $log_entry, FILE_APPEND);

    $result = validate_unified_qr($mysqli, $qr_code);
    
    if ($result['success']) {
        // Success - return redirect URL
        http_response_code(200);
        
        // Log successful validation
        $log_entry = sprintf(
            "[%s] ✓ Valid %s: %s (ID: %s)\n",
            date('Y-m-d H:i:s'),
            $result['type'],
            $result['code'],
            $result['id']
        );
        @file_put_contents($log_file, $log_entry, FILE_APPEND);
        
        echo json_encode([
            'success' => true,
            'type' => $result['type'],
            'code' => $result['code'],
            'id' => $result['id'],
            'redirect_url' => $result['redirect_url'],
            'message' => $result['message'],
            'amount' => $result['amount'] ?? null,
            'points' => $result['points'] ?? null
        ]);
    } else {
        // Validation failed
        http_response_code(400);
        
        // Log failed validation
        $log_entry = sprintf(
            "[%s] ✗ Invalid QR: %s (Error: %s)\n",
            date('Y-m-d H:i:s'),
            substr($qr_code, 0, 50),
            $result['error']
        );
        @file_put_contents($log_file, $log_entry, FILE_APPEND);
        
        echo json_encode([
            'success' => false,
            'error' => $result['error'],
            'message' => $result['message']
        ]);
    }
} catch (Exception $e) {
    // Unexpected error
    http_response_code(500);
    
    // Log error
    $log_entry = sprintf(
        "[%s] ⚠ Exception: %s\n",
        date('Y-m-d H:i:s'),
        $e->getMessage()
    );
    @file_put_contents($log_file, $log_entry, FILE_APPEND);
    
    echo json_encode([
        'success' => false,
        'error' => 'System error',
        'message' => 'An error occurred while validating the QR code. Please try again.'
    ]);
}
?>
