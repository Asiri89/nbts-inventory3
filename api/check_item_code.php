<?php
/**
 * API endpoint to check if item code exists
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Require authentication
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$code = $input['code'] ?? '';

if (empty($code)) {
    echo json_encode(['error' => 'Code is required']);
    exit();
}

try {
    $existing = fetchSingle(
        "SELECT id FROM items WHERE code = ?",
        [$code],
        's'
    );
    
    echo json_encode(['exists' => !empty($existing)]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error']);
}
?>