<?php
/**
 * Add Item Page for NBTS Inventory Management System
 * 
 * This page handles adding new inventory items
 */

require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication and proper role
requireAuth(['admin', 'inventory_manager']);

$page_title = 'Add New Item';

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
            // Check if code already exists
            $existing = fetchSingle(
                "SELECT id FROM items WHERE code = ?",
                [$code],
                's'
            );
            
            if ($existing) {
                $error_message = 'Item code already exists. Please use a different code.';
            } else {
                // Insert new item
                executeQuery(
					"INSERT INTO stock_movements (inventory_id, movement_type, to_branch_id, quantity, unit_cost, total_cost, reference_number, reason, created_by) VALUES (?, 'receipt', ?, ?, ?, ?, ?, 'Initial stock receipt', ?)",
						[1, 1, 1, 25000, 25000, '', 2],
					'iiddsi'
				);
                
                $item_id = getLastInsertId();
                
                // Log activity
                logActivity('Create Item', 'items', $item_id, null, [
                    'name' => $name,
                    'code' => $code,
                    'category_id' => $category_id,
                    'unit_cost' => $unit_cost
                ]);
                
                // Generate QR code
                QRCode::generateForItem($item_id);
                
                $success_message = 'Item added successfully.';
                
                // Redirect after successful creation
                header('Location: index.php?success=' . urlencode($success_message));
                exit();
            }
        } catch (Exception $e) {
            $error_message = 'Error adding item: ' . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
?>

<div class="page-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-plus-circle me-2"></i>Add New Item</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/pages/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Items</a></li>
                    <li class="breadcrumb-item active">Add New Item</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Items
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card fade-in">
            <div class="card-header">
                <h5 class="mb-0">Item Information</h5>
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
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="code" class="form-label">Item Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="code" name="code" 
                                   value="<?php echo htmlspecialchars($_POST['code'] ?? ''); ?>" required
                                   onchange="checkItemCode(this.value)">
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
                                            <?php echo (($_POST['category_id'] ?? '') == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?> 
                                        (<?php echo ucfirst($category['type']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="unit_of_measure" class="form-label">Unit of Measure</label>
                            <select class="form-select" id="unit_of_measure" name="unit_of_measure">
                                <option value="pcs" <?php echo (($_POST['unit_of_measure'] ?? 'pcs') === 'pcs') ? 'selected' : ''; ?>>Pieces</option>
                                <option value="set" <?php echo (($_POST['unit_of_measure'] ?? '') === 'set') ? 'selected' : ''; ?>>Set</option>
                                <option value="box" <?php echo (($_POST['unit_of_measure'] ?? '') === 'box') ? 'selected' : ''; ?>>Box</option>
                                <option value="pack" <?php echo (($_POST['unit_of_measure'] ?? '') === 'pack') ? 'selected' : ''; ?>>Pack</option>
                                <option value="roll" <?php echo (($_POST['unit_of_measure'] ?? '') === 'roll') ? 'selected' : ''; ?>>Roll</option>
                                <option value="bottle" <?php echo (($_POST['unit_of_measure'] ?? '') === 'bottle') ? 'selected' : ''; ?>>Bottle</option>
                                <option value="kg" <?php echo (($_POST['unit_of_measure'] ?? '') === 'kg') ? 'selected' : ''; ?>>Kilogram</option>
                                <option value="liter" <?php echo (($_POST['unit_of_measure'] ?? '') === 'liter') ? 'selected' : ''; ?>>Liter</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="brand" class="form-label">Brand</label>
                            <input type="text" class="form-control" id="brand" name="brand" 
                                   value="<?php echo htmlspecialchars($_POST['brand'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="model" class="form-label">Model</label>
                            <input type="text" class="form-control" id="model" name="model" 
                                   value="<?php echo htmlspecialchars($_POST['model'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                                  placeholder="Enter detailed description of the item..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="unit_cost" class="form-label">Unit Cost (Rs.)</label>
                            <input type="number" class="form-control" id="unit_cost" name="unit_cost" 
                                   value="<?php echo htmlspecialchars($_POST['unit_cost'] ?? '0.00'); ?>" 
                                   min="0" step="0.01">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="reorder_level" class="form-label">Reorder Level</label>
                            <input type="number" class="form-control" id="reorder_level" name="reorder_level" 
                                   value="<?php echo htmlspecialchars($_POST['reorder_level'] ?? '0'); ?>" min="0">
                            <div class="form-text">Minimum stock level before reorder alert</div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                                   <?php echo (($_POST['is_active'] ?? '1') === '1' || !isset($_POST['is_active'])) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                Active Item
                            </label>
                            <div class="form-text">Inactive items will not appear in stock operations</div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-outline-secondary me-md-2">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Add Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card fade-in">
            <div class="card-header">
                <h6 class="mb-0">Help & Tips</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6><i class="bi bi-lightbulb text-warning me-2"></i>Item Code Guidelines</h6>
                    <ul class="small">
                        <li>Use a unique, descriptive code</li>
                        <li>Consider category prefixes (e.g., BPE001, LAB001)</li>
                        <li>Keep it short but meaningful</li>
                        <li>Avoid special characters</li>
                    </ul>
                </div>
                
                <div class="mb-3">
                    <h6><i class="bi bi-info-circle text-info me-2"></i>Categories</h6>
                    <div class="small">
                        <strong>Medical:</strong> Equipment directly used for medical procedures<br>
                        <strong>Non-Medical:</strong> Support equipment and office supplies
                    </div>
                </div>
                
                <div class="mb-3">
                    <h6><i class="bi bi-arrow-repeat text-primary me-2"></i>Reorder Level</h6>
                    <div class="small">
                        Set the minimum stock quantity that should trigger a reorder notification. 
                        This helps prevent stockouts.
                    </div>
                </div>
                
                <div class="alert alert-info small">
                    <i class="bi bi-qr-code me-2"></i>
                    <strong>QR Code:</strong> A QR code will be automatically generated for this item after creation.
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$additional_js = '
<script>
function checkItemCode(code) {
    if (code.length > 0) {
        fetch("/api/check_item_code.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({code: code})
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

// Auto-generate item code based on category and name
document.getElementById("category_id").addEventListener("change", function() {
    generateItemCode();
});

document.getElementById("name").addEventListener("input", function() {
    generateItemCode();
});

function generateItemCode() {
    const categorySelect = document.getElementById("category_id");
    const nameInput = document.getElementById("name");
    const codeInput = document.getElementById("code");
    
    if (categorySelect.value && nameInput.value && !codeInput.value) {
        const categoryText = categorySelect.options[categorySelect.selectedIndex].text;
        const categoryCode = categoryText.split("(")[0].trim().substring(0, 3).toUpperCase();
        const nameCode = nameInput.value.substring(0, 3).toUpperCase();
        const randomNum = Math.floor(Math.random() * 999) + 1;
        
        const suggestedCode = categoryCode + nameCode + randomNum.toString().padStart(3, "0");
        codeInput.value = suggestedCode;
        checkItemCode(suggestedCode);
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