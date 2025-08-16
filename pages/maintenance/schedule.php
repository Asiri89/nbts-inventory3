<?php
/**
 * Maintenance Schedule Page for NBTS Inventory Management System
 * 
 * This page manages maintenance schedules
 */

require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication and proper role
requireAuth(['admin', 'inventory_manager']);

$page_title = 'Maintenance Schedule';

// Get user info and branch restriction
$current_user = Auth::getCurrentUser();
$branch_id = ($_SESSION['user_role'] === 'admin') ? null : $_SESSION['user_branch_id'];

// Handle add/edit maintenance schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $inventory_id = (int)$_POST['inventory_id'];
    $maintenance_type = sanitizeInput($_POST['maintenance_type']);
    $description = sanitizeInput($_POST['description']);
    $frequency_days = (int)$_POST['frequency_days'];
    $next_maintenance_date = $_POST['next_maintenance_date'];
    $assigned_technician = sanitizeInput($_POST['assigned_technician']);
    $cost_estimate = floatval($_POST['cost_estimate']);
    $notes = sanitizeInput($_POST['notes']);
    
    if (empty($inventory_id) || empty($maintenance_type) || empty($description) || empty($next_maintenance_date)) {
        $error_message = 'Please fill in all required fields.';
    } else {
        try {
            if ($action === 'add') {
                // Insert new maintenance schedule
                executeQuery(
                    "INSERT INTO maintenance_schedules (inventory_id, maintenance_type, description, frequency_days, next_maintenance_date, assigned_technician, cost_estimate, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [$inventory_id, $maintenance_type, $description, $frequency_days, $next_maintenance_date, $assigned_technician, $cost_estimate, $notes],
                    'issiisds'
                );
                
                $schedule_id = getLastInsertId();
                
                // Log activity
                logActivity('Create Maintenance Schedule', 'maintenance_schedules', $schedule_id, null, [
                    'inventory_id' => $inventory_id,
                    'maintenance_type' => $maintenance_type,
                    'next_maintenance_date' => $next_maintenance_date
                ]);
                
                $success_message = 'Maintenance schedule created successfully.';
            } elseif ($action === 'edit') {
                $schedule_id = (int)$_POST['schedule_id'];
                
                // Get old values for logging
                $old_schedule = fetchSingle("SELECT * FROM maintenance_schedules WHERE id = ?", [$schedule_id], 'i');
                
                // Update maintenance schedule
                executeQuery(
                    "UPDATE maintenance_schedules SET maintenance_type = ?, description = ?, frequency_days = ?, next_maintenance_date = ?, assigned_technician = ?, cost_estimate = ?, notes = ?, updated_at = NOW() WHERE id = ?",
                    [$maintenance_type, $description, $frequency_days, $next_maintenance_date, $assigned_technician, $cost_estimate, $notes, $schedule_id],
                    'ssiisdsi'
                );
                
                // Log activity
                logActivity('Update Maintenance Schedule', 'maintenance_schedules', $schedule_id, $old_schedule, [
                    'maintenance_type' => $maintenance_type,
                    'next_maintenance_date' => $next_maintenance_date
                ]);
                
                $success_message = 'Maintenance schedule updated successfully.';
            }
        } catch (Exception $e) {
            $error_message = 'Error processing maintenance schedule: ' . $e->getMessage();
        }
    }
}

// Handle complete maintenance
if (isset($_POST['complete_id'])) {
    $schedule_id = (int)$_POST['complete_id'];
    $maintenance_date = $_POST['maintenance_date'];
    $technician_name = sanitizeInput($_POST['technician_name']);
    $cost = floatval($_POST['cost']);
    $findings = sanitizeInput($_POST['findings']);
    
    try {
        // Get schedule details
        $schedule = fetchSingle("SELECT * FROM maintenance_schedules WHERE id = ?", [$schedule_id], 'i');
        
        if ($schedule) {
            // Insert maintenance history
            executeQuery(
                "INSERT INTO maintenance_history (inventory_id, schedule_id, maintenance_type, description, maintenance_date, technician_name, cost, findings, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$schedule['inventory_id'], $schedule_id, $schedule['maintenance_type'], $schedule['description'], $maintenance_date, $technician_name, $cost, $findings, $_SESSION['user_id']],
                'iissisdsi'
            );
            
            // Calculate next maintenance date
            $next_date = date('Y-m-d', strtotime($maintenance_date . ' + ' . $schedule['frequency_days'] . ' days'));
            
            // Update schedule
            executeQuery(
                "UPDATE maintenance_schedules SET last_maintenance_date = ?, next_maintenance_date = ?, status = 'scheduled', updated_at = NOW() WHERE id = ?",
                [$maintenance_date, $next_date, $schedule_id],
                'ssi'
            );
            
            // Log activity
            logActivity('Complete Maintenance', 'maintenance_schedules', $schedule_id);
            
            $success_message = 'Maintenance completed successfully.';
        }
    } catch (Exception $e) {
        $error_message = 'Error completing maintenance: ' . $e->getMessage();
    }
}

// Get maintenance schedules
$schedules_query = "
    SELECT ms.*, i.name as item_name, i.code as item_code, b.name as branch_name,
           inv.serial_number, inv.location,
           DATEDIFF(ms.next_maintenance_date, CURDATE()) as days_until_due
    FROM maintenance_schedules ms
    JOIN inventory inv ON ms.inventory_id = inv.id
    JOIN items i ON inv.item_id = i.id
    JOIN branches b ON inv.branch_id = b.id
    WHERE ms.status IN ('scheduled', 'overdue')
";

$params = [];
$types = '';

if ($branch_id) {
    $schedules_query .= " AND inv.branch_id = ?";
    $params[] = $branch_id;
    $types = 'i';
}

$schedules_query .= " ORDER BY ms.next_maintenance_date ASC";
$schedules = fetchAll($schedules_query, $params, $types);

// Get inventory items for dropdown
$inventory_query = "
    SELECT inv.id, i.name as item_name, i.code as item_code, b.name as branch_name,
           inv.serial_number, inv.location
    FROM inventory inv
    JOIN items i ON inv.item_id = i.id
    JOIN branches b ON inv.branch_id = b.id
    WHERE inv.status = 'active'
";

if ($branch_id) {
    $inventory_query .= " AND inv.branch_id = ?";
    $inventory_items = fetchAll($inventory_query, [$branch_id], 'i');
} else {
    $inventory_items = fetchAll($inventory_query);
}

include '../../includes/header.php';
?>

<div class="page-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-calendar-check me-2"></i>Maintenance Schedule</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Maintenance Schedule</li>
                </ol>
            </nav>
        </div>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#scheduleModal" onclick="openAddModal()">
                <i class="bi bi-plus-circle me-2"></i>Schedule Maintenance
            </button>
        </div>
    </div>
</div>

<!-- Maintenance Schedules Table -->
<div class="card fade-in">
    <div class="card-header">
        <h5 class="mb-0">Scheduled Maintenance (<?php echo count($schedules); ?> items)</h5>
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
            <table class="table table-hover" id="schedulesTable">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Branch</th>
                        <th>Maintenance Type</th>
                        <th>Next Due Date</th>
                        <th>Days Until Due</th>
                        <th>Technician</th>
                        <th>Cost Estimate</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $schedule): ?>
                        <tr class="<?php echo $schedule['days_until_due'] < 0 ? 'table-danger' : ($schedule['days_until_due'] <= 7 ? 'table-warning' : ''); ?>">
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($schedule['item_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($schedule['item_code']); ?></small>
                                    <?php if ($schedule['serial_number']): ?>
                                        <br><small class="text-muted">S/N: <?php echo htmlspecialchars($schedule['serial_number']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($schedule['branch_name']); ?></td>
                            <td>
                                <span class="badge bg-info">
                                    <?php echo ucfirst($schedule['maintenance_type']); ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($schedule['next_maintenance_date']); ?></td>
                            <td>
                                <?php if ($schedule['days_until_due'] < 0): ?>
                                    <span class="badge bg-danger"><?php echo abs($schedule['days_until_due']); ?> days overdue</span>
                                <?php elseif ($schedule['days_until_due'] == 0): ?>
                                    <span class="badge bg-warning">Due today</span>
                                <?php elseif ($schedule['days_until_due'] <= 7): ?>
                                    <span class="badge bg-warning"><?php echo $schedule['days_until_due']; ?> days</span>
                                <?php else: ?>
                                    <span class="badge bg-success"><?php echo $schedule['days_until_due']; ?> days</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($schedule['assigned_technician'] ?: '-'); ?></td>
                            <td><?php echo formatCurrency($schedule['cost_estimate']); ?></td>
                            <td>
                                <?php if ($schedule['days_until_due'] < 0): ?>
                                    <span class="badge bg-danger">Overdue</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Scheduled</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-success" 
                                            onclick="openCompleteModal(<?php echo htmlspecialchars(json_encode($schedule)); ?>)" title="Complete">
                                        <i class="bi bi-check-circle"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="openEditModal(<?php echo htmlspecialchars(json_encode($schedule)); ?>)" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Schedule Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Schedule Maintenance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="scheduleForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="schedule_id" id="scheduleId">
                    
                    <div class="mb-3">
                        <label for="modalInventoryId" class="form-label">Inventory Item <span class="text-danger">*</span></label>
                        <select class="form-select" id="modalInventoryId" name="inventory_id" required>
                            <option value="">Select Item</option>
                            <?php foreach ($inventory_items as $item): ?>
                                <option value="<?php echo $item['id']; ?>">
                                    <?php echo htmlspecialchars($item['item_code'] . ' - ' . $item['item_name']); ?>
                                    <?php if ($item['serial_number']): ?>
                                        (S/N: <?php echo htmlspecialchars($item['serial_number']); ?>)
                                    <?php endif; ?>
                                    - <?php echo htmlspecialchars($item['branch_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="modalMaintenanceType" class="form-label">Maintenance Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="modalMaintenanceType" name="maintenance_type" required>
                                <option value="">Select Type</option>
                                <option value="preventive">Preventive</option>
                                <option value="calibration">Calibration</option>
                                <option value="repair">Repair</option>
                                <option value="inspection">Inspection</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="modalFrequencyDays" class="form-label">Frequency (Days)</label>
                            <input type="number" class="form-control" id="modalFrequencyDays" name="frequency_days" value="365" min="1">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="modalDescription" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="modalDescription" name="description" rows="3" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="modalNextMaintenanceDate" class="form-label">Next Maintenance Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="modalNextMaintenanceDate" name="next_maintenance_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="modalCostEstimate" class="form-label">Cost Estimate (Rs.)</label>
                            <input type="number" class="form-control" id="modalCostEstimate" name="cost_estimate" min="0" step="0.01">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="modalAssignedTechnician" class="form-label">Assigned Technician</label>
                        <input type="text" class="form-control" id="modalAssignedTechnician" name="assigned_technician">
                    </div>
                    
                    <div class="mb-3">
                        <label for="modalNotes" class="form-label">Notes</label>
                        <textarea class="form-control" id="modalNotes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Schedule Maintenance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Complete Maintenance Modal -->
<div class="modal fade" id="completeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Complete Maintenance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="completeForm">
                <div class="modal-body">
                    <input type="hidden" name="complete_id" id="completeId">
                    
                    <div class="mb-3">
                        <label class="form-label">Item</label>
                        <input type="text" class="form-control" id="completeItemName" readonly>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="maintenanceDate" class="form-label">Maintenance Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="maintenanceDate" name="maintenance_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="technicianName" class="form-label">Technician Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="technicianName" name="technician_name" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="maintenanceCost" class="form-label">Actual Cost (Rs.)</label>
                        <input type="number" class="form-control" id="maintenanceCost" name="cost" min="0" step="0.01">
                    </div>
                    
                    <div class="mb-3">
                        <label for="maintenanceFindings" class="form-label">Findings/Notes</label>
                        <textarea class="form-control" id="maintenanceFindings" name="findings" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Complete Maintenance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$additional_js = '
<script>
$(document).ready(function() {
    initDataTable("#schedulesTable", {
        order: [[3, "asc"]],
        columnDefs: [
            { orderable: false, targets: [8] }
        ]
    });
    
    // Set default next maintenance date to 30 days from now
    const nextMonth = new Date();
    nextMonth.setDate(nextMonth.getDate() + 30);
    document.getElementById("modalNextMaintenanceDate").value = nextMonth.toISOString().split("T")[0];
});

function openAddModal() {
    document.getElementById("modalTitle").textContent = "Schedule Maintenance";
    document.getElementById("formAction").value = "add";
    document.getElementById("submitBtn").textContent = "Schedule Maintenance";
    document.getElementById("scheduleForm").reset();
    
    // Set default date
    const nextMonth = new Date();
    nextMonth.setDate(nextMonth.getDate() + 30);
    document.getElementById("modalNextMaintenanceDate").value = nextMonth.toISOString().split("T")[0];
    document.getElementById("modalFrequencyDays").value = "365";
}

function openEditModal(schedule) {
    document.getElementById("modalTitle").textContent = "Edit Maintenance Schedule";
    document.getElementById("formAction").value = "edit";
    document.getElementById("submitBtn").textContent = "Update Schedule";
    document.getElementById("scheduleId").value = schedule.id;
    document.getElementById("modalInventoryId").value = schedule.inventory_id;
    document.getElementById("modalMaintenanceType").value = schedule.maintenance_type;
    document.getElementById("modalDescription").value = schedule.description;
    document.getElementById("modalFrequencyDays").value = schedule.frequency_days;
    document.getElementById("modalNextMaintenanceDate").value = schedule.next_maintenance_date;
    document.getElementById("modalAssignedTechnician").value = schedule.assigned_technician || "";
    document.getElementById("modalCostEstimate").value = schedule.cost_estimate;
    document.getElementById("modalNotes").value = schedule.notes || "";
}

function openCompleteModal(schedule) {
    document.getElementById("completeId").value = schedule.id;
    document.getElementById("completeItemName").value = schedule.item_name + " (" + schedule.item_code + ")";
    document.getElementById("technicianName").value = schedule.assigned_technician || "";
    document.getElementById("maintenanceCost").value = schedule.cost_estimate;
    
    // Set maintenance date to today
    document.getElementById("maintenanceDate").value = new Date().toISOString().split("T")[0];
}
</script>
';

include '../../includes/footer.php';
?>