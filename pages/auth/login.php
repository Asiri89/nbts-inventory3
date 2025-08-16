<?php
/**
 * Login Page for NBTS Inventory Management System
 * 
 * This page handles user authentication and login
 */

require_once '../../config/config.php';
require_once '../../includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit();
}

$error_message = '';
$success_message = '';

// Check for URL parameters
if (isset($_GET['timeout'])) {
    $error_message = 'Your session has expired. Please log in again.';
}
if (isset($_GET['logged_out'])) {
    $success_message = 'You have been successfully logged out.';
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        $result = Auth::login($username, $password);
        
        if ($result['success']) {
            header('Location: ' . BASE_URL . '/pages/dashboard.php');
            exit();
        } else {
            $error_message = $result['message'];
        }
    }
}

$page_title = 'Login';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - NBTS Inventory Management</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --nbts-primary: #dc3545;
            --nbts-secondary: #0d6efd;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Arial', sans-serif;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
        }
        
        .login-left {
            background: linear-gradient(135deg, var(--nbts-primary), #b02a37);
            color: white;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        
        .login-right {
            padding: 60px 40px;
        }
        
        .logo {
            font-size: 3rem;
            margin-bottom: 20px;
        }
        
        .welcome-text {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .welcome-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .login-form h2 {
            color: var(--nbts-primary);
            font-weight: 700;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .form-floating {
            margin-bottom: 20px;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--nbts-primary);
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--nbts-primary), #b02a37);
            border: none;
            border-radius: 10px;
            padding: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            width: 100%;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }
        
        .alert {
            border: none;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        
        .demo-credentials {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .demo-credentials h6 {
            color: var(--nbts-primary);
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .demo-account {
            margin-bottom: 10px;
            padding: 8px;
            background: white;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .login-left {
                padding: 40px 30px;
            }
            
            .login-right {
                padding: 40px 30px;
            }
            
            .welcome-text {
                font-size: 1.3rem;
            }
            
            .logo {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="row g-0 h-100">
            <div class="col-lg-6 login-left">
                <div class="w-100">
                    <i class="bi bi-heart-pulse-fill logo"></i>
                    <h1 class="welcome-text">Welcome to NBTS</h1>
                    <p class="welcome-subtitle">
                        Inventory Management System<br>
                        National Blood Transfusion Service<br>
                        Sri Lanka
                    </p>
                    
                    <div class="mt-4">
                        <div class="d-flex justify-content-center gap-4">
                            <div class="text-center">
                                <i class="bi bi-shield-check" style="font-size: 2rem; margin-bottom: 10px;"></i>
                                <div>Secure</div>
                            </div>
                            <div class="text-center">
                                <i class="bi bi-speedometer2" style="font-size: 2rem; margin-bottom: 10px;"></i>
                                <div>Fast</div>
                            </div>
                            <div class="text-center">
                                <i class="bi bi-phone" style="font-size: 2rem; margin-bottom: 10px;"></i>
                                <div>Mobile</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 login-right">
                <div class="login-form">
                    <h2>Sign In</h2>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="username" name="username" 
                                   placeholder="Username or Email" required>
                            <label for="username">
                                <i class="bi bi-person me-2"></i>Username or Email
                            </label>
                        </div>
                        
                        <div class="form-floating">
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Password" required>
                            <label for="password">
                                <i class="bi bi-lock me-2"></i>Password
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-login">
                            <i class="bi bi-box-arrow-in-right me-2"></i>
                            Sign In
                        </button>
                    </form>
                    
                    <!-- Demo Credentials -->
                    <div class="demo-credentials">
                        <h6><i class="bi bi-info-circle me-2"></i>Demo Accounts</h6>
                        <div class="demo-account">
                            <strong>Admin:</strong> admin / password
                        </div>
                        <div class="demo-account">
                            <strong>Inventory Manager:</strong> inv_manager_nbc / password
                        </div>
                        <div class="demo-account">
                            <strong>View Only:</strong> viewer_galle / password
                        </div>
                        <div class="demo-account">
                            <strong>Auditor:</strong> auditor / password
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>