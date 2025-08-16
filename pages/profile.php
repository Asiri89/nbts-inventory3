<?php
/**
 * User Profile Page for NBTS Inventory Management System
 * 
 * This page allows users to view and edit their profile
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// Require authentication
requireAuth();

$page_title = 'My Profile';

// Get current user information
$user = Auth::getCurrentUser();

$success_message = '';
$error_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'update_profile') {
        $first_name = sanitizeInput($_POST['first_name']);
        $last_name = sanitizeInput($_POST['last_name']);
        $email = sanitizeInput($_POST['email']);
        
        if (empty($first_name) || empty($last_name) || empty($email)) {
            $error_message = 'Please fill in all required fields.';
        } else {
            try {
                // Check if email already exists (excluding current user)
                $existing = fetchSingle(
                    "SELECT id FROM users WHERE email = ? AND id != ?",
                    [$email, $_SESSION['user_id']],
                    'si'
                );
                
                if ($existing) {
                    $error_message = 'Email address already exists.';
                } else {
                    // Update profile
                    executeQuery(
                        "UPDATE users SET first_name = ?, last_name = ?, email = ?, updated_at = NOW() WHERE id = ?",
                        [$first_name, $last_name, $email, $_SESSION['user_id']],
                        'sssi'
                    );
                    
                    // Update session
                    $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                    
                    // Log activity
                    logActivity('Update Profile', 'users', $_SESSION['user_id']);
                    
                    $success_message = 'Profile updated successfully.';
                    
                    // Refresh user data
                    $user = Auth::getCurrentUser();
                }
            } catch (Exception $e) {
                $error_message = 'Error updating profile: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = 'Please fill in all password fields.';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'New passwords do not match.';
        } elseif (strlen($new_password) < 6) {
            $error_message = 'New password must be at least 6 characters long.';
        } else {
            $result = Auth::changePassword($_SESSION['user_id'], $current_password, $new_password);
            
            if ($result['success']) {
                $success_message = $result['message'];
            } else {
                $error_message = $result['message'];
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="page-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-person-circle me-2"></i>My Profile</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">My Profile</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="row">
    <!-- Profile Information -->
    <div class="col-lg-8">
        <div class="card fade-in mb-4">
            <div class="card-header">
                <h5 class="mb-0">Profile Information</h5>
            </div>
            <div class="card-body">
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i><?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" 
                                   value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                            <div class="form-text">Username cannot be changed</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Role</label>
                            <input type="text" class="form-control" id="role" 
                                   value="<?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="branch" class="form-label">Branch</label>
                            <input type="text" class="form-control" id="branch" 
                                   value="<?php echo htmlspecialchars($user['branch_name'] ?: 'All Branches'); ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="card fade-in">
            <div class="card-header">
                <h5 class="mb-0">Change Password</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="passwordForm">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   minlength="6" required>
                            <div class="form-text">Minimum 6 characters</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   minlength="6" required>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-key me-2"></i>Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Profile Summary -->
    <div class="col-lg-4">
        <div class="card fade-in">
            <div class="card-header">
                <h6 class="mb-0">Account Summary</h6>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <i class="bi bi-person-circle" style="font-size: 4rem; color: var(--nbts-primary);"></i>
                    <h5 class="mt-2"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                    <p class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?></p>
                </div>
                
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>Username:</strong></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Email:</strong></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Branch:</strong></td>
                        <td><?php echo htmlspecialchars($user['branch_name'] ?: 'All Branches'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Last Login:</strong></td>
                        <td>
                            <?php if ($user['last_login']): ?>
                                <?php echo formatDateTime($user['last_login'], 'M j, Y g:i A'); ?>
                            <?php else: ?>
                                <span class="text-muted">Never</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Account Created:</strong></td>
                        <td><?php echo formatDateTime($user['created_at'], 'M j, Y'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>
                            <span class="badge <?php echo $user['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$additional_js = '
<script>
// Password confirmation validation
document.getElementById("passwordForm").addEventListener("submit", function(e) {
    const newPassword = document.getElementById("new_password").value;
    const confirmPassword = document.getElementById("confirm_password").value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        showToast("Error", "New passwords do not match", "error");
        return false;
    }
    
    if (newPassword.length < 6) {
        e.preventDefault();
        showToast("Error", "Password must be at least 6 characters long", "error");
        return false;
    }
});

// Real-time password confirmation check
document.getElementById("confirm_password").addEventListener("input", function() {
    const newPassword = document.getElementById("new_password").value;
    const confirmPassword = this.value;
    
    if (confirmPassword && newPassword !== confirmPassword) {
        this.classList.add("is-invalid");
    } else {
        this.classList.remove("is-invalid");
        if (confirmPassword) {
            this.classList.add("is-valid");
        }
    }
});
</script>
';

include '../includes/footer.php';
?>