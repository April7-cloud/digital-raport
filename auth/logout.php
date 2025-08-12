<?php
// Define BASE_PATH for direct access check
define('BASE_PATH', dirname(dirname(__FILE__)));
require_once BASE_PATH . '/config/app_config.php';
require_once BASE_PATH . '/auth/session.php';

// Logout user
logout();

// Redirect to login page
setFlash('success', 'You have been logged out');
redirect(BASE_URL . '/auth/login.php');