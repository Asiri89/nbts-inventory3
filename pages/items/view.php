<?php
/**
 * View Item Details Page for NBTS Inventory Management System
 * 
 * This page displays detailed information about a specific item
 */

require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication
requireAuth();

$page_title = 'Item Details';

// Get item ID
$item_id = (int)($_GET['id'] ?? 0);

if (!$item_id) {
    header('Location: index.php?error=Invalid item ID');
    exit();
}

// Get item details
$item = fetchSingle(
    "SELECT i.*, c.name as category_name, c.type as category_type
     FROM items i 
     LEFT JOIN categories c ON i.category_id = c.id 
     WHERE i.id = ?",
    [$item_id],
    'i'
);

if (!$item) {
    header('Location: index.php?error=Item not found');
    exit();
}

// Get inventory records for this item
$current_user = Auth::getCurrentUser();
$branch_id = ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'auditor') ? null : $_SESSION['user_branch_id'];

$inventory_query = "
    SELECT inv.*, b.name as branch_name, s.name as supplier_name
    FROM inventory inv
    LEFT JOIN branches b ON inv.branch_id = b.id
    LEFT JOIN suppliers s ON inv.supplier_id = s.id
    WHERE inv.item_id = ?
";

$params = [$item_id];
$types = 'i';

if ($branch_id) {
    $inventory_query .= " AND inv.branch_id = ?";
    $params[] = $branch_id;
    $types = 'ii';
}

$inventory_query .= " ORDER BY inv.created_at DESC";
$inventory_records = fetchAll($inventory_query, $params, $types);

// Get stock summary
$stock_summary = fetchAll(
    "SELECT b.name as branch_name, 
            SUM(CASE WHEN inv.status = 'active' THEN inv.quantity ELSE 0 END) as active_stock,
            SUM(CASE WHEN inv.status = 'maintenance' THEN inv.quantity ELSE 0 END) as maintenance_stock,
            SUM(CASE WHEN inv.status = 'repair' THEN inv.quantity ELSE 0 END) as repair_stock,
            SUM(inv.quantity * inv.unit_cost) as total_value
     FROM inventory inv
     JOIN branches b ON inv.branch_id = b.id
     WHERE inv.item_id = ?" . ($branch_id ? " AND inv.branch_id = ?" : "") . "
     GROUP BY b.id
     ORDER BY b.name",
    $branch_id ? [$item_id, $branch_id] : [$item_id],
    $branch_id ? 'ii' : 'i'
);

// Get recent movements
$movements = fetchAll(
    "SELECT sm.*, fb.name as from_branch, tb.name as to_branch, u.first_name, u.last_name
     FROM stock_movements sm
     JOIN inventory inv ON sm.inventory_id = inv.id
     LEFT JOIN branches fb ON sm.from_branch_id = fb.id
     LEFT JOIN branches tb ON sm.to_branch_id = tb.id
     JOIN users u ON sm.created_by = u.id
     WHERE inv.item_id = ?" . ($branch_id ? " AND (sm.from_branch_id = ? OR sm.to_branch_id = ?)" : "") . "
     ORDER BY sm.created_at DESC
     LIMIT 10",
    $branch_id ? [$item_id, $branch_id, $branch_id] : [$item_id],
    $branch_id ? 'iii' : 'i'
);

include '../../includes/header.php';
?>

<div class="page-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-box me-2"></i><?php echo htmlspecialchars($item['name']); ?></h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/pages/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Items</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($item['code']); ?></li>
                </ol>
            </nav>
        </div>
        <div>
            <?php if (hasRole(['admin', 'inventory_manager'])): ?>
                <a href="edit.php?id=<?php echo $item['id']; ?>" class="btn btn-primary me-2">
                    <i class="bi bi-pencil me-2"></i>Edit Item
                </a>
            <?php endif; ?>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Items
            </a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Item Information -->
    <div class="col-lg-8">
        <div class="card fade-in mb-4">
            <div class="card-header">
                <h5 class="mb-0">Item Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Item Code:</strong></td>
                                <td><?php echo htmlspecialchars($item['code']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Name:</strong></td>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Category:</strong></td>
                                <td>
                                    <span class="badge <?php echo $item['category_type'] === 'medical' ? 'bg-primary' : 'bg-secondary'; ?>">
                                        <?php echo htmlspecialchars($item['category_name']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Brand:</strong></td>
                                <td><?php echo htmlspecialchars($item['brand'] ?: '-'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Model:</strong></td>
                                <td><?php echo htmlspecialchars($item['model'] ?: '-'); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Unit of Measure:</strong></td>
                                <td><?php echo htmlspecialchars($item['unit_of_measure']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Unit Cost:</strong></td>
                                <td><?php echo formatCurrency($item['unit_cost']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Reorder Level:</strong></td>
                                <td><?php echo number_format($item['reorder_level']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge <?php echo $item['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $item['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Created:</strong></td>
                                <td><?php echo formatDateTime($item['created_at']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php if ($item['description']): ?>
                    <div class="mt-3">
                        <strong>Description:</strong>
                        <p class="mt-2"><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stock Summary -->
        <div class="card fade-in mb-4">
            <div class="card-header">
                <h5 class="mb-0">Stock Summary by Branch</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th>Active Stock</th>
                                <th>In Maintenance</th>
                                <th>In Repair</th>
                                <th>Total Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($stock_summary)): ?>
                                <?php 
                                $total_active = 0;
                                $total_maintenance = 0;
                                $total_repair = 0;
                                $total_value = 0;
                                ?>
                                <?php foreach ($stock_summary as $summary): ?>
                                    <?php
                                    $total_active += $summary['active_stock'];
                                    $total_maintenance += $summary['maintenance_stock'];
                                    $total_repair += $summary['repair_stock'];
                                    $total_value += $summary['total_value'];
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($summary['branch_name']); ?></td>
                                        <td>
                                            <span class="badge bg-success"><?php echo number_format($summary['active_stock']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning"><?php echo number_format($summary['maintenance_stock']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger"><?php echo number_format($summary['repair_stock']); ?></span>
                                        </td>
                                        <td><?php echo formatCurrency($summary['total_value']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-secondary fw-bold">
                                    <td>Total</td>
                                    <td><span class="badge bg-success"><?php echo number_format($total_active); ?></span></td>
                                    <td><span class="badge bg-warning"><?php echo number_format($total_maintenance); ?></span></td>
                                    <td><span class="badge bg-danger"><?php echo number_format($total_repair); ?></span></td>
                                    <td><?php echo formatCurrency($total_value); ?></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No stock records found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Movements -->
        <div class="card fade-in">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Stock Movements</h5>
                <a href="/pages/reports/movements.php?item_id=<?php echo $item['id']; ?>" class="text-white text-decoration-none">
                    View All <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Quantity</th>
                                <th>Value</th>
                                <th>By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($movements)): ?>
                                <?php foreach ($movements as $movement): ?>
                                    <tr>
                                        <td><?php echo formatDateTime($movement['created_at'], 'M j, Y'); ?></td>
                                        <td>
                                            <?php
                                            $badge_class = [
                                                'receipt' => 'bg-success',
                                                'issue' => 'bg-primary',
                                                'transfer_out' => 'bg-warning',
                                                'transfer_in' => 'bg-info',
                                                'adjustment' => 'bg-secondary',
                                                'return' => 'bg-success',
                                                'disposal' => 'bg-danger'
                                            ];
                                            ?>
                                            <span class="badge <?php echo $badge_class[$movement['movement_type']] ?? 'bg-secondary'; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $movement['movement_type'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($movement['from_branch'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($movement['to_branch'] ?? '-'); ?></td>
                                        <td><?php echo number_format($movement['quantity']); ?></td>
                                        <td><?php echo formatCurrency($movement['total_cost']); ?></td>
                                        <td><?php echo htmlspecialchars($movement['first_name'] . ' ' . $movement['last_name']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No movements found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- QR Code -->
        <?php if ($item['qr_code']): ?>
            <div class="card fade-in mb-4">
                <div class="card-header">
                    <h6 class="mb-0">QR Code</h6>
                </div>
                <div class="card-body text-center">
                    <img src="<?php echo QR_CODES_URL . '/' . $item['qr_code']; ?>" 
                         alt="QR Code" class="img-fluid mb-3" style="max-width: 200px;">
                    <div>
                        <button class="btn btn-outline-primary btn-sm" 
                                onclick="printQRCode('<?php echo QR_CODES_URL . '/' . $item['qr_code']; ?>', '<?php echo htmlspecialchars($item['name']); ?>')">
                            <i class="bi bi-printer me-2"></i>Print QR Code
                        </button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card fade-in mb-4">
                <div class="card-header">
                    <h6 class="mb-0">QR Code</h6>
                </div>
                <div class="card-body text-center">
                    <p class="text-muted">No QR code generated</p>
                    <?php if (hasRole(['admin', 'inventory_manager'])): ?>
                        <a href="generate_qr.php?id=<?php echo $item['id']; ?>" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-qr-code me-2"></i>Generate QR Code
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <?php if (hasRole(['admin', 'inventory_manager'])): ?>
            <div class="card fade-in mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="/pages/inventory/add.php?item_id=<?php echo $item['id']; ?>" class="btn btn-outline-success btn-sm">
                            <i class="bi bi-plus-circle me-2"></i>Add Stock
                        </a>
                        <a href="/pages/inventory/transfer.php?item_id=<?php echo $item['id']; ?>" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-arrow-left-right me-2"></i>Transfer Stock
                        </a>
                        <a href="/pages/maintenance/schedule.php?item_id=<?php echo $item['id']; ?>" class="btn btn-outline-warning btn-sm">
                            <i class="bi bi-tools me-2"></i>Schedule Maintenance
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Item Statistics -->
        <div class="card fade-in">
            <div class="card-header">
                <h6 class="mb-0">Statistics</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="border-end">
                            <h4 class="text-primary mb-1"><?php echo count($inventory_records); ?></h4>
                            <small class="text-muted">Total Records</small>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <h4 class="text-success mb-1"><?php echo count($stock_summary); ?></h4>
                        <small class="text-muted">Branches</small>
                    </div>
                    <div class="col-6">
                        <div class="border-end">
                            <h4 class="text-info mb-1"><?php echo count($movements); ?></h4>
                            <small class="text-muted">Recent Movements</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <h4 class="text-warning mb-1">
                            <?php 
                            $total_active = array_sum(array_column($stock_summary, 'active_stock'));
                            echo number_format($total_active); 
                            ?>
                        </h4>
                        <small class="text-muted">Active Stock</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>