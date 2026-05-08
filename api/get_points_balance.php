<?php
/**
 * API endpoint to get current points balance for the logged-in user
 * Returns JSON with current balance and recent transactions
 */
require_once __DIR__ . '/../config.php';
require_login();

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$user_id = (int)$_SESSION['user']['id'];

// Include points functions
require_once __DIR__.'/../includes/points.php';
if (file_exists(__DIR__.'/../points.php')) { 
    require_once __DIR__.'/../points.php'; 
}

try {
    // Force fresh query - no caching
    // Direct query to ensure we get the absolute latest balance
    $stmt = $mysqli->prepare("SELECT COALESCE(SUM(points), 0) AS balance FROM point_transactions WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $points_balance = (int)($row['balance'] ?? 0);
    $stmt->close();
    
    // Get recent transactions (last 5 for quick update)
    $transactions = get_user_point_history($user_id, 5, 0);
    
    echo json_encode([
        'success' => true,
        'balance' => $points_balance,
        'transactions' => $transactions,
        'timestamp' => time()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch points balance',
        'message' => $e->getMessage()
    ]);
}
?>

