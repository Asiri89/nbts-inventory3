<?php
/**
 * Maintenance History Page for NBTS Inventory Management System
 * 
 * This page displays maintenance history records
 */

require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication
requireAuth();

$page_title = 'Maintenance History';

// Get user info and branch restriction
$current_user = Auth::getCurrentUser();
$branch_id = ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'auditor') ? null : $_SESSION['user_branch_id'];

// Get filter parameters
$item_filter = $_GET['item_id'] ?? '';
$branch_filter = $_GET['branch_id'] ?? '';
$maintenance_type_filter = $_GET['maintenance_type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Apply branch restriction
if ($branch_id && !$branch_filter) {
    $branch_filter = $branch_id;
}

// Build query
$query = "
    SELECT mh.*, i.name as item_name, i.code as item_code, b.name as branch_name,
           inv.serial_number, u.first_name, u.last_name
    FROM maintenance_history mh
    JOIN inventory inv ON mh.inventory_id = inv.id
    JOIN items i ON inv.item_id = i.id
    JOIN branches b ON inv.branch_id = b.id
    JOIN users u ON mh.created_by = u.id
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

if ($branch_filter) {
    $query .= " AND inv.branch_id = ?";
    $params[] = $branch_filter;
    $types .= 'i';
}

if ($maintenance_type_filter) {
    $query .= " AND mh.maintenance_type = ?";
    $params[] = $maintenance_type_filter;
    $types .= 's';
}

if ($date_from) {
    $query .= " AND mh.maintenance_date >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if ($date_to) {
    $query .= " AND mh.maintenance_date <= ?";
    $params[] = $date_to;
    $types .= 's';
}

$query .= " ORDER BY mh.maintenance_date DESC";

// Execute query
$maintenance_history = fetchAll($query, $params, $types);

// Get filter options
$items = fetchAll("SELECT id, name, code FROM items WHERE is_active = 1 ORDER BY name");
$branches = $branch_id ? [] : fetchAll("SELECT id, name FROM branches WHERE is_active = 1 ORDER BY name");

include '../../includes/header.php';
?>

<div class="page-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-clock-history me-2"></i>Maintenance History</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Maintenance History</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4 fade-in">
    <div class="card-header">
        <h5 class="mb-0">Filter History</h5>
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
                <label for="maintenance_type" class="form-label">Type</label>
                <select class="form-select" id="maintenance_type" name="maintenance_type">
                    <option value="">All Types</option>
                    <option value="preventive" <?php echo $maintenance_type_filter === 'preventive' ? 'selected' : ''; ?>>Preventive</option>
                    <option value="calibration" <?php echo $maintenance_type_filter === 'calibration' ? 'selected' : ''; ?>>Calibration</option>
                    <option value="repair" <?php echo $maintenance_type_filter === 'repair' ? 'selected' : ''; ?>>Repair</option>
                    <option value="inspection" <?php echo $maintenance_type_filter === 'inspection' ? 'selected' : ''; ?>>Inspection</option>
                    <option value="emergency" <?php echo $maintenance_type_filter === 'emergency' ? 'selected' : ''; ?>>Emergency</option>
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
                    <a href="history.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Maintenance History Table -->
<div class="card fade-in">
    <div class="card-header">
        <h5 class="mb-0">Maintenance History (<?php echo count($maintenance_history); ?> records)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="historyTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Item</th>
                        <th>Branch</th>
                        <th>Type</th>
                        <th>Technician</th>
                        <th>Cost</th>
                        <th>Downtime</th>
                        <th>Findings</th>
                        <th>Created By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($maintenance_history as $record): ?>
                        <tr>
                            <td><?php echo formatDate($record['maintenance_date']); ?></td>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($record['item_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($record['item_code']); ?></small>
                                    <?php if ($record['serial_number']): ?>
                                        <br><small class="text-muted">S/N: <?php echo htmlspecialchars($record['serial_number']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($record['branch_name']); ?></td>
                            <td>
                                <?php
                                $type_badges = [
                                    'preventive' => 'bg-success',
                                    'calibration' => 'bg-info',
                                    'repair' => 'bg-warning',
                                    'inspection' => 'bg-primary',
                                    'emergency' => 'bg-danger'
                                ];
                                ?>
                                <span class="badge <?php echo $type_badges[$record['maintenance_type']] ?? 'bg-secondary'; ?>">
                                    <?php echo ucfirst($record['maintenance_type']); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($record['technician_name']); ?>
                                <?php if ($record['service_provider']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($record['service_provider']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatCurrency($record['cost']); ?></td>
                            <td>
                                <?php if ($record['downtime_hours'] > 0): ?>
                                    <span class="badge bg-warning"><?php echo $record['downtime_hours']; ?> hrs</span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($record['findings']): ?>
                                    <span data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($record['findings']); ?>">
                                        <?php echo htmlspecialchars(substr($record['findings'], 0, 50)); ?>
                                        <?php echo strlen($record['findings']) > 50 ? '...' : ''; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
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
    initDataTable("#historyTable", {
        order: [[0, "desc"]],
        columnDefs: [
            { orderable: false, targets: [7] }
        ]
    });
});
</script>
';

include '../../includes/footer.php';
?>