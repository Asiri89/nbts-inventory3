<?php
/**
 * Authentication Functions for NBTS Inventory Management System
 * 
 * This file contains all authentication-related functions
 * including login, logout, and session management.
 */

require_once __DIR__ . '/../config/config.php';

// User authentication class
class Auth {
    
    /**
     * Authenticate user login
     */
    public static function login($username, $password) {
        try {
            // Get user from database
            $user = fetchSingle(
                "SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1",
                [$username, $username],
                'ss'
            );
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            // Check password
            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            // Create session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_branch_id'] = $user['branch_id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['last_activity'] = time();
            
            // Update last login
            executeQuery(
                "UPDATE users SET last_login = NOW() WHERE id = ?",
                [$user['id']],
                'i'
            );
            
            // Log activity
            logActivity('User Login');
            
            return ['success' => true, 'message' => 'Login successful'];
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred during login'];
        }
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        if (isLoggedIn()) {
            logActivity('User Logout');
        }
        
        session_unset();
        session_destroy();
        
        header('Location: ' . BASE_URL . '/pages/auth/login.php?logged_out=1');
        exit();
    }
    
    /**
     * Get current user information
     */
    public static function getCurrentUser() {
        if (!isLoggedIn()) {
            return null;
        }
        
        try {
            return fetchSingle(
                "SELECT u.*, b.name as branch_name FROM users u 
                 LEFT JOIN branches b ON u.branch_id = b.id 
                 WHERE u.id = ?",
                [$_SESSION['user_id']],
                'i'
            );
        } catch (Exception $e) {
            error_log("Get current user error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Change user password
     */
    public static function changePassword($user_id, $current_password, $new_password) {
        try {
            // Get current password hash
            $user = fetchSingle(
                "SELECT password FROM users WHERE id = ?",
                [$user_id],
                'i'
            );
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Verify current password
            if (!password_verify($current_password, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            // Hash new password
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            executeQuery(
                "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?",
                [$new_password_hash, $user_id],
                'si'
            );
            
            // Log activity
            logActivity('Password Changed', 'users', $user_id);
            
            return ['success' => true, 'message' => 'Password changed successfully'];
            
        } catch (Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while changing password'];
        }
    }
    
    /**
     * Get user permissions based on role (now from database)
     */
    public static function getPermissions($role) {
        try {
            $permissions_data = fetchAll(
                "SELECT module_name, permission_type FROM user_permissions 
                 WHERE role_name = ? AND is_allowed = 1",
                [$role],
                's'
            );
           
            $permissions = [];
            foreach ($permissions_data as $perm) {
               if (!isset($permissions[$perm['module_name']])) {
                    $permissions[$perm['module_name']] = [];
                }
                $permissions[$perm['module_name']][] = $perm['permission_type'];
            }
          
           return $permissions;
           
        } catch (Exception $e) {
            error_log("Error getting permissions: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update role permissions
     */
    public static function updateRolePermissions($role, $permissions) {
        try {
            // Delete existing permissions for this role
            executeQuery(
                "DELETE FROM user_permissions WHERE role_name = ?",
                [$role],
                's'
            );
           
            // Insert new permissions
            foreach ($permissions as $module => $actions) {
                foreach ($actions as $action) {
                    executeQuery(
                        "INSERT INTO user_permissions (role_name, module_name, permission_type) VALUES (?, ?, ?)",
                        [$role, $module, $action],
                        'sss'
                    );
                }
            }
            
            return ['success' => true, 'message' => 'Permissions updated successfully'];
            
        } catch (Exception $e) {
            error_log("Error updating permissions: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error updating permissions'];
        }
    }
    
    /**
     * Get all available modules and actions
     */
    public static function getAvailablePermissions() {
        return [
            'users' => ['create', 'read', 'update', 'delete'],
            'branches' => ['create', 'read', 'update', 'delete'],
            'suppliers' => ['create', 'read', 'update', 'delete'],
            'categories' => ['create', 'read', 'update', 'delete'],
            'items' => ['create', 'read', 'update', 'delete'],
            'inventory' => ['create', 'read', 'update', 'delete'],
            'movements' => ['create', 'read', 'update', 'delete'],
            'maintenance' => ['create', 'read', 'update', 'delete'],
            'reports' => ['read', 'export'],
            'settings' => ['read', 'update'],
            'logs' => ['read']
        ];
    }
}

/**
 * Central Procurement Functions
 */
class CentralProcurement {
        /**
     * Create bulk allocation
     */
    public static function createBulkAllocation($item_id, $supplier_id, $purchase_date, $invoice_number, $serial_numbers, $unit_cost, $warranty_start, $warranty_end, $notes) {
        try {
            $total_quantity = count($serial_numbers);
            $total_cost = $total_quantity * $unit_cost;
            
            // Insert bulk allocation
            executeQuery(
                "INSERT INTO bulk_allocations (item_id, supplier_id, purchase_date, invoice_number, total_quantity, unit_cost, total_cost, warranty_start_date, warranty_end_date, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$item_id, $supplier_id, $purchase_date, $invoice_number, $total_quantity, $unit_cost, $total_cost, $warranty_start, $warranty_end, $notes, $_SESSION['user_id']],
                'iissiddsssi'
            );
            
            $allocation_id = getLastInsertId();
            
            // Insert individual items
            foreach ($serial_numbers as $serial) {
                if (!empty(trim($serial))) {
                    executeQuery(
                        "INSERT INTO allocation_items (allocation_id, serial_number) VALUES (?, ?)",
                        [$allocation_id, trim($serial)],
                        'is'
                    );
                }
            }
           
            return ['success' => true, 'allocation_id' => $allocation_id];
           
        } catch (Exception $e) {
            error_log("Bulk allocation error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Allocate items to branches
     */
    public static function allocateItemsToBranches($allocation_id, $allocations) {
        try {
            foreach ($allocations as $branch_id => $serial_numbers) {
                foreach ($serial_numbers as $serial) {
                    executeQuery(
                        "UPDATE allocation_items SET branch_id = ?, status = 'allocated', allocated_at = NOW() WHERE allocation_id = ? AND serial_number = ?",
                        [$branch_id, $allocation_id, $serial],
                        'iis'
                    );
                }
            }
           
            return ['success' => true];
    
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

/**
 * Transfer Management Functions
 */
class TransferManager {
    
    /**
     * Create transfer request
     */
    public static function createTransferRequest($from_branch_id, $to_branch_id, $item_id, $quantity, $reason, $priority = 'medium') {
        try {
            executeQuery(
                "INSERT INTO transfer_requests (from_branch_id, to_branch_id, item_id, requested_quantity, reason, priority, requested_by) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$from_branch_id, $to_branch_id, $item_id, $quantity, $reason, $priority, $_SESSION['user_id']],
                'iiisssi'
            );
           
            $request_id = getLastInsertId();
           
            // Create notification
            createNotification(
                'system',
                'Transfer Request',
                "New transfer request for {$quantity} units",
                null,
                $from_branch_id,
                'transfer_requests',
                $request_id
            );
           
            return ['success' => true, 'request_id' => $request_id];
           
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
        /**
     * Accept transfer
     */
    public static function acceptTransfer($movement_id) {
    try {
        executeQuery(
            "UPDATE stock_movements SET transfer_status = 'received' WHERE id = ?",
            [$movement_id],
            'i'
        );
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Some other function that returns permissions
public static function getPermissions($role) {
    // ... some code that sets $permissions array
    return $permissions[$role] ?? [];
}

    /**
     * Check if user has specific permission
     */
    public static function hasPermission($module, $action) {
        if (!isLoggedIn()) {
            return false;
        }
        
        $permissions = self::getPermissions($_SESSION['user_role']);
        
        if (!isset($permissions[$module])) {
            return false;
        }
        
        return in_array($action, $permissions[$module]);
    }
}
?>