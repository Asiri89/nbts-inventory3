<?php
/**
 * Pending Allocations Page for NBTS Inventory Management System
 * 
 * **NEW PAGE** - Shows items allocated to branches waiting for acceptance
 */

require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication
requireAuth();

$page_title = 'Pending Allocations';

// Get user info and branch restriction
$current_user = Auth::getCurrentUser();
$branch_id = ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'auditor') 
             ? 1 // Central branch
             : $_SESSION['user_branch_id'];


// Handle accept/reject allocation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $item_ids = $_POST['item_ids'] ?? [];
    
    if (empty($item_ids)) {
        $error_message = 'Please select at least one item.';
    } else {
        try {
            $status = ($action === 'accept') ? 'accepted' : 'rejected';
            
            foreach ($item_ids as $item_id) {
                // Update allocation item status
                executeQuery(
                    "UPDATE allocation_items SET status = ?, accepted_by = ?, accepted_at = NOW() WHERE id = ? AND branch_id = ?",
                    [$status, $_SESSION['user_id'], $item_id, $branch_id],
                    'siii'
                );
                
                if ($action === 'accept') {
                    // Get allocation item details
                    $alloc_item = fetchSingle(
                        "SELECT ai.*, ba.item_id, ba.unit_cost, ba.warranty_start_date, ba.warranty_end_date, ba.purchase_date, ba.supplier_id, ba.invoice_number
                         FROM allocation_items ai
                         JOIN bulk_allocations ba ON ai.allocation_id = ba.id
                         WHERE ai.id = ?",
                        [$item_id],
                        'i'
                    );
                    
                    if ($alloc_item) {
                        // Create inventory record at branch
                        executeQuery(
                            "INSERT INTO inventory (item_id, branch_id, serial_number, purchase_date, supplier_id, invoice_number, warranty_start_date, warranty_end_date, quantity, unit_cost, total_cost, allocation_id, is_allocated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, 1)",
                            [$alloc_item['item_id'], $branch_id, $alloc_item['serial_number'], $alloc_item['purchase_date'], $alloc_item['supplier_id'], $alloc_item['invoice_number'], $alloc_item['warranty_start_date'], $alloc_item['warranty_end_date'], $alloc_item['unit_cost'], $alloc_item['unit_cost'], $alloc_item['allocation_id']],
                            'iisissiiddi'
                        );
                        
                        $inventory_id = getLastInsertId();
                        
                        // Create stock movement
                        executeQuery(
                            "INSERT INTO stock_movements (inventory_id, movement_type, from_branch_id, to_branch_id, quantity, unit_cost, total_cost, reason, created_by) VALUES (?, 'transfer_in', 1, ?, 1, ?, ?, 'Central allocation accepted', ?)",
                            [$inventory_id, $branch_id, $alloc_item['unit_cost'], $alloc_item['unit_cost'], $_SESSION['user_id']],
                            'iiddi'
                        );
                        
                        // Generate QR code
                        QRCode::generateForInventory($inventory_id);
                    }
                }
            }
            
            // Log activity
            logActivity($action === 'accept' ? 'Accept Allocation' : 'Reject Allocation', 'allocation_items', null, null, [
                'item_count' => count($item_ids),
                'action' => $action
            ]);
            
            $success_message = count($item_ids) . ' items ' . ($action === 'accept' ? 'accepted' : 'rejected') . ' successfully.';
            
        } catch (Exception $e) {
            $error_message = 'Error processing allocation: ' . $e->getMessage();
        }
    }
}

// Get pending allocations for current branch
$allocations_query = "
    SELECT ai.*, ba.item_id, ba.purchase_date, ba.unit_cost, ba.warranty_start_date, ba.warranty_end_date,
           i.name as item_name, i.code as item_code, c.name as category_name
    FROM allocation_items ai
    JOIN bulk_allocations ba ON ai.allocation_id = ba.id
    JOIN items i ON ba.item_id = i.id
    JOIN categories c ON i.category_id = c.id
    WHERE ai.status = 'allocated'
";

$params = [];
$types = '';

if ($branch_id) {
    $allocations_query .= " AND ai.branch_id = ?";
    $params[] = $branch_id;
    $types = 'i';
}

$allocations_query .= " ORDER BY ai.allocated_at DESC";
$pending_allocations = fetchAll($allocations_query, $params, $types);

include '../../includes/header.php';
?>

<div class="page-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-inbox me-2"></i>Pending Allocations</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Inventory</a></li>
                    <li class="breadcrumb-item active">Pending Allocations</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<!-- Pending Allocations -->
<div class="card fade-in">
    <div class="card-header">
        <h5 class="mb-0">Items Allocated to Your Branch (<?php echo count($pending_allocations); ?> items)</h5>
    </div>
    <div class="card-body">
        <?php
// Helper function to safely escape messages
		function safeMessage($msg) {
			return htmlspecialchars((string)($msg ?? ''));
		}

		// Get messages safely from GET or POST
		$success_message = $_GET['success'] ?? ($success_message ?? '');
		$error_message   = $_GET['error']   ?? ($error_message ?? '');
		?>

		<?php if ($success_message !== ''): ?>
			<div class="alert alert-success">
				<i class="bi bi-check-circle me-2"></i><?php echo safeMessage($success_message); ?>
			</div>
		<?php endif; ?>

		<?php if ($error_message !== ''): ?>
			<div class="alert alert-danger">
				<i class="bi bi-exclamation-triangle me-2"></i><?php echo safeMessage($error_message); ?>
			</div>
		<?php endif; ?>

        
        <?php if (!empty($pending_allocations)): ?>
            <form method="POST" id="allocationForm">
                <div class="mb-3">
                    <div class="btn-group" role="group">
                        <button type="submit" name="action" value="accept" class="btn btn-success">
                            <i class="bi bi-check-circle me-2"></i>Accept Selected
                        </button>
                        <button type="submit" name="action" value="reject" class="btn btn-danger">
                            <i class="bi bi-x-circle me-2"></i>Reject Selected
                        </button>
                    </div>
                    <button type="button" class="btn btn-outline-secondary ms-2" onclick="toggleSelectAll()">
                        <i class="bi bi-check-all me-2"></i>Select All
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover" id="allocationsTable">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="select-all" onchange="toggleSelectAll()">
                                </th>
                                <th>Item</th>
                                <th>Category</th>
                                <th>Serial Number</th>
                                <th>Unit Cost</th>
                                <th>Warranty Period</th>
                                <th>Allocated Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_allocations as $allocation): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="item_ids[]" value="<?php echo $allocation['id']; ?>" class="item-checkbox">
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($allocation['item_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($allocation['item_code']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo htmlspecialchars($allocation['category_name']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($allocation['serial_number']); ?></strong>
                                    </td>
                                    <td><?php echo formatCurrency($allocation['unit_cost']); ?></td>
                                    <td>
                                        <?php if ($allocation['warranty_start_date'] && $allocation['warranty_end_date']): ?>
                                            <?php echo formatDate($allocation['warranty_start_date']); ?> to 
                                            <?php echo formatDate($allocation['warranty_end_date']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">No warranty</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatDateTime($allocation['allocated_at'], 'M j, Y H:i'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                <h5 class="mt-3 text-muted">No Pending Allocations</h5>
                <p class="text-muted">No items are currently allocated to your branch.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$additional_js = '
<script>
function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById("select-all");
    const itemCheckboxes = document.querySelectorAll(".item-checkbox");
    
    itemCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
}

// Form validation
document.getElementById("allocationForm").addEventListener("submit", function(e) {
    const selectedItems = document.querySelectorAll(".item-checkbox:checked");
    
    if (selectedItems.length === 0) {
        e.preventDefault();
        showToast("Error", "Please select at least one item", "error");
        return false;
    }
    
    const action = e.submitter.value;
    const actionText = action === "accept" ? "accept" : "reject";
    
    if (!confirm(`Are you sure you want to ${actionText} ${selectedItems.length} selected items?`)) {
        e.preventDefault();
        return false;
    }
});

$(document).ready(function() {
    initDataTable("#allocationsTable", {
        order: [[6, "desc"]],
        columnDefs: [
            { orderable: false, targets: [0] }
        ]
    });
});
</script>
';

include '../../includes/footer.php';
?>