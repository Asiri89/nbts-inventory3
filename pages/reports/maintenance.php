<?php
/**
 * Maintenance Reports Page for NBTS Inventory Management System
 * 
 * This page generates maintenance-related reports
 */

require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication
requireAuth();

$page_title = 'Maintenance Reports';

// Get user info and branch restriction
$current_user = Auth::getCurrentUser();
$branch_id = ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'auditor') ? null : $_SESSION['user_branch_id'];

// Get filter parameters
$report_type = $_GET['report_type'] ?? 'due_maintenance';
$branch_filter = $_GET['branch_id'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Apply branch restriction
if ($branch_id && !$branch_filter) {
    $branch_filter = $branch_id;
}

// Generate report data based on type
$report_data = [];
$report_title = '';

switch ($report_type) {
    case 'due_maintenance':
        $report_title = 'Due Maintenance Report';
        $query = "
            SELECT ms.*, i.name as item_name, i.code as item_code, b.name as branch_name,
                   inv.serial_number, inv.location,
                   DATEDIFF(ms.next_maintenance_date, CURDATE()) as days_until_due
            FROM maintenance_schedules ms
            JOIN inventory inv ON ms.inventory_id = inv.id
            JOIN items i ON inv.item_id = i.id
            JOIN branches b ON inv.branch_id = b.id
            WHERE ms.status IN ('scheduled', 'overdue')
            AND ms.next_maintenance_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ";
        break;
        
    case 'maintenance_history':
        $report_title = 'Maintenance History Report';
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
        break;
        
    case 'maintenance_costs':
        $report_title = 'Maintenance Costs Report';
        $query = "
            SELECT b.name as branch_name, i.name as item_name, i.code as item_code,
                   COUNT(mh.id) as maintenance_count,
                   SUM(mh.cost) as total_cost,
                   AVG(mh.cost) as average_cost,
                   MAX(mh.maintenance_date) as last_maintenance
            FROM maintenance_history mh
            JOIN inventory inv ON mh.inventory_id = inv.id
            JOIN items i ON inv.item_id = i.id
            JOIN branches b ON inv.branch_id = b.id
            WHERE 1=1
        ";
        break;
}

// Apply filters
$params = [];
$types = '';

if ($branch_filter) {
    if ($report_type === 'maintenance_costs') {
        $query .= " AND b.id = ?";
    } else {
        $query .= " AND inv.branch_id = ?";
    }
    $params[] = $branch_filter;
    $types .= 'i';
}

if ($date_from && $report_type === 'maintenance_history') {
    $query .= " AND mh.maintenance_date >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if ($date_to && $report_type === 'maintenance_history') {
    $query .= " AND mh.maintenance_date <= ?";
    $params[] = $date_to;
    $types .= 's';
}

// Add GROUP BY and ORDER BY clauses
switch ($report_type) {
    case 'due_maintenance':
        $query .= " ORDER BY ms.next_maintenance_date ASC";
        break;
    case 'maintenance_history':
        $query .= " ORDER BY mh.maintenance_date DESC";
        break;
    case 'maintenance_costs':
        $query .= " GROUP BY b.id, i.id ORDER BY total_cost DESC";
        break;
}

// Execute query
$report_data = fetchAll($query, $params, $types);

// Get filter options
$branches = $branch_id ? [] : fetchAll("SELECT id, name FROM branches WHERE is_active = 1 ORDER BY name");

// Handle export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . strtolower(str_replace(' ', '_', $report_title)) . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF"); // BOM for UTF-8
    
    // Write headers based on report type
    switch ($report_type) {
        case 'due_maintenance':
            fputcsv($output, ['Item Code', 'Item Name', 'Branch', 'Serial Number', 'Maintenance Type', 'Due Date', 'Days Until Due', 'Technician', 'Cost Estimate']);
            break;
        case 'maintenance_history':
            fputcsv($output, ['Date', 'Item Code', 'Item Name', 'Branch', 'Serial Number', 'Maintenance Type', 'Technician', 'Cost', 'Findings']);
            break;
        case 'maintenance_costs':
            fputcsv($output, ['Branch', 'Item Code', 'Item Name', 'Maintenance Count', 'Total Cost', 'Average Cost', 'Last Maintenance']);
            break;
    }
    
    // Write data
    foreach ($report_data as $row) {
        fputcsv($output, array_values($row));
    }
    
    fclose($output);
    exit();
}

include '../../includes/header.php';
?>

<div class="page-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-tools me-2"></i>Maintenance Reports</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Maintenance Reports</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<!-- Report Filters -->
<div class="card mb-4 fade-in">
    <div class="card-header">
        <h5 class="mb-0">Report Filters</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="report_type" class="form-label">Report Type</label>
                <select class="form-select" id="report_type" name="report_type" onchange="this.form.submit()">
                    <option value="due_maintenance" <?php echo $report_type === 'due_maintenance' ? 'selected' : ''; ?>>Due Maintenance</option>
                    <option value="maintenance_history" <?php echo $report_type === 'maintenance_history' ? 'selected' : ''; ?>>Maintenance History</option>
                    <option value="maintenance_costs" <?php echo $report_type === 'maintenance_costs' ? 'selected' : ''; ?>>Maintenance Costs</option>
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
            
            <?php if ($report_type === 'maintenance_history'): ?>
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
            <?php endif; ?>
            
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search"></i> Generate
                    </button>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" 
                       class="btn btn-outline-success">
                        <i class="bi bi-download"></i> CSV
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Report Results -->
<div class="card fade-in">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?php echo $report_title; ?></h5>
        <span class="badge bg-primary"><?php echo count($report_data); ?> records</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="reportTable">
                <thead>
                    <tr>
                        <?php if ($report_type === 'due_maintenance'): ?>
                            <th>Item</th>
                            <th>Branch</th>
                            <th>Maintenance Type</th>
                            <th>Due Date</th>
                            <th>Days Until Due</th>
                            <th>Technician</th>
                            <th>Cost Estimate</th>
                            <th>Status</th>
                        <?php elseif ($report_type === 'maintenance_history'): ?>
                            <th>Date</th>
                            <th>Item</th>
                            <th>Branch</th>
                            <th>Maintenance Type</th>
                            <th>Technician</th>
                            <th>Cost</th>
                            <th>Findings</th>
                            <th>Created By</th>
                        <?php elseif ($report_type === 'maintenance_costs'): ?>
                            <th>Branch</th>
                            <th>Item</th>
                            <th>Maintenance Count</th>
                            <th>Total Cost</th>
                            <th>Average Cost</th>
                            <th>Last Maintenance</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($report_data)): ?>
                        <?php foreach ($report_data as $row): ?>
                            <tr>
                                <?php if ($report_type === 'due_maintenance'): ?>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($row['item_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($row['item_code']); ?></small>
                                            <?php if ($row['serial_number']): ?>
                                                <br><small class="text-muted">S/N: <?php echo htmlspecialchars($row['serial_number']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['branch_name']); ?></td>
                                    <td><span class="badge bg-info"><?php echo ucfirst($row['maintenance_type']); ?></span></td>
                                    <td><?php echo formatDate($row['next_maintenance_date']); ?></td>
                                    <td>
                                        <?php if ($row['days_until_due'] < 0): ?>
                                            <span class="badge bg-danger"><?php echo abs($row['days_until_due']); ?> days overdue</span>
                                        <?php elseif ($row['days_until_due'] == 0): ?>
                                            <span class="badge bg-warning">Due today</span>
                                        <?php else: ?>
                                            <span class="badge bg-success"><?php echo $row['days_until_due']; ?> days</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['assigned_technician'] ?: '-'); ?></td>
                                    <td><?php echo formatCurrency($row['cost_estimate']); ?></td>
                                    <td>
                                        <?php if ($row['days_until_due'] < 0): ?>
                                            <span class="badge bg-danger">Overdue</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Scheduled</span>
                                        <?php endif; ?>
                                    </td>
                                <?php elseif ($report_type === 'maintenance_history'): ?>
                                    <td><?php echo formatDate($row['maintenance_date']); ?></td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($row['item_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($row['item_code']); ?></small>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['branch_name']); ?></td>
                                    <td><span class="badge bg-info"><?php echo ucfirst($row['maintenance_type']); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['technician_name']); ?></td>
                                    <td><?php echo formatCurrency($row['cost']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($row['findings'] ?: '-', 0, 50)); ?><?php echo strlen($row['findings'] ?: '') > 50 ? '...' : ''; ?></td>
                                    <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <?php elseif ($report_type === 'maintenance_costs'): ?>
                                    <td><?php echo htmlspecialchars($row['branch_name']); ?></td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($row['item_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($row['item_code']); ?></small>
                                        </div>
                                    </td>
                                    <td><?php echo number_format($row['maintenance_count']); ?></td>
                                    <td><?php echo formatCurrency($row['total_cost']); ?></td>
                                    <td><?php echo formatCurrency($row['average_cost']); ?></td>
                                    <td><?php echo formatDate($row['last_maintenance']); ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">
                                No data found for the selected criteria
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$additional_js = '
<script>
$(document).ready(function() {
    initDataTable("#reportTable", {
        order: [[0, "asc"]],
        pageLength: 50
    });
});
</script>
';

include '../../includes/footer.php';
?>