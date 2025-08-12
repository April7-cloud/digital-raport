<?php
// Start output buffering
ob_start();

// Include necessary files
require_once 'config/config.php';
require_once 'auth/session.php';

// Redirect to dashboard if logged in, otherwise to login page
if (isLoggedIn()) {
    redirect(BASE_URL . '/modules/dashboard/index.php');
} else {
    redirect(BASE_URL . '/auth/login.php');
}

// End output buffering
ob_end_flush();
?>
