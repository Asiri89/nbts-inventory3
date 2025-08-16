<?php
/**
 * **NEW PAGE** - Edit Inventory Page for NBTS Inventory Management System
 * 
 * This page handles editing existing inventory records
 */

require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication and proper role
requireAuth(['admin', 'inventory_manager']);

$page_title = 'Edit Inventory';

// Get inventory ID
$inventory_id = (int)($_GET['id'] ?? 0);

if (!$inventory_id) {
    header('Location: index.php?error=Invalid inventory ID');
    exit();
}

// Get inventory details
$inventory = fetchSingle(
    "SELECT inv.*, i.name as item_name, i.code as item_code, b.name as branch_name
     FROM inventory inv
     JOIN items i ON inv.item_id = i.id
     JOIN branches b ON inv.branch_id = b.id
     WHERE inv.id = ?",
    [$inventory_id],
    'i'
);

if (!$inventory) {
    header('Location: index.php?error=Inventory record not found');
    exit();
}

// Check branch access
if (!hasBranchAccess($inventory['branch_id'])) {
    header('Location: index.php?error=Access denied');
    exit();
}

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $location = sanitizeInput($_POST['location']);
    $assigned_staff = sanitizeInput($_POST['assigned_staff']);
    $status = sanitizeInput($_POST['status']);
    $installation_date = !empty($_POST['installation_date']) ? $_POST['installation_date'] : null;
    $notes = sanitizeInput($_POST['notes']);
    
    try {
        // Store old values for logging
        $old_values = $inventory;
        
        // Update inventory
        executeQuery(
            "UPDATE inventory SET location = ?, assigned_staff = ?, status = ?, installation_date = ?, notes = ?, updated_at = NOW() WHERE id = ?",
            [$location, $assigned_staff, $status, $installation_date, $notes, $inventory_id],
            'sssssi'
        );
        
        // Log activity
        logActivity('Update Inventory', 'inventory', $inventory_id, $old_values, [
            'location' => $location,
            'assigned_staff' => $assigned_staff,
            'status' => $status
        ]);
        
        $success_message = 'Inventory updated successfully.';
        
        // Redirect after successful update
        header('Location: view.php?id=' . $inventory_id . '&success=' . urlencode($success_message));
        exit();
        
    } catch (Exception $e) {
        $error_message = 'Error updating inventory: ' . $e->getMessage();
    }
}

include '../../includes/header.php';
?>

<div class="page-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-pencil me-2"></i>Edit Inventory</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Inventory</a></li>
                    <li class="breadcrumb-item"><a href="view.php?id=<?php echo $inventory['id']; ?>"><?php echo htmlspecialchars($inventory['item_code']); ?></a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="view.php?id=<?php echo $inventory['id']; ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Item
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card fade-in">
            <div class="card-header">
                <h5 class="mb-0">Edit Inventory Information</h5>
            </div>
            <div class="card-body">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Current Item Info -->
                <div class="alert alert-info">
                    <h6><i class="bi bi-info-circle me-2"></i>Item Information</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Item:</strong> <?php echo htmlspecialchars($inventory['item_name']); ?><br>
                            <strong>Code:</strong> <?php echo htmlspecialchars($inventory['item_code']); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Branch:</strong> <?php echo htmlspecialchars($inventory['branch_name']); ?><br>
                            <strong>Serial:</strong> <?php echo htmlspecialchars($inventory['serial_number'] ?: '-'); ?>
                        </div>
                    </div>
                </div>
                
                <form method="POST" id="editForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   value="<?php echo htmlspecialchars($_POST['location'] ?? $inventory['location'] ?? ''); ?>"
                                   placeholder="e.g., Lab Room 1, Storage Area A">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="assigned_staff" class="form-label">Assigned Staff</label>
                            <input type="text" class="form-control" id="assigned_staff" name="assigned_staff" 
                                   value="<?php echo htmlspecialchars($_POST['assigned_staff'] ?? $inventory['assigned_staff'] ?? ''); ?>"
                                   placeholder="Staff member responsible for this item">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <?php
                                $current_status = $_POST['status'] ?? $inventory['status'];
                                $statuses = ['active', 'maintenance', 'repair', 'disposed', 'lost', 'damaged'];
                                ?>
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?php echo $status; ?>" <?php echo ($current_status === $status) ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($status); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="installation_date" class="form-label">Installation Date</label>
                            <input type="date" class="form-control" id="installation_date" name="installation_date" 
                                   value="<?php echo htmlspecialchars($_POST['installation_date'] ?? $inventory['installation_date'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"
                                  placeholder="Additional notes or comments..."><?php echo htmlspecialchars($_POST['notes'] ?? $inventory['notes'] ?? '', ENT_QUOTES); ?></textarea>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="view.php?id=<?php echo $inventory['id']; ?>" class="btn btn-outline-secondary me-md-2">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Update Inventory
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card fade-in">
            <div class="card-header">
                <h6 class="mb-0">Current Details</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>Quantity:</strong></td>
                        <td><?php echo number_format($inventory['quantity']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Unit Cost:</strong></td>
                        <td><?php echo formatCurrency($inventory['unit_cost']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total Cost:</strong></td>
                        <td><?php echo formatCurrency($inventory['total_cost']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Created:</strong></td>
                        <td><?php echo formatDateTime($inventory['created_at']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Last Updated:</strong></td>
                        <td><?php echo formatDateTime($inventory['updated_at']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>