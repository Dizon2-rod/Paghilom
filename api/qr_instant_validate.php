<?php
/**
 * Instant QR Code Validation API
 * Optimized for sub-second response time
 */

// Disable output buffering for instant response
if (ob_get_level()) ob_end_clean();

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/qr_generator.php';

// Get input
$input = json_decode(file_get_contents('php://input'), true);
$code = isset($input['code']) ? trim($input['code']) : '';

// Quick validation
if (empty($code)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'No QR code provided'
    ]);
    exit;
}

// Perform instant validation
$result = instant_validate_qr($mysqli, $code);

// Set appropriate HTTP status code
if ($result['success']) {
    http_response_code(200);
} else {
    // Determine error code
    if (strpos($result['error'], 'not found') !== false) {
        http_response_code(404);
    } elseif (strpos($result['error'], 'already') !== false || strpos($result['error'], 'expired') !== false) {
        http_response_code(410); // Gone
    } else {
        http_response_code(400);
    }
}

// Return result
echo json_encode($result);
exit;
?>
