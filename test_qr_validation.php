<?php
/**
 * Quick QR Code Validation Test
 * Use this to test if QR codes are being validated correctly
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/qr_unified.php';

// Get a recent order code for testing
$result = $mysqli->query("SELECT code, id, created_at FROM orders ORDER BY id DESC LIMIT 1");
$order = $result->fetch_assoc();

if (!$order) {
    die("No orders found in database. Create an order first.");
}

echo "<h1>QR Code Validation Test</h1>";
echo "<hr>";

// Test 1: Plain code format
echo "<h2>Test 1: Plain Code Format</h2>";
echo "<p><strong>Testing code:</strong> " . htmlspecialchars($order['code']) . "</p>";

$result1 = validate_unified_qr($mysqli, $order['code']);

echo "<pre>";
print_r($result1);
echo "</pre>";

if ($result1['success']) {
    echo "<p style='color: green;'><strong>✓ SUCCESS:</strong> Plain code validation works!</p>";
} else {
    echo "<p style='color: red;'><strong>✗ FAILED:</strong> " . $result1['message'] . "</p>";
}

echo "<hr>";

// Test 2: JSON format
echo "<h2>Test 2: JSON Format</h2>";

$json_payload = json_encode([
    'type' => 'order',
    'code' => $order['code'],
    'id' => $order['id'],
    'timestamp' => time()
]);

echo "<p><strong>Testing JSON:</strong></p>";
echo "<pre>" . htmlspecialchars($json_payload) . "</pre>";

$result2 = validate_unified_qr($mysqli, $json_payload);

echo "<pre>";
print_r($result2);
echo "</pre>";

if ($result2['success']) {
    echo "<p style='color: green;'><strong>✓ SUCCESS:</strong> JSON validation works!</p>";
} else {
    echo "<p style='color: red;'><strong>✗ FAILED:</strong> " . $result2['message'] . "</p>";
}

echo "<hr>";

// Test 3: Check database column
echo "<h2>Test 3: Database Schema Check</h2>";

$check = $mysqli->query("SHOW COLUMNS FROM orders LIKE 'code'");
if ($check && $check->num_rows > 0) {
    echo "<p style='color: green;'><strong>✓ Column 'code' exists in orders table</strong></p>";
} else {
    echo "<p style='color: red;'><strong>✗ Column 'code' NOT FOUND in orders table</strong></p>";
}

$check2 = $mysqli->query("SHOW COLUMNS FROM orders LIKE 'order_code'");
if ($check2 && $check2->num_rows > 0) {
    echo "<p style='color: orange;'><strong>⚠ Column 'order_code' also exists (duplicate?)</strong></p>";
}

// Check for qr_expires_at
$check3 = $mysqli->query("SHOW COLUMNS FROM orders LIKE 'qr_expires_at'");
if ($check3 && $check3->num_rows > 0) {
    echo "<p style='color: green;'><strong>✓ Column 'qr_expires_at' exists</strong></p>";
} else {
    echo "<p style='color: orange;'><strong>⚠ Column 'qr_expires_at' NOT FOUND (run migration)</strong></p>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p><a href='admin/tools/qr_tester.php'>Go to Full QR Tester Tool →</a></p>";
?>
