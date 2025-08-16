<?php
/**
 * Main Configuration File for NBTS Inventory Management System
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Define BASE_PATH as project root directory (one level up from config folder)
define('BASE_PATH', dirname(__DIR__));

// Error reporting settings (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone setting for Sri Lanka
date_default_timezone_set('Asia/Colombo');

// Application constants
define('APP_NAME', 'NBTS Inventory Management System');
define('APP_VERSION', '1.0.0');

// Detect base URL dynamically (works in subfolders and root)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];

// Hardcode your project folder here:
$projectFolder = '/nbts-inventory3';

$base_url = $protocol . '://' . $host . $projectFolder;

define('BASE_URL', $base_url);

// Directory paths
define('UPLOADS_DIR', BASE_PATH . '/uploads');
define('DOCUMENTS_DIR', UPLOADS_DIR . '/documents');
define('QR_CODES_DIR', UPLOADS_DIR . '/qr_codes');
define('REPORTS_DIR', UPLOADS_DIR . '/reports');

// URL paths
define('UPLOADS_URL', BASE_URL . '/uploads');
define('DOCUMENTS_URL', UPLOADS_URL . '/documents');
define('QR_CODES_URL', UPLOADS_URL . '/qr_codes');
define('REPORTS_URL', UPLOADS_URL . '/reports');

// Create upload directories if they don't exist
$uploadDirs = [UPLOADS_DIR, DOCUMENTS_DIR, QR_CODES_DIR, REPORTS_DIR];
foreach ($uploadDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', [
    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'txt'
]);

// Pagination settings
define('RECORDS_PER_PAGE', 25);

// Email settings (configure for your SMTP server)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('FROM_EMAIL', 'noreply@nbts.health.gov.lk');
define('FROM_NAME', 'NBTS Inventory System');

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes

// Include database configuration
require_once 'database.php';

// Utility functions
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function formatCurrency($amount) {
    $amount = is_numeric($amount) ? $amount : 0;
    return 'Rs. ' . number_format($amount, 2);
}

function formatDate($date, $format = 'Y-m-d') {
    return (empty($date) || $date === '0000-00-00') ? '' : date($format, strtotime($date));
}

function formatDateTime($datetime, $format = 'Y-m-d H:i:s') {
    return (empty($datetime) || $datetime === '0000-00-00 00:00:00') ? '' : date($format, strtotime($datetime));
}

function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )), 1, $length);
}

// Auto-logout function
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . '/pages/auth/login.php?timeout=1');
        exit();
    }
    $_SESSION['last_activity'] = time();
}

// Check if user is logged in
function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}

// Check user role
function hasRole($required_roles) {
    if (!isLoggedIn()) return false;
    if (is_string($required_roles)) $required_roles = [$required_roles];
    return in_array($_SESSION['user_role'], $required_roles);
}

// Check branch access
function hasBranchAccess($branch_id) {
    if (!isLoggedIn()) {
        return false;
    }
       // Admin and auditor have access to all branches
    if (in_array($_SESSION['user_role'], ['admin', 'auditor'])) {
        return true;
    }
        // Other roles can only access their assigned branch
    return $_SESSION['user_branch_id'] == $branch_id;
}

// Redirect if not authorized
function requireAuth($required_roles = null) {
    checkSessionTimeout();
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/pages/auth/login.php');
        exit();
    }
    if ($required_roles && !hasRole($required_roles)) {
        header('Location: ' . BASE_URL . '/pages/dashboard.php?error=access_denied');
        exit();
    }
}

// Log activity function
function logActivity($action, $table_name = null, $record_id = null, $old_values = null, $new_values = null) {
    if (!isLoggedIn()) return;
    $user_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $old_values_json = $old_values ? json_encode($old_values) : null;
    $new_values_json = $new_values ? json_encode($new_values) : null;
    try {
        executeQuery(
            "INSERT INTO activity_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$user_id, $action, $table_name, $record_id, $old_values_json, $new_values_json, $ip_address, $user_agent],
            'issiisss'
        );
    } catch (Exception $e) {
        error_log("Activity logging failed: " . $e->getMessage());
    }
}

// Generate notification
function createNotification($type, $title, $message, $user_id = null, $branch_id = null, $related_table = null, $related_id = null) {
    try {
        executeQuery(
            "INSERT INTO notifications (type, title, message, user_id, branch_id, related_table, related_id) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$type, $title, $message, $user_id, $branch_id, $related_table, $related_id],
            'sssiisi'
        );
    } catch (Exception $e) {
        error_log("Notification creation failed: " . $e->getMessage());
    }
}
?>
