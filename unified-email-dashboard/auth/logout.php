<?php
/**
 * Unified Email Dashboard - Admin Logout
 */

require_once __DIR__ . '/../includes/functions.php';

// Clear all session data
$_SESSION = [];

// Destroy session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

// Destroy session
session_destroy();

// Redirect to login page
header('Location: /auth/login.php');
exit();
?>
