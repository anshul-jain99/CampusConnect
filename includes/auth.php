<?php
/**
 * CampusConnect - Session & Authentication Helper Functions
 * Manages user sessions, role-based access control, and flash notifications.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Allow demo_view parameter for automated screenshots and previewing
if (isset($_GET['demo_view'])) {
    if ($_GET['demo_view'] === 'student') {
        $_SESSION['user_id'] = 4;
        $_SESSION['user_name'] = 'Anshul Jain';
        $_SESSION['user_email'] = 'anshul@student.edu';
        $_SESSION['user_role'] = 'student';
        $_SESSION['user_dept'] = 'Information Technology';
        $_SESSION['user_enrollment'] = '01815603124';
    } elseif ($_GET['demo_view'] === 'organizer') {
        $_SESSION['user_id'] = 2;
        $_SESSION['user_name'] = 'Aarav Sharma (Coding Club)';
        $_SESSION['user_email'] = 'organizer.tech@campus.edu';
        $_SESSION['user_role'] = 'organizer';
        $_SESSION['user_dept'] = 'Information Technology';
        $_SESSION['user_enrollment'] = 'ORG-001';
    } elseif ($_GET['demo_view'] === 'admin') {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_name'] = 'System Administrator';
        $_SESSION['user_email'] = 'admin@campus.edu';
        $_SESSION['user_role'] = 'admin';
        $_SESSION['user_dept'] = 'Administration';
        $_SESSION['user_enrollment'] = 'ADM-001';
    }
}

/**
 * Check if a user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged in user details array
 */
function currentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'            => $_SESSION['user_id'] ?? null,
        'name'          => $_SESSION['user_name'] ?? 'User',
        'email'         => $_SESSION['user_email'] ?? '',
        'role'          => $_SESSION['user_role'] ?? 'student',
        'department'    => $_SESSION['user_dept'] ?? '',
        'enrollment_no' => $_SESSION['user_enrollment'] ?? ''
    ];
}

/**
 * Verify if the logged in user matches a specific role
 */
function hasRole($role) {
    if (!isLoggedIn()) {
        return false;
    }
    return ($_SESSION['user_role'] ?? '') === $role;
}

/**
 * Enforce role requirement. Redirects unauthorized users.
 */
function requireRole($role, $redirectPath = '../login.php') {
    if (!isLoggedIn()) {
        setFlash('error', 'Please log in to access this page.');
        header("Location: {$redirectPath}");
        exit();
    }
    if (!hasRole($role)) {
        setFlash('error', 'Unauthorized access! You do not have permission to view this section.');
        // Redirect to appropriate dashboard based on their actual role
        $currentRole = $_SESSION['user_role'];
        if ($currentRole === 'admin') {
            header("Location: ../admin/admin_dashboard.php");
        } elseif ($currentRole === 'organizer') {
            header("Location: ../organizer/organizer_dashboard.php");
        } else {
            header("Location: ../student/student_dashboard.php");
        }
        exit();
    }
}

/**
 * Set a one-time flash notification message
 */
function setFlash($type, $message) {
    $_SESSION['flash_notification'] = [
        'type'    => $type, // 'success', 'error', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Retrieve and clear the flash notification message
 */
function getFlash() {
    if (isset($_SESSION['flash_notification'])) {
        $flash = $_SESSION['flash_notification'];
        unset($_SESSION['flash_notification']);
        return $flash;
    }
    return null;
}

/**
 * Escape text for secure HTML output (Prevents XSS attacks)
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
?>
