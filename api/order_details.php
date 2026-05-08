<?php
require __DIR__.'/../config.php';

header('Content-Type: application/json');

// Require login
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$uid = (int)($_SESSION['user']['id'] ?? 0);

// Validate input
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit;
}

// Fetch order belonging to current user (prepared)
$stmt = $mysqli->prepare('SELECT * FROM orders WHERE id=? AND user_id=? LIMIT 1');
$stmt->bind_param('ii', $order_id, $uid);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

// Fetch items (prepared)
$items = [];
$st2 = $mysqli->prepare('SELECT name, qty, price, subtotal FROM order_items WHERE order_id=?');
$st2->bind_param('i', $order_id);
$st2->execute();
$res2 = $st2->get_result();
while ($it = $res2->fetch_assoc()) {
    $items[] = [
        'product_name' => $it['name'],
        'quantity' => (int)$it['qty'],
        'price' => (float)$it['price'],
        'subtotal' => (float)$it['subtotal'],
    ];
}
$st2->close();

// Status colors mapping
$status_colors = [
    'pending' => 'warning',
    'queued' => 'info',
    'in_progress' => 'info',
    'ready' => 'primary',
    'paid' => 'success',
    'fulfilled' => 'secondary',
    'completed' => 'success',
    'cancelled' => 'danger',
];
$payment_colors = [
    'unpaid' => 'warning',
    'pending' => 'warning',
    'paid' => 'success',
    'failed' => 'danger',
    'refunded' => 'danger',
];

$response = [
    'success' => true,
    'order' => [
        'order_number' => $order['code'] ?? (string)$order['id'],
        'date' => date('M d, Y g:i A', strtotime($order['created_at'] ?? 'now')),
        'order_status' => $order['status'] ?? 'pending',
        'status_color' => $status_colors[$order['status']] ?? 'secondary',
        'payment_method' => $order['payment_method'] ?? 'cash',
        'payment_status' => $order['payment_status'] ?? 'unpaid',
        'payment_color' => $payment_colors[strtolower($order['payment_status'] ?? 'unpaid')] ?? 'secondary',
        'total_amount' => (float)($order['total_amount'] ?? 0),
        'discount_amount' => (float)($order['discount_amount'] ?? 0),
        'points_earned' => (int)($order['points_awarded'] ?? 0),
        'notes' => $order['notes'] ?? '',
        'items' => $items,
    ],
];

echo json_encode($response);
?>
