<?php
/**
 * Add Inventory Stock Page for NBTS Inventory Management System
 * 
 * **UPDATED PAGE** - Now handles central procurement with bulk serial numbers
 * Changes: Added bulk serial number input, allocation workflow, NBC-only access
 */

require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication and proper role
requireAuth(['admin']); // **CHANGE**: Only admin (NBC) can add stock centrally

$page_title = 'Add Stock';

// **CHANGE**: Only NBC can add stock centrally
$current_user = Auth::getCurrentUser();

// Check if user is from NBC (National Blood Centre)
$nbc_branch = fetchSingle("SELECT id FROM branches WHERE code = 'NBC'", [], '');
if (!$nbc_branch || ($_SESSION['user_branch_id'] && $_SESSION['user_branch_id'] != $nbc_branch['id'])) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php?error=Only NBC can add stock centrally');
    exit();
}

// Get pre-selected item if provided
$preselected_item_id = $_GET['item_id'] ?? '';

// Get dropdown data
$items = fetchAll("SELECT * FROM items WHERE is_active = 1 ORDER BY name");
$suppliers = fetchAll("SELECT * FROM suppliers WHERE is_active = 1 ORDER BY name");
$branches = fetchAll("SELECT * FROM branches WHERE is_active = 1 AND code != 'NBC' ORDER BY name"); // **CHANGE**: Exclude NBC from allocation

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = (int)$_POST['item_id'];
    $procurement_type = $_POST['procurement_type']; // **NEW**: bulk or single
    $unit_cost = floatval($_POST['unit_cost']);
    
    $supplier_id = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
    $invoice_number = sanitizeInput($_POST['invoice_number']);
    $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
    $warranty_start_date = !empty($_POST['warranty_start_date']) ? $_POST['warranty_start_date'] : null;
    $warranty_end_date = !empty($_POST['warranty_end_date']) ? $_POST['warranty_end_date'] : null;
    $notes = sanitizeInput($_POST['notes']);
    
    // **CHANGE**: Handle bulk or single procurement
    if ($procurement_type === 'bulk') {
        $serial_numbers_text = sanitizeInput($_POST['serial_numbers']);
        $serial_numbers = array_filter(array_map('trim', explode("\n", $serial_numbers_text)));
        
        if (empty($item_id) || empty($serial_numbers) || empty($unit_cost)) {
            $error_message = 'Please fill in all required fields for bulk procurement.';
        } else {
            try {
                $result = CentralProcurement::createBulkAllocation(
                    $item_id, $supplier_id, $purchase_date, $invoice_number, 
                    $serial_numbers, $unit_cost, $warranty_start_date, $warranty_end_date, $notes
                );
                
                if ($result['success']) {
                    $success_message = 'Bulk procurement completed. ' . count($serial_numbers) . ' items added for allocation.';
                    
                    // Redirect to allocation page
                    header('Location: allocate.php?allocation_id=' . $result['allocation_id'] . '&success=' . urlencode($success_message));
                    exit();
                } else {
                    $error_message = $result['message'];
                }
            } catch (Exception $e) {
                $error_message = 'Error creating bulk allocation: ' . $e->getMessage();
            }
        }
    } else {
        // Single item procurement (original logic)
        $quantity = (int)$_POST['quantity'];
        $serial_number = sanitizeInput($_POST['serial_number']);
        $batch_number = sanitizeInput($_POST['batch_number']);
        
        if (empty($item_id) || empty($quantity) || $quantity <= 0) {
        $error_message = 'Please fill in all required fields with valid values.';
    } else {
        try {
                // Insert inventory record at NBC
            executeQuery(
                    "INSERT INTO inventory (item_id, branch_id, serial_number, batch_number, purchase_date, supplier_id, invoice_number, warranty_start_date, warranty_end_date, quantity, unit_cost, total_cost, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$item_id, $nbc_branch['id'], $serial_number, $batch_number, $purchase_date, $supplier_id, $invoice_number, $warranty_start_date, $warranty_end_date, $quantity, $unit_cost, $quantity * $unit_cost, $notes],
                    'iisssisssidds'
            );
            
            $inventory_id = getLastInsertId();
            
            // Create stock movement record
            executeQuery(
                    "INSERT INTO stock_movements (inventory_id, movement_type, to_branch_id, quantity, unit_cost, total_cost, reference_number, reason, created_by) VALUES (?, 'receipt', ?, ?, ?, ?, ?, 'Central procurement', ?)",
                    [$inventory_id, $nbc_branch['id'], $quantity, $unit_cost, $quantity * $unit_cost, $invoice_number, $_SESSION['user_id']],
                'iiiddsi'
            );
            
            // Log activity
            logActivity('Add Stock', 'inventory', $inventory_id, null, [
                'item_id' => $item_id,
                    'branch_id' => $nbc_branch['id'],
                'quantity' => $quantity,
                'unit_cost' => $unit_cost
            ]);
            
            // Generate QR code
            QRCode::generateForInventory($inventory_id);
            
                $success_message = 'Stock added to NBC successfully.';
            
            // Redirect after successful creation
            header('Location: view.php?id=' . $inventory_id . '&success=' . urlencode($success_message));
            exit();
            
        } catch (Exception $e) {
            $error_message = 'Error adding stock: ' . $e->getMessage();
        }
    }
    }
}

include '../../includes/header.php';
?>

<div class="page-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-plus-circle me-2"></i>Central Procurement</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Inventory</a></li>
                    <li class="breadcrumb-item active">Central Procurement</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Inventory
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card fade-in">
            <div class="card-header">
                <h5 class="mb-0">Procurement Information</h5>
            </div>
            <div class="card-body">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <!-- **NEW**: Procurement Type Selection -->
                <div class="mb-4">
                    <label class="form-label">Procurement Type <span class="text-danger">*</span></label>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="procurement_type" id="single_item" value="single" checked onchange="toggleProcurementType()">
                                <label class="form-check-label" for="single_item">
                                    <strong>Single Item</strong><br>
                                    <small class="text-muted">Add individual item to NBC stock</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="procurement_type" id="bulk_items" value="bulk" onchange="toggleProcurementType()">
                                <label class="form-check-label" for="bulk_items">
                                    <strong>Bulk Procurement</strong><br>
                                    <small class="text-muted">Add multiple items with serial numbers for allocation</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <form method="POST" id="stockForm">
                    <input type="hidden" name="procurement_type" id="procurement_type_hidden" value="single">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="item_id" class="form-label">Item <span class="text-danger">*</span></label>
                            <select class="form-select" id="item_id" name="item_id" required onchange="updateItemInfo()">
                                <option value="">Select Item</option>
                                <?php foreach ($items as $item): ?>
                                    <option value="<?php echo $item['id']; ?>" 
                                            data-unit-cost="<?php echo $item['unit_cost']; ?>"
                                            data-unit-measure="<?php echo $item['unit_of_measure']; ?>"
                                            <?php echo (($_POST['item_id'] ?? $preselected_item_id) == $item['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($item['code'] . ' - ' . $item['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Procurement Location</label>
                            <input type="text" class="form-control" value="National Blood Centre (NBC)" readonly>
                            <div class="form-text">Items will be added to NBC for central distribution</div>
                        </div>
                    </div>
                    
                    <!-- **NEW**: Bulk Serial Numbers Section -->
                    <div id="bulk-section" style="display: none;">
                        <div class="mb-3">
                            <label for="serial_numbers" class="form-label">Serial Numbers <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="serial_numbers" name="serial_numbers" rows="8"
                                      placeholder="Enter one serial number per line:&#10;SN001&#10;SN002&#10;SN003&#10;..."></textarea>
                            <div class="form-text">Enter one serial number per line. Quantity will be calculated automatically.</div>
                        </div>
                    </div>
                    
                    <!-- **UPDATED**: Single Item Section -->
                    <div id="single-section">
                        <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="quantity" name="quantity" 
                                   value="<?php echo htmlspecialchars($_POST['quantity'] ?? '1'); ?>" 
                                   min="1" required onchange="calculateTotal()">
                            <div class="form-text">Unit: <span id="unit-measure">-</span></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="unit_cost" class="form-label">Unit Cost (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="unit_cost" name="unit_cost" 
                                   value="<?php echo htmlspecialchars($_POST['unit_cost'] ?? '0.00'); ?>" 
                                   min="0" step="0.01" required onchange="calculateTotal()">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="total_cost" class="form-label">Total Cost (Rs.)</label>
                            <input type="text" class="form-control" id="total_cost" readonly>
                        </div>
                    </div>
                        
                        <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="serial_number" class="form-label">Serial Number</label>
                            <input type="text" class="form-control" id="serial_number" name="serial_number" 
                                   value="<?php echo htmlspecialchars($_POST['serial_number'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="batch_number" class="form-label">Batch Number</label>
                            <input type="text" class="form-control" id="batch_number" name="batch_number" 
                                   value="<?php echo htmlspecialchars($_POST['batch_number'] ?? ''); ?>">
                        </div>
                    </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="supplier_id" class="form-label">Supplier</label>
                            <select class="form-select" id="supplier_id" name="supplier_id">
                                <option value="">Select Supplier</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['id']; ?>" 
                                            <?php echo (($_POST['supplier_id'] ?? '') == $supplier['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($supplier['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="invoice_number" class="form-label">Invoice Number</label>
                            <input type="text" class="form-control" id="invoice_number" name="invoice_number" 
                                   value="<?php echo htmlspecialchars($_POST['invoice_number'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="purchase_date" class="form-label">Purchase Date</label>
                            <input type="date" class="form-control" id="purchase_date" name="purchase_date" 
                                   value="<?php echo htmlspecialchars($_POST['purchase_date'] ?? ''); ?>">
					</div>			   
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="warranty_start_date" class="form-label">Warranty Start</label>
                            <input type="date" class="form-control" id="warranty_start_date" name="warranty_start_date" 
                                   value="<?php echo htmlspecialchars($_POST['warranty_start_date'] ?? ''); ?>"
                                   onchange="calculateWarrantyEnd()">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="warranty_end_date" class="form-label">Warranty End</label>
                            <input type="date" class="form-control" id="warranty_end_date" name="warranty_end_date" 
                                   value="<?php echo htmlspecialchars($_POST['warranty_end_date'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"
                                  placeholder="Additional notes or comments..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-outline-secondary me-md-2">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="bi bi-check-circle me-2"></i>Add to NBC Stock
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
	</div>
	
    
    <div class="col-lg-4">
        <div class="card fade-in">
            <div class="card-header">
                <h6 class="mb-0">Central Procurement Guide</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6><i class="bi bi-building text-primary me-2"></i>NBC Central Procurement</h6>
                    <div class="small">
                        All items are procured centrally by NBC and then allocated to other blood banks. 
                        Use bulk procurement for multiple items with serial numbers.
                    </div>
                </div>
                
                <div class="mb-3">
                    <h6><i class="bi bi-list-ol text-success me-2"></i>Bulk Procurement</h6>
                    <div class="small">
                        For bulk procurement, enter serial numbers (one per line) and the system will 
                        create individual trackable items for allocation to branches.
                    </div>
                </div>
                
                <div class="mb-3">
                    <h6><i class="bi bi-arrow-right-circle text-info me-2"></i>Allocation Process</h6>
                    <div class="small">
                        After adding bulk items, you'll be redirected to the allocation page where 
                        you can assign items to specific branches.
                    </div>
                </div>
                
                <div class="alert alert-warning small">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Note:</strong> Only NBC can perform central procurement. Other branches receive items through allocation.
                </div>
            </div>
        </div>
    </div>

</div>
</div>

<?php
$additional_js = '
<script>
// **NEW**: Toggle between single and bulk procurement
function toggleProcurementType() {
    const singleRadio = document.getElementById("single_item");
    const bulkRadio = document.getElementById("bulk_items");
    const singleSection = document.getElementById("single-section");
    const bulkSection = document.getElementById("bulk-section");
    const submitBtn = document.getElementById("submit-btn");
    const procurementTypeHidden = document.getElementById("procurement_type_hidden");
    
    if (bulkRadio.checked) {
        singleSection.style.display = "none";
        bulkSection.style.display = "block";
        submitBtn.innerHTML = "<i class=\"bi bi-check-circle me-2\"></i>Create Bulk Allocation";
        procurementTypeHidden.value = "bulk";
    } else {
        singleSection.style.display = "block";
        bulkSection.style.display = "none";
        submitBtn.innerHTML = "<i class=\"bi bi-check-circle me-2\"></i>Add to NBC Stock";
        procurementTypeHidden.value = "single";
    }
}

function updateItemInfo() {
    const itemSelect = document.getElementById("item_id");
    const selectedOption = itemSelect.options[itemSelect.selectedIndex];
    
    if (selectedOption.value) {
        const unitCost = selectedOption.getAttribute("data-unit-cost");
        const unitMeasure = selectedOption.getAttribute("data-unit-measure");
        
        document.getElementById("unit_cost").value = unitCost;
        document.getElementById("unit-measure").textContent = unitMeasure;
        
        calculateTotal();
    } else {
        document.getElementById("unit_cost").value = "0.00";
        document.getElementById("unit-measure").textContent = "-";
        document.getElementById("total_cost").value = "";
    }
}

function calculateTotal() {
    const quantity = parseFloat(document.getElementById("quantity").value) || 0;
    const unitCost = parseFloat(document.getElementById("unit_cost").value) || 0;
    const total = quantity * unitCost;
    
    document.getElementById("total_cost").value = formatCurrency(total);
}

function calculateWarrantyEnd() {
    const startDate = document.getElementById("warranty_start_date").value;
    if (startDate) {
        // Default to 1 year warranty
        const start = new Date(startDate);
        const end = new Date(start);
        end.setFullYear(start.getFullYear() + 1);
        
        document.getElementById("warranty_end_date").value = end.toISOString().split("T")[0];
    }
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", function() {
    updateItemInfo();
    
    // Set default purchase date to today
    if (!document.getElementById("purchase_date").value) {
        document.getElementById("purchase_date").value = new Date().toISOString().split("T")[0];
    }
});

// Form validation
document.getElementById("stockForm").addEventListener("submit", function(e) {
    const procurementType = document.querySelector("input[name=\"procurement_type\"]:checked").value;
    const itemId = document.getElementById("item_id").value;
    const unitCost = document.getElementById("unit_cost").value;
    
    if (!itemId || !unitCost || unitCost < 0) {
        e.preventDefault();
        showToast("Error", "Please fill in all required fields", "error");
        return false;
    }
    
    if (procurementType === "bulk") {
        const serialNumbers = document.getElementById("serial_numbers").value.trim();
        if (!serialNumbers) {
            e.preventDefault();
            showToast("Error", "Please enter serial numbers for bulk procurement", "error");
            return false;
        }
    } else {
        const quantity = document.getElementById("quantity").value;
        if (!quantity || quantity <= 0) {
            e.preventDefault();
            showToast("Error", "Please enter valid quantity", "error");
            return false;
        }
    }
});
</script>
';

include '../../includes/footer.php';
?>