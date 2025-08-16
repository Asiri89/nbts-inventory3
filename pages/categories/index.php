<?php
/**
 * Categories Management Page for NBTS Inventory Management System
 * 
 * This page displays and manages item categories
 */

require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication
requireAuth();

$page_title = 'Categories Management';

// Handle delete request
if (isset($_POST['delete_id']) && hasRole(['admin', 'inventory_manager'])) {
    $delete_id = (int)$_POST['delete_id'];
    
    try {
        // Check if category has items
        $item_count = fetchSingle(
            "SELECT COUNT(*) as count FROM items WHERE category_id = ?",
            [$delete_id],
            'i'
        )['count'];
        
        if ($item_count > 0) {
            $error_message = 'Cannot delete category with existing items.';
        } else {
            // Get category info for logging
            $category = fetchSingle("SELECT * FROM categories WHERE id = ?", [$delete_id], 'i');
            
            // Delete category
            executeQuery("DELETE FROM categories WHERE id = ?", [$delete_id], 'i');
            
            // Log activity
            logActivity('Delete Category', 'categories', $delete_id, $category);
            
            $success_message = 'Category deleted successfully.';
        }
    } catch (Exception $e) {
        $error_message = 'Error deleting category: ' . $e->getMessage();
    }
}

// Handle add/edit category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && hasRole(['admin', 'inventory_manager'])) {
    $action = $_POST['action'];
    $name = sanitizeInput($_POST['name']);
    $code = sanitizeInput($_POST['code']);
    $type = sanitizeInput($_POST['type']);
    $description = sanitizeInput($_POST['description']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($name) || empty($code) || empty($type)) {
        $error_message = 'Please fill in all required fields.';
    } else {
        try {
            if ($action === 'add') {
                // Check if code already exists
                $existing = fetchSingle(
                    "SELECT id FROM categories WHERE code = ?",
                    [$code],
                    's'
                );
                
                if ($existing) {
                    $error_message = 'Category code already exists.';
                } else {
                    // Insert new category
                    executeQuery(
                        "INSERT INTO categories (name, code, type, description, is_active) VALUES (?, ?, ?, ?, ?)",
                        [$name, $code, $type, $description, $is_active],
                        'ssssi'
                    );
                    
                    $category_id = getLastInsertId();
                    
                    // Log activity
                    logActivity('Create Category', 'categories', $category_id, null, [
                        'name' => $name,
                        'code' => $code,
                        'type' => $type
                    ]);
                    
                    $success_message = 'Category added successfully.';
                }
            } elseif ($action === 'edit') {
                $category_id = (int)$_POST['category_id'];
                
                // Check if code already exists (excluding current category)
                $existing = fetchSingle(
                    "SELECT id FROM categories WHERE code = ? AND id != ?",
                    [$code, $category_id],
                    'si'
                );
                
                if ($existing) {
                    $error_message = 'Category code already exists.';
                } else {
                    // Get old values for logging
                    $old_category = fetchSingle("SELECT * FROM categories WHERE id = ?", [$category_id], 'i');
                    
                    // Update category
                    executeQuery(
                        "UPDATE categories SET name = ?, code = ?, type = ?, description = ?, is_active = ?, updated_at = NOW() WHERE id = ?",
                        [$name, $code, $type, $description, $is_active, $category_id],
                        'ssssii'
                    );
                    
                    // Log activity
                    logActivity('Update Category', 'categories', $category_id, $old_category, [
                        'name' => $name,
                        'code' => $code,
                        'type' => $type
                    ]);
                    
                    $success_message = 'Category updated successfully.';
                }
            }
        } catch (Exception $e) {
            $error_message = 'Error processing category: ' . $e->getMessage();
        }
    }
}

// Get categories with item counts
$categories = fetchAll(
    "SELECT c.*, COUNT(i.id) as item_count 
     FROM categories c 
     LEFT JOIN items i ON c.id = i.category_id 
     GROUP BY c.id 
     ORDER BY c.name"
);

include '../../includes/header.php';
?>

<div class="page-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-tags me-2"></i>Categories Management</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/pages/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Categories</li>
                </ol>
            </nav>
        </div>
        <?php if (hasRole(['admin', 'inventory_manager'])): ?>
            <div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="openAddModal()">
                    <i class="bi bi-plus-circle me-2"></i>Add Category
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Categories Table -->
<div class="card fade-in">
    <div class="card-header">
        <h5 class="mb-0">Categories List (<?php echo count($categories); ?> categories)</h5>
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
            <table class="table table-hover" id="categoriesTable">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Items Count</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($category['code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                            <td>
                                <span class="badge <?php echo $category['type'] === 'medical' ? 'bg-primary' : 'bg-secondary'; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $category['type'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($category['description']): ?>
                                    <?php echo htmlspecialchars(substr($category['description'], 0, 100)); ?>
                                    <?php echo strlen($category['description']) > 100 ? '...' : ''; ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo number_format($category['item_count']); ?></span>
                            </td>
                            <td>
                                <span class="badge <?php echo $category['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td><?php echo formatDateTime($category['created_at'], 'M j, Y'); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <?php if (hasRole(['admin', 'inventory_manager'])): ?>
                                        <button type="button" class="btn btn-sm btn-outline-success" 
                                                onclick="openEditModal(<?php echo htmlspecialchars(json_encode($category)); ?>)" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        
                                        <?php if ($category['item_count'] == 0): ?>
                                            <form method="POST" class="d-inline" 
                                                  onsubmit="return confirmDelete('Are you sure you want to delete this category?')">
                                                <input type="hidden" name="delete_id" value="<?php echo $category['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <a href="/pages/items/index.php?category=<?php echo $category['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary" title="View Items">
                                        <i class="bi bi-box"></i>
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
<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="categoryForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="category_id" id="categoryId">
                    
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
                    
                    <div class="mb-3">
                        <label for="modalType" class="form-label">Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="modalType" name="type" required>
                            <option value="">Select Type</option>
                            <option value="medical">Medical</option>
                            <option value="non_medical">Non-Medical</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="modalDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="modalDescription" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="modalIsActive" name="is_active" checked>
                        <label class="form-check-label" for="modalIsActive">
                            Active Category
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Add Category</button>
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
    initDataTable("#categoriesTable", {
        order: [[1, "asc"]],
        columnDefs: [
            { orderable: false, targets: [7] }
        ]
    });
});

function openAddModal() {
    document.getElementById("modalTitle").textContent = "Add Category";
    document.getElementById("formAction").value = "add";
    document.getElementById("submitBtn").textContent = "Add Category";
    document.getElementById("categoryForm").reset();
    document.getElementById("modalIsActive").checked = true;
}

function openEditModal(category) {
    document.getElementById("modalTitle").textContent = "Edit Category";
    document.getElementById("formAction").value = "edit";
    document.getElementById("submitBtn").textContent = "Update Category";
    document.getElementById("categoryId").value = category.id;
    document.getElementById("modalName").value = category.name;
    document.getElementById("modalCode").value = category.code;
    document.getElementById("modalType").value = category.type;
    document.getElementById("modalDescription").value = category.description || "";
    document.getElementById("modalIsActive").checked = category.is_active == 1;
}
</script>
';

include '../../includes/footer.php';
?>