<?php
/**
 * Branches Management Page for NBTS Inventory Management System
 * 
 * This page displays and manages branches (Admin only)
 */

require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require admin authentication
requireAuth(['admin']);

$page_title = 'Branches Management';

// Handle delete request
if (isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    
    try {
        // Check if branch has users or inventory
        $user_count = fetchSingle(
            "SELECT COUNT(*) as count FROM users WHERE branch_id = ?",
            [$delete_id],
            'i'
        )['count'];
        
        $inventory_count = fetchSingle(
            "SELECT COUNT(*) as count FROM inventory WHERE branch_id = ?",
            [$delete_id],
            'i'
        )['count'];
        
        if ($user_count > 0 || $inventory_count > 0) {
            $error_message = 'Cannot delete branch with existing users or inventory records.';
        } else {
            // Get branch info for logging
            $branch = fetchSingle("SELECT * FROM branches WHERE id = ?", [$delete_id], 'i');
            
            // Delete branch
            executeQuery("DELETE FROM branches WHERE id = ?", [$delete_id], 'i');
            
            // Log activity
            logActivity('Delete Branch', 'branches', $delete_id, $branch);
            
            $success_message = 'Branch deleted successfully.';
        }
    } catch (Exception $e) {
        $error_message = 'Error deleting branch: ' . $e->getMessage();
    }
}

// Handle add/edit branch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $name = sanitizeInput($_POST['name']);
    $code = sanitizeInput($_POST['code']);
    $address = sanitizeInput($_POST['address']);
    $city = sanitizeInput($_POST['city']);
    $district = sanitizeInput($_POST['district']);
    $phone = sanitizeInput($_POST['phone']);
    $email = sanitizeInput($_POST['email']);
    $manager_name = sanitizeInput($_POST['manager_name']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($name) || empty($code) || empty($address) || empty($city) || empty($district)) {
        $error_message = 'Please fill in all required fields.';
    } else {
        try {
            if ($action === 'add') {
                // Check if code already exists
                $existing = fetchSingle(
                    "SELECT id FROM branches WHERE code = ?",
                    [$code],
                    's'
                );
                
                if ($existing) {
                    $error_message = 'Branch code already exists.';
                } else {
                    // Insert new branch
                    executeQuery(
                        "INSERT INTO branches (name, code, address, city, district, phone, email, manager_name, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [$name, $code, $address, $city, $district, $phone, $email, $manager_name, $is_active],
                        'ssssssssi'
                    );
                    
                    $branch_id = getLastInsertId();
                    
                    // Log activity
                    logActivity('Create Branch', 'branches', $branch_id, null, [
                        'name' => $name,
                        'code' => $code,
                        'city' => $city,
                        'district' => $district
                    ]);
                    
                    $success_message = 'Branch added successfully.';
                }
            } elseif ($action === 'edit') {
                $branch_id = (int)$_POST['branch_id'];
                
                // Check if code already exists (excluding current branch)
                $existing = fetchSingle(
                    "SELECT id FROM branches WHERE code = ? AND id != ?",
                    [$code, $branch_id],
                    'si'
                );
                
                if ($existing) {
                    $error_message = 'Branch code already exists.';
                } else {
                    // Get old values for logging
                    $old_branch = fetchSingle("SELECT * FROM branches WHERE id = ?", [$branch_id], 'i');
                    
                    // Update branch
                    executeQuery(
                        "UPDATE branches SET name = ?, code = ?, address = ?, city = ?, district = ?, phone = ?, email = ?, manager_name = ?, is_active = ?, updated_at = NOW() WHERE id = ?",
                        [$name, $code, $address, $city, $district, $phone, $email, $manager_name, $is_active, $branch_id],
                        'ssssssssii'
                    );
                    
                    // Log activity
                    logActivity('Update Branch', 'branches', $branch_id, $old_branch, [
                        'name' => $name,
                        'code' => $code,
                        'city' => $city,
                        'district' => $district
                    ]);
                    
                    $success_message = 'Branch updated successfully.';
                }
            }
        } catch (Exception $e) {
            $error_message = 'Error processing branch: ' . $e->getMessage();
        }
    }
}

// Get branches with user and inventory counts
$branches = fetchAll(
    "SELECT b.*, 
            COUNT(DISTINCT u.id) as user_count,
            COUNT(DISTINCT inv.id) as inventory_count,
            SUM(CASE WHEN inv.status = 'active' THEN inv.total_cost ELSE 0 END) as total_value
     FROM branches b 
     LEFT JOIN users u ON b.id = u.branch_id 
     LEFT JOIN inventory inv ON b.id = inv.branch_id
     GROUP BY b.id 
     ORDER BY b.name"
);

include '../../includes/header.php';
?>

<div class="page-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-geo-alt me-2"></i>Branches Management</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/pages/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Branches</li>
                </ol>
            </nav>
        </div>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#branchModal" onclick="openAddModal()">
                <i class="bi bi-plus-circle me-2"></i>Add Branch
            </button>
        </div>
    </div>
</div>

<!-- Branches Table -->
<div class="card fade-in">
    <div class="card-header">
        <h5 class="mb-0">Branches List (<?php echo count($branches); ?> branches)</h5>
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
            <table class="table table-hover" id="branchesTable">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Location</th>
                        <th>Manager</th>
                        <th>Contact</th>
                        <th>Users</th>
                        <th>Inventory</th>
                        <th>Total Value</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($branches as $branch): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($branch['code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($branch['name']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($branch['city']); ?>, <?php echo htmlspecialchars($branch['district']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars(substr($branch['address'], 0, 50)); ?><?php echo strlen($branch['address']) > 50 ? '...' : ''; ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($branch['manager_name'] ?: '-'); ?></td>
                            <td>
                                <?php if ($branch['phone']): ?>
                                    <i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($branch['phone']); ?><br>
                                <?php endif; ?>
                                <?php if ($branch['email']): ?>
                                    <i class="bi bi-envelope me-1"></i><a href="mailto:<?php echo htmlspecialchars($branch['email']); ?>"><?php echo htmlspecialchars($branch['email']); ?></a>
                                <?php endif; ?>
                                <?php if (!$branch['phone'] && !$branch['email']): ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo number_format($branch['user_count']); ?></span>
                            </td>
                            <td>
                                <span class="badge bg-primary"><?php echo number_format($branch['inventory_count']); ?></span>
                            </td>
                            <td><?php echo formatCurrency($branch['total_value']); ?></td>
                            <td>
                                <span class="badge <?php echo $branch['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo $branch['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-success" 
                                            onclick="openEditModal(<?php echo htmlspecialchars(json_encode($branch)); ?>)" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    
                                    <?php if ($branch['user_count'] == 0 && $branch['inventory_count'] == 0): ?>
                                        <form method="POST" class="d-inline" 
                                              onsubmit="return confirmDelete('Are you sure you want to delete this branch?')">
                                            <input type="hidden" name="delete_id" value="<?php echo $branch['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                data-bs-toggle="dropdown" title="More Actions">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="/pages/users/index.php?branch_id=<?php echo $branch['id']; ?>">
                                                    <i class="bi bi-people me-2"></i>View Users
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="/pages/inventory/index.php?branch_id=<?php echo $branch['id']; ?>">
                                                    <i class="bi bi-box-seam me-2"></i>View Inventory
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Branch Modal -->
<div class="modal fade" id="branchModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Branch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="branchForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="branch_id" id="branchId">
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="modalName" class="form-label">Branch Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modalName" name="name" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="modalCode" class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modalCode" name="code" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="modalAddress" class="form-label">Address <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="modalAddress" name="address" rows="2" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="modalCity" class="form-label">City <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modalCity" name="city" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="modalDistrict" class="form-label">District <span class="text-danger">*</span></label>
                            <select class="form-select" id="modalDistrict" name="district" required>
                                <option value="">Select District</option>
                                <option value="Colombo">Colombo</option>
                                <option value="Gampaha">Gampaha</option>
                                <option value="Kalutara">Kalutara</option>
                                <option value="Kandy">Kandy</option>
                                <option value="Matale">Matale</option>
                                <option value="Nuwara Eliya">Nuwara Eliya</option>
                                <option value="Galle">Galle</option>
                                <option value="Matara">Matara</option>
                                <option value="Hambantota">Hambantota</option>
                                <option value="Jaffna">Jaffna</option>
                                <option value="Kilinochchi">Kilinochchi</option>
                                <option value="Mannar">Mannar</option>
                                <option value="Vavuniya">Vavuniya</option>
                                <option value="Mullaitivu">Mullaitivu</option>
                                <option value="Batticaloa">Batticaloa</option>
                                <option value="Ampara">Ampara</option>
                                <option value="Trincomalee">Trincomalee</option>
                                <option value="Kurunegala">Kurunegala</option>
                                <option value="Puttalam">Puttalam</option>
                                <option value="Anuradhapura">Anuradhapura</option>
                                <option value="Polonnaruwa">Polonnaruwa</option>
                                <option value="Badulla">Badulla</option>
                                <option value="Moneragala">Moneragala</option>
                                <option value="Ratnapura">Ratnapura</option>
                                <option value="Kegalle">Kegalle</option>
                            </select>
                        </div>
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
                        <div class="col-md-8 mb-3">
                            <label for="modalManagerName" class="form-label">Manager Name</label>
                            <input type="text" class="form-control" id="modalManagerName" name="manager_name">
                        </div>
                        <div class="col-md-4 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="modalIsActive" name="is_active" checked>
                                <label class="form-check-label" for="modalIsActive">
                                    Active Branch
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Add Branch</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$additional_js = '
<script>
$(document).ready(function() {
    initDataTable("#branchesTable", {
        order: [[1, "asc"]],
        columnDefs: [
            { orderable: false, targets: [9] }
        ]
    });
});

function openAddModal() {
    document.getElementById("modalTitle").textContent = "Add Branch";
    document.getElementById("formAction").value = "add";
    document.getElementById("submitBtn").textContent = "Add Branch";
    document.getElementById("branchForm").reset();
    document.getElementById("modalIsActive").checked = true;
}

function openEditModal(branch) {
    document.getElementById("modalTitle").textContent = "Edit Branch";
    document.getElementById("formAction").value = "edit";
    document.getElementById("submitBtn").textContent = "Update Branch";
    document.getElementById("branchId").value = branch.id;
    document.getElementById("modalName").value = branch.name;
    document.getElementById("modalCode").value = branch.code;
    document.getElementById("modalAddress").value = branch.address;
    document.getElementById("modalCity").value = branch.city;
    document.getElementById("modalDistrict").value = branch.district;
    document.getElementById("modalPhone").value = branch.phone || "";
    document.getElementById("modalEmail").value = branch.email || "";
    document.getElementById("modalManagerName").value = branch.manager_name || "";
    document.getElementById("modalIsActive").checked = branch.is_active == 1;
}
</script>
';

include '../../includes/footer.php';
?>