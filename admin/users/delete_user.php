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

// Get user ID from POST data
$userId = isset($_POST['id']) ? (int)$_POST['id'] : 0;

// Validate user ID
if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

// Prevent deleting admin user (ID 1) and current user
if ($userId === 1 || $userId === ($_SESSION['user_id'] ?? 0)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You cannot delete this user account']);
    exit;
}

try {
    $db = db();
    
    // First, check if the user exists
    $stmt = $db->prepare("SELECT id, name, email FROM users WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Start transaction for data consistency
    $db->begin_transaction();
    
    try {
        // Log the action before deletion
        $adminId = (int)$_SESSION['user_id'];
        $action = 'Account Deleted';
        $details = "Deleted user: {$user['name']} ({$user['email']}), ID: $userId";
        
        $logStmt = $db->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
        $logStmt->bind_param('iss', $adminId, $action, $details);
        $logStmt->execute();
        
        // Delete the user
        $deleteStmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $deleteStmt->bind_param('i', $userId);
        
        if ($deleteStmt->execute()) {
            // Commit the transaction
            $db->commit();
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('Failed to delete user');
        }
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while deleting the user: ' . $e->getMessage()
    ]);
}

exit;
