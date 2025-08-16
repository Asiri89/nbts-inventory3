<?php
/**
 * General Utility Functions for NBTS Inventory Management System
 * 
 * This file contains utility functions used throughout the application
 */

require_once BASE_PATH . '/config/config.php';

/**
 * QR Code Generation Functions
 */
class QRCode {
    
    /**
     * Generate QR code for inventory item
     */
    public static function generateForInventory($inventory_id) {
        try {
            // Get inventory details
			$inventory_query = "
				SELECT inv.id, inv.item_id, inv.serial_number, inv.quantity,
					   inv.location, i.name AS item_name, i.code AS item_code,
					   i.unit_of_measure, i.category_id,  -- ✅ add this line
					   b.name AS branch_name
				FROM inventory inv
				JOIN items i ON inv.item_id = i.id
				JOIN branches b ON inv.branch_id = b.id
				WHERE inv.status = 'active'
			";

            
            if (!$inventory) {
                return false;
            }
            
            // Create QR code data
            $qr_data = json_encode([
                'type' => 'inventory',
                'id' => $inventory_id,
                'item_name' => $inventory['name'],
                'item_code' => $inventory['code'],
                'serial_number' => $inventory['serial_number'],
                'branch' => $inventory['branch_name'],
                'url' => BASE_URL . '/pages/inventory/view.php?id=' . $inventory_id
            ]);
            
            // Generate QR code using online service (you can replace with local library)
            $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($qr_data);
            
            // Save QR code image
            $qr_filename = 'inv_' . $inventory_id . '_' . time() . '.png';
            $qr_filepath = QR_CODES_DIR . '/' . $qr_filename;
            
            if (file_put_contents($qr_filepath, file_get_contents($qr_code_url))) {
                // Update inventory record with QR code path
                executeQuery(
                    "UPDATE inventory SET qr_code = ? WHERE id = ?",
                    [$qr_filename, $inventory_id],
                    'si'
                );
                
                return $qr_filename;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("QR Code generation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate QR code for item
     */
    public static function generateForItem($item_id) {
        try {
            $item = fetchSingle(
                "SELECT * FROM items WHERE id = ?",
                [$item_id],
                'i'
            );
            
            if (!$item) {
                return false;
            }
            
            $qr_data = json_encode([
                'type' => 'item',
                'id' => $item_id,
                'name' => $item['name'],
                'code' => $item['code'],
                'url' => BASE_URL . '/pages/items/view.php?id=' . $item_id
            ]);
            
            $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($qr_data);
            $qr_filename = 'item_' . $item_id . '_' . time() . '.png';
            $qr_filepath = QR_CODES_DIR . '/' . $qr_filename;
            
            if (file_put_contents($qr_filepath, file_get_contents($qr_code_url))) {
                executeQuery(
                    "UPDATE items SET qr_code = ? WHERE id = ?",
                    [$qr_filename, $item_id],
                    'si'
                );
                
                return $qr_filename;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("QR Code generation error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * File Upload Functions
 */
class FileUpload {
    
    /**
     * Upload file with validation
     */
    public static function upload($file, $directory = 'documents') {
        try {
            if (!isset($file['error']) || is_array($file['error'])) {
                throw new RuntimeException('Invalid file parameters');
            }
            
            // Check upload errors
            switch ($file['error']) {
                case UPLOAD_ERR_OK:
                    break;
                case UPLOAD_ERR_NO_FILE:
                    throw new RuntimeException('No file sent');
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new RuntimeException('File size exceeds limit');
                default:
                    throw new RuntimeException('Unknown upload error');
            }
            
            // Check file size
            if ($file['size'] > MAX_FILE_SIZE) {
                throw new RuntimeException('File size exceeds maximum allowed size');
            }
            
            // Get file extension
            $file_info = pathinfo($file['name']);
            $extension = strtolower($file_info['extension']);
            
            // Check file type
            if (!in_array($extension, ALLOWED_FILE_TYPES)) {
                throw new RuntimeException('File type not allowed');
            }
            
            // Generate unique filename
            $new_filename = uniqid() . '_' . time() . '.' . $extension;
            $upload_path = constant(strtoupper($directory) . '_DIR') . '/' . $new_filename;
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                throw new RuntimeException('Failed to move uploaded file');
            }
            
            return [
                'success' => true,
                'filename' => $new_filename,
                'original_name' => $file['name'],
                'size' => $file['size'],
                'type' => $file['type']
            ];
            
        } catch (RuntimeException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete uploaded file
     */
    public static function delete($filename, $directory = 'documents') {
        $file_path = constant(strtoupper($directory) . '_DIR') . '/' . $filename;
        if (file_exists($file_path)) {
            return unlink($file_path);
        }
        return true;
    }
}

/**
 * Report Generation Functions
 */
class ReportGenerator {
    
    /**
     * Generate CSV report
     */
    public static function generateCSV($data, $filename, $headers = []) {
        $csv_file = REPORTS_DIR . '/' . $filename . '_' . date('Y-m-d_H-i-s') . '.csv';
        $file = fopen($csv_file, 'w');
        
        // Add BOM for UTF-8
        fwrite($file, "\xEF\xBB\xBF");
        
        // Write headers
        if (!empty($headers)) {
            fputcsv($file, $headers);
        }
        
        // Write data
        foreach ($data as $row) {
            fputcsv($file, $row);
        }
        
        fclose($file);
        return basename($csv_file);
    }
    
    /**
     * Generate HTML report for PDF conversion
     */
    public static function generateHTML($title, $data, $template = 'default') {
        $html = '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . $title . '</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .logo { max-width: 100px; }
                .report-title { color: #2c3e50; margin: 10px 0; }
                .report-date { color: #7f8c8d; font-size: 14px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
                th { background-color: #f2f2f2; font-weight: bold; }
                .text-right { text-align: right; }
                .text-center { text-align: center; }
                .total-row { background-color: #f9f9f9; font-weight: bold; }
                .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #7f8c8d; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1 class="report-title">National Blood Transfusion Service</h1>
                <h2 class="report-title">' . $title . '</h2>
                <p class="report-date">Generated on: ' . date('Y-m-d H:i:s') . '</p>
            </div>
            ' . $data . '
            <div class="footer">
                <p>This report was generated by NBTS Inventory Management System</p>
            </div>
        </body>
        </html>';
        
        $filename = 'report_' . date('Y-m-d_H-i-s') . '.html';
        $file_path = REPORTS_DIR . '/' . $filename;
        file_put_contents($file_path, $html);
        
        return $filename;
    }
}

/**
 * Notification Functions
 */
class NotificationManager {
    
    /**
     * Check for low stock and create notifications
     */
    public static function checkLowStock() {
        try {
            $low_stock_items = fetchAll(
                "SELECT i.id, i.name, i.code, i.reorder_level, b.id as branch_id, b.name as branch_name,
                        SUM(inv.quantity) as current_stock
                 FROM items i
                 JOIN inventory inv ON i.id = inv.item_id
                 JOIN branches b ON inv.branch_id = b.id
                 WHERE inv.status = 'active'
                 GROUP BY i.id, b.id
                 HAVING current_stock <= i.reorder_level"
            );
            
            foreach ($low_stock_items as $item) {
                // Check if notification already exists for today
                $existing = fetchSingle(
                    "SELECT id FROM notifications 
                     WHERE type = 'low_stock' AND related_table = 'items' AND related_id = ? 
                     AND branch_id = ? AND DATE(created_at) = CURDATE()",
                    [$item['id'], $item['branch_id']],
                    'ii'
                );
                
                if (!$existing) {
                    createNotification(
                        'low_stock',
                        'Low Stock Alert',
                        "Item {$item['name']} ({$item['code']}) at {$item['branch_name']} has low stock. Current: {$item['current_stock']}, Reorder Level: {$item['reorder_level']}",
                        null,
                        $item['branch_id'],
                        'items',
                        $item['id']
                    );
                }
            }
            
        } catch (Exception $e) {
            error_log("Low stock check error: " . $e->getMessage());
        }
    }
    
    /**
     * Check for warranty expiry and create notifications
     */
    public static function checkWarrantyExpiry() {
        try {
            $expiring_warranties = fetchAll(
                "SELECT inv.id, i.name, i.code, inv.serial_number, inv.warranty_end_date,
                        b.id as branch_id, b.name as branch_name,
                        DATEDIFF(inv.warranty_end_date, CURDATE()) as days_to_expiry
                 FROM inventory inv
                 JOIN items i ON inv.item_id = i.id
                 JOIN branches b ON inv.branch_id = b.id
                 WHERE inv.warranty_end_date IS NOT NULL 
                 AND inv.warranty_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                 AND inv.status = 'active'"
            );
            
            foreach ($expiring_warranties as $warranty) {
                $existing = fetchSingle(
                    "SELECT id FROM notifications 
                     WHERE type = 'warranty_expiry' AND related_table = 'inventory' AND related_id = ? 
                     AND DATE(created_at) = CURDATE()",
                    [$warranty['id']],
                    'i'
                );
                
                if (!$existing) {
                    createNotification(
                        'warranty_expiry',
                        'Warranty Expiry Alert',
                        "Warranty for {$warranty['name']} (S/N: {$warranty['serial_number']}) at {$warranty['branch_name']} expires in {$warranty['days_to_expiry']} days",
                        null,
                        $warranty['branch_id'],
                        'inventory',
                        $warranty['id']
                    );
                }
            }
            
        } catch (Exception $e) {
            error_log("Warranty expiry check error: " . $e->getMessage());
        }
    }
    
    /**
     * Check for maintenance due and create notifications
     */
    public static function checkMaintenanceDue() {
        try {
            $due_maintenance = fetchAll(
                "SELECT ms.id, i.name, i.code, inv.serial_number, ms.next_maintenance_date,
                        b.id as branch_id, b.name as branch_name, ms.maintenance_type,
                        DATEDIFF(ms.next_maintenance_date, CURDATE()) as days_overdue
                 FROM maintenance_schedules ms
                 JOIN inventory inv ON ms.inventory_id = inv.id
                 JOIN items i ON inv.item_id = i.id
                 JOIN branches b ON inv.branch_id = b.id
                 WHERE ms.status = 'scheduled' AND ms.next_maintenance_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)"
            );
            
            foreach ($due_maintenance as $maintenance) {
                $existing = fetchSingle(
                    "SELECT id FROM notifications 
                     WHERE type = 'maintenance_due' AND related_table = 'maintenance_schedules' AND related_id = ? 
                     AND DATE(created_at) = CURDATE()",
                    [$maintenance['id']],
                    'i'
                );
                
                if (!$existing) {
                    $status = $maintenance['days_overdue'] < 0 ? 'overdue' : 'due';
                    $days_text = abs($maintenance['days_overdue']?? 0) == 0 ? 'today' : 
                                (abs($maintenance['days_overdue']?? 0) . ' days ' . ($maintenance['days_overdue'] < 0 ? 'overdue' : 'remaining'));
                    
                    createNotification(
                        'maintenance_due',
                        'Maintenance Due Alert',
                        "{$maintenance['maintenance_type']} maintenance for {$maintenance['name']} (S/N: {$maintenance['serial_number']}) at {$maintenance['branch_name']} is {$status} - {$days_text}",
                        null,
                        $maintenance['branch_id'],
                        'maintenance_schedules',
                        $maintenance['id']
                    );
                }
            }
            
        } catch (Exception $e) {
            error_log("Maintenance due check error: " . $e->getMessage());
        }
    }
}

/**
 * Dashboard Statistics Functions
 */
class DashboardStats {
       /**
     * Get low stock count
     */
    public static function getLowStockCount($branch_id = null) {
    $query = "SELECT COUNT(*) as count FROM (
                SELECT i.id, i.reorder_level
                FROM items i
                JOIN inventory inv ON i.id = inv.item_id
                WHERE inv.status = 'active'";
    
    $params = [];
    $types = '';
    
    if ($branch_id) {
        $query .= " AND inv.branch_id = ?";
        $params[] = $branch_id;
        $types = 'i';
    }

    $query .= " GROUP BY i.id" . ($branch_id ? ", inv.branch_id" : "") . "
                HAVING SUM(inv.quantity) <= i.reorder_level
              ) AS low_stock";
    
    $result = fetchSingle($query, $params, $types);
    return $result['count'] ?? 0;
}


        /**
        /**
     * Get warranty expiry count
     */
    public static function getWarrantyExpiryCount($branch_id = null, $days = 30) {
        $query = "SELECT COUNT(*) as count FROM inventory 
                  WHERE warranty_end_date IS NOT NULL 
                  AND warranty_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                  AND status = 'active'";
        
        $params = [$days];
        $types = 'i';
        
        if ($branch_id) {
            $query .= " AND branch_id = ?";
            $params[] = $branch_id;
            $types = 'ii';
        }
        
        $result = fetchSingle($query, $params, $types);
        return $result['count'] ?? 0;
    }
    
    /**
     * Get maintenance due count
     */
    public static function getMaintenanceDueCount($branch_id = null, $days = 7) {
        $query = "SELECT COUNT(*) as count FROM maintenance_schedules ms
                  JOIN inventory inv ON ms.inventory_id = inv.id
                  WHERE ms.status = 'scheduled' 
                  AND ms.next_maintenance_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)";
        
        $params = [$days];
        $types = 'i';
        
        if ($branch_id) {
            $query .= " AND inv.branch_id = ?";
            $params[] = $branch_id;
            $types = 'ii';
        }
        
        $result = fetchSingle($query, $params, $types);
        return $result['count'] ?? 0;
    }
    
    /**
	* Get pending allocations count
     */
    public static function getPendingAllocationsCount($branch_id = null) {
        $query = "SELECT COUNT(*) as count FROM allocation_items 
                  WHERE status = 'allocated'";
        
        $params = [];
        $types = '';
       
        if ($branch_id) {
            $query .= " AND branch_id = ?";
            $params = [$branch_id];
            $types = 'i';
        }
        
        $result = fetchSingle($query, $params, $types);
        return $result['count'] ?? 0;
    }
    
    /**
     * Get recent activities
     */
    public static function getRecentActivities($limit = 10, $branch_id = null) {
        $query = "SELECT al.*, u.first_name, u.last_name, u.username 
                  FROM activity_logs al
                  JOIN users u ON al.user_id = u.id";
        
        $params = [];
        $types = '';
       
        if ($branch_id) {
            $query .= " WHERE u.branch_id = ?";
            $params[] = $branch_id;
            $types = 'i';
        }
       
        $query .= " ORDER BY al.created_at DESC LIMIT ?";
        $params[] = $limit;
        $types .= 'i';
        
        return fetchAll($query, $params, $types);
    }
}
// --- add near other helpers ---
function url($path, $params = []) {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $href = $base . '/' . ltrim($path, '/');
    if (!empty($params)) {
        $href .= (strpos($href, '?') === false ? '?' : '&') . http_build_query($params);
    }
    return htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
}

?>
