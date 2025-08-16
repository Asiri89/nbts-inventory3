<?php
/**
 * Suppliers Management Page for NBTS Inventory Management System
 * 
 * This page displays and manages suppliers
 */

require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication
requireAuth();

$page_title = 'Suppliers Management';

// Handle delete request
if (isset($_POST['delete_id']) && hasRole(['admin', 'inventory_manager'])) {
    $delete_id = (int)$_POST['delete_id'];
    
    try {
        // Check if supplier has inventory records
        $inventory_count = fetchSingle(
            "SELECT COUNT(*) as count FROM inventory WHERE supplier_id = ?",
            [$delete_id],
            'i'
        )['count'];
        
        if ($inventory_count > 0) {
            $error_message = 'Cannot delete supplier with existing inventory records.';
        } else {
            // Get supplier info for logging
            $supplier = fetchSingle("SELECT * FROM suppliers WHERE id = ?", [$delete_id], 'i');
            
            // Delete supplier
            executeQuery("DELETE FROM suppliers WHERE id = ?", [$delete_id], 'i');
            
            // Log activity
            logActivity('Delete Supplier', 'suppliers', $delete_id, $supplier);
            
            $success_message = 'Supplier deleted successfully.';
        }
    } catch (Exception $e) {
        $error_message = 'Error deleting supplier: ' . $e->getMessage();
    }
}

// Handle add/edit supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && hasRole(['admin', 'inventory_manager'])) {
    $action = $_POST['action'];
    $name = sanitizeInput($_POST['name']);
    $code = sanitizeInput($_POST['code']);
    $contact_person = sanitizeInput($_POST['contact_person']);
    $address = sanitizeInput($_POST['address']);
    $city = sanitizeInput($_POST['city']);
    $phone = sanitizeInput($_POST['phone']);
    $email = sanitizeInput($_POST['email']);
    $tax_number = sanitizeInput($_POST['tax_number']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($name) || empty($code)) {
        $error_message = 'Please fill in all required fields.';
    } else {
        try {
            if ($action === 'add') {
                // Check if code already exists
                $existing = fetchSingle(
                    "SELECT id FROM suppliers WHERE code = ?",
                    [$code],
                    's'
                );
                
                if ($existing) {
                    $error_message = 'Supplier code already exists.';
                } else {
                    // Insert new supplier
                    executeQuery(
                        "INSERT INTO suppliers (name, code, contact_person, address, city, phone, email, tax_number, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [$name, $code, $contact_person, $address, $city, $phone, $email, $tax_number, $is_active],
                        'ssssssssi'
                    );
                    
                    $supplier_id = getLastInsertId();
                    
                    // Log activity
                    logActivity('Create Supplier', 'suppliers', $supplier_id, null, [
                        'name' => $name,
                        'code' => $code,
                        'contact_person' => $contact_person
                    ]);
                    
                    $success_message = 'Supplier added successfully.';
                }
            } elseif ($action === 'edit') {
                $supplier_id = (int)$_POST['supplier_id'];
                
                // Check if code already exists (excluding current supplier)
                $existing = fetchSingle(
                    "SELECT id FROM suppliers WHERE code = ? AND id != ?",
                    [$code, $supplier_id],
                    'si'
                );
                
                if ($existing) {
                    $error_message = 'Supplier code already exists.';
                } else {
                    // Get old values for logging
                    $old_supplier = fetchSingle("SELECT * FROM suppliers WHERE id = ?", [$supplier_id], 'i');
                    
                    // Update supplier
                    executeQuery(
                        "UPDATE suppliers SET name = ?, code = ?, contact_person = ?, address = ?, city = ?, phone = ?, email = ?, tax_number = ?, is_active = ?, updated_at = NOW() WHERE id = ?",
                        [$name, $code, $contact_person, $address, $city, $phone, $email, $tax_number, $is_active, $supplier_id],
                        'ssssssssii'
                    );
                    
                    // Log activity
                    logActivity('Update Supplier', 'suppliers', $supplier_id, $old_supplier, [
                        'name' => $name,
                        'code' => $code,
                        'contact_person' => $contact_person
                    ]);
                    
                    $success_message = 'Supplier updated successfully.';
                }
            }
        } catch (Exception $e) {
            $error_message = 'Error processing supplier: ' . $e->getMessage();
        }
    }
}

// Get suppliers with inventory counts
$suppliers = fetchAll(
    "SELECT s.*, COUNT(inv.id) as inventory_count,
            SUM(CASE WHEN inv.status = 'active' THEN inv.total_cost ELSE 0 END) as total_value
     FROM suppliers s 
     LEFT JOIN inventory inv ON s.id = inv.supplier_id 
     GROUP BY s.id 
     ORDER BY s.name"
);

include '../../includes/header.php';
?>

<div class="page-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-building me-2"></i>Suppliers Management</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/pages/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Suppliers</li>
                </ol>
            </nav>
        </div>
        <?php if (hasRole(['admin', 'inventory_manager'])): ?>
            <div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#supplierModal" onclick="openAddModal()">
                    <i class="bi bi-plus-circle me-2"></i>Add Supplier
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Suppliers Table -->
<div class="card fade-in">
    <div class="card-header">
        <h5 class="mb-0">Suppliers List (<?php echo count($suppliers); ?> suppliers)</h5>
    </div>
    <div class="card-body">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-2"></i><?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-hover" id="suppliersTable">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Contact Person</th>
                        <th>Location</th>
                        <th>Contact Info</th>
                        <th>Inventory Count</th>
                        <th>Total Value</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suppliers as $supplier): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($supplier['code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($supplier['name']); ?></td>
                            <td><?php echo htmlspecialchars($supplier['contact_person'] ?: '-'); ?></td>
                            <td>
                                <?php if ($supplier['city']): ?>
                                    <strong><?php echo htmlspecialchars($supplier['city']); ?></strong><br>
                                <?php endif; ?>
                                <?php if ($supplier['address']): ?>
                                    <small class="text-muted"><?php echo htmlspecialchars(substr($supplier['address'], 0, 50)); ?><?php echo strlen($supplier['address']) > 50 ? '...' : ''; ?></small>
                                <?php endif; ?>
                                <?php if (!$supplier['city'] && !$supplier['address']): ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($supplier['phone']): ?>
                                    <i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($supplier['phone']); ?><br>
                                <?php endif; ?>
                                <?php if ($supplier['email']): ?>
                                    <i class="bi bi-envelope me-1"></i><a href="mailto:<?php echo htmlspecialchars($supplier['email']); ?>"><?php echo htmlspecialchars($supplier['email']); ?></a>
                                <?php endif; ?>
                                <?php if (!$supplier['phone'] && !$supplier['email']): ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo number_format($supplier['inventory_count']); ?></span>
                            </td>
                            <td><?php echo formatCurrency($supplier['total_value']); ?></td>
                            <td>
                                <span class="badge <?php echo $supplier['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo $supplier['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <?php if (hasRole(['admin', 'inventory_manager'])): ?>
                                        <button type="button" class="btn btn-sm btn-outline-success" 
                                                onclick="openEditModal(<?php echo htmlspecialchars(json_encode($supplier)); ?>)" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        
                                        <?php if ($supplier['inventory_count'] == 0): ?>
                                            <form method="POST" class="d-inline" 
                                                  onsubmit="return confirmDelete('Are you sure you want to delete this supplier?')">
                                                <input type="hidden" name="delete_id" value="<?php echo $supplier['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <a href="/pages/inventory/index.php?supplier_id=<?php echo $supplier['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary" title="View Inventory">
                                        <i class="bi bi-box-seam"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (hasRole(['admin', 'inventory_manager'])): ?>
<!-- Supplier Modal -->
<div class="modal fade" id="supplierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="supplierForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="supplier_id" id="supplierId">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="modalName" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modalName" name="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="modalCode" class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modalCode" name="code" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="modalContactPerson" class="form-label">Contact Person</label>
                            <input type="text" class="form-control" id="modalContactPerson" name="contact_person">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="modalCity" class="form-label">City</label>
                            <input type="text" class="form-control" id="modalCity" name="city">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="modalAddress" class="form-label">Address</label>
                        <textarea class="form-control" id="modalAddress" name="address" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="modalPhone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="modalPhone" name="phone">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="modalEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="modalEmail" name="email">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="modalTaxNumber" class="form-label">Tax Number</label>
                            <input type="text" class="form-control" id="modalTaxNumber" name="tax_number">
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="modalIsActive" name="is_active" checked>
                                <label class="form-check-label" for="modalIsActive">
                                    Active Supplier
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Add Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$additional_js = '
<script>
$(document).ready(function() {
    initDataTable("#suppliersTable", {
        order: [[1, "asc"]],
        columnDefs: [
            { orderable: false, targets: [8] }
        ]
    });
});

function openAddModal() {
    document.getElementById("modalTitle").textContent = "Add Supplier";
    document.getElementById("formAction").value = "add";
    document.getElementById("submitBtn").textContent = "Add Supplier";
    document.getElementById("supplierForm").reset();
    document.getElementById("modalIsActive").checked = true;
}

function openEditModal(supplier) {
    document.getElementById("modalTitle").textContent = "Edit Supplier";
    document.getElementById("formAction").value = "edit";
    document.getElementById("submitBtn").textContent = "Update Supplier";
    document.getElementById("supplierId").value = supplier.id;
    document.getElementById("modalName").value = supplier.name;
    document.getElementById("modalCode").value = supplier.code;
    document.getElementById("modalContactPerson").value = supplier.contact_person || "";
    document.getElementById("modalAddress").value = supplier.address || "";
    document.getElementById("modalCity").value = supplier.city || "";
    document.getElementById("modalPhone").value = supplier.phone || "";
    document.getElementById("modalEmail").value = supplier.email || "";
    document.getElementById("modalTaxNumber").value = supplier.tax_number || "";
    document.getElementById("modalIsActive").checked = supplier.is_active == 1;
}
</script>
';

include '../../includes/footer.php';
?>