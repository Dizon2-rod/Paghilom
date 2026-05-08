<?php
require_once __DIR__ . '/../includes/db_bootstrap.php';

// Accept JSON { code }
$input = json_decode(file_get_contents('php://input'), true);
$raw = isset($input['code']) ? trim((string)$input['code']) : '';
if ($raw === '') {
    kiosk_json_response(['message' => 'No code provided'], 400);
}

$conn = kiosk_db_connect();

// Extract code if a full URL was scanned (e.g., /pos/order_fulfill.php?code=XXXX)
$code = $raw;
if (filter_var($raw, FILTER_VALIDATE_URL)) {
    $u = parse_url($raw);
    if (!empty($u['query'])) {
        parse_str($u['query'], $q);
        if (!empty($q['code'])) $code = (string)$q['code'];
        if (!empty($q['id']) && empty($code)) $code = (string)$q['id'];
    }
}
$code = trim($code);

if ($code === '') {
    kiosk_log_scan($conn, $raw, 'NO_CODE_IN_URL');
    kiosk_json_response(['message' => 'Invalid QR Code. Please scan a valid Order or Reward QR.'], 400);
}

// Determine candidate type by prefix if present; otherwise discover via DB
$prefixType = null;
if (preg_match('/^(ORDER|REWARD)-/i', $code, $m)) {
    $prefixType = strtolower($m[1]);
} elseif (preg_match('/^(ORD|REW)[A-Z0-9]{6,}$/i', $code, $m)) {
    $prefixType = strtolower($m[1]) === 'ord' ? 'order' : 'reward';
}

function find_by_code(mysqli $conn, string $table, string $code): ?array {
    if (!table_exists($conn, $table)) return null;
    $codeCol = first_existing_column($conn, $table, ['qr_code','code','token','reference','ref','qr']);
    if (!$codeCol) return null;
    $sql = "SELECT * FROM `{$table}` WHERE `{$codeCol}` = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if (!$row) return null;
    $row['__code_col'] = $codeCol;
    $row['__id_col'] = first_existing_column($conn, $table, ['id', $table === 'orders' ? 'order_id' : 'reward_id']) ?: 'id';
    $row['__status_col'] = first_existing_column($conn, $table, ['status','state']);
    $row['__expiry_col'] = first_existing_column($conn, $table, ['expires_at','expiry_at','expires_on','valid_until','valid_to','expiration','expiry','exp_at']);
    return $row;
}

$record = null; $type = null;

$tryOrderFirst = $prefixType === 'order' || $prefixType === null;
$tryRewardFirst = $prefixType === 'reward';

if ($tryOrderFirst) {
    $r = find_by_code($conn, 'orders', $code);
    if ($r) { $record = $r; $type = 'order'; }
}
if (!$record) {
    $r = find_by_code($conn, 'reward_redemptions', $code);
    if ($r) { $record = $r; $type = 'reward'; }
}
// If prefix suggested reward first
if (!$record && $tryRewardFirst) {
    $r = find_by_code($conn, 'orders', $code);
    if ($r) { $record = $r; $type = 'order'; }
}

if (!$record || !$type) {
    kiosk_log_scan($conn, $code, 'NOT_FOUND');
    kiosk_json_response(['message' => 'QR Code not found.'], 404);
}

$statusCol = $record['__status_col'];
$expiryCol = $record['__expiry_col'];
if ($statusCol && isset($record[$statusCol])) {
    $st = strtolower((string)$record[$statusCol]);
    if ($type === 'order' && in_array($st, ['paid','completed','cancelled'], true)) {
        kiosk_log_scan($conn, $code, 'ORDER_INVALID', json_encode(['status'=>$st]));
        kiosk_json_response(['message' => 'Order is not payable.'], 409);
    }
    if ($type === 'reward' && in_array($st, ['redeemed','used','expired'], true)) {
        kiosk_log_scan($conn, $code, 'REWARD_INVALID', json_encode(['status'=>$st]));
        kiosk_json_response(['message' => 'QR Code is invalid or already redeemed.'], 410);
    }
}
if ($expiryCol && !empty($record[$expiryCol])) {
    $ts = strtotime((string)$record[$expiryCol]);
    if ($ts !== false && $ts < time()) {
        kiosk_log_scan($conn, $code, 'EXPIRED');
        kiosk_json_response(['message' => 'QR Code is expired.'], 410);
    }
}

$id = $record[$record['__id_col']] ?? null;
if (!$id) kiosk_json_response(['message' => 'Record missing ID'], 500);

kiosk_log_scan($conn, $code, strtoupper($type) . '_OK', json_encode(['id'=>$id]));

// Always return kiosk payment URL using code to avoid POS pages
kiosk_json_response([
    'type' => $type,
    'id'   => $id,
    'redirect_url' => 'payment.php?code=' . urlencode($code)
]);
