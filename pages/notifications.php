<?php
/**
 * Notifications Page for NBTS Inventory Management System
 * 
 * This page displays all notifications for the current user
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// Require authentication
requireAuth();

$page_title = 'Notifications';

// Get current user info
$user_id = $_SESSION['user_id'];
$branch_id = $_SESSION['user_branch_id'];

// Handle mark as read
if (isset($_POST['mark_read_id'])) {
    $notification_id = (int)$_POST['mark_read_id'];
    
    try {
        executeQuery(
            "UPDATE notifications SET is_read = 1 WHERE id = ? AND (user_id = ? OR user_id IS NULL)",
            [$notification_id, $user_id],
            'ii'
        );
        
        $success_message = 'Notification marked as read.';
    } catch (Exception $e) {
        $error_message = 'Error updating notification.';
    }
}

// Handle mark all as read
if (isset($_POST['mark_all_read'])) {
    try {
        executeQuery(
            "UPDATE notifications SET is_read = 1 WHERE (user_id = ? OR user_id IS NULL) AND (branch_id = ? OR branch_id IS NULL OR ? IS NULL)",
            [$user_id, $branch_id, $branch_id],
            'iii'
        );
        
        $success_message = 'All notifications marked as read.';
    } catch (Exception $e) {
        $error_message = 'Error updating notifications.';
    }
}

// Get notifications
$notifications = fetchAll(
    "SELECT * FROM notifications 
     WHERE (user_id = ? OR user_id IS NULL) 
     AND (branch_id = ? OR branch_id IS NULL OR ? IS NULL)
     ORDER BY created_at DESC 
     LIMIT 100",
    [$user_id, $branch_id, $branch_id],
    'iii'
);

include '../includes/header.php';
?>

<div class="page-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-bell me-2"></i>Notifications</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Notifications</li>
                </ol>
            </nav>
        </div>
        <div>
            <?php if (!empty($notifications)): ?>
                <form method="POST" class="d-inline">
                    <button type="submit" name="mark_all_read" class="btn btn-outline-primary">
                        <i class="bi bi-check-all me-2"></i>Mark All Read
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Notifications List -->
<div class="card fade-in">
    <div class="card-header">
        <h5 class="mb-0">All Notifications (<?php echo count($notifications); ?>)</h5>
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
        
        <?php if (!empty($notifications)): ?>
            <div class="list-group">
                <?php foreach ($notifications as $notification): ?>
                    <div class="list-group-item <?php echo $notification['is_read'] ? '' : 'list-group-item-warning'; ?>">
                        <div class="d-flex w-100 justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center mb-2">
                                    <?php
                                    $icon_map = [
                                        'low_stock' => 'bi-exclamation-triangle text-warning',
                                        'warranty_expiry' => 'bi-calendar-x text-info',
                                        'maintenance_due' => 'bi-tools text-success',
                                        'system' => 'bi-info-circle text-primary'
                                    ];
                                    ?>
                                    <i class="<?php echo $icon_map[$notification['type']] ?? 'bi-bell text-secondary'; ?> me-2"></i>
                                    <h6 class="mb-0 <?php echo $notification['is_read'] ? '' : 'fw-bold'; ?>">
                                        <?php echo htmlspecialchars($notification['title']); ?>
                                    </h6>
                                    <?php if (!$notification['is_read']): ?>
                                        <span class="badge bg-primary ms-2">New</span>
                                    <?php endif; ?>
                                </div>
                                
                                <p class="mb-2 text-muted">
                                    <?php echo htmlspecialchars($notification['message']); ?>
                                </p>
                                
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1"></i>
                                    <?php echo formatDateTime($notification['created_at'], 'M j, Y g:i A'); ?>
                                </small>
                            </div>
                            
                            <div class="ms-3">
                                <?php if (!$notification['is_read']): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="mark_read_id" value="<?php echo $notification['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-primary" title="Mark as read">
                                            <i class="bi bi-check"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-bell-slash" style="font-size: 4rem; color: #ccc;"></i>
                <h5 class="mt-3 text-muted">No notifications</h5>
                <p class="text-muted">You're all caught up! No new notifications at this time.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>