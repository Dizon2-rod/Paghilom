<?php
require_once __DIR__ . '/../../kiosk/includes/db_bootstrap.php';
session_start();

function current_user_id(): ?string {
    $keys = ['user_id','customer_id','account_id','id'];
    foreach ($keys as $k) if (!empty($_SESSION[$k])) return (string)$_SESSION[$k];
    return null;
}

$inputId = $_GET['id'] ?? $_POST['id'] ?? null;
$orderId = $inputId !== null ? trim((string)$inputId) : null;
if (!$orderId) kiosk_json_response(['message' => 'Missing order id'], 400);

$conn = kiosk_db_connect();
if (!table_exists($conn, 'orders')) kiosk_json_response(['message' => 'Orders table not found'], 500);

$userId = current_user_id();
if (!$userId) kiosk_json_response(['message' => 'Unauthorized'], 401);

$orderIdCol = first_existing_column($conn, 'orders', ['id','order_id']);
$userCol = first_existing_column($conn, 'orders', ['user_id','customer_id','account_id']);
$statusCol = first_existing_column($conn, 'orders', ['status','state']);
$typeCol = first_existing_column($conn, 'orders', ['order_type','type','source']);
$dtCol = first_existing_column($conn, 'orders', ['created_at','ordered_at','order_date','date']);
$remarksCol = first_existing_column($conn, 'orders', ['remarks','notes','note']);
$staffCol = first_existing_column($conn, 'orders', ['staff','served_by','server']);
$totalCol = first_existing_column($conn, 'orders', ['total_amount','amount','total']);
$pointsCol = first_existing_column($conn, 'orders', ['points_earned','reward_points']);

if (!$orderIdCol || !$userCol) kiosk_json_response(['message' => 'Access denied'], 403);

$stmt = $conn->prepare("SELECT * FROM `orders` WHERE `{$orderIdCol}` = ? AND `{$userCol}` = ? LIMIT 1");
$stmt->bind_param('ss', $orderId, $userId);
$stmt->execute();
$res = $stmt->get_result();
$order = $res ? $res->fetch_assoc() : null;
$stmt->close();
if (!$order) kiosk_json_response(['message' => 'This order cannot be found or may have been removed.'], 404);

// Items
$items = [];
if (table_exists($conn, 'order_items')) {
    $fk = first_existing_column($conn, 'order_items', ['order_id','orderId','order']);
    if ($fk) {
        $stmt = $conn->prepare("SELECT * FROM `order_items` WHERE `{$fk}` = ?");
        $stmt->bind_param('s', $orderId);
        $stmt->execute();
        $ri = $stmt->get_result();
        while ($ri && ($row = $ri->fetch_assoc())) $items[] = $row;
        $stmt->close();
        // decorate with product info if available
        if (table_exists($conn, 'products')) {
            $pidCol = first_existing_column($conn, 'order_items', ['product_id','item_id','product']);
            if ($pidCol) {
                foreach ($items as &$it) {
                    $pid = $it[$pidCol] ?? null;
                    if ($pid !== null) {
                        $pr = $conn->query("SELECT name, image, price FROM `products` WHERE `id` = " . intval($pid) . " LIMIT 1");
                        if ($pr && $pr->num_rows) {
                            $pi = $pr->fetch_assoc();
                            $it['__product_name'] = $pi['name'] ?? null;
                            $it['__product_image'] = $pi['image'] ?? null;
                            if (!isset($it['price']) && isset($pi['price'])) $it['price'] = $pi['price'];
                        }
                    }
                }
                unset($it);
            }
        }
    }
}

// Payment
$payment = null;
if (table_exists($conn, 'payments')) {
    // Try ref_type/ref_id pattern first
    if (column_exists($conn, 'payments', 'ref_type') && column_exists($conn, 'payments', 'ref_id')) {
        $stmt = $conn->prepare("SELECT * FROM `payments` WHERE `ref_type` = 'order' AND `ref_id` = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param('s', $orderId);
        $stmt->execute();
        $rp = $stmt->get_result();
        $payment = $rp ? $rp->fetch_assoc() : null;
        $stmt->close();
    }
    // Fallback: payments.order_id
    if (!$payment && column_exists($conn, 'payments', 'order_id')) {
        $stmt = $conn->prepare("SELECT * FROM `payments` WHERE `order_id` = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param('s', $orderId);
        $stmt->execute();
        $rp = $stmt->get_result();
        $payment = $rp ? $rp->fetch_assoc() : null;
        $stmt->close();
    }
}

$data = [
    'order' => [
        'id' => $order[$orderIdCol] ?? $orderId,
        'datetime' => $dtCol ? ($order[$dtCol] ?? null) : null,
        'type' => $typeCol ? ($order[$typeCol] ?? null) : null,
        'status' => $statusCol ? ($order[$statusCol] ?? null) : null,
        'remarks' => $remarksCol ? ($order[$remarksCol] ?? null) : null,
        'staff' => $staffCol ? ($order[$staffCol] ?? null) : null,
        'points' => $pointsCol ? ($order[$pointsCol] ?? null) : null,
    ],
    'items' => [],
    'payment' => null,
];

$total = 0.0;
foreach ($items as $it) {
    $qty = (float)($it['quantity'] ?? $it['qty'] ?? 1);
    $price = (float)($it['price'] ?? 0);
    $name = $it['name'] ?? $it['__product_name'] ?? ('Item #' . ($it['product_id'] ?? $it['item_id'] ?? ''));
    $img = $it['image'] ?? $it['__product_image'] ?? null;
    $subtotal = $qty * $price;
    $total += $subtotal;
    $data['items'][] = [
        'name' => $name,
        'image' => $img,
        'qty' => $qty,
        'price' => $price,
        'subtotal' => $subtotal,
    ];
}

if ($totalCol && isset($order[$totalCol])) $total = (float)$order[$totalCol];

if ($payment) {
    $method = $payment['method'] ?? ($payment['type'] ?? null);
    $status = $payment['status'] ?? (isset($order[$statusCol]) ? $order[$statusCol] : null);
    $ref = $payment['reference'] ?? $payment['ref'] ?? $payment['txn_ref'] ?? null;
    $amount = isset($payment['amount']) ? (float)$payment['amount'] : $total;
    $data['payment'] = [
        'method' => $method,
        'status' => $status,
        'reference' => $ref,
        'amount' => $amount,
    ];
} else {
    $data['payment'] = [
        'method' => null,
        'status' => isset($order[$statusCol]) ? $order[$statusCol] : null,
        'reference' => null,
        'amount' => $total,
    ];
}

$data['totals'] = [ 'total' => $total ];

kiosk_json_response($data);
