<?php
/**
 * **NEW PAGE** - Generate QR Code for Items
 * 
 * This page generates QR codes for items that don't have them
 */

require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication and proper role
requireAuth(['admin', 'inventory_manager']);

// Get item ID
$item_id = (int)($_GET['id'] ?? 0);

if (!$item_id) {
    header('Location: index.php?error=Invalid item ID');
    exit();
}

// Get item details
$item = fetchSingle("SELECT * FROM items WHERE id = ?", [$item_id], 'i');

if (!$item) {
    header('Location: index.php?error=Item not found');
    exit();
}

try {
    // Generate QR code
    $qr_filename = QRCode::generateForItem($item_id);
    
    if ($qr_filename) {
        // Log activity
        logActivity('Generate QR Code', 'items', $item_id);
        
        header('Location: view.php?id=' . $item_id . '&success=QR code generated successfully');
    } else {
        header('Location: view.php?id=' . $item_id . '&error=Failed to generate QR code');
    }
} catch (Exception $e) {
    header('Location: view.php?id=' . $item_id . '&error=Error generating QR code: ' . $e->getMessage());
}

exit();
?>