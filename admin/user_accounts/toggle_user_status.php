<?php
require_once __DIR__.'/../includes/bootstrap.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get user ID and status from POST data
$userId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = isset($_POST['status']) ? (int)$_POST['status'] : 0;

// Validate user ID
if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

// Prevent modifying admin user (ID 1) and current user
if ($userId === 1 || $userId === ($_SESSION['user_id'] ?? 0)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You cannot modify this user\'s status']);
    exit;
}

try {
    $db = db();
    
    // Prepare and execute the update query
    $stmt = $db->prepare("UPDATE users SET is_active = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('ii', $status, $userId);
    
    if ($stmt->execute()) {
        // Log the action
        $adminId = (int)$_SESSION['user_id'];
        $action = $status ? 'Account Activated' : 'Account Deactivated';
        $details = "User ID: $userId was " . ($status ? 'activated' : 'deactivated');
        
        $logStmt = $db->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
        $logStmt->bind_param('iss', $adminId, $action, $details);
        $logStmt->execute();
        
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to update user status');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while updating user status: ' . $e->getMessage()
    ]);
}

exit;
