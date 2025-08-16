<?php
/**
 * Dashboard Page for NBTS Inventory Management System
 * 
 * This page displays the main dashboard with statistics and overview
 */

require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require authentication
requireAuth();

$page_title = 'Dashboard';

// Get user info and branch restriction
$current_user = Auth::getCurrentUser();
$branch_id = ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'auditor') ? null : $_SESSION['user_branch_id'];

// Get dashboard statistics
//remove inventory value
$low_stock_count = DashboardStats::getLowStockCount($branch_id);
$warranty_expiry_count = DashboardStats::getWarrantyExpiryCount($branch_id);
$maintenance_due_count = DashboardStats::getMaintenanceDueCount($branch_id);
$pending_allocations_count = DashboardStats::getPendingAllocationsCount($branch_id); //new
$recent_activities = DashboardStats::getRecentActivities(10, $branch_id);

// Get inventory summary by category
$inventory_by_category_query = "
    SELECT c.name as category_name, c.type, 
           COUNT(inv.id) as item_count,
           SUM(inv.quantity) as total_quantity,
           SUM(inv.quantity * inv.unit_cost) as total_value
    FROM categories c
    LEFT JOIN items i ON c.id = i.category_id
    LEFT JOIN inventory inv ON i.id = inv.item_id AND inv.status = 'active'
";

if ($branch_id) {
    $inventory_by_category_query .= " AND inv.branch_id = ?";
    $inventory_by_category = fetchAll($inventory_by_category_query . " GROUP BY c.id ORDER BY total_value DESC", [$branch_id], 'i');
} else {
    $inventory_by_category = fetchAll($inventory_by_category_query . " GROUP BY c.id ORDER BY total_value DESC");
}

// Get recent stock movements
$recent_movements_query = "
    SELECT sm.*, i.name as item_name, i.code as item_code,
           fb.name as from_branch, tb.name as to_branch,
           u.first_name, u.last_name
    FROM stock_movements sm
    JOIN inventory inv ON sm.inventory_id = inv.id
    JOIN items i ON inv.item_id = i.id
    LEFT JOIN branches fb ON sm.from_branch_id = fb.id
    LEFT JOIN branches tb ON sm.to_branch_id = tb.id
    JOIN users u ON sm.created_by = u.id
";

if ($branch_id) {
    $recent_movements_query .= " WHERE (sm.from_branch_id = ? OR sm.to_branch_id = ?)";
    $recent_movements = fetchAll($recent_movements_query . " ORDER BY sm.created_at DESC LIMIT 10", [$branch_id, $branch_id], 'ii');
} else {
    $recent_movements = fetchAll($recent_movements_query . " ORDER BY sm.created_at DESC LIMIT 10");
}

// Run notification checks (for admin and inventory managers)
if (hasRole(['admin', 'inventory_manager'])) {
    NotificationManager::checkLowStock();
    NotificationManager::checkWarrantyExpiry();
    NotificationManager::checkMaintenanceDue();
}

include '../includes/header.php';
?>

<div class="page-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </nav>
        </div>
        <div>
            <?php if ($branch_id && $current_user['branch_name']): ?>
                <span class="badge bg-primary fs-6">
                    <i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($current_user['branch_name']); ?>
                </span>
            <?php else: ?>
                <span class="badge bg-secondary fs-6">
                    <i class="bi bi-building me-1"></i>All Branches
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stats-card warning fade-in">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <div class="stats-number text-warning"><?php echo $low_stock_count; ?></div>
                    <p class="stats-label">Low Stock Items</p>
                </div>
                <div class="ms-3">
                    <i class="bi bi-exclamation-triangle" style="font-size: 2.5rem; color: var(--nbts-warning); opacity: 0.3;"></i>
                </div>
            </div>
            <?php if ($low_stock_count > 0): ?>
                <div class="mt-2">
                    <a href="<?php echo BASE_URL; ?>/pages/reports/inventory.php?filter=low_stock" class="text-warning text-decoration-none">
                        View Details <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stats-card info fade-in">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <div class="stats-number text-info"><?php echo $warranty_expiry_count; ?></div>
                    <p class="stats-label">Warranty Expiring (30 days)</p>
                </div>
                <div class="ms-3">
                    <i class="bi bi-calendar-x" style="font-size: 2.5rem; color: var(--nbts-info); opacity: 0.3;"></i>
                </div>
            </div>
            <?php if ($warranty_expiry_count > 0): ?>
                <div class="mt-2">
                    <a href="<?php echo BASE_URL; ?>/pages/reports/inventory.php?filter=warranty_expiry" class="text-info text-decoration-none">
                        View Details <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stats-card success fade-in">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <div class="stats-number text-success"><?php echo $maintenance_due_count; ?></div>
                    <p class="stats-label">Maintenance Due (7 days)</p>
                </div>
                <div class="ms-3">
                    <i class="bi bi-tools" style="font-size: 2.5rem; color: var(--nbts-success); opacity: 0.3;"></i>
                </div>
            </div>
            <?php if ($maintenance_due_count > 0): ?>
                <div class="mt-2">
                    <a href="<?php echo BASE_URL; ?>/pages/maintenance/schedule.php?filter=due" class="text-success text-decoration-none">
                        View Schedule <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
	   
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stats-card primary fade-in">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <div class="stats-number text-primary"><?php echo $pending_allocations_count; ?></div>
                    <p class="stats-label">Pending Allocations</p>
                </div>
                <div class="ms-3">
                    <i class="bi bi-inbox" style="font-size: 2.5rem; color: var(--nbts-primary); opacity: 0.3;"></i>
                </div>
            </div>
            <?php if ($pending_allocations_count > 0): ?>
                <div class="mt-2">
                    <a href="<?php echo BASE_URL; ?>/pages/inventory/pending_allocations.php" class="text-primary text-decoration-none">
                        View Allocations <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Charts and Tables Row -->
<div class="row">
    <!-- Inventory by Category Chart -->
    <div class="col-lg-6 mb-4">
        <div class="card fade-in">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Inventory by Category</h5>
            </div>
            <div class="card-body">
                <canvas id="categoryChart" width="400" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Recent Activities -->
    <div class="col-lg-6 mb-4">
        <div class="card fade-in">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Activities</h5>
                <a href="<?php echo BASE_URL; ?>/pages/logs/index.php" class="text-white text-decoration-none">
                    View All <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if (!empty($recent_activities)): ?>
                        <?php foreach ($recent_activities as $activity): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($activity['action']); ?></h6>
                                    <small><?php echo formatDateTime($activity['created_at'], 'M j, g:i A'); ?></small>
                                </div>
                                <p class="mb-1 text-muted">
                                    by <?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?>
                                    <?php if ($activity['table_name']): ?>
                                        on <?php echo htmlspecialchars($activity['table_name']); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="list-group-item text-center text-muted">
                            No recent activities found
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Stock Movements -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card fade-in">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-arrow-repeat me-2"></i>Recent Stock Movements</h5>
                <a href="<?php echo BASE_URL; ?>/pages/reports/movements.php" class="text-white text-decoration-none">
                    View All <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Item</th>
                                <th>Movement Type</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Quantity</th>
                                <th>Value</th>
                                <th>By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_movements)): ?>
                                <?php foreach ($recent_movements as $movement): ?>
                                    <tr>
                                        <td><?php echo formatDateTime($movement['created_at'], 'M j, Y'); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($movement['item_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($movement['item_code']); ?></small>
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
                                        <td><?php echo htmlspecialchars($movement['first_name'] . ' ' . $movement['last_name']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">No recent movements found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$additional_js = '
<script>
// Initialize category chart
const categoryData = ' . json_encode($inventory_by_category) . ';
const ctx = document.getElementById("categoryChart").getContext("2d");

new Chart(ctx, {
    type: "doughnut",
    data: {
        labels: categoryData.map(item => item.category_name),
        datasets: [{
            data: categoryData.map(item => parseFloat(item.total_value) || 0),
            backgroundColor: [
                "#dc3545",
                "#0d6efd", 
                "#198754",
                "#ffc107",
                "#6f42c1",
                "#fd7e14",
                "#20c997",
                "#e83e8c"
            ],
            borderWidth: 2,
            borderColor: "#fff"
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: "bottom",
                labels: {
                    padding: 20,
                    usePointStyle: true
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || "";
                        const value = context.raw;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return label + ": " + formatCurrency(value) + " (" + percentage + "%)";
                    }
                }
            }
        }
    }
});

// Auto-refresh dashboard data every 5 minutes
setInterval(function() {
    location.reload();
}, 300000);
</script>
';

include '../includes/footer.php';
?>