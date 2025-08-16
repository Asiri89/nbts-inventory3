<?php
/**
 * Adjust Stock Page for NBTS Inventory Management System
 * 
 * **NEW PAGE** - Handles stock adjustments for corrections, damages, and losses
 */

require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication and proper role
requireAuth(['admin', 'inventory_manager']);

$page_title = 'Adjust Stock';

// Get inventory ID
$inventory_id = (int)($_GET['inventory_id'] ?? 0);

if (!$inventory_id) {
    header('Location: index.php?error=Invalid inventory ID');
    exit();
}

// Get inventory details
$inventory = fetchSingle(
    "SELECT inv.*, i.name as item_name, i.code as item_code, i.unit_of_measure,
            b.name as branch_name
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
    $adjustment_type = sanitizeInput($_POST['adjustment_type']);
    $quantity_change = (int)$_POST['quantity_change'];
    $reason = sanitizeInput($_POST['reason']);
    $notes = sanitizeInput($_POST['notes']);
    
    if (empty($adjustment_type) || empty($quantity_change) || empty($reason)) {
        $error_message = 'Please fill in all required fields.';
    } elseif ($adjustment_type === 'decrease' && $quantity_change > $inventory['quantity']) {
        $error_message = 'Cannot decrease quantity more than available stock (' . $inventory['quantity'] . ').';
    } else {
        try {
            $old_quantity = $inventory['quantity'];
            $new_quantity = ($adjustment_type === 'increase') ? 
                           $old_quantity + $quantity_change : 
                           $old_quantity - $quantity_change;
            
            if ($new_quantity < 0) {
                $new_quantity = 0;
            }
            
            // Update inventory
            executeQuery(
                "UPDATE inventory SET quantity = ?, total_cost = ? * unit_cost, updated_at = NOW() WHERE id = ?",
                [$new_quantity, $new_quantity, $inventory_id],
                'idi'
            );
            
            // Create stock movement record
            $movement_type = 'adjustment';
            $movement_quantity = ($adjustment_type === 'increase') ? $quantity_change : -$quantity_change;
            
            executeQuery(
                "INSERT INTO stock_movements (inventory_id, movement_type, to_branch_id, quantity, unit_cost, total_cost, reason, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$inventory_id, $movement_type, $inventory['branch_id'], $movement_quantity, $inventory['unit_cost'], $movement_quantity * $inventory['unit_cost'], $reason, $notes, $_SESSION['user_id']],
                'isiidsssi'
            );
            
            // Log activity
            logActivity('Adjust Stock', 'inventory', $inventory_id, 
                       ['quantity' => $old_quantity], 
                       ['quantity' => $new_quantity, 'adjustment_type' => $adjustment_type, 'reason' => $reason]);
            
            $success_message = 'Stock adjusted successfully.';
            
            // Redirect after successful adjustment
            header('Location: view.php?id=' . $inventory_id . '&success=' . urlencode($success_message));
            exit();
            
        } catch (Exception $e) {
            $error_message = 'Error adjusting stock: ' . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
?>

<div class="page-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-plus-minus me-2"></i>Adjust Stock</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Inventory</a></li>
                    <li class="breadcrumb-item"><a href="view.php?id=<?php echo $inventory['id']; ?>"><?php echo htmlspecialchars($inventory['item_code']); ?></a></li>
                    <li class="breadcrumb-item active">Adjust Stock</li>
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
                <h5 class="mb-0">Stock Adjustment</h5>
            </div>
            <div class="card-body">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Current Item Info -->
                <div class="alert alert-info">
                    <h6><i class="bi bi-info-circle me-2"></i>Current Item Information</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Item:</strong> <?php echo htmlspecialchars($inventory['item_name']); ?><br>
                            <strong>Code:</strong> <?php echo htmlspecialchars($inventory['item_code']); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Current Quantity:</strong> <?php echo number_format($inventory['quantity']); ?> <?php echo htmlspecialchars($inventory['unit_of_measure']); ?><br>
                            <strong>Branch:</strong> <?php echo htmlspecialchars($inventory['branch_name']); ?>
                        </div>
                    </div>
                </div>
                
                <form method="POST" id="adjustForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="adjustment_type" class="form-label">Adjustment Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="adjustment_type" name="adjustment_type" required onchange="updateAdjustmentInfo()">
                                <option value="">Select Adjustment Type</option>
                                <option value="increase" <?php echo (($_POST['adjustment_type'] ?? '') === 'increase') ? 'selected' : ''; ?>>Increase Stock</option>
                                <option value="decrease" <?php echo (($_POST['adjustment_type'] ?? '') === 'decrease') ? 'selected' : ''; ?>>Decrease Stock</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="quantity_change" class="form-label">Quantity Change <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="quantity_change" name="quantity_change" 
                                   value="<?php echo htmlspecialchars($_POST['quantity_change'] ?? '1'); ?>" 
                                   min="1" required onchange="calculateNewQuantity()">
                            <div class="form-text">
                                New quantity will be: <span id="new-quantity" class="fw-bold">-</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason for Adjustment <span class="text-danger">*</span></label>
                        <select class="form-select" id="reason" name="reason" required>
                            <option value="">Select Reason</option>
                            <optgroup label="Increase Reasons">
                                <option value="Stock count correction" <?php echo (($_POST['reason'] ?? '') === 'Stock count correction') ? 'selected' : ''; ?>>Stock Count Correction</option>
                                <option value="Found missing items" <?php echo (($_POST['reason'] ?? '') === 'Found missing items') ? 'selected' : ''; ?>>Found Missing Items</option>
                                <option value="Return from maintenance" <?php echo (($_POST['reason'] ?? '') === 'Return from maintenance') ? 'selected' : ''; ?>>Return from Maintenance</option>
                            </optgroup>
                            <optgroup label="Decrease Reasons">
                                <option value="Damaged items" <?php echo (($_POST['reason'] ?? '') === 'Damaged items') ? 'selected' : ''; ?>>Damaged Items</option>
                                <option value="Lost items" <?php echo (($_POST['reason'] ?? '') === 'Lost items') ? 'selected' : ''; ?>>Lost Items</option>
                                <option value="Expired items" <?php echo (($_POST['reason'] ?? '') === 'Expired items') ? 'selected' : ''; ?>>Expired Items</option>
                                <option value="Stock count correction" <?php echo (($_POST['reason'] ?? '') === 'Stock count correction') ? 'selected' : ''; ?>>Stock Count Correction</option>
                                <option value="Disposed items" <?php echo (($_POST['reason'] ?? '') === 'Disposed items') ? 'selected' : ''; ?>>Disposed Items</option>
                            </optgroup>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="notes" class="form-label">Additional Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"
                                  placeholder="Provide additional details about this adjustment..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="view.php?id=<?php echo $inventory['id']; ?>" class="btn btn-outline-secondary me-md-2">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-plus-minus me-2"></i>Adjust Stock
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card fade-in">
            <div class="card-header">
                <h6 class="mb-0">Adjustment Guidelines</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6><i class="bi bi-arrow-up-circle text-success me-2"></i>Increase Stock</h6>
                    <div class="small">
                        Use when you find additional items, correct counting errors, or items return from maintenance.
                    </div>
                </div>
                
                <div class="mb-3">
                    <h6><i class="bi bi-arrow-down-circle text-danger me-2"></i>Decrease Stock</h6>
                    <div class="small">
                        Use for damaged, lost, expired, or disposed items. Always provide detailed reasons.
                    </div>
                </div>
                
                <div class="alert alert-warning small">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Important:</strong> All adjustments are logged and cannot be undone. Ensure accuracy before submitting.
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$additional_js = '
<script>
function updateAdjustmentInfo() {
    calculateNewQuantity();
}

function calculateNewQuantity() {
    const adjustmentType = document.getElementById("adjustment_type").value;
    const quantityChange = parseInt(document.getElementById("quantity_change").value) || 0;
    const currentQuantity = ' . $inventory['quantity'] . ';
    
    let newQuantity;
    if (adjustmentType === "increase") {
        newQuantity = currentQuantity + quantityChange;
    } else if (adjustmentType === "decrease") {
        newQuantity = Math.max(0, currentQuantity - quantityChange);
    } else {
        newQuantity = currentQuantity;
    }
    
    document.getElementById("new-quantity").textContent = newQuantity + " " + "' . $inventory['unit_of_measure'] . '";
    
    // Update quantity change max for decrease
    if (adjustmentType === "decrease") {
        document.getElementById("quantity_change").max = currentQuantity;
    } else {
        document.getElementById("quantity_change").removeAttribute("max");
    }
}

// Form validation
document.getElementById("adjustForm").addEventListener("submit", function(e) {
    const adjustmentType = document.getElementById("adjustment_type").value;
    const quantityChange = parseInt(document.getElementById("quantity_change").value);
    const reason = document.getElementById("reason").value;
    
    if (!adjustmentType || !quantityChange || quantityChange <= 0 || !reason) {
        e.preventDefault();
        showToast("Error", "Please fill in all required fields", "error");
        return false;
    }
    
    if (adjustmentType === "decrease" && quantityChange > ' . $inventory['quantity'] . ') {
        e.preventDefault();
        showToast("Error", "Cannot decrease more than available stock", "error");
        return false;
    }
    
    // Confirmation dialog
    const action = adjustmentType === "increase" ? "increase" : "decrease";
    if (!confirm(`Are you sure you want to ${action} stock by ${quantityChange} units?`)) {
        e.preventDefault();
        return false;
    }
});

// Initialize
document.addEventListener("DOMContentLoaded", function() {
    calculateNewQuantity();
});
</script>
';

include '../../includes/footer.php';
?>