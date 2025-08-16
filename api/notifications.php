<?php
/**
 * API endpoint for managing notifications
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Require authentication
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Get notifications for current user
    $user_id = $_SESSION['user_id'];
    $branch_id = $_SESSION['user_branch_id'];
    
    try {
        // **FIXED**: Get user-specific and branch-specific notifications with proper NULL handling
        $notifications_query = "
            SELECT * FROM notifications 
            WHERE (user_id = ? OR user_id IS NULL) 
            AND (branch_id = ? OR branch_id IS NULL OR ? IS NULL)
            ORDER BY created_at DESC 
            LIMIT 10
        ";
        
        // **FIXED**: Proper parameter binding for NULL handling
        $notifications = fetchAll(
            $notifications_query,
            [$user_id, $branch_id, $branch_id],
            'iii'
        );
        
        // Count unread notifications
        $unread_count = 0;
        foreach ($notifications as $notification) {
            if ($notification['is_read'] == 0) {
                $unread_count++;
            }
        }
        
        echo json_encode([
            'success' => true,
            'notifications' => array_slice($notifications, 0, 10),
            'unread_count' => $unread_count
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Database error']);
    }
    
} elseif ($method === 'POST') {
    // Handle notification actions
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    if ($action === 'mark_read') {
        $notification_id = (int)($input['notification_id'] ?? 0);
        
        try {
            executeQuery(
                "UPDATE notifications SET is_read = 1 WHERE id = ?",
                [$notification_id],
                'i'
            );
            
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            echo json_encode(['error' => 'Database error']);
        }
        
    } else {
        echo json_encode(['error' => 'Invalid action']);
    }
    
} else {
    echo json_encode(['error' => 'Method not allowed']);
}
?>