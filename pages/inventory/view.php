<?php
/**
 * View Inventory Details Page for NBTS Inventory Management System
 * 
 * This page displays detailed information about a specific inventory item
 */

require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication
requireAuth();

$page_title = 'Inventory Details';

// Get inventory ID
$inventory_id = (int)($_GET['id'] ?? 0);

if (!$inventory_id) {
    header('Location: index.php?error=Invalid inventory ID');
    exit();
}

// Get inventory details
$inventory = fetchSingle(
    "SELECT inv.*, i.name as item_name, i.code as item_code, i.unit_of_measure,
            b.name as branch_name, s.name as supplier_name, c.name as category_name
     FROM inventory inv
     JOIN items i ON inv.item_id = i.id
     JOIN branches b ON inv.branch_id = b.id
     LEFT JOIN suppliers s ON inv.supplier_id = s.id
     LEFT JOIN categories c ON i.category_id = c.id
     WHERE inv.id = ?",
    [$inventory_id],
    'i'
);

if (!$inventory) {
    header('Location: index.php?error=Inventory record not found');
    exit();
}

// Check branch access
if (!hasBranchAccess($inventory['branch_id'])) {
    header('Location: index.php?error=Access denied');
    exit();
}

// Get stock movements for this inventory item
$movements = fetchAll(
    "SELECT sm.*, fb.name as from_branch, tb.name as to_branch, u.first_name, u.last_name
     FROM stock_movements sm
     LEFT JOIN branches fb ON sm.from_branch_id = fb.id
     LEFT JOIN branches tb ON sm.to_branch_id = tb.id
     JOIN users u ON sm.created_by = u.id
     WHERE sm.inventory_id = ?
     ORDER BY sm.created_at DESC",
    [$inventory_id],
    'i'
);

// Get maintenance history
$maintenance_history = fetchAll(
    "SELECT mh.*, u.first_name, u.last_name
     FROM maintenance_history mh
     JOIN users u ON mh.created_by = u.id
     WHERE mh.inventory_id = ?
     ORDER BY mh.maintenance_date DESC",
    [$inventory_id],
    'i'
);

// Get documents
$documents = fetchAll(
    "SELECT d.*, u.first_name, u.last_name
     FROM documents d
     JOIN users u ON d.uploaded_by = u.id
     WHERE d.inventory_id = ?
     ORDER BY d.created_at DESC",
    [$inventory_id],
    'i'
);

include '../../includes/header.php';
?>

<div class="page-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-box-seam me-2"></i><?php echo htmlspecialchars($inventory['item_name']); ?></h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Inventory</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($inventory['item_code']); ?></li>
                </ol>
            </nav>
        </div>
        <div>
            <?php if (hasRole(['admin', 'inventory_manager'])): ?>
                <a href="edit.php?id=<?php echo $inventory['id']; ?>" class="btn btn-primary me-2">
                    <i class="bi bi-pencil me-2"></i>Edit
                </a>
            <?php endif; ?>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Inventory
            </a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Main Information -->
    <div class="col-lg-8">
        <!-- Basic Information -->
        <div class="card fade-in mb-4">
            <div class="card-header">
                <h5 class="mb-0">Basic Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Item:</strong></td>
                                <td><?php echo htmlspecialchars($inventory['item_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Code:</strong></td>
                                <td><?php echo htmlspecialchars($inventory['item_code']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Category:</strong></td>
                                <td><?php echo htmlspecialchars($inventory['category_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Branch:</strong></td>
                                <td><?php echo htmlspecialchars($inventory['branch_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Serial Number:</strong></td>
                                <td><?php echo htmlspecialchars($inventory['serial_number'] ?: '-'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Batch Number:</strong></td>
                                <td><?php echo htmlspecialchars($inventory['batch_number'] ?: '-'); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Quantity:</strong></td>
                                <td><?php echo number_format($inventory['quantity']); ?> <?php echo htmlspecialchars($inventory['unit_of_measure']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Unit Cost:</strong></td>
                                <td><?php echo formatCurrency($inventory['unit_cost']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Total Cost:</strong></td>
                                <td><?php echo formatCurrency($inventory['total_cost']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <?php
                                    $status_badges = [
                                        'active' => 'bg-success',
                                        'maintenance' => 'bg-warning',
                                        'repair' => 'bg-danger',
                                        'disposed' => 'bg-dark',
                                        'lost' => 'bg-secondary',
                                        'damaged' => 'bg-danger'
                                    ];
                                    ?>
                                    <span class="badge <?php echo $status_badges[$inventory['status']] ?? 'bg-secondary'; ?>">
                                        <?php echo ucfirst($inventory['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Location:</strong></td>
                                <td><?php echo htmlspecialchars($inventory['location'] ?: '-'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Assigned Staff:</strong></td>
                                <td><?php echo htmlspecialchars($inventory['assigned_staff'] ?: '-'); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php if ($inventory['notes']): ?>
                    <div class="mt-3">
                        <strong>Notes:</strong>
                        <p class="mt-2"><?php echo nl2br(htmlspecialchars($inventory['notes'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Purchase & Warranty Information -->
        <div class="card fade-in mb-4">
            <div class="card-header">
                <h5 class="mb-0">Purchase & Warranty Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Supplier:</strong></td>
                                <td><?php echo htmlspecialchars($inventory['supplier_name'] ?: '-'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Invoice Number:</strong></td>
                                <td><?php echo htmlspecialchars($inventory['invoice_number'] ?: '-'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Purchase Date:</strong></td>
                                <td><?php echo $inventory['purchase_date'] ? formatDate($inventory['purchase_date']) : '-'; ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Warranty Start:</strong></td>
                                <td><?php echo $inventory['warranty_start_date'] ? formatDate($inventory['warranty_start_date']) : '-'; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Warranty End:</strong></td>
                                <td>
                                    <?php if ($inventory['warranty_end_date']): ?>
                                        <?php
                                        $warranty_end = new DateTime($inventory['warranty_end_date']);
                                        $today = new DateTime();
                                        $days_left = $today->diff($warranty_end)->days;
                                        $is_expired = $warranty_end < $today;
                                        ?>
                                        <?php echo formatDate($inventory['warranty_end_date']); ?>
                                        <?php if ($is_expired): ?>
                                            <br><span class="badge bg-danger">Expired</span>
                                        <?php elseif ($days_left <= 30): ?>
                                            <br><span class="badge bg-warning"><?php echo $days_left; ?> days left</span>
                                        <?php else: ?>
                                            <br><span class="badge bg-success">Active</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Installation Date:</strong></td>
                                <td><?php echo $inventory['installation_date'] ? formatDate($inventory['installation_date']) : '-'; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Movements -->
        <div class="card fade-in mb-4">
            <div class="card-header">
                <h5 class="mb-0">Stock Movements</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Quantity</th>
                                <th>Value</th>
                                <th>By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($movements)): ?>
                                <?php foreach ($movements as $movement): ?>
                                    <tr>
                                        <td><?php echo formatDateTime($movement['created_at'], 'M j, Y H:i'); ?></td>
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
                                    <td colspan="7" class="text-center text-muted">No movements found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Maintenance History -->
        <div class="card fade-in">
            <div class="card-header">
                <h5 class="mb-0">Maintenance History</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Technician</th>
                                <th>Cost</th>
                                <th>Downtime</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($maintenance_history)): ?>
                                <?php foreach ($maintenance_history as $maintenance): ?>
                                    <tr>
                                        <td><?php echo formatDate($maintenance['maintenance_date']); ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo ucfirst($maintenance['maintenance_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($maintenance['technician_name']); ?></td>
                                        <td><?php echo formatCurrency($maintenance['cost']); ?></td>
                                        <td><?php echo $maintenance['downtime_hours']; ?> hrs</td>
                                        <td>
                                            <span class="badge bg-success">Completed</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No maintenance history found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- QR Code -->
        <?php if ($inventory['qr_code']): ?>
            <div class="card fade-in mb-4">
                <div class="card-header">
                    <h6 class="mb-0">QR Code</h6>
                </div>
                <div class="card-body text-center">
                    <img src="<?php echo QR_CODES_URL . '/' . $inventory['qr_code']; ?>" 
                         alt="QR Code" class="img-fluid mb-3" style="max-width: 200px;">
                    <div>
                        <button class="btn btn-outline-primary btn-sm" 
                                onclick="printQRCode('<?php echo QR_CODES_URL . '/' . $inventory['qr_code']; ?>', '<?php echo htmlspecialchars($inventory['item_name']); ?>')">
                            <i class="bi bi-printer me-2"></i>Print QR Code
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <?php if (hasRole(['admin', 'inventory_manager'])): ?>
            <div class="card fade-in mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="transfer.php?inventory_id=<?php echo $inventory['id']; ?>" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-arrow-left-right me-2"></i>Transfer
                        </a>
                        <a href="<?php echo BASE_URL; ?>/pages/inventory/adjust.php?inventory_id=<?php echo $inventory['id']; ?>" class="btn btn-outline-warning btn-sm">
                            <i class="bi bi-plus-minus me-2"></i>Adjust Stock
                        </a>
                        <a href="<?php echo BASE_URL; ?>/pages/maintenance/schedule.php?inventory_id=<?php echo $inventory['id']; ?>" class="btn btn-outline-success btn-sm">
                            <i class="bi bi-tools me-2"></i>Schedule Maintenance
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Documents -->
        <div class="card fade-in">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Documents</h6>
                <?php if (hasRole(['admin', 'inventory_manager'])): ?>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <i class="bi bi-upload"></i>
                    </button>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!empty($documents)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($documents as $document): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($document['title']); ?></h6>
                                    <p class="mb-1 text-muted small">
                                        <?php echo ucfirst(str_replace('_', ' ', $document['document_type'])); ?>
                                    </p>
                                    <small class="text-muted">
                                        Uploaded by <?php echo htmlspecialchars($document['first_name'] . ' ' . $document['last_name']); ?>
                                        on <?php echo formatDateTime($document['created_at'], 'M j, Y'); ?>
                                    </small>
                                </div>
                                <a href="<?php echo DOCUMENTS_URL . '/' . $document['file_name']; ?>" 
                                   class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="bi bi-download"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center">No documents uploaded</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>