<?php
/**
 * Inventory Reports Page for NBTS Inventory Management System
 * 
 * This page generates various inventory reports
 */

require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication
requireAuth();

$page_title = 'Inventory Reports';

// Get user info and branch restriction
$current_user = Auth::getCurrentUser();
$branch_id = ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'auditor') ? null : $_SESSION['user_branch_id'];

// Get filter parameters
$report_type = $_GET['report_type'] ?? 'stock_summary';
$branch_filter = $_GET['branch_id'] ?? '';
$category_filter = $_GET['category_id'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Apply branch restriction
if ($branch_id && !$branch_filter) {
    $branch_filter = $branch_id;
}

// Get dropdown data
$branches = $branch_id ? [] : fetchAll("SELECT id, name FROM branches WHERE is_active = 1 ORDER BY name");
$categories = fetchAll("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");

// Generate report data based on type
$report_data = [];
$report_title = '';

switch ($report_type) {
    case 'stock_summary':
        $report_title = 'Stock Summary Report';
        $query = "
            SELECT i.code, i.name as item_name, c.name as category_name, b.name as branch_name,
                   SUM(CASE WHEN inv.status = 'active' THEN inv.quantity ELSE 0 END) as active_stock,
                   SUM(CASE WHEN inv.status = 'maintenance' THEN inv.quantity ELSE 0 END) as maintenance_stock,
                   SUM(CASE WHEN inv.status = 'repair' THEN inv.quantity ELSE 0 END) as repair_stock,
                   SUM(inv.quantity * inv.unit_cost) as total_value,
                   i.reorder_level
            FROM items i
            LEFT JOIN inventory inv ON i.id = inv.item_id
            LEFT JOIN categories c ON i.category_id = c.id
            LEFT JOIN branches b ON inv.branch_id = b.id
            WHERE i.is_active = 1
        ";
        break;
        
    case 'low_stock':
        $report_title = 'Low Stock Report';
        $query = "
            SELECT i.code, i.name as item_name, c.name as category_name, b.name as branch_name,
                   SUM(CASE WHEN inv.status = 'active' THEN inv.quantity ELSE 0 END) as current_stock,
                   i.reorder_level,
                   SUM(inv.quantity * inv.unit_cost) as total_value
            FROM items i
            JOIN inventory inv ON i.id = inv.item_id
            JOIN categories c ON i.category_id = c.id
            JOIN branches b ON inv.branch_id = b.id
            WHERE i.is_active = 1 AND inv.status = 'active'
        ";
        break;
        
    case 'warranty_expiry':
        $report_title = 'Warranty Expiry Report';
        $query = "
            SELECT i.code, i.name as item_name, inv.serial_number, b.name as branch_name,
                   inv.warranty_end_date, inv.total_cost,
                   DATEDIFF(inv.warranty_end_date, CURDATE()) as days_to_expiry
            FROM inventory inv
            JOIN items i ON inv.item_id = i.id
            JOIN branches b ON inv.branch_id = b.id
            WHERE inv.warranty_end_date IS NOT NULL 
            AND inv.warranty_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)
            AND inv.status = 'active'
        ";
        break;
        
    case 'value_report':
        $report_title = 'Inventory Value Report';
        $query = "
            SELECT b.name as branch_name, c.name as category_name,
                   COUNT(inv.id) as item_count,
                   SUM(CASE WHEN inv.status = 'active' THEN inv.quantity ELSE 0 END) as active_quantity,
                   SUM(CASE WHEN inv.status = 'active' THEN inv.total_cost ELSE 0 END) as active_value,
                   SUM(inv.total_cost) as total_value
            FROM inventory inv
            JOIN items i ON inv.item_id = i.id
            JOIN branches b ON inv.branch_id = b.id
            JOIN categories c ON i.category_id = c.id
            WHERE 1=1
        ";
        break;
}

// Apply filters
$params = [];
$types = '';

if ($branch_filter) {
    if ($report_type === 'value_report') {
        $query .= " AND b.id = ?";
    } else {
        $query .= " AND inv.branch_id = ?";
    }
    $params[] = $branch_filter;
    $types .= 'i';
}

if ($category_filter) {
    if ($report_type === 'value_report') {
        $query .= " AND c.id = ?";
    } else {
        $query .= " AND i.category_id = ?";
    }
    $params[] = $category_filter;
    $types .= 'i';
}

if ($status_filter && $report_type === 'stock_summary') {
    $query .= " AND inv.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

// Add GROUP BY and HAVING clauses
switch ($report_type) {
    case 'stock_summary':
        $query .= " GROUP BY i.id, b.id ORDER BY i.name, b.name";
        break;
        
    case 'low_stock':
        $query .= " GROUP BY i.id, b.id HAVING current_stock <= i.reorder_level ORDER BY current_stock ASC";
        break;
        
    case 'warranty_expiry':
        $query .= " ORDER BY inv.warranty_end_date ASC";
        break;
        
    case 'value_report':
        $query .= " GROUP BY b.id, c.id ORDER BY b.name, c.name";
        break;
}

// Execute query
$report_data = fetchAll($query, $params, $types);

// Handle export
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    if ($export_type === 'csv') {
        // Generate CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . strtolower(str_replace(' ', '_', $report_title)) . '_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fwrite($output, "\xEF\xBB\xBF");
        
        // Write headers based on report type
        switch ($report_type) {
            case 'stock_summary':
                fputcsv($output, ['Item Code', 'Item Name', 'Category', 'Branch', 'Active Stock', 'Maintenance Stock', 'Repair Stock', 'Total Value', 'Reorder Level']);
                break;
            case 'low_stock':
                fputcsv($output, ['Item Code', 'Item Name', 'Category', 'Branch', 'Current Stock', 'Reorder Level', 'Total Value']);
                break;
            case 'warranty_expiry':
                fputcsv($output, ['Item Code', 'Item Name', 'Serial Number', 'Branch', 'Warranty End Date', 'Total Cost', 'Days to Expiry']);
                break;
            case 'value_report':
                fputcsv($output, ['Branch', 'Category', 'Item Count', 'Active Quantity', 'Active Value', 'Total Value']);
                break;
        }
        
        // Write data
        foreach ($report_data as $row) {
            fputcsv($output, array_values($row));
        }
        
        fclose($output);
        exit();
    }
}

include '../../includes/header.php';
?>

<div class="page-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-bar-chart me-2"></i>Inventory Reports</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Inventory Reports</li>
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
                    <option value="stock_summary" <?php echo $report_type === 'stock_summary' ? 'selected' : ''; ?>>Stock Summary</option>
                    <option value="low_stock" <?php echo $report_type === 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                    <option value="warranty_expiry" <?php echo $report_type === 'warranty_expiry' ? 'selected' : ''; ?>>Warranty Expiry</option>
                    <option value="value_report" <?php echo $report_type === 'value_report' ? 'selected' : ''; ?>>Value Report</option>
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
                <label for="category_id" class="form-label">Category</label>
                <select class="form-select" id="category_id" name="category_id">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" 
                                <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <?php if ($report_type === 'stock_summary'): ?>
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="maintenance" <?php echo $status_filter === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                        <option value="repair" <?php echo $status_filter === 'repair' ? 'selected' : ''; ?>>Repair</option>
                    </select>
                </div>
            <?php endif; ?>
            
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search"></i> Generate
                    </button>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-success dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-download"></i> Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>">
                                <i class="bi bi-file-earmark-spreadsheet me-2"></i>Export CSV
                            </a></li>
                        </ul>
                    </div>
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
                        <?php if ($report_type === 'stock_summary'): ?>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Branch</th>
                            <th>Active Stock</th>
                            <th>Maintenance</th>
                            <th>Repair</th>
                            <th>Total Value</th>
                            <th>Reorder Level</th>
                        <?php elseif ($report_type === 'low_stock'): ?>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Branch</th>
                            <th>Current Stock</th>
                            <th>Reorder Level</th>
                            <th>Total Value</th>
                            <th>Status</th>
                        <?php elseif ($report_type === 'warranty_expiry'): ?>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Serial Number</th>
                            <th>Branch</th>
                            <th>Warranty End Date</th>
                            <th>Total Cost</th>
                            <th>Days to Expiry</th>
                            <th>Status</th>
                        <?php elseif ($report_type === 'value_report'): ?>
                            <th>Branch</th>
                            <th>Category</th>
                            <th>Item Count</th>
                            <th>Active Quantity</th>
                            <th>Active Value</th>
                            <th>Total Value</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($report_data)): ?>
                        <?php foreach ($report_data as $row): ?>
                            <tr>
                                <?php if ($report_type === 'stock_summary'): ?>
                                    <td><?php echo htmlspecialchars($row['code']); ?></td>
                                    <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['branch_name'] ?: 'All'); ?></td>
                                    <td><span class="badge bg-success"><?php echo number_format($row['active_stock']); ?></span></td>
                                    <td><span class="badge bg-warning"><?php echo number_format($row['maintenance_stock']); ?></span></td>
                                    <td><span class="badge bg-danger"><?php echo number_format($row['repair_stock']); ?></span></td>
                                    <td><?php echo formatCurrency($row['total_value']); ?></td>
                                    <td><?php echo number_format($row['reorder_level']); ?></td>
                                <?php elseif ($report_type === 'low_stock'): ?>
                                    <td><?php echo htmlspecialchars($row['code']); ?></td>
                                    <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['branch_name']); ?></td>
                                    <td><span class="badge bg-warning"><?php echo number_format($row['current_stock']); ?></span></td>
                                    <td><?php echo number_format($row['reorder_level']); ?></td>
                                    <td><?php echo formatCurrency($row['total_value']); ?></td>
                                    <td><span class="badge bg-danger">Low Stock</span></td>
                                <?php elseif ($report_type === 'warranty_expiry'): ?>
                                    <td><?php echo htmlspecialchars($row['code']); ?></td>
                                    <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['serial_number'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($row['branch_name']); ?></td>
                                    <td><?php echo formatDate($row['warranty_end_date']); ?></td>
                                    <td><?php echo formatCurrency($row['total_cost']); ?></td>
                                    <td><?php echo $row['days_to_expiry']; ?> days</td>
                                    <td>
                                        <?php if ($row['days_to_expiry'] < 0): ?>
                                            <span class="badge bg-danger">Expired</span>
                                        <?php elseif ($row['days_to_expiry'] <= 30): ?>
                                            <span class="badge bg-warning">Expiring Soon</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">Active</span>
                                        <?php endif; ?>
                                    </td>
                                <?php elseif ($report_type === 'value_report'): ?>
                                    <td><?php echo htmlspecialchars($row['branch_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                    <td><?php echo number_format($row['item_count']); ?></td>
                                    <td><?php echo number_format($row['active_quantity']); ?></td>
                                    <td><?php echo formatCurrency($row['active_value']); ?></td>
                                    <td><?php echo formatCurrency($row['total_value']); ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo ($report_type === 'stock_summary') ? '9' : (($report_type === 'warranty_expiry') ? '8' : '6'); ?>" class="text-center text-muted">
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