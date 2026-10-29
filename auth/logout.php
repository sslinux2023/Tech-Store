<?php
/**
 * User Logout Action
 * File: auth/logout.php
 *
 * Destroys user session and redirects back to homepage.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Unset all session variables
$_SESSION = [];

// Clear session cookie if active
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// Destroy session data
session_destroy();

// Start fresh session to provide logged-out notification
session_start();
set_flash_message('info', 'You have been successfully logged out.');

header('Location: ' . BASE_URL . '/index.php');
exit;
