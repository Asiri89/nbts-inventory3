<?php
/**
 * **NEW API** - Accept Allocation API for NBTS Inventory Management System
 * 
 * This API handles accepting allocated items from NBC
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Require authentication
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$allocation_item_id = (int)($input['allocation_item_id'] ?? 0);

if (!$allocation_item_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid allocation item ID']);
    exit();
}

try {
    // Get allocation item details
    $alloc_item = fetchSingle(
        "SELECT ai.*, ba.item_id, ba.unit_cost, ba.warranty_start_date, ba.warranty_end_date, ba.purchase_date, ba.supplier_id, ba.invoice_number
         FROM allocation_items ai
         JOIN bulk_allocations ba ON ai.allocation_id = ba.id
         WHERE ai.id = ? AND ai.branch_id = ? AND ai.status = 'allocated'",
        [$allocation_item_id, $_SESSION['user_branch_id']],
        'ii'
    );
    
    if (!$alloc_item) {
        echo json_encode(['success' => false, 'message' => 'Allocation item not found or not accessible']);
        exit();
    }
    
    // Update allocation item status
    executeQuery(
        "UPDATE allocation_items SET status = 'accepted', accepted_by = ?, accepted_at = NOW() WHERE id = ?",
        [$_SESSION['user_id'], $allocation_item_id],
        'ii'
    );
    
    // Create inventory record at branch
    executeQuery(
        "INSERT INTO inventory (item_id, branch_id, serial_number, purchase_date, supplier_id, invoice_number, warranty_start_date, warranty_end_date, quantity, unit_cost, total_cost, allocation_id, is_allocated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, 1)",
        [$alloc_item['item_id'], $_SESSION['user_branch_id'], $alloc_item['serial_number'], $alloc_item['purchase_date'], $alloc_item['supplier_id'], $alloc_item['invoice_number'], $alloc_item['warranty_start_date'], $alloc_item['warranty_end_date'], $alloc_item['unit_cost'], $alloc_item['unit_cost'], $alloc_item['allocation_id']],
        'iisssissdddi'
    );
    
    $inventory_id = getLastInsertId();
    
    // Create stock movement
    executeQuery(
        "INSERT INTO stock_movements (inventory_id, movement_type, from_branch_id, to_branch_id, quantity, unit_cost, total_cost, reason, created_by) VALUES (?, 'transfer_in', 1, ?, 1, ?, ?, 'Central allocation accepted', ?)",
        [$inventory_id, $_SESSION['user_branch_id'], $alloc_item['unit_cost'], $alloc_item['unit_cost'], $_SESSION['user_id']],
        'iiddsi'
    );
    
    // Generate QR code
    QRCode::generateForInventory($inventory_id);
    
    // Log activity
    logActivity('Accept Allocation', 'allocation_items', $allocation_item_id);
    
    echo json_encode(['success' => true, 'message' => 'Item accepted successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error accepting allocation: ' . $e->getMessage()]);
}
?>