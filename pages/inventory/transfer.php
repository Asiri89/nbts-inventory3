<?php
/**
 * Transfer Management Page for NBTS Inventory Management System
 * 
 * **NEW PAGE** - Manages transfer requests and approvals
 */

require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication
requireAuth();

$page_title = 'Transfer Management';

// Get user info and branch restriction
$current_user = Auth::getCurrentUser();
$branch_id = ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'auditor') ? null : $_SESSION['user_branch_id'];

// Handle accept transfer
if (isset($_POST['accept_transfer'])) {
    $movement_id = (int)$_POST['movement_id'];
    
    try {
        $result = TransferManager::acceptTransfer($movement_id);
        
        if ($result['success']) {
            $success_message = 'Transfer accepted successfully.';
            
            // Log activity
            logActivity('Accept Transfer', 'stock_movements', $movement_id);
        } else {
            $error_message = $result['message'];
        }
    } catch (Exception $e) {
        $error_message = 'Error accepting transfer: ' . $e->getMessage();
    }
}

// Get pending transfers
$transfers_query = "
    SELECT sm.*, i.name as item_name, i.code as item_code,
           fb.name as from_branch, tb.name as to_branch,
           u.first_name, u.last_name, inv.serial_number, inv.quantity as available_qty
    FROM stock_movements sm
    JOIN inventory inv ON sm.inventory_id = inv.id
    JOIN items i ON inv.item_id = i.id
    LEFT JOIN branches fb ON sm.from_branch_id = fb.id
    LEFT JOIN branches tb ON sm.to_branch_id = tb.id
    JOIN users u ON sm.created_by = u.id
    WHERE sm.movement_type IN ('transfer_out', 'transfer_in') 
    AND sm.transfer_status = 'pending'
";

$params = [];
$types = '';

if ($branch_id) {
    $transfers_query .= " AND (sm.from_branch_id = ? OR sm.to_branch_id = ?)";
    $params = [$branch_id, $branch_id];
    $types = 'ii';
}

$transfers_query .= " ORDER BY sm.created_at DESC";
$pending_transfers = fetchAll($transfers_query, $params, $types);

include '../../includes/header.php';
?>

<div class="page-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-arrow-left-right me-2"></i>Transfer Management</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Transfer Management</li>
                </ol>
            </nav>
        </div>
        <?php if (hasRole(['admin', 'inventory_manager'])): ?>
            <div>
                <a href="transfers.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>New Transfer
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Pending Transfers -->
<div class="card fade-in">
    <div class="card-header">
        <h5 class="mb-0">Pending Transfers (<?php echo count($pending_transfers); ?> items)</h5>
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
            <table class="table table-hover" id="transfersTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Item</th>
                        <th>From Branch</th>
                        <th>To Branch</th>
                        <th>Quantity</th>
                        <th>Type</th>
                        <th>Requested By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_transfers as $transfer): ?>
                        <tr>
                            <td><?php echo formatDateTime($transfer['created_at'], 'M j, Y H:i'); ?></td>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($transfer['item_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($transfer['item_code']); ?></small>
                                    <?php if ($transfer['serial_number']): ?>
                                        <br><small class="text-muted">S/N: <?php echo htmlspecialchars($transfer['serial_number']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($transfer['from_branch']); ?></td>
                            <td><?php echo htmlspecialchars($transfer['to_branch']); ?></td>
                            <td>
                                <span class="badge bg-primary"><?php echo number_format($transfer['quantity']); ?></span>
                            </td>
                            <td>
                                <span class="badge <?php echo $transfer['movement_type'] === 'transfer_out' ? 'bg-warning' : 'bg-info'; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $transfer['movement_type'])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($transfer['first_name'] . ' ' . $transfer['last_name']); ?></td>
                            <td>
                                <?php if ($transfer['movement_type'] === 'transfer_in' && hasBranchAccess($transfer['to_branch_id'])): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="movement_id" value="<?php echo $transfer['id']; ?>">
                                        <button type="submit" name="accept_transfer" class="btn btn-sm btn-success" 
                                                onclick="return confirm('Accept this transfer?')">
                                            <i class="bi bi-check-circle me-1"></i>Accept
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge bg-warning">Pending</span>
                                <?php endif; ?>
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
    initDataTable("#transfersTable", {
        order: [[0, "desc"]]
    });
});
</script>
';

include '../../includes/footer.php';
?>