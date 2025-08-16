<?php
/**
 * Database Configuration for NBTS Inventory Management System
 * 
 * This file contains the database connection settings.
 * Update these settings according to your MySQL server configuration.
 */

// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'nbts-inventory3');
define('DB_CHARSET', 'utf8mb4');

// Create database connection using MySQLi
function getDatabaseConnection() {
    static $connection = null;
    
    if ($connection === null) {
        try {
            $connection = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
            
            // Check connection
            if ($connection->connect_error) {
                throw new Exception("Connection failed: " . $connection->connect_error);
            }
            
            // Set charset
            $connection->set_charset(DB_CHARSET);
            
            // Set timezone
            $connection->query("SET time_zone = '+05:30'"); // Sri Lanka timezone
            
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            die("Database connection failed. Please check configuration.");
        }
    }
    
    return $connection;
}

// Function to execute prepared statements safely
function executeQuery($query, $params = [], $types = '') {
    $conn = getDatabaseConnection();
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    // Validate parameter count
    if (!empty($params)) {
        if (strlen($types) !== count($params)) {
            throw new Exception(
                "Parameter count mismatch: expected " . strlen($types) . 
                " type definitions but got " . count($params) . " parameters."
            );
        }

        $stmt->bind_param($types, ...$params);
    }
    
    $result = $stmt->execute();
    
    if (!$result) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    return $stmt;
}

// Function to fetch single row
function fetchSingle($query, $params = [], $types = '') {
    $stmt = executeQuery($query, $params, $types);
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to fetch multiple rows
function fetchAll($query, $params = [], $types = '') {
    $stmt = executeQuery($query, $params, $types);
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to get last inserted ID
function getLastInsertId() {
    $conn = getDatabaseConnection();
    return $conn->insert_id;
}

// Close database connection
function closeDatabaseConnection() {
    $conn = getDatabaseConnection();
    if ($conn) {
        $conn->close();
    }
}
?>