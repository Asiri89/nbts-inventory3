<?php
/**
 * Inventory Management Page for NBTS Inventory Management System
 * 
 * This page displays and manages inventory records
 */

require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication
requireAuth();

$page_title = 'Inventory Management';

// Get user info and branch restriction
$current_user = Auth::getCurrentUser();
$branch_id = ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'auditor') ? null : $_SESSION['user_branch_id'];

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$item_filter = $_GET['item_id'] ?? '';
$branch_filter = $_GET['branch_id'] ?? '';
$status_filter = $_GET['status'] ?? '';
$category_filter = $_GET['category'] ?? '';

// Build query
$query = "
    SELECT inv.*, i.name as item_name, i.code as item_code, i.unit_of_measure,
           b.name as branch_name, s.name as supplier_name, c.name as category_name
    FROM inventory inv
    JOIN items i ON inv.item_id = i.id
    JOIN branches b ON inv.branch_id = b.id
    LEFT JOIN suppliers s ON inv.supplier_id = s.id
    LEFT JOIN categories c ON i.category_id = c.id
    WHERE 1=1
";

$params = [];
$types = '';

// Apply branch restriction
if ($branch_id) {
    $query .= " AND inv.branch_id = ?";
    $params[] = $branch_id;
    $types .= 'i';
}

// Apply filters
if ($search) {
    $query .= " AND (i.name LIKE ? OR i.code LIKE ? OR inv.serial_number LIKE ? OR inv.batch_number LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= 'ssss';
}

if ($item_filter) {
    $query .= " AND inv.item_id = ?";
    $params[] = $item_filter;
    $types .= 'i';
}

if ($branch_filter && !$branch_id) {
    $query .= " AND inv.branch_id = ?";
    $params[] = $branch_filter;
    $types .= 'i';
}

if ($status_filter) {
    $query .= " AND inv.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($category_filter) {
    $query .= " AND i.category_id = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

$query .= " ORDER BY inv.created_at DESC";

// Execute query
$inventory_records = fetchAll($query, $params, $types);

// Get filter options
$items = fetchAll("SELECT id, name, code FROM items WHERE is_active = 1 ORDER BY name");
$branches = $branch_id ? [] : fetchAll("SELECT id, name FROM branches WHERE is_active = 1 ORDER BY name");
$categories = fetchAll("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");

include '../../includes/header.php';
?>

<div class="page-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-box-seam me-2"></i>Inventory Management</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/pages/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Inventory</li>
                </ol>
            </nav>
        </div>
        <?php if (hasRole(['admin', 'inventory_manager'])): ?>
            <div>
                <a href="add.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Add Stock
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4 fade-in">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search by item, code, serial...">
            </div>
            <div class="col-md-2">
                <label for="item_id" class="form-label">Item</label>
                <select class="form-select" id="item_id" name="item_id">
                    <option value="">All Items</option>
                    <?php foreach ($items as $item): ?>
                        <option value="<?php echo $item['id']; ?>" 
                                <?php echo $item_filter == $item['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($item['code'] . ' - ' . $item['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (!$branch_id): ?>
                <div class="col-md-2">
                    <label for="branch_id" class="form-label">Branch</label>
                    <select class="form-select" id="branch_id" name="branch_id">
                        <option value="">All Branches</option>
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['id']; ?>" 
                                    <?php echo $branch_filter == $branch['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($branch['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="col-md-2">
                <label for="category" class="form-label">Category</label>
                <select class="form-select" id="category" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" 
                                <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="maintenance" <?php echo $status_filter === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                    <option value="repair" <?php echo $status_filter === 'repair' ? 'selected' : ''; ?>>Repair</option>
                    <option value="disposed" <?php echo $status_filter === 'disposed' ? 'selected' : ''; ?>>Disposed</option>
                    <option value="lost" <?php echo $status_filter === 'lost' ? 'selected' : ''; ?>>Lost</option>
                    <option value="damaged" <?php echo $status_filter === 'damaged' ? 'selected' : ''; ?>>Damaged</option>
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i>
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Inventory Table -->
<div class="card fade-in">
    <div class="card-header">
        <h5 class="mb-0">Inventory Records (<?php echo count($inventory_records); ?> records found)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="inventoryTable">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Serial/Batch</th>
                        <?php if (!$branch_id): ?>
                            <th>Branch</th>
                        <?php endif; ?>
                        <th>Quantity</th>
                        <th>Unit Cost</th>
                        <th>Total Value</th>
                        <th>Status</th>
                        <th>Warranty</th>
                        <th>Location</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventory_records as $record): ?>
                        <tr>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($record['item_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($record['item_code']); ?></small>
                                </div>
                            </td>
                            <td>
                                <?php if ($record['serial_number']): ?>
                                    <strong>S/N:</strong> <?php echo htmlspecialchars($record['serial_number']); ?><br>
                                <?php endif; ?>
                                <?php if ($record['batch_number']): ?>
                                    <strong>Batch:</strong> <?php echo htmlspecialchars($record['batch_number']); ?>
                                <?php endif; ?>
                                <?php if (!$record['serial_number'] && !$record['batch_number']): ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <?php if (!$branch_id): ?>
                                <td><?php echo htmlspecialchars($record['branch_name']); ?></td>
                            <?php endif; ?>
                            <td>
                                <span class="badge bg-primary"><?php echo number_format($record['quantity']); ?></span>
                                <small class="text-muted d-block"><?php echo htmlspecialchars($record['unit_of_measure']); ?></small>
                            </td>
                            <td><?php echo formatCurrency($record['unit_cost']); ?></td>
                            <td><?php echo formatCurrency($record['total_cost']); ?></td>
                            <td>
                                <?php
                                $status_badges = [
                                    'active' => 'bg-success',
                                    'maintenance' => 'bg-warning',
                                    'repair' => 'bg-danger',
                                    'disposed' => 'bg-dark',
                                    'lost' => 'bg-secondary',
                                    'damaged' => 'bg-danger'
                                ];
                                ?>
                                <span class="badge <?php echo $status_badges[$record['status']] ?? 'bg-secondary'; ?>">
                                    <?php echo ucfirst($record['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($record['warranty_end_date']): ?>
                                    <?php
                                    $warranty_end = new DateTime($record['warranty_end_date']);
                                    $today = new DateTime();
                                    $days_left = $today->diff($warranty_end)->days;
                                    $is_expired = $warranty_end < $today;
                                    ?>
                                    <small class="<?php echo $is_expired ? 'text-danger' : ($days_left <= 30 ? 'text-warning' : 'text-success'); ?>">
                                        <?php echo formatDate($record['warranty_end_date']); ?>
                                        <?php if ($is_expired): ?>
                                            <br><span class="badge bg-danger">Expired</span>
                                        <?php elseif ($days_left <= 30): ?>
                                            <br><span class="badge bg-warning"><?php echo $days_left; ?> days left</span>
                                        <?php endif; ?>
                                    </small>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($record['location']): ?>
                                    <?php echo htmlspecialchars($record['location']); ?>
                                    <?php if ($record['assigned_staff']): ?>
                                        <br><small class="text-muted">Assigned: <?php echo htmlspecialchars($record['assigned_staff']); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="view.php?id=<?php echo $record['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    
                                    <?php if (hasRole(['admin', 'inventory_manager'])): ?>
                                        <a href="edit.php?id=<?php echo $record['id']; ?>" 
                                           class="btn btn-sm btn-outline-success" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                data-bs-toggle="dropdown" title="More Actions">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <?php if ($record['qr_code']): ?>
                                                <li>
                                                    <a class="dropdown-item" href="#" 
                                                       onclick="printQRCode('<?php echo QR_CODES_URL . '/' . $record['qr_code']; ?>', '<?php echo htmlspecialchars($record['item_name']); ?>')">
                                                        <i class="bi bi-qr-code me-2"></i>Print QR Code
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            <li>
                                                <a class="dropdown-item" href="/pages/reports/movements.php?inventory_id=<?php echo $record['id']; ?>">
                                                    <i class="bi bi-arrow-repeat me-2"></i>View Movements
                                                </a>
                                            </li>
                                            <?php if (hasRole(['admin', 'inventory_manager'])): ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item" href="transfer.php?inventory_id=<?php echo $record['id']; ?>">
                                                        <i class="bi bi-arrow-left-right me-2"></i>Transfer
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="adjust.php?inventory_id=<?php echo $record['id']; ?>">
                                                        <i class="bi bi-plus-minus me-2"></i>Adjust Stock
                                                    </a>
                                                </li>
                                            <?php endif; ?>
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
    initDataTable("#inventoryTable", {
        order: [[0, "asc"]],
        columnDefs: [
            { orderable: false, targets: [-1] }
        ]
    });
});
</script>
';

include '../../includes/footer.php';
?>