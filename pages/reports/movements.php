<?php
/**
 * Stock Movements Report Page for NBTS Inventory Management System
 * 
 * This page displays stock movement history and reports
 */

require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication
requireAuth();

$page_title = 'Stock Movements Report';

// Get user info and branch restriction
$current_user = Auth::getCurrentUser();
$branch_id = ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'auditor') ? null : $_SESSION['user_branch_id'];

// Get filter parameters
$item_filter = $_GET['item_id'] ?? '';
$inventory_filter = $_GET['inventory_id'] ?? '';
$branch_filter = $_GET['branch_id'] ?? '';
$movement_type_filter = $_GET['movement_type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Apply branch restriction
if ($branch_id && !$branch_filter) {
    $branch_filter = $branch_id;
}

// Build query
$query = "
    SELECT sm.*, i.name as item_name, i.code as item_code,
           fb.name as from_branch, tb.name as to_branch,
           u.first_name, u.last_name, inv.serial_number
    FROM stock_movements sm
    JOIN inventory inv ON sm.inventory_id = inv.id
    JOIN items i ON inv.item_id = i.id
    LEFT JOIN branches fb ON sm.from_branch_id = fb.id
    LEFT JOIN branches tb ON sm.to_branch_id = tb.id
    JOIN users u ON sm.created_by = u.id
    WHERE 1=1
";

$params = [];
$types = '';

// Apply filters
if ($item_filter) {
    $query .= " AND inv.item_id = ?";
    $params[] = $item_filter;
    $types .= 'i';
}

if ($inventory_filter) {
    $query .= " AND sm.inventory_id = ?";
    $params[] = $inventory_filter;
    $types .= 'i';
}

if ($branch_filter) {
    $query .= " AND (sm.from_branch_id = ? OR sm.to_branch_id = ?)";
    $params[] = $branch_filter;
    $params[] = $branch_filter;
    $types .= 'ii';
}

if ($movement_type_filter) {
    $query .= " AND sm.movement_type = ?";
    $params[] = $movement_type_filter;
    $types .= 's';
}

if ($date_from) {
    $query .= " AND DATE(sm.created_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if ($date_to) {
    $query .= " AND DATE(sm.created_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

$query .= " ORDER BY sm.created_at DESC LIMIT 1000";

// Execute query
$movements = fetchAll($query, $params, $types);

// Get filter options
$items = fetchAll("SELECT id, name, code FROM items WHERE is_active = 1 ORDER BY name");
$branches = $branch_id ? [] : fetchAll("SELECT id, name FROM branches WHERE is_active = 1 ORDER BY name");

// Handle export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="stock_movements_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF"); // BOM for UTF-8
    
    fputcsv($output, ['Date', 'Item Code', 'Item Name', 'Serial Number', 'Movement Type', 'From Branch', 'To Branch', 'Quantity', 'Unit Cost', 'Total Cost', 'Reference', 'Reason', 'Created By']);
    
    foreach ($movements as $movement) {
        fputcsv($output, [
            formatDateTime($movement['created_at']),
            $movement['item_code'],
            $movement['item_name'],
            $movement['serial_number'] ?: '-',
            ucfirst(str_replace('_', ' ', $movement['movement_type'])),
            $movement['from_branch'] ?: '-',
            $movement['to_branch'] ?: '-',
            $movement['quantity'],
            $movement['unit_cost'],
            $movement['total_cost'],
            $movement['reference_number'] ?: '-',
            $movement['reason'] ?: '-',
            $movement['first_name'] . ' ' . $movement['last_name']
        ]);
    }
    
    fclose($output);
    exit();
}

include '../../includes/header.php';
?>

<div class="page-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-arrow-repeat me-2"></i>Stock Movements Report</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Stock Movements</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4 fade-in">
    <div class="card-header">
        <h5 class="mb-0">Filter Movements</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
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
                <label for="movement_type" class="form-label">Movement Type</label>
                <select class="form-select" id="movement_type" name="movement_type">
                    <option value="">All Types</option>
                    <option value="receipt" <?php echo $movement_type_filter === 'receipt' ? 'selected' : ''; ?>>Receipt</option>
                    <option value="issue" <?php echo $movement_type_filter === 'issue' ? 'selected' : ''; ?>>Issue</option>
                    <option value="transfer_out" <?php echo $movement_type_filter === 'transfer_out' ? 'selected' : ''; ?>>Transfer Out</option>
                    <option value="transfer_in" <?php echo $movement_type_filter === 'transfer_in' ? 'selected' : ''; ?>>Transfer In</option>
                    <option value="adjustment" <?php echo $movement_type_filter === 'adjustment' ? 'selected' : ''; ?>>Adjustment</option>
                    <option value="return" <?php echo $movement_type_filter === 'return' ? 'selected' : ''; ?>>Return</option>
                    <option value="disposal" <?php echo $movement_type_filter === 'disposal' ? 'selected' : ''; ?>>Disposal</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="date_from" class="form-label">From Date</label>
                <input type="date" class="form-control" id="date_from" name="date_from" 
                       value="<?php echo htmlspecialchars($date_from); ?>">
            </div>
            
            <div class="col-md-2">
                <label for="date_to" class="form-label">To Date</label>
                <input type="date" class="form-control" id="date_to" name="date_to" 
                       value="<?php echo htmlspecialchars($date_to); ?>">
            </div>
            
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i>
                    </button>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" 
                       class="btn btn-outline-success">
                        <i class="bi bi-download"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Movements Table -->
<div class="card fade-in">
    <div class="card-header">
        <h5 class="mb-0">Stock Movements (<?php echo count($movements); ?> records)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="movementsTable">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>Item</th>
                        <th>Movement Type</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Quantity</th>
                        <th>Value</th>
                        <th>Reference</th>
                        <th>Created By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movements as $movement): ?>
                        <tr>
                            <td><?php echo formatDateTime($movement['created_at'], 'M j, Y H:i'); ?></td>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($movement['item_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($movement['item_code']); ?></small>
                                    <?php if ($movement['serial_number']): ?>
                                        <br><small class="text-muted">S/N: <?php echo htmlspecialchars($movement['serial_number']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
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
                            <td>
                                <?php if ($movement['reference_number']): ?>
                                    <?php echo htmlspecialchars($movement['reference_number']); ?>
                                <?php endif; ?>
                                <?php if ($movement['reason']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($movement['reason']); ?></small>
                                <?php endif; ?>
                                <?php if (!$movement['reference_number'] && !$movement['reason']): ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($movement['first_name'] . ' ' . $movement['last_name']); ?></td>
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
    initDataTable("#movementsTable", {
        order: [[0, "desc"]],
        pageLength: 50
    });
});
</script>
';

include '../../includes/footer.php';
?>