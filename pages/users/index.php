<?php
/**
 * Users Management Page for NBTS Inventory Management System
 * 
 * This page displays and manages users (Admin only)
 */

require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require admin authentication
requireAuth(['admin']);

$page_title = 'Users Management';

// Handle delete request
if (isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    
    // Prevent deleting current user
    if ($delete_id == $_SESSION['user_id']) {
        $error_message = 'Cannot delete your own account.';
    } else {
        try {
            // Get user info for logging
            $user = fetchSingle("SELECT * FROM users WHERE id = ?", [$delete_id], 'i');
            
            // Delete user
            executeQuery("DELETE FROM users WHERE id = ?", [$delete_id], 'i');
            
            // Log activity
            logActivity('Delete User', 'users', $delete_id, $user);
            
            $success_message = 'User deleted successfully.';
        } catch (Exception $e) {
            $error_message = 'Error deleting user: ' . $e->getMessage();
        }
    }
}

// Handle add/edit user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $role = sanitizeInput($_POST['role']);
    $branch_id = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($email) || empty($first_name) || empty($last_name) || empty($role)) {
        $error_message = 'Please fill in all required fields.';
    } elseif ($action === 'add' && empty($password)) {
        $error_message = 'Password is required for new users.';
    } else {
        try {
            if ($action === 'add') {
                // Check if username or email already exists
                $existing = fetchSingle(
                    "SELECT id FROM users WHERE username = ? OR email = ?",
                    [$username, $email],
                    'ss'
                );
                
                if ($existing) {
                    $error_message = 'Username or email already exists.';
                } else {
                    // Hash password
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert new user
                    executeQuery(
                        "INSERT INTO users (username, email, password, first_name, last_name, role, branch_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                        [$username, $email, $password_hash, $first_name, $last_name, $role, $branch_id, $is_active],
                        'ssssssii'
                    );
                    
                    $user_id = getLastInsertId();
                    
                    // Log activity
                    logActivity('Create User', 'users', $user_id, null, [
                        'username' => $username,
                        'email' => $email,
                        'role' => $role,
                        'branch_id' => $branch_id
                    ]);
                    
                    $success_message = 'User added successfully.';
                }
            } elseif ($action === 'edit') {
                $user_id = (int)$_POST['user_id'];
                
                // Check if username or email already exists (excluding current user)
                $existing = fetchSingle(
                    "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?",
                    [$username, $email, $user_id],
                    'ssi'
                );
                
                if ($existing) {
                    $error_message = 'Username or email already exists.';
                } else {
                    // Get old values for logging
                    $old_user = fetchSingle("SELECT * FROM users WHERE id = ?", [$user_id], 'i');
                    
                    // Update user (with or without password)
                    if (!empty($password)) {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        executeQuery(
                            "UPDATE users SET username = ?, email = ?, password = ?, first_name = ?, last_name = ?, role = ?, branch_id = ?, is_active = ?, updated_at = NOW() WHERE id = ?",
                            [$username, $email, $password_hash, $first_name, $last_name, $role, $branch_id, $is_active, $user_id],
                            'ssssssiii'
                        );
                    } else {
                        executeQuery(
                            "UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, role = ?, branch_id = ?, is_active = ?, updated_at = NOW() WHERE id = ?",
                            [$username, $email, $first_name, $last_name, $role, $branch_id, $is_active, $user_id],
                            'sssssiii'
                        );
                    }
                    
                    // Log activity
                    logActivity('Update User', 'users', $user_id, $old_user, [
                        'username' => $username,
                        'email' => $email,
                        'role' => $role,
                        'branch_id' => $branch_id
                    ]);
                    
                    $success_message = 'User updated successfully.';
                }
            }
        } catch (Exception $e) {
            $error_message = 'Error processing user: ' . $e->getMessage();
        }
    }
}

// Get filter parameters
$branch_filter = $_GET['branch_id'] ?? '';
$role_filter = $_GET['role'] ?? '';

// Build query
$query = "SELECT u.*, b.name as branch_name FROM users u LEFT JOIN branches b ON u.branch_id = b.id WHERE 1=1";
$params = [];
$types = '';

if ($branch_filter) {
    $query .= " AND u.branch_id = ?";
    $params[] = $branch_filter;
    $types .= 'i';
}

if ($role_filter) {
    $query .= " AND u.role = ?";
    $params[] = $role_filter;
    $types .= 's';
}

$query .= " ORDER BY u.first_name, u.last_name";

// Get users
$users = fetchAll($query, $params, $types);

// Get branches for dropdowns
$branches = fetchAll("SELECT * FROM branches WHERE is_active = 1 ORDER BY name");

include '../../includes/header.php';
?>

<div class="page-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-people me-2"></i>Users Management</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/pages/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Users</li>
                </ol>
            </nav>
        </div>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" onclick="openAddModal()">
                <i class="bi bi-plus-circle me-2"></i>Add User
            </button>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4 fade-in">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="branch_id" class="form-label">Branch</label>
                <select class="form-select" id="branch_id" name="branch_id">
                    <option value="">All Branches</option>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?php echo $branch['id']; ?>" 
                                <?php echo $branch_filter == $branch['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($branch['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="role" class="form-label">Role</label>
                <select class="form-select" id="role" name="role">
                    <option value="">All Roles</option>
                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="inventory_manager" <?php echo $role_filter === 'inventory_manager' ? 'selected' : ''; ?>>Inventory Manager</option>
                    <option value="view_only" <?php echo $role_filter === 'view_only' ? 'selected' : ''; ?>>View Only</option>
                    <option value="auditor" <?php echo $role_filter === 'auditor' ? 'selected' : ''; ?>>Auditor</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card fade-in">
    <div class="card-header">
        <h5 class="mb-0">Users List (<?php echo count($users); ?> users)</h5>
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
            <table class="table table-hover" id="usersTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Branch</th>
                        <th>Last Login</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                    <span class="badge bg-info ms-2">You</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td>
                                <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>">
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </a>
                            </td>
                            <td>
                                <?php
                                $role_badges = [
                                    'admin' => 'bg-danger',
                                    'inventory_manager' => 'bg-primary',
                                    'view_only' => 'bg-secondary',
                                    'auditor' => 'bg-warning'
                                ];
                                ?>
                                <span class="badge <?php echo $role_badges[$user['role']] ?? 'bg-secondary'; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($user['branch_name'] ?: 'All Branches'); ?></td>
                            <td>
                                <?php if ($user['last_login']): ?>
                                    <?php echo formatDateTime($user['last_login'], 'M j, Y g:i A'); ?>
                                <?php else: ?>
                                    <span class="text-muted">Never</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $user['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-success" 
                                            onclick="openEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" class="d-inline" 
                                              onsubmit="return confirmDelete('Are you sure you want to delete this user?')">
                                            <input type="hidden" name="delete_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="userForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="user_id" id="userId">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="modalFirstName" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modalFirstName" name="first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="modalLastName" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modalLastName" name="last_name" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="modalUsername" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modalUsername" name="username" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="modalEmail" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="modalEmail" name="email" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="modalRole" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select" id="modalRole" name="role" required>
                                <option value="">Select Role</option>
                                <option value="admin">Admin</option>
                                <option value="inventory_manager">Inventory Manager</option>
                                <option value="view_only">View Only</option>
                                <option value="auditor">Auditor</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="modalBranchId" class="form-label">Branch</label>
                            <select class="form-select" id="modalBranchId" name="branch_id">
                                <option value="">All Branches (Admin/Auditor)</option>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?php echo $branch['id']; ?>">
                                        <?php echo htmlspecialchars($branch['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="modalPassword" class="form-label">Password <span class="text-danger" id="passwordRequired">*</span></label>
                            <input type="password" class="form-control" id="modalPassword" name="password">
                            <div class="form-text" id="passwordHelp">Leave blank to keep current password (when editing)</div>
                        </div>
                        <div class="col-md-4 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="modalIsActive" name="is_active" checked>
                                <label class="form-check-label" for="modalIsActive">
                                    Active User
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$additional_js = '
<script>
$(document).ready(function() {
    initDataTable("#usersTable", {
        order: [[0, "asc"]],
        columnDefs: [
            { orderable: false, targets: [7] }
        ]
    });
});

function openAddModal() {
    document.getElementById("modalTitle").textContent = "Add User";
    document.getElementById("formAction").value = "add";
    document.getElementById("submitBtn").textContent = "Add User";
    document.getElementById("userForm").reset();
    document.getElementById("modalIsActive").checked = true;
    document.getElementById("passwordRequired").style.display = "inline";
    document.getElementById("passwordHelp").style.display = "none";
    document.getElementById("modalPassword").required = true;
}

function openEditModal(user) {
    document.getElementById("modalTitle").textContent = "Edit User";
    document.getElementById("formAction").value = "edit";
    document.getElementById("submitBtn").textContent = "Update User";
    document.getElementById("userId").value = user.id;
    document.getElementById("modalFirstName").value = user.first_name;
    document.getElementById("modalLastName").value = user.last_name;
    document.getElementById("modalUsername").value = user.username;
    document.getElementById("modalEmail").value = user.email;
    document.getElementById("modalRole").value = user.role;
    document.getElementById("modalBranchId").value = user.branch_id || "";
    document.getElementById("modalPassword").value = "";
    document.getElementById("modalIsActive").checked = user.is_active == 1;
    document.getElementById("passwordRequired").style.display = "none";
    document.getElementById("passwordHelp").style.display = "block";
    document.getElementById("modalPassword").required = false;
}
</script>
';

include '../../includes/footer.php';
?>