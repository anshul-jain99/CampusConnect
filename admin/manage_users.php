<?php
/**
 * CampusConnect - Admin: Manage Users
 * View and manage student and organizer profiles.
 */
$pageTitle = "Manage Users";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');
$admin = currentUser();

// Handle User Role Change or Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? '';

    if ($userId && $userId !== (int)$admin['id']) {
        if ($action === 'make_organizer') {
            $stmt = $pdo->prepare("UPDATE users SET role = 'organizer' WHERE id = ?");
            $stmt->execute([$userId]);
            setFlash('success', 'User promoted to Organizer successfully.');
        } elseif ($action === 'make_student') {
            $stmt = $pdo->prepare("UPDATE users SET role = 'student' WHERE id = ?");
            $stmt->execute([$userId]);
            setFlash('info', 'User converted to Student role.');
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            setFlash('warning', 'User account permanently deleted.');
        }
        header('Location: manage_users.php');
        exit();
    } else {
        setFlash('error', 'Cannot perform this action on your own admin account.');
        header('Location: manage_users.php');
        exit();
    }
}

$roleFilter = $_GET['role'] ?? 'all';
$search = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($roleFilter !== 'all' && in_array($roleFilter, ['student', 'organizer', 'admin'])) {
    $sql .= " AND role = ?";
    $params[] = $roleFilter;
}

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR email LIKE ? OR enrollment_no LIKE ?)";
    $w = "%{$search}%";
    $params[] = $w;
    $params[] = $w;
    $params[] = $w;
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Manage Campus Users</h1>
            <p class="page-subtitle">Directory of students, faculty club organizers, and portal administrators.</p>
        </div>
        <a href="admin_dashboard.php" class="btn btn-secondary btn-sm">&larr; Back to Dashboard</a>
    </div>

    <!-- Filter & Search Bar -->
    <div class="filter-bar">
        <form method="GET" action="manage_users.php" style="display: flex; gap: 1rem; width: 100%; flex-wrap: wrap;">
            <div class="search-input-group">
                <input type="text" name="q" class="form-control" placeholder="Search by name, email, enrollment number..." value="<?= e($search) ?>">
            </div>
            <div class="filter-group">
                <select name="role" class="form-control" onchange="this.form.submit()">
                    <option value="all" <?= $roleFilter === 'all' ? 'selected' : '' ?>>All Roles</option>
                    <option value="student" <?= $roleFilter === 'student' ? 'selected' : '' ?>>Students</option>
                    <option value="organizer" <?= $roleFilter === 'organizer' ? 'selected' : '' ?>>Club Organizers</option>
                    <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admins</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Search</button>
            </div>
        </form>
    </div>

    <?php if (!empty($users)): ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User Name</th>
                        <th>Email Address</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Enrollment No</th>
                        <th>Joined Date</th>
                        <th>Manage Role / Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>#<?= $u['id'] ?></td>
                            <td><strong><?= e($u['name']) ?></strong></td>
                            <td><a href="mailto:<?= e($u['email']) ?>"><?= e($u['email']) ?></a></td>
                            <td>
                                <span class="role-tag" style="background: <?= $u['role'] === 'admin' ? '#ef4444' : ($u['role'] === 'organizer' ? '#3b82f6' : '#10b981') ?>;">
                                    <?= strtoupper(e($u['role'])) ?>
                                </span>
                            </td>
                            <td><?= e($u['department']) ?></td>
                            <td><?= e($u['enrollment_no'] ?: '—') ?></td>
                            <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <?php if ($u['id'] !== (int)$admin['id']): ?>
                                    <form method="POST" action="manage_users.php" style="display: flex; gap: 0.35rem; align-items: center;">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <?php if ($u['role'] === 'student'): ?>
                                            <button type="submit" name="action" value="make_organizer" class="btn btn-secondary btn-sm" title="Promote to Organizer">
                                                &#8679; Make Org
                                            </button>
                                        <?php elseif ($u['role'] === 'organizer'): ?>
                                            <button type="submit" name="action" value="make_student" class="btn btn-secondary btn-sm" title="Revert to Student">
                                                &#8681; Make Student
                                            </button>
                                        <?php endif; ?>

                                        <button type="submit" name="action" value="delete" class="btn btn-danger btn-sm" onclick="return confirmAction('Are you sure you want to delete <?= addslashes($u['name']) ?>?');" title="Delete Account">
                                            &#128465;
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size: 0.8rem; color: var(--text-muted); font-style: italic;">Current Session</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 4rem; background: #fff; border: 1px solid var(--border); border-radius: var(--radius);">
            <p style="color: var(--text-muted);">No users found matching the search query.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
