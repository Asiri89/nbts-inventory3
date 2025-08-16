<?php
/**
 * Items Management Page for NBTS Inventory Management System
 * 
 * This page displays and manages inventory items
 */

require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication
requireAuth();

$page_title = 'Items Management';

// Handle delete request
if (isset($_POST['delete_id']) && hasRole(['admin', 'inventory_manager'])) {
    $delete_id = (int)$_POST['delete_id'];
    
    try {
        // Check if item has inventory records
        $inventory_count = fetchSingle(
            "SELECT COUNT(*) as count FROM inventory WHERE item_id = ?",
            [$delete_id],
            'i'
        )['count'];
        
        if ($inventory_count > 0) {
            $error_message = 'Cannot delete item with existing inventory records.';
        } else {
            // Get item info for logging
            $item = fetchSingle("SELECT * FROM items WHERE id = ?", [$delete_id], 'i');
            
            // Delete item
            executeQuery("DELETE FROM items WHERE id = ?", [$delete_id], 'i');
            
            // Log activity
            logActivity('Delete Item', 'items', $delete_id, $item);
            
            $success_message = 'Item deleted successfully.';
        }
    } catch (Exception $e) {
        $error_message = 'Error deleting item: ' . $e->getMessage();
    }
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query
$query = "
    SELECT i.*, c.name as category_name, c.type as category_type,
           COUNT(inv.id) as stock_count,
           SUM(CASE WHEN inv.status = 'active' THEN inv.quantity ELSE 0 END) as active_stock,
           SUM(CASE WHEN inv.status = 'active' THEN inv.quantity * inv.unit_cost ELSE 0 END) as total_value
    FROM items i 
    LEFT JOIN categories c ON i.category_id = c.id
    LEFT JOIN inventory inv ON i.id = inv.item_id
    WHERE 1=1
";

$params = [];
$types = '';

// Apply filters
if ($search) {
    $query .= " AND (i.name LIKE ? OR i.code LIKE ? OR i.brand LIKE ? OR i.model LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= 'ssss';
}

if ($category_filter) {
    $query .= " AND i.category_id = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

if ($status_filter) {
    if ($status_filter === 'active') {
        $query .= " AND i.is_active = 1";
    } else {
        $query .= " AND i.is_active = 0";
    }
}

$query .= " GROUP BY i.id ORDER BY i.name";

// Execute query
$items = fetchAll($query, $params, $types);

// Get categories for filter dropdown
$categories = fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");

include '../../includes/header.php';
?>

<div class="page-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-box me-2"></i>Items Management</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/pages/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Items</li>
                </ol>
            </nav>
        </div>
        <?php if (hasRole(['admin', 'inventory_manager'])): ?>
            <div>
                <a href="add.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Add New Item
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4 fade-in">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Search Items</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search by name, code, brand, or model...">
            </div>
            <div class="col-md-3">
                <label for="category" class="form-label">Category</label>
                <select class="form-select" id="category" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" 
                                <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?> 
                            (<?php echo ucfirst($category['type']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Items Table -->
<div class="card fade-in">
    <div class="card-header">
        <h5 class="mb-0">Items List (<?php echo count($items); ?> items found)</h5>
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
            <table class="table table-hover" id="itemsTable">
                <thead>
                    <tr>
                        <th>Item Code</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Brand/Model</th>
                        <th>Unit Cost</th>
                        <th>Stock Count</th>
                        <th>Active Stock</th>
                        <th>Total Value</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($item['code']); ?></strong>
                            </td>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                    <?php if ($item['description']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($item['description'], 0, 100)); ?><?php echo strlen($item['description']) > 100 ? '...' : ''; ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?php echo $item['category_type'] === 'medical' ? 'bg-primary' : 'bg-secondary'; ?>">
                                    <?php echo htmlspecialchars($item['category_name']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($item['brand']): ?>
                                    <strong><?php echo htmlspecialchars($item['brand']); ?></strong><br>
                                <?php endif; ?>
                                <?php if ($item['model']): ?>
                                    <small class="text-muted"><?php echo htmlspecialchars($item['model']); ?></small>
                                <?php endif; ?>
                                <?php if (!$item['brand'] && !$item['model']): ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatCurrency($item['unit_cost']); ?></td>
                            <td>
                                <span class="badge bg-info"><?php echo number_format($item['stock_count']); ?></span>
                            </td>
                            <td>
                                <?php if ($item['active_stock'] <= $item['reorder_level'] && $item['reorder_level'] > 0): ?>
                                    <span class="badge bg-warning"><?php echo number_format($item['active_stock']); ?></span>
                                    <small class="text-warning d-block">Low Stock</small>
                                <?php else: ?>
                                    <span class="badge bg-success"><?php echo number_format($item['active_stock']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatCurrency($item['total_value']); ?></td>
                            <td>
                                <span class="badge <?php echo $item['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo $item['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="view.php?id=<?php echo $item['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    
                                    <?php if (hasRole(['admin', 'inventory_manager'])): ?>
                                        <a href="edit.php?id=<?php echo $item['id']; ?>" 
                                           class="btn btn-sm btn-outline-success" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        
                                        <?php if ($item['stock_count'] == 0): ?>
                                            <form method="POST" class="d-inline" 
                                                  onsubmit="return confirmDelete('Are you sure you want to delete this item?')">
                                                <input type="hidden" name="delete_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                data-bs-toggle="dropdown" title="More Actions">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <?php if ($item['qr_code']): ?>
                                                <li>
                                                    <a class="dropdown-item" href="#" 
                                                       onclick="printQRCode('<?php echo QR_CODES_URL . '/' . $item['qr_code']; ?>', '<?php echo htmlspecialchars($item['name']); ?>')">
                                                        <i class="bi bi-qr-code me-2"></i>Print QR Code
                                                    </a>
                                                </li>
                                            <?php else: ?>
                                                <li>
                                                    <a class="dropdown-item" href="generate_qr.php?id=<?php echo $item['id']; ?>">
                                                        <i class="bi bi-qr-code me-2"></i>Generate QR Code
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            <li>
                                                <a class="dropdown-item" href="/pages/inventory/index.php?item_id=<?php echo $item['id']; ?>">
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

<?php
$additional_js = '
<script>
$(document).ready(function() {
    initDataTable("#itemsTable", {
        order: [[1, "asc"]],
        columnDefs: [
            { orderable: false, targets: [9] }
        ]
    });
});
</script>
';

include '../../includes/footer.php';
?>