<?php
/**
 * Edit Item Page for NBTS Inventory Management System
 * 
 * This page handles editing existing inventory items
 */

require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication and proper role
requireAuth(['admin', 'inventory_manager']);

$page_title = 'Edit Item';

// Get item ID
$item_id = (int)($_GET['id'] ?? 0);

if (!$item_id) {
    header('Location: index.php?error=Invalid item ID');
    exit();
}

// Get item details
$item = fetchSingle("SELECT * FROM items WHERE id = ?", [$item_id], 'i');

if (!$item) {
    header('Location: index.php?error=Item not found');
    exit();
}

// Get categories for dropdown
$categories = fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $code = sanitizeInput($_POST['code']);
    $category_id = (int)$_POST['category_id'];
    $brand = sanitizeInput($_POST['brand']);
    $model = sanitizeInput($_POST['model']);
    $description = sanitizeInput($_POST['description']);
    $unit_of_measure = sanitizeInput($_POST['unit_of_measure']);
    $unit_cost = floatval($_POST['unit_cost']);
    $reorder_level = (int)$_POST['reorder_level'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate required fields
    if (empty($name) || empty($code) || empty($category_id)) {
        $error_message = 'Please fill in all required fields.';
    } else {
        try {
            // Check if code already exists (excluding current item)
            $existing = fetchSingle(
                "SELECT id FROM items WHERE code = ? AND id != ?",
                [$code, $item_id],
                'si'
            );
            
            if ($existing) {
                $error_message = 'Item code already exists. Please use a different code.';
            } else {
                // Store old values for logging
                $old_values = $item;
                
                // Update item
                executeQuery(
                    "UPDATE items SET name = ?, code = ?, category_id = ?, brand = ?, model = ?, description = ?, unit_of_measure = ?, unit_cost = ?, reorder_level = ?, is_active = ?, updated_at = NOW() WHERE id = ?",
                    [$name, $code, $category_id, $brand, $model, $description, $unit_of_measure, $unit_cost, $reorder_level, $is_active, $item_id],
                    'ssississdii'
                );
                
                // Log activity
                logActivity('Update Item', 'items', $item_id, $old_values, [
                    'name' => $name,
                    'code' => $code,
                    'category_id' => $category_id,
                    'unit_cost' => $unit_cost
                ]);
                
                $success_message = 'Item updated successfully.';
                
                // Redirect after successful update
                header('Location: view.php?id=' . $item_id . '&success=' . urlencode($success_message));
                exit();
            }
        } catch (Exception $e) {
            $error_message = 'Error updating item: ' . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
?>

<div class="page-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-pencil me-2"></i>Edit Item</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/pages/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Items</a></li>
                    <li class="breadcrumb-item"><a href="view.php?id=<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['code']); ?></a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="view.php?id=<?php echo $item['id']; ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Item
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card fade-in">
            <div class="card-header">
                <h5 class="mb-0">Edit Item Information</h5>
            </div>
            <div class="card-body">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="itemForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Item Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? $item['name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="code" class="form-label">Item Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="code" name="code" 
                                   value="<?php echo htmlspecialchars($_POST['code'] ?? $item['code']); ?>" required
                                   onchange="checkItemCode(this.value, <?php echo $item_id; ?>)">
                            <div class="form-text">Unique identifier for this item</div>
                            <div id="codeStatus"></div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo (($_POST['category_id'] ?? $item['category_id']) == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?> 
                                        (<?php echo ucfirst($category['type']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="unit_of_measure" class="form-label">Unit of Measure</label>
                            <select class="form-select" id="unit_of_measure" name="unit_of_measure">
                                <?php
                                $current_unit = $_POST['unit_of_measure'] ?? $item['unit_of_measure'];
                                $units = ['pcs', 'set', 'box', 'pack', 'roll', 'bottle', 'kg', 'liter'];
                                ?>
                                <?php foreach ($units as $unit): ?>
                                    <option value="<?php echo $unit; ?>" <?php echo ($current_unit === $unit) ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($unit === 'pcs' ? 'Pieces' : $unit); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="brand" class="form-label">Brand</label>
                            <input type="text" class="form-control" id="brand" name="brand" 
                                   value="<?php echo htmlspecialchars($_POST['brand'] ?? $item['brand']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="model" class="form-label">Model</label>
                            <input type="text" class="form-control" id="model" name="model" 
                                   value="<?php echo htmlspecialchars($_POST['model'] ?? $item['model']); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                                  placeholder="Enter detailed description of the item..."><?php echo htmlspecialchars($_POST['description'] ?? $item['description']); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="unit_cost" class="form-label">Unit Cost (Rs.)</label>
                            <input type="number" class="form-control" id="unit_cost" name="unit_cost" 
                                   value="<?php echo htmlspecialchars($_POST['unit_cost'] ?? $item['unit_cost']); ?>" 
                                   min="0" step="0.01">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="reorder_level" class="form-label">Reorder Level</label>
                            <input type="number" class="form-control" id="reorder_level" name="reorder_level" 
                                   value="<?php echo htmlspecialchars($_POST['reorder_level'] ?? $item['reorder_level']); ?>" min="0">
                            <div class="form-text">Minimum stock level before reorder alert</div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                                   <?php echo (($_POST['is_active'] ?? $item['is_active']) == 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                Active Item
                            </label>
                            <div class="form-text">Inactive items will not appear in stock operations</div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="view.php?id=<?php echo $item['id']; ?>" class="btn btn-outline-secondary me-md-2">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Update Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card fade-in">
            <div class="card-header">
                <h6 class="mb-0">Current Item Details</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>Code:</strong></td>
                        <td><?php echo htmlspecialchars($item['code']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Created:</strong></td>
                        <td><?php echo formatDateTime($item['created_at']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Last Updated:</strong></td>
                        <td><?php echo formatDateTime($item['updated_at']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>
                            <span class="badge <?php echo $item['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                <?php echo $item['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                    </tr>
                </table>
                
                <div class="alert alert-warning small mt-3">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Note:</strong> Changes to unit cost will not affect existing inventory records. Only new stock additions will use the updated cost.
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$additional_js = '
<script>
function checkItemCode(code, excludeId) {
    if (code.length > 0) {
        fetch("/api/check_item_code.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({code: code, exclude_id: excludeId})
        })
        .then(response => response.json())
        .then(data => {
            const statusDiv = document.getElementById("codeStatus");
            if (data.exists) {
                statusDiv.innerHTML = "<small class=\"text-danger\">❌ Code already exists</small>";
                document.getElementById("code").classList.add("is-invalid");
            } else {
                statusDiv.innerHTML = "<small class=\"text-success\">✅ Code available</small>";
                document.getElementById("code").classList.remove("is-invalid");
                document.getElementById("code").classList.add("is-valid");
            }
        })
        .catch(error => {
            console.error("Error:", error);
        });
    } else {
        document.getElementById("codeStatus").innerHTML = "";
        document.getElementById("code").classList.remove("is-valid", "is-invalid");
    }
}

// Form validation
document.getElementById("itemForm").addEventListener("submit", function(e) {
    const code = document.getElementById("code").value;
    const name = document.getElementById("name").value;
    const categoryId = document.getElementById("category_id").value;
    
    if (!code || !name || !categoryId) {
        e.preventDefault();
        showToast("Error", "Please fill in all required fields", "error");
        return false;
    }
    
    if (document.getElementById("code").classList.contains("is-invalid")) {
        e.preventDefault();
        showToast("Error", "Please choose a different item code", "error");
        return false;
    }
});
</script>
';

include '../../includes/footer.php';
?>