<?php
/**
 * Transfer Stock Page for NBTS Inventory Management System
 * 
 * This page handles stock transfers between branches
 */

require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication and proper role
requireAuth(['admin', 'inventory_manager']);

$page_title = 'Transfer Stock';

// Get current user and branch info
$current_user = Auth::getCurrentUser();
$user_branch_id = $_SESSION['user_branch_id'];

// Get pre-selected inventory if provided
$preselected_inventory_id = $_GET['inventory_id'] ?? '';

// Get dropdown data
$inventory_query = "
    SELECT inv.id, inv.serial_number, inv.batch_number, inv.quantity, inv.location,
           i.name as item_name, i.code as item_code, i.unit_of_measure, i.category_id,
           b.name as branch_name
    FROM inventory inv
    JOIN items i ON inv.item_id = i.id
    JOIN branches b ON inv.branch_id = b.id
    WHERE inv.status = 'active' AND inv.quantity > 0
";

$params = [];
$types = '';

// Branch restriction for non-admin users
if ($_SESSION['user_role'] !== 'admin') {
    $inventory_query .= " AND inv.branch_id = ?";
    $params[] = $user_branch_id;
    $types = 'i';
}

$inventory_query .= " ORDER BY i.name, inv.serial_number";
$inventory_items = fetchAll($inventory_query, $params, $types);

// Get branches (exclude current branch for non-admin users)
$branches_query = "SELECT * FROM branches WHERE is_active = 1";
if ($_SESSION['user_role'] !== 'admin') {
    $branches_query .= " AND id != ?";
    $branches = fetchAll($branches_query, [$user_branch_id], 'i');
} else {
    $branches = fetchAll($branches_query);
}

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inventory_id = (int)$_POST['inventory_id'];
    $to_branch_id = (int)$_POST['to_branch_id'];
    $quantity = (int)$_POST['quantity'];
    $reason = sanitizeInput($_POST['reason']);
    $notes = sanitizeInput($_POST['notes']);
    
    // Validate required fields
    if (empty($inventory_id) || empty($to_branch_id) || empty($quantity) || $quantity <= 0) {
        $error_message = 'Please fill in all required fields with valid values.';
    } else {
        try {
            // Get inventory details
            $inventory = fetchSingle(
                "SELECT inv.*, i.name as item_name, i.code as item_code, i.category_id, b.name as from_branch_name
                 FROM inventory inv
                 JOIN items i ON inv.item_id = i.id
                 JOIN branches b ON inv.branch_id = b.id
                 WHERE inv.id = ? AND inv.status = 'active'",
                [$inventory_id],
                'i'
            );
            
            if (!$inventory) {
                $error_message = 'Invalid inventory item selected.';
            } elseif ($quantity > $inventory['quantity']) {
                $error_message = 'Transfer quantity cannot exceed available stock (' . $inventory['quantity'] . ').';
            } elseif (!hasBranchAccess($inventory['branch_id'])) {
                $error_message = 'Access denied to this inventory item.';
            } else {
                // Get destination branch info
                $to_branch = fetchSingle("SELECT name FROM branches WHERE id = ?", [$to_branch_id], 'i');
                $require_acceptance = isset($_POST['require_acceptance']);
                $transfer_status = $require_acceptance ? 'pending' : 'received'; // **NEW**: Transfer status
                
                // Start transaction
                $conn = getDatabaseConnection();
                $conn->autocommit(false);
                
                try {
                    // Update source inventory
                    if ($quantity == $inventory['quantity']) {
                        // Transfer all - update branch
                        executeQuery(
                            "UPDATE inventory SET branch_id = ?, updated_at = NOW() WHERE id = ?",
                            [$require_acceptance ? $inventory['branch_id'] : $to_branch_id, $inventory_id],
                            'ii'
                        );
                        
                        // Create transfer out movement
                        executeQuery(
                            "INSERT INTO stock_movements (inventory_id, movement_type, from_branch_id, to_branch_id, quantity, unit_cost, total_cost, reference_number, reason, notes, transfer_status, created_by) VALUES (?, 'transfer_out', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            [$inventory_id, $inventory['branch_id'], $to_branch_id, $quantity, $inventory['unit_cost'], $quantity * $inventory['unit_cost'], '', $reason, $notes, $transfer_status, $_SESSION['user_id']],
                            'iiidddssssi'
                        );
                        
                        if (!$require_acceptance) {
                            // Create transfer in movement immediately
                            executeQuery(
                                "INSERT INTO stock_movements (inventory_id, movement_type, from_branch_id, to_branch_id, quantity, unit_cost, total_cost, reference_number, reason, notes, transfer_status, created_by) VALUES (?, 'transfer_in', ?, ?, ?, ?, ?, ?, ?, ?, 'received', ?)",
                                [$inventory_id, $inventory['branch_id'], $to_branch_id, $quantity, $inventory['unit_cost'], $quantity * $inventory['unit_cost'], '', $reason, $notes, $_SESSION['user_id']],
                                'iiiddssssi'
                            );
                        }
                    } else {
                        // Partial transfer - reduce source quantity
                        executeQuery(
                            "UPDATE inventory SET quantity = quantity - ?, total_cost = (quantity - ?) * unit_cost, updated_at = NOW() WHERE id = ?",
                            [$quantity, $quantity, $inventory_id],
                            'iii'
                        );
                        
                        // Create new inventory record at destination
                        executeQuery(
                            "INSERT INTO inventory (item_id, branch_id, serial_number, batch_number, purchase_date, supplier_id, invoice_number, warranty_start_date, warranty_end_date, quantity, unit_cost, total_cost, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            [$inventory['item_id'], $require_acceptance ? $inventory['branch_id'] : $to_branch_id, $inventory['serial_number'], $inventory['batch_number'], $inventory['purchase_date'], $inventory['supplier_id'], $inventory['invoice_number'], $inventory['warranty_start_date'], $inventory['warranty_end_date'], $quantity, $inventory['unit_cost'], $quantity * $inventory['unit_cost'], $inventory['notes']],
                            'iisssisssidds'
                        );
                        
                        $new_inventory_id = getLastInsertId();
                        
                        // Create transfer out movement for source
                        executeQuery(
                            "INSERT INTO stock_movements (inventory_id, movement_type, from_branch_id, to_branch_id, quantity, unit_cost, total_cost, reference_number, reason, notes, transfer_status, created_by) VALUES (?, 'transfer_out', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            [$inventory_id, $inventory['branch_id'], $to_branch_id, $quantity, $inventory['unit_cost'], $quantity * $inventory['unit_cost'], '', $reason, $notes, $transfer_status, $_SESSION['user_id']],
                            'iiiddssssi'
                        );
                        
                        if (!$require_acceptance) {
                            // Create transfer in movement for destination immediately
                            executeQuery(
                                "INSERT INTO stock_movements (inventory_id, movement_type, from_branch_id, to_branch_id, quantity, unit_cost, total_cost, reference_number, reason, notes, transfer_status, created_by) VALUES (?, 'transfer_in', ?, ?, ?, ?, ?, ?, ?, ?, 'received', ?)",
                                [$new_inventory_id, $inventory['branch_id'], $to_branch_id, $quantity, $inventory['unit_cost'], $quantity * $inventory['unit_cost'], '', $reason, $notes, $_SESSION['user_id']],
                                'iiiddssssi'
                            );
                        }
                    }
                    
                    // Commit transaction
                    $conn->commit();
                    
                    // Log activity
                    logActivity('Transfer Stock', 'inventory', $inventory_id, null, [
                        'item' => $inventory['item_name'],
                        'from_branch' => $inventory['from_branch_name'],
                        'to_branch' => $to_branch['name'],
                        'quantity' => $quantity,
                        'reason' => $reason
                    ]);
                    
                    // Create notifications
                    createNotification(
                        'system',
                        $require_acceptance ? 'Transfer Pending' : 'Stock Transfer',
                        $require_acceptance ? 
                            "Transfer pending: {$inventory['item_name']} ({$quantity} units) from {$inventory['from_branch_name']}. Please review and accept." :
                            "Stock transferred: {$inventory['item_name']} ({$quantity} units) from {$inventory['from_branch_name']} to {$to_branch['name']}",
                        null,
                        $to_branch_id,
                        'inventory',
                        $inventory_id
                    );
                    
                    $success_message = $require_acceptance ? 
                        'Transfer initiated. Waiting for acceptance by receiving branch.' :
                        'Stock transferred successfully.';
                    
                    // Redirect after successful transfer
                    header('Location: transfers.php?success=' . urlencode($success_message));
                    exit();
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    throw $e;
                }
            }
        } catch (Exception $e) {
            $error_message = 'Error transferring stock: ' . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
?>

<div class="page-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-arrow-left-right me-2"></i>Transfer Stock</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Inventory</a></li>
                    <li class="breadcrumb-item active">Transfer Stock</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="transfer.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Transfer Management
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card fade-in">
            <div class="card-header">
                <h5 class="mb-0">Transfer Information</h5>
            </div>
            <div class="card-body">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="transferForm">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="category_filter" class="form-label">Filter by Category</label>
                            <select class="form-select" id="category_filter" onchange="filterItems()">
                                <option value="">All Categories</option>
                                <?php
                                $categories = fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
                                foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="inventory_id" class="form-label">Select Item to Transfer <span class="text-danger">*</span></label>
                            <select class="form-select" id="inventory_id" name="inventory_id" required onchange="updateInventoryInfo()">
								<option value="">Select Inventory Item</option>
								<?php foreach ($inventory_items as $item): ?>
									<option value="<?php echo $item['id']; ?>" 
											data-category="<?php echo $item['category_id']; ?>"
											data-quantity="<?php echo $item['quantity']; ?>"
											data-unit-measure="<?php echo $item['unit_of_measure']; ?>"
											data-location="<?php echo htmlspecialchars($item['location'] ?? ''); ?>"
											data-branch="<?php echo htmlspecialchars($item['branch_name']); ?>"
											<?php echo (($_POST['inventory_id'] ?? $preselected_inventory_id) == $item['id']) ? 'selected' : ''; ?>>
										<?php echo htmlspecialchars($item['item_code'] . ' - ' . $item['item_name']); ?>
										<?php if ($item['serial_number']): ?>
											(S/N: <?php echo htmlspecialchars($item['serial_number']); ?>)
										<?php endif; ?>
										- <?php echo htmlspecialchars($item['branch_name']); ?>
										(Qty: <?php echo $item['quantity']; ?>)
									</option>
								<?php endforeach; ?>

							</select>

                        </div>
                    </div>
                    
                    <!-- **NEW**: Transfer Status and Accept Option -->
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="require_acceptance" name="require_acceptance" checked>
                                <label class="form-check-label" for="require_acceptance">
                                    Require acceptance by receiving branch
                                </label>
                                <div class="form-text">If checked, the receiving branch must accept the transfer before it's completed</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">From Branch</label>
                            <input type="text" class="form-control" id="from_branch" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="to_branch_id" class="form-label">To Branch <span class="text-danger">*</span></label>
                            <select class="form-select" id="to_branch_id" name="to_branch_id" required>
                                <option value="">Select Destination Branch</option>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?php echo $branch['id']; ?>" 
                                            <?php echo (($_POST['to_branch_id'] ?? '') == $branch['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($branch['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="quantity" class="form-label">Transfer Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="quantity" name="quantity" 
                                   value="<?php echo htmlspecialchars($_POST['quantity'] ?? '1'); ?>" 
                                   min="1" required>
                            <div class="form-text">
                                Available: <span id="available-quantity">-</span> <span id="unit-measure">-</span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Current Location</label>
                            <input type="text" class="form-control" id="current_location" readonly>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reason" class="form-label">Transfer Reason <span class="text-danger">*</span></label>
                        <select class="form-select" id="reason" name="reason" required>
                            <option value="">Select Reason</option>
                            <option value="Branch requirement" <?php echo (($_POST['reason'] ?? '') === 'Branch requirement') ? 'selected' : ''; ?>>Branch Requirement</option>
                            <option value="Equipment redistribution" <?php echo (($_POST['reason'] ?? '') === 'Equipment redistribution') ? 'selected' : ''; ?>>Equipment Redistribution</option>
                            <option value="Maintenance support" <?php echo (($_POST['reason'] ?? '') === 'Maintenance support') ? 'selected' : ''; ?>>Maintenance Support</option>
                            <option value="Emergency need" <?php echo (($_POST['reason'] ?? '') === 'Emergency need') ? 'selected' : ''; ?>>Emergency Need</option>
                            <option value="Stock balancing" <?php echo (($_POST['reason'] ?? '') === 'Stock balancing') ? 'selected' : ''; ?>>Stock Balancing</option>
                            <option value="Other" <?php echo (($_POST['reason'] ?? '') === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="notes" class="form-label">Additional Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"
                                  placeholder="Any additional information about this transfer..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-outline-secondary me-md-2">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-arrow-left-right me-2"></i>Transfer Stock
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card fade-in">
            <div class="card-header">
                <h6 class="mb-0">Transfer Guidelines</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6><i class="bi bi-info-circle text-info me-2"></i>Important Notes</h6>
                    <ul class="small">
                        <li>Transfers are immediate and cannot be undone</li>
                        <li>Both branches will receive notifications</li>
                        <li>All movements are logged for audit purposes</li>
                        <li>Warranty and maintenance schedules transfer with the item</li>
                    </ul>
                </div>
                
                <div class="mb-3">
                    <h6><i class="bi bi-shield-check text-success me-2"></i>Approval Process</h6>
                    <div class="small">
                        <strong>Admin:</strong> Can transfer between any branches<br>
                        <strong>Inventory Manager:</strong> Can transfer from their assigned branch
                    </div>
                </div>
                
                <div class="alert alert-warning small">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Verification Required:</strong> Please verify the destination branch and quantity before confirming the transfer.
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$additional_js = '
<script>
// **NEW**: Filter items by category
function filterItems() {
    const categoryFilter = document.getElementById("category_filter").value;
    const inventorySelect = document.getElementById("inventory_id");
    const options = inventorySelect.querySelectorAll("option");
    
    options.forEach(option => {
        if (option.value === "") {
            option.style.display = "block";
            return;
        }
        
        const itemCategory = option.getAttribute("data-category");
        if (!categoryFilter || itemCategory === categoryFilter) {
            option.style.display = "block";
        } else {
            option.style.display = "none";
        }
    });
    
    // Reset selection if current selection is hidden
    if (inventorySelect.value && inventorySelect.options[inventorySelect.selectedIndex].style.display === "none") {
        inventorySelect.value = "";
        updateInventoryInfo();
    }
}

function updateInventoryInfo() {
    const inventorySelect = document.getElementById("inventory_id");
    const selectedOption = inventorySelect.options[inventorySelect.selectedIndex];
    
    if (selectedOption.value) {
        const quantity = selectedOption.getAttribute("data-quantity");
        const unitMeasure = selectedOption.getAttribute("data-unit-measure");
        const location = selectedOption.getAttribute("data-location");
        const branch = selectedOption.getAttribute("data-branch");
        
        document.getElementById("available-quantity").textContent = quantity;
        document.getElementById("unit-measure").textContent = unitMeasure;
        document.getElementById("current_location").value = location || "-";
        document.getElementById("from_branch").value = branch;
        document.getElementById("quantity").max = quantity;
        
        // Reset quantity to 1 or max available
        document.getElementById("quantity").value = Math.min(1, parseInt(quantity));
    } else {
        document.getElementById("available-quantity").textContent = "-";
        document.getElementById("unit-measure").textContent = "-";
        document.getElementById("current_location").value = "";
        document.getElementById("from_branch").value = "";
        document.getElementById("quantity").max = "";
        document.getElementById("quantity").value = "1";
    }
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", function() {
    updateInventoryInfo();
});

// Form validation
document.getElementById("transferForm").addEventListener("submit", function(e) {
    const inventoryId = document.getElementById("inventory_id").value;
    const toBranchId = document.getElementById("to_branch_id").value;
    const quantity = parseInt(document.getElementById("quantity").value);
    const maxQuantity = parseInt(document.getElementById("quantity").max);
    const reason = document.getElementById("reason").value;
    
    if (!inventoryId || !toBranchId || !quantity || quantity <= 0 || !reason) {
        e.preventDefault();
        showToast("Error", "Please fill in all required fields", "error");
        return false;
    }
    
    if (quantity > maxQuantity) {
        e.preventDefault();
        showToast("Error", "Transfer quantity cannot exceed available stock", "error");
        return false;
    }
    
    // Confirmation dialog
    if (!confirm("Are you sure you want to transfer this stock? This action cannot be undone.")) {
        e.preventDefault();
        return false;
    }
});
</script>
';

include '../../includes/footer.php';
?>