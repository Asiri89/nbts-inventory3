<?php
/**
 * Logout script for NBTS Inventory Management System
 * 
 * This script handles user logout and session cleanup
 */

require_once '../config/config.php';
require_once 'auth.php';

// Perform logout
Auth::logout();
?>