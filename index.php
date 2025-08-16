<?php
/**
 * Main Index File for NBTS Inventory Management System
 * 
 * This file redirects users to appropriate pages based on authentication status
 */

require_once 'config/config.php';

// Check if user is logged in
if (isLoggedIn()) {
    // Redirect to dashboard if logged in
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
} else {
    // Redirect to login page if not logged in
    header('Location: ' . BASE_URL . '/pages/auth/login.php');
}

exit();
?>