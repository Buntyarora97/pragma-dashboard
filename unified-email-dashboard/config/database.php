<?php
/**
 * Unified Email Dashboard - Database Configuration
 * 
 * This file contains database connection settings.
 * Modify these settings according to your server configuration.
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'unified_email_dashboard');
define('DB_CHARSET', 'utf8mb4');

// Create database connection
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed. Please check your configuration.");
        }
    }
    
    return $pdo;
}

// Test database connection
function testDBConnection() {
    try {
        $db = getDBConnection();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
