<?php
/**
 * User Permissions Management Page for NBTS Inventory Management System
 * 
 * **NEW PAGE** - Allows admin to customize role permissions
 */

require_once '../../config/config.php';
require_once '../../includes/auth.php';

// Require admin authentication
requireAuth(['admin']);

$page_title = 'User Permissions';

$success_message = '';
$error_message = '';

// Handle permission updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_permissions'])) {
    $role = sanitizeInput($_POST['role']);
    $permissions = $_POST['permissions'] ?? [];
    
    if (empty($role)) {
        $error_message = 'Please select a role.';
    } else {
        try {
            $result = Auth::updateRolePermissions($role, $permissions);
            
            if ($result['success']) {
                $success_message = $result['message'];
                
                // Log activity
                logActivity('Update Role Permissions', 'user_permissions', null, null, [
                    'role' => $role,
                    'permissions' => $permissions
                ]);
            } else {
                $error_message = $result['message'];
            }
        } catch (Exception $e) {
            $error_message = 'Error updating permissions: ' . $e->getMessage();
        }
    }
}

// Get available permissions structure
$available_permissions = Auth::getAvailablePermissions();

// Get current permissions for all roles
$current_permissions = [];
$roles = ['admin', 'inventory_manager', 'view_only', 'auditor'];

foreach ($roles as $role) {
    $current_permissions[$role] = Auth::getPermissions($role);
}

include '../../includes/header.php';
?>

<div class="page-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-shield-lock me-2"></i>User Permissions</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Users</a></li>
                    <li class="breadcrumb-item active">Permissions</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Users
            </a>
        </div>
    </div>
</div>

<!-- Role Selection -->
<div class="card fade-in mb-4">
    <div class="card-header">
        <h5 class="mb-0">Select Role to Configure</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <?php foreach ($roles as $role): ?>
                <div class="col-md-3 mb-3">
                    <button type="button" class="btn btn-outline-primary w-100" onclick="loadRolePermissions('<?php echo $role; ?>')">
                        <i class="bi bi-person-badge me-2"></i>
                        <?php echo ucfirst(str_replace('_', ' ', $role)); ?>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Permissions Configuration -->
<div class="card fade-in" id="permissions-card" style="display: none;">
    <div class="card-header">
        <h5 class="mb-0">Configure Permissions for <span id="current-role"></span></h5>
    </div>
    <div class="card-body">
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-2"></i><?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="permissionsForm">
            <input type="hidden" name="role" id="selected-role">
            
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th class="text-center">Create</th>
                            <th class="text-center">Read</th>
                            <th class="text-center">Update</th>
                            <th class="text-center">Delete</th>
                            <th class="text-center">Export</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($available_permissions as $module => $actions): ?>
                            <tr>
                                <td><strong><?php echo ucfirst(str_replace('_', ' ', $module)); ?></strong></td>
                                <?php foreach (['create', 'read', 'update', 'delete', 'export'] as $action): ?>
                                    <td class="text-center">
                                        <?php if (in_array($action, $actions)): ?>
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="permissions[<?php echo $module; ?>][]" 
                                                       value="<?php echo $action; ?>"
                                                       id="<?php echo $module; ?>_<?php echo $action; ?>">
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" name="update_permissions" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Update Permissions
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$additional_js = '
<script>
const currentPermissions = ' . json_encode($current_permissions) . ';

function loadRolePermissions(role) {
    document.getElementById("current-role").textContent = role.replace("_", " ").toUpperCase();
    document.getElementById("selected-role").value = role;
    document.getElementById("permissions-card").style.display = "block";
    
    // Clear all checkboxes
    document.querySelectorAll("input[type=\"checkbox\"]").forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Set current permissions
    const rolePermissions = currentPermissions[role] || {};
    
    Object.keys(rolePermissions).forEach(module => {
        const actions = rolePermissions[module];
        actions.forEach(action => {
            const checkbox = document.getElementById(module + "_" + action);
            if (checkbox) {
                checkbox.checked = true;
            }
        });
    });
    
    // Scroll to permissions card
    document.getElementById("permissions-card").scrollIntoView({ behavior: "smooth" });
}

// Form validation
document.getElementById("permissionsForm").addEventListener("submit", function(e) {
    const role = document.getElementById("selected-role").value;
    
    if (!role) {
        e.preventDefault();
        showToast("Error", "Please select a role first", "error");
        return false;
    }
    
    if (!confirm("Are you sure you want to update permissions for " + role.replace("_", " ") + "?")) {
        e.preventDefault();
        return false;
    }
});
</script>
';

include '../../includes/footer.php';
?>