<?php
/**
 * Allocate Items Page for NBTS Inventory Management System
 * 
 * **NEW PAGE** - Handles allocation of bulk procured items to branches
 */

require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require admin authentication (NBC only)
requireAuth(['admin']);

$page_title = 'Allocate Items';

// Get allocation ID
$allocation_id = (int)($_GET['allocation_id'] ?? 0);

if (!$allocation_id) {
    header('Location: add.php?error=Invalid allocation ID');
    exit();
}

// Get allocation details
$allocation = fetchSingle(
    "SELECT ba.*, i.name as item_name, i.code as item_code, s.name as supplier_name
     FROM bulk_allocations ba
     JOIN items i ON ba.item_id = i.id
     LEFT JOIN suppliers s ON ba.supplier_id = s.id
     WHERE ba.id = ?",
    [$allocation_id],
    'i'
);

if (!$allocation) {
    header('Location: add.php?error=Allocation not found');
    exit();
}

// Get allocation items
$allocation_items = fetchAll(
    "SELECT ai.*, b.name as branch_name
     FROM allocation_items ai
     LEFT JOIN branches b ON ai.branch_id = b.id
     WHERE ai.allocation_id = ?
     ORDER BY ai.serial_number",
    [$allocation_id],
    'i'
);

// Get branches for allocation
$branches = fetchAll("SELECT * FROM branches WHERE is_active = 1 AND code != 'NBC' ORDER BY name");

$success_message = '';
$error_message = '';

// Handle allocation submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['allocate'])) {
    $allocations = [];
    
    foreach ($_POST['allocations'] as $item_id => $branch_id) {
        if (!empty($branch_id)) {
            if (!isset($allocations[$branch_id])) {
                $allocations[$branch_id] = [];
            }
            
            // Get serial number for this item
            $item = fetchSingle(
                "SELECT serial_number FROM allocation_items WHERE id = ?",
                [$item_id],
                'i'
            );
            
            if ($item) {
                $allocations[$branch_id][] = $item['serial_number'];
            }
        }
    }
    
    if (empty($allocations)) {
        $error_message = 'Please allocate at least one item to a branch.';
    } else {
        try {
            $result = CentralProcurement::allocateItemsToBranches($allocation_id, $allocations);
            
            if ($result['success']) {
                // Update allocation status
                executeQuery(
                    "UPDATE bulk_allocations SET status = 'allocated' WHERE id = ?",
                    [$allocation_id],
                    'i'
                );
                
                // Log activity
                logActivity('Allocate Items', 'bulk_allocations', $allocation_id, null, $allocations);
                
                $success_message = 'Items allocated successfully. Branches will be notified.';
                
                // Create notifications for branches
                foreach ($allocations as $branch_id => $serials) {
                    $branch = fetchSingle("SELECT name FROM branches WHERE id = ?", [$branch_id], 'i');
                    createNotification(
                        'system',
                        'New Item Allocation',
                        count($serials) . " units of {$allocation['item_name']} allocated to your branch. Please review and accept.",
                        null,
                        $branch_id,
                        'bulk_allocations',
                        $allocation_id
                    );
                }
                
                // Redirect to pending allocations
                header('Location: pending_allocations.php?success=' . urlencode($success_message));
                exit();
            } else {
                $error_message = $result['message'];
            }
        } catch (Exception $e) {
            $error_message = 'Error allocating items: ' . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
?>

<div class="page-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-distribute-horizontal me-2"></i>Allocate Items to Branches</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Inventory</a></li>
                    <li class="breadcrumb-item active">Allocate Items</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<!-- Allocation Information -->
<div class="card fade-in mb-4">
    <div class="card-header">
        <h5 class="mb-0">Allocation Details</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-borderless">
                    <tr>
                        <td><strong>Item:</strong></td>
                        <td><?php echo htmlspecialchars($allocation['item_name']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Code:</strong></td>
                        <td><?php echo htmlspecialchars($allocation['item_code']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Supplier:</strong></td>
                        <td><?php echo htmlspecialchars($allocation['supplier_name'] ?: '-'); ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-borderless">
                    <tr>
                        <td><strong>Total Quantity:</strong></td>
                        <td><?php echo number_format($allocation['total_quantity']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Unit Cost:</strong></td>
                        <td><?php echo formatCurrency($allocation['unit_cost']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total Cost:</strong></td>
                        <td><?php echo formatCurrency($allocation['total_cost']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Allocation Form -->
<div class="card fade-in">
    <div class="card-header">
        <h5 class="mb-0">Allocate Items to Branches</h5>
    </div>
    <div class="card-body">
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="allocationForm">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Serial Number</th>
                            <th>Allocate to Branch</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allocation_items as $item): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['serial_number']); ?></strong>
                                </td>
                                <td>
                                    <?php if ($item['status'] === 'available'): ?>
                                        <select class="form-select" name="allocations[<?php echo $item['id']; ?>]">
                                            <option value="">Keep at NBC</option>
                                            <?php foreach ($branches as $branch): ?>
                                                <option value="<?php echo $branch['id']; ?>">
                                                    <?php echo htmlspecialchars($branch['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <span class="text-muted">
                                            <?php echo htmlspecialchars($item['branch_name'] ?: 'NBC'); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $status_badges = [
                                        'available' => 'bg-secondary',
                                        'allocated' => 'bg-warning',
                                        'accepted' => 'bg-success',
                                        'rejected' => 'bg-danger'
                                    ];
                                    ?>
                                    <span class="badge <?php echo $status_badges[$item['status']] ?? 'bg-secondary'; ?>">
                                        <?php echo ucfirst($item['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                <a href="add.php" class="btn btn-outline-secondary me-md-2">
                    <i class="bi bi-arrow-left me-2"></i>Back to Procurement
                </a>
                <button type="submit" name="allocate" class="btn btn-primary">
                    <i class="bi bi-distribute-horizontal me-2"></i>Allocate Items
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$additional_js = '
<script>
// Form validation
document.getElementById("allocationForm").addEventListener("submit", function(e) {
    const allocations = document.querySelectorAll("select[name^=\"allocations\"]");
    let hasAllocations = false;
    
    allocations.forEach(select => {
        if (select.value) {
            hasAllocations = true;
        }
    });
    
    if (!hasAllocations) {
        e.preventDefault();
        showToast("Error", "Please allocate at least one item to a branch", "error");
        return false;
    }
    
    if (!confirm("Are you sure you want to allocate these items? This will notify the selected branches.")) {
        e.preventDefault();
        return false;
    }
});
</script>
';

include '../../includes/footer.php';
?>