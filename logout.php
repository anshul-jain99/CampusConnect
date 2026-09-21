<?php
/**
 * CampusConnect - Logout Handler
 * Clears session cookies and redirects cleanly to home page
 */
require_once __DIR__ . '/includes/auth.php';

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Start new session to carry flash message
session_start();
setFlash('info', 'You have been successfully logged out.');
header('Location: login.php');
exit();
?>
