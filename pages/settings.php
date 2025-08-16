<?php
/**
 * System Settings Page for NBTS Inventory Management System
 * 
 * This page allows administrators to configure system settings
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// Require admin authentication
requireAuth(['admin']);

$page_title = 'System Settings';

$success_message = '';
$error_message = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // This is a placeholder for system settings
    // In a real implementation, you would store settings in a database table
    $success_message = 'Settings updated successfully.';
}

include '../includes/header.php';
?>

<div class="page-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-gear me-2"></i>System Settings</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Settings</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- General Settings -->
        <div class="card fade-in mb-4">
            <div class="card-header">
                <h5 class="mb-0">General Settings</h5>
            </div>
            <div class="card-body">
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i><?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="app_name" class="form-label">Application Name</label>
                            <input type="text" class="form-control" id="app_name" name="app_name" 
                                   value="<?php echo APP_NAME; ?>" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="app_version" class="form-label">Version</label>
                            <input type="text" class="form-control" id="app_version" name="app_version" 
                                   value="<?php echo APP_VERSION; ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="session_timeout" class="form-label">Session Timeout (minutes)</label>
                            <input type="number" class="form-control" id="session_timeout" name="session_timeout" 
                                   value="<?php echo SESSION_TIMEOUT / 60; ?>" min="5" max="480">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="records_per_page" class="form-label">Records Per Page</label>
                            <input type="number" class="form-control" id="records_per_page" name="records_per_page" 
                                   value="<?php echo RECORDS_PER_PAGE; ?>" min="10" max="100">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="timezone" class="form-label">Timezone</label>
                        <select class="form-select" id="timezone" name="timezone">
                            <option value="Asia/Colombo" selected>Asia/Colombo (Sri Lanka)</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <!-- Email Settings -->
        <div class="card fade-in mb-4">
            <div class="card-header">
                <h5 class="mb-0">Email Settings</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="smtp_host" class="form-label">SMTP Host</label>
                            <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                                   value="<?php echo SMTP_HOST; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="smtp_port" class="form-label">SMTP Port</label>
                            <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                                   value="<?php echo SMTP_PORT; ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="from_email" class="form-label">From Email</label>
                            <input type="email" class="form-control" id="from_email" name="from_email" 
                                   value="<?php echo FROM_EMAIL; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="from_name" class="form-label">From Name</label>
                            <input type="text" class="form-control" id="from_name" name="from_name" 
                                   value="<?php echo FROM_NAME; ?>">
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Security Settings -->
        <div class="card fade-in">
            <div class="card-header">
                <h5 class="mb-0">Security Settings</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="max_login_attempts" class="form-label">Max Login Attempts</label>
                            <input type="number" class="form-control" id="max_login_attempts" name="max_login_attempts" 
                                   value="<?php echo MAX_LOGIN_ATTEMPTS; ?>" min="3" max="10">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="lockout_time" class="form-label">Lockout Time (minutes)</label>
                            <input type="number" class="form-control" id="lockout_time" name="lockout_time" 
                                   value="<?php echo LOCKOUT_TIME / 60; ?>" min="5" max="60">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="max_file_size" class="form-label">Max File Size (MB)</label>
                            <input type="number" class="form-control" id="max_file_size" name="max_file_size" 
                                   value="<?php echo MAX_FILE_SIZE / (1024 * 1024); ?>" min="1" max="50">
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- System Information -->
    <div class="col-lg-4">
        <div class="card fade-in">
            <div class="card-header">
                <h6 class="mb-0">System Information</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>PHP Version:</strong></td>
                        <td><?php echo phpversion(); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Server Software:</strong></td>
                        <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Database:</strong></td>
                        <td>MySQL <?php echo getDatabaseConnection()->server_info; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Upload Max Size:</strong></td>
                        <td><?php echo ini_get('upload_max_filesize'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Memory Limit:</strong></td>
                        <td><?php echo ini_get('memory_limit'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Timezone:</strong></td>
                        <td><?php echo date_default_timezone_get(); ?></td>
                    </tr>
                </table>
                
                <hr>
                
                <div class="text-center">
                    <h6>NBTS Inventory System</h6>
                    <p class="text-muted small">
                        Version <?php echo APP_VERSION; ?><br>
                        National Blood Transfusion Service<br>
                        Sri Lanka
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>