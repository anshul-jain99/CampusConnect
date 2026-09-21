<?php
/**
 * CampusConnect - Common Navigation Header
 * Dynamic navigation that adapts to public guests and logged-in roles
 */

// Determine base URL path relative to the current script location
$currentDir = basename(dirname($_SERVER['SCRIPT_NAME']));
$isSubDir = in_array($currentDir, ['student', 'organizer', 'admin']);
$basePath = $isSubDir ? '../' : '';

require_once __DIR__ . '/auth.php';
$user = currentUser();
$flash = getFlash();

$activePage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' | CampusConnect' : 'CampusConnect - College Event & Club Portal' ?></title>
    <link rel="stylesheet" href="<?= $basePath ?>css/style.css">
</head>
<body>

<!-- Navigation Bar -->
<header class="navbar">
    <div class="nav-container">
        <a href="<?= $basePath ?>index.php" class="brand">
            <div class="brand-icon">CC</div>
            <div>
                CampusConnect
                <span class="brand-subtitle">Event & Club Portal</span>
            </div>
        </a>

        <button class="nav-toggle" aria-label="Toggle navigation">&#9776;</button>

        <ul class="nav-links">
            <!-- Public / Global Links -->
            <li><a href="<?= $basePath ?>index.php" class="<?= $activePage === 'index' ? 'active' : '' ?>">Home</a></li>
            <li><a href="<?= $basePath ?>events.php" class="<?= $activePage === 'events' ? 'active' : '' ?>">Explore Events</a></li>

            <?php if (isLoggedIn()): ?>
                <!-- Student Links -->
                <?php if ($user['role'] === 'student'): ?>
                    <li><a href="<?= $basePath ?>student/student_dashboard.php" class="<?= $activePage === 'student_dashboard' ? 'active' : '' ?>">Dashboard</a></li>
                    <li><a href="<?= $basePath ?>student/my_registrations.php" class="<?= $activePage === 'my_registrations' ? 'active' : '' ?>">My Registrations</a></li>
                    <li><a href="<?= $basePath ?>student/profile.php" class="<?= $activePage === 'profile' ? 'active' : '' ?>">Profile</a></li>
                <?php endif; ?>

                <!-- Organizer Links -->
                <?php if ($user['role'] === 'organizer'): ?>
                    <li><a href="<?= $basePath ?>organizer/organizer_dashboard.php" class="<?= $activePage === 'organizer_dashboard' ? 'active' : '' ?>">Dashboard</a></li>
                    <li><a href="<?= $basePath ?>organizer/create_event.php" class="<?= $activePage === 'create_event' ? 'active' : '' ?>">Create Event</a></li>
                    <li><a href="<?= $basePath ?>organizer/manage_events.php" class="<?= $activePage === 'manage_events' ? 'active' : '' ?>">Manage Events</a></li>
                <?php endif; ?>

                <!-- Admin Links -->
                <?php if ($user['role'] === 'admin'): ?>
                    <li><a href="<?= $basePath ?>admin/admin_dashboard.php" class="<?= $activePage === 'admin_dashboard' ? 'active' : '' ?>">Dashboard</a></li>
                    <li><a href="<?= $basePath ?>admin/manage_events.php" class="<?= $activePage === 'manage_events' ? 'active' : '' ?>">Approvals</a></li>
                    <li><a href="<?= $basePath ?>admin/manage_users.php" class="<?= $activePage === 'manage_users' ? 'active' : '' ?>">Users</a></li>
                    <li><a href="<?= $basePath ?>admin/manage_clubs.php" class="<?= $activePage === 'manage_clubs' ? 'active' : '' ?>">Clubs</a></li>
                    <li><a href="<?= $basePath ?>admin/manage_registrations.php" class="<?= $activePage === 'manage_registrations' ? 'active' : '' ?>">All Registrations</a></li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>

        <div class="nav-actions">
            <?php if (isLoggedIn()): ?>
                <div class="user-badge">
                    <span><?= e($user['name']) ?></span>
                    <span class="role-tag"><?= strtoupper(e($user['role'])) ?></span>
                </div>
                <a href="<?= $basePath ?>logout.php" class="btn btn-secondary btn-sm">Logout</a>
            <?php else: ?>
                <a href="<?= $basePath ?>login.php" class="btn btn-secondary btn-sm">Login</a>
                <a href="<?= $basePath ?>register.php" class="btn btn-primary btn-sm">Student Register</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- Flash Messages -->
<?php if ($flash): ?>
    <div class="flash-container">
        <div class="flash-alert flash-<?= e($flash['type']) ?>">
            <span><?= e($flash['message']) ?></span>
            <button class="flash-close" onclick="this.parentElement.remove();">&times;</button>
        </div>
    </div>
<?php endif; ?>
