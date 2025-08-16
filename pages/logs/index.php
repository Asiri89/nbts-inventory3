<?php
/**
 * Activity Logs Page for NBTS Inventory Management System
 * 
 * This page displays system activity logs (Admin only)
 */

require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require admin authentication
requireAuth(['admin', 'auditor']);

$page_title = 'Activity Logs';

// Get filter parameters
$user_filter = $_GET['user_id'] ?? '';
$action_filter = $_GET['action'] ?? '';
$table_filter = $_GET['table_name'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$query = "
    SELECT al.*, u.first_name, u.last_name, u.username, b.name as branch_name
    FROM activity_logs al
    JOIN users u ON al.user_id = u.id
    LEFT JOIN branches b ON u.branch_id = b.id
    WHERE 1=1
";

$params = [];
$types = '';

// Apply filters
if ($user_filter) {
    $query .= " AND al.user_id = ?";
    $params[] = $user_filter;
    $types .= 'i';
}

if ($action_filter) {
    $query .= " AND al.action LIKE ?";
    $params[] = "%$action_filter%";
    $types .= 's';
}

if ($table_filter) {
    $query .= " AND al.table_name = ?";
    $params[] = $table_filter;
    $types .= 's';
}

if ($date_from) {
    $query .= " AND DATE(al.created_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if ($date_to) {
    $query .= " AND DATE(al.created_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

$query .= " ORDER BY al.created_at DESC LIMIT 1000";

// Execute query
$logs = fetchAll($query, $params, $types);

// Get filter options
$users = fetchAll("SELECT id, first_name, last_name, username FROM users ORDER BY first_name, last_name");
$actions = fetchAll("SELECT DISTINCT action FROM activity_logs ORDER BY action");
$tables = fetchAll("SELECT DISTINCT table_name FROM activity_logs WHERE table_name IS NOT NULL ORDER BY table_name");

include '../../includes/header.php';
?>

<div class="page-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-journal-text me-2"></i>Activity Logs</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Activity Logs</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4 fade-in">
    <div class="card-header">
        <h5 class="mb-0">Filter Logs</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="user_id" class="form-label">User</label>
                <select class="form-select" id="user_id" name="user_id">
                    <option value="">All Users</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" 
                                <?php echo $user_filter == $user['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['username'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="action" class="form-label">Action</label>
                <select class="form-select" id="action" name="action">
                    <option value="">All Actions</option>
                    <?php foreach ($actions as $action): ?>
                        <option value="<?php echo htmlspecialchars($action['action']); ?>" 
                                <?php echo $action_filter === $action['action'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($action['action']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="table_name" class="form-label">Table</label>
                <select class="form-select" id="table_name" name="table_name">
                    <option value="">All Tables</option>
                    <?php foreach ($tables as $table): ?>
                        <option value="<?php echo htmlspecialchars($table['table_name']); ?>" 
                                <?php echo $table_filter === $table['table_name'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($table['table_name']); ?>
                        </option>
                    <?php endforeach; ?>
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
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Activity Logs Table -->
<div class="card fade-in">
    <div class="card-header">
        <h5 class="mb-0">Activity Logs (<?php echo count($logs); ?> records)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="logsTable">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Table</th>
                        <th>Record ID</th>
                        <th>IP Address</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>
                                <small><?php echo formatDateTime($log['created_at'], 'M j, Y H:i:s'); ?></small>
                            </td>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($log['username']); ?></small>
                                    <?php if ($log['branch_name']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($log['branch_name']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?php 
                                    if (strpos($log['action'], 'Create') !== false) echo 'bg-success';
                                    elseif (strpos($log['action'], 'Update') !== false) echo 'bg-primary';
                                    elseif (strpos($log['action'], 'Delete') !== false) echo 'bg-danger';
                                    elseif (strpos($log['action'], 'Login') !== false) echo 'bg-info';
                                    else echo 'bg-secondary';
                                ?>">
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($log['table_name'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($log['record_id'] ?: '-'); ?></td>
                            <td>
                                <small><?php echo htmlspecialchars($log['ip_address'] ?: '-'); ?></small>
                            </td>
                            <td>
                                <?php if ($log['old_values'] || $log['new_values']): ?>
                                    <button class="btn btn-sm btn-outline-info" 
                                            onclick="showLogDetails(<?php echo htmlspecialchars(json_encode([
                                                'action' => $log['action'],
                                                'old_values' => $log['old_values'],
                                                'new_values' => $log['new_values'],
                                                'user_agent' => $log['user_agent']
                                            ])); ?>)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Log Details Modal -->
<div class="modal fade" id="logDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Activity Log Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="logDetailsContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php
$additional_js = '
<script>
$(document).ready(function() {
    initDataTable("#logsTable", {
        order: [[0, "desc"]],
        columnDefs: [
            { orderable: false, targets: [6] }
        ],
        pageLength: 50
    });
});

function showLogDetails(logData) {
    let content = "<h6>Action: " + logData.action + "</h6>";
    
    if (logData.old_values) {
        content += "<h6 class=\"mt-3\">Previous Values:</h6>";
        content += "<pre class=\"bg-light p-2 rounded\">" + JSON.stringify(JSON.parse(logData.old_values), null, 2) + "</pre>";
    }
    
    if (logData.new_values) {
        content += "<h6 class=\"mt-3\">New Values:</h6>";
        content += "<pre class=\"bg-light p-2 rounded\">" + JSON.stringify(JSON.parse(logData.new_values), null, 2) + "</pre>";
    }
    
    if (logData.user_agent) {
        content += "<h6 class=\"mt-3\">User Agent:</h6>";
        content += "<small class=\"text-muted\">" + logData.user_agent + "</small>";
    }
    
    document.getElementById("logDetailsContent").innerHTML = content;
    
    const modal = new bootstrap.Modal(document.getElementById("logDetailsModal"));
    modal.show();
}
</script>
';

include '../../includes/footer.php';
?>