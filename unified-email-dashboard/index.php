<?php
/**
 * Unified Email Dashboard - Main Entry Point
 * 
 * Redirects to dashboard or login page
 */

require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header('Location: /dashboard/');
} else {
    header('Location: /auth/login.php');
}
exit();
?>
