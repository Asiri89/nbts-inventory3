<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>NBTS Inventory Management</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <style>
        :root {
            --nbts-primary: #dc3545;
            --nbts-secondary: #0d6efd;
            --nbts-success: #198754;
            --nbts-warning: #ffc107;
            --nbts-danger: #dc3545;
            --nbts-info: #0dcaf0;
            --nbts-light: #f8f9fa;
            --nbts-dark: #212529;
        }
        
        .navbar-brand {
            font-weight: bold;
            color: var(--nbts-primary) !important;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--nbts-primary), #b02a37) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-nav .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            margin: 0 5px;
            padding: 8px 16px !important;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .navbar-nav .nav-link:hover {
            color: white !important;
            background-color: rgba(255,255,255,0.1);
            transform: translateY(-1px);
        }
        
        .navbar-nav .nav-link.active {
            background-color: rgba(255,255,255,0.2);
            color: white !important;
        }
        
        .sidebar {
            min-height: calc(100vh - 76px);
            background: linear-gradient(180deg, #fff, #f8f9fa);
            border-right: 1px solid #e9ecef;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
        }
        
        .sidebar .nav-link {
            color: var(--nbts-dark);
            font-weight: 500;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 10px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover {
            background-color: var(--nbts-primary);
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active {
            background-color: var(--nbts-primary);
            color: white;
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            padding: 20px;
            background-color: #f8f9fa;
            min-height: calc(100vh - 76px);
        }
        
        .page-header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            border-left: 4px solid var(--nbts-primary);
        }
        
        .page-header h1 {
            margin: 0;
            color: var(--nbts-dark);
            font-weight: 600;
        }
        
        .page-header .breadcrumb {
            margin: 0;
            background: none;
            padding: 0;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--nbts-primary), #b02a37);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
            border: none;
            font-weight: 600;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--nbts-primary), #b02a37);
            border: none;
            border-radius: 8px;
            font-weight: 500;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }
        
        .btn-outline-primary {
            border-color: var(--nbts-primary);
            color: var(--nbts-primary);
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-outline-primary:hover {
            background-color: var(--nbts-primary);
            border-color: var(--nbts-primary);
            transform: translateY(-1px);
        }
        
        .table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .table thead th {
            background: linear-gradient(135deg, var(--nbts-primary), #b02a37);
            color: white;
            font-weight: 600;
            border: none;
            padding: 15px;
        }
        
        .table tbody tr {
            transition: background-color 0.3s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(220, 53, 69, 0.05);
        }
        
        .badge {
            font-weight: 500;
            border-radius: 6px;
            padding: 6px 12px;
        }
        
        .alert {
            border: none;
            border-radius: 10px;
            border-left: 4px solid;
        }
        
        .alert-primary {
            border-left-color: var(--nbts-primary);
            background-color: rgba(220, 53, 69, 0.1);
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--nbts-primary), #b02a37);
            color: white;
            border-radius: 10px 10px 0 0;
        }
        
        .modal-content {
            border-radius: 10px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .form-control:focus,
        .form-select:focus {
            border-color: var(--nbts-primary);
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border-left: 5px solid;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .stats-card.primary {
            border-left-color: var(--nbts-primary);
        }
        
        .stats-card.success {
            border-left-color: var(--nbts-success);
        }
        
        .stats-card.warning {
            border-left-color: var(--nbts-warning);
        }
        
        .stats-card.info {
            border-left-color: var(--nbts-info);
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stats-label {
            color: #6c757d;
            font-weight: 500;
            margin-bottom: 0;
        }
        
        .toast {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .navbar-toggler {
            border: none;
            color: white;
        }
        
        .navbar-toggler:focus {
            box-shadow: none;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
                margin-top: 20px;
            }
            
            .main-content {
                padding: 15px;
            }
            
            .stats-card {
                margin-bottom: 20px;
            }
            
            .table-responsive {
                border-radius: 10px;
            }
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
    
    <?php if (isset($additional_css)): ?>
        <?php echo $additional_css; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
        <div id="toast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="bi bi-info-circle-fill text-primary me-2"></i>
                <strong class="me-auto" id="toast-title">Notification</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body" id="toast-body">
                Message content
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>/pages/dashboard.php">
                <i class="bi bi-heart-pulse-fill me-2"></i>
                NBTS Inventory
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="bi bi-list"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-bell"></i>
                                <span class="badge bg-danger" id="notification-count"></span>
                            </a>
                            <ul class="dropdown-menu" id="notifications-dropdown">
                                <li><a class="dropdown-item" href="#">Loading...</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i>
                                <?php echo $_SESSION['user_name']; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/includes/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <?php if (isLoggedIn()): ?>
                <!-- Sidebar -->
                <nav class="col-md-3 col-lg-2 sidebar">
                    <div class="position-sticky pt-3">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                                   href="<?php echo BASE_URL; ?>/pages/dashboard.php">
                                    <i class="bi bi-speedometer2"></i> Dashboard
                                </a>
                            </li>
                            
                            <?php if (hasRole(['admin', 'inventory_manager'])): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="#inventorySubmenu" data-bs-toggle="collapse">
                                        <i class="bi bi-box-seam"></i> Inventory <i class="bi bi-chevron-down ms-auto"></i>
                                    </a>
                                    <div class="collapse" id="inventorySubmenu">
                                        <ul class="nav flex-column ms-3">
                                            <li class="nav-item">
                                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['REQUEST_URI'], '/inventory/') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/inventory/index.php">
                                                    <i class="bi bi-list-ul"></i> View Inventory
                                                </a>
                                            </li>
                                            <?php if (hasRole(['admin'])): ?>
                                                <li class="nav-item">
                                                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'add.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/inventory/add.php">
                                                        <i class="bi bi-plus-circle"></i> Central Procurement
                                                    </a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pending_allocations.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/inventory/pending_allocations.php">
                                                        <i class="bi bi-inbox"></i> Pending Allocations
                                                    </a>
                                                </li>
                                            <?php else: ?>
                                                <li class="nav-item">
                                                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pending_allocations.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/inventory/pending_allocations.php">
                                                        <i class="bi bi-inbox"></i> Pending Allocations
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            <li class="nav-item">
                                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'transfer.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/inventory/transfer.php">
                                                    <i class="bi bi-arrow-left-right"></i> Transfer Management
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'transfers.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/inventory/transfers.php">
                                                    <i class="bi bi-send"></i> New Transfer
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </li>
                            <?php endif; ?>
                            
                            <li class="nav-item">
                                <a class="nav-link" href="#masterDataSubmenu" data-bs-toggle="collapse">
                                    <i class="bi bi-database"></i> Master Data <i class="bi bi-chevron-down ms-auto"></i>
                                </a>
                                <div class="collapse" id="masterDataSubmenu">
                                    <ul class="nav flex-column ms-3">
                                        <li class="nav-item">
                                            <a class="nav-link" href="<?php echo BASE_URL; ?>/pages/items/index.php">
                                                <i class="bi bi-box"></i> Items
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="<?php echo BASE_URL; ?>/pages/categories/index.php">
                                                <i class="bi bi-tags"></i> Categories
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="<?php echo BASE_URL; ?>/pages/suppliers/index.php">
                                                <i class="bi bi-building"></i> Suppliers
                                            </a>
                                        </li>
                                        <?php if (hasRole(['admin'])): ?>
                                            <li class="nav-item">
                                                <a class="nav-link" href="<?php echo BASE_URL; ?>/pages/branches/index.php">
                                                    <i class="bi bi-geo-alt"></i> Branches
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </li>
                            
                            <?php if (hasRole(['admin', 'inventory_manager'])): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="#maintenanceSubmenu" data-bs-toggle="collapse">
                                        <i class="bi bi-tools"></i> Maintenance <i class="bi bi-chevron-down ms-auto"></i>
                                    </a>
                                    <div class="collapse" id="maintenanceSubmenu">
                                        <ul class="nav flex-column ms-3">
                                            <li class="nav-item">
                                                <a class="nav-link" href="<?php echo BASE_URL; ?>/pages/maintenance/schedule.php">
                                                    <i class="bi bi-calendar-check"></i> Schedules
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" href="<?php echo BASE_URL; ?>/pages/maintenance/history.php">
                                                    <i class="bi bi-clock-history"></i> History
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </li>
                            <?php endif; ?>
                            
                            <li class="nav-item">
                                <a class="nav-link" href="#reportsSubmenu" data-bs-toggle="collapse">
                                    <i class="bi bi-graph-up"></i> Reports <i class="bi bi-chevron-down ms-auto"></i>
                                </a>
                                <div class="collapse" id="reportsSubmenu">
                                    <ul class="nav flex-column ms-3">
                                        <li class="nav-item">
                                            <a class="nav-link" href="<?php echo BASE_URL; ?>/pages/reports/inventory.php">
                                                <i class="bi bi-bar-chart"></i> Inventory Reports
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="<?php echo BASE_URL; ?>/pages/reports/movements.php">
                                                <i class="bi bi-arrow-repeat"></i> Stock Movements
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="<?php echo BASE_URL; ?>/pages/reports/maintenance.php">
                                                <i class="bi bi-tools"></i> Maintenance Reports
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                            
                            <?php if (hasRole(['admin'])): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="#adminSubmenu" data-bs-toggle="collapse">
                                        <i class="bi bi-shield-lock"></i> Administration <i class="bi bi-chevron-down ms-auto"></i>
                                    </a>
                                    <div class="collapse" id="adminSubmenu">
                                        <ul class="nav flex-column ms-3">
                                            <li class="nav-item">
                                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['REQUEST_URI'], '/users/') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/users/index.php">
                                                    <i class="bi bi-people"></i> Users
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'permissions.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/users/permissions.php">
                                                    <i class="bi bi-shield-lock"></i> Permissions
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['REQUEST_URI'], '/logs/') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/logs/index.php">
                                                    <i class="bi bi-journal-text"></i> Activity Logs
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </nav>
                
                <!-- Main Content -->
                <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
            <?php else: ?>
                <!-- Full width for login page -->
                <main class="col-12">
            <?php endif; ?>
<?php
// ... existing header start
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/database.php';

// fetch unread notifications (limit 10)
$unread = [];
$unread_count = 0;
if (isLoggedIn()) {
    $stmt = $mysqli->prepare("
        SELECT id, title, message, created_at 
        FROM notifications 
        WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0 
        ORDER BY created_at DESC LIMIT 10
    ");
    $uid = $_SESSION['user']['id'];
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $unread = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $unread_count = count($unread);
}
?>
<!-- in your top navbar area -->
<li class="nav-item dropdown">
  <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
    <i class="bi bi-bell"></i>
    <?php if ($unread_count > 0): ?>
      <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
        <?= $unread_count ?>
      </span>
    <?php endif; ?>
  </a>
  <ul class="dropdown-menu dropdown-menu-end p-0" style="min-width:320px">
    <li class="p-2 border-bottom">
      <strong>Notifications</strong>
      <?php if ($unread_count > 0): ?>
        <a class="float-end small" href="<?= url('api/notifications.php', ['action'=>'mark_all_read']) ?>">Mark all read</a>
      <?php endif; ?>
    </li>
    <?php if ($unread_count === 0): ?>
      <li class="p-3 text-muted small">No new notifications</li>
    <?php else: foreach ($unread as $n): ?>
      <li>
        <a class="dropdown-item small" href="<?= url('api/notifications.php', ['action'=>'open','id'=>$n['id']]) ?>">
          <div class="fw-semibold"><?= htmlspecialchars($n['title']) ?></div>
          <div class="text-muted text-truncate"><?= htmlspecialchars($n['message']) ?></div>
          <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($n['created_at']) ?></div>
        </a>
      </li>
    <?php endforeach; endif; ?>
  </ul>
</li>
