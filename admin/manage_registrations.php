<?php
/**
 * CampusConnect - Admin: Master Registration Audit Log
 */
$pageTitle = "All Registrations";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$eventIdFilter = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
$statusFilter = $_GET['status'] ?? 'all';
$search = trim($_GET['q'] ?? '');

$sql = "
    SELECT r.id AS reg_id, r.registration_date, r.status AS reg_status,
           e.id AS event_id, e.title AS event_title, e.category, e.event_date,
           c.club_name,
           u.id AS student_id, u.name AS student_name, u.email AS student_email, 
           u.department, u.phone, u.enrollment_no
    FROM registrations r
    JOIN events e ON r.event_id = e.id
    JOIN clubs c ON e.club_id = c.id
    JOIN users u ON r.student_id = u.id
    WHERE 1=1
";
$params = [];

if ($eventIdFilter) {
    $sql .= " AND r.event_id = ?";
    $params[] = $eventIdFilter;
}

if ($statusFilter !== 'all' && in_array($statusFilter, ['confirmed', 'cancelled'])) {
    $sql .= " AND r.status = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.enrollment_no LIKE ? OR e.title LIKE ?)";
    $w = "%{$search}%";
    $params[] = $w;
    $params[] = $w;
    $params[] = $w;
    $params[] = $w;
}

$sql .= " ORDER BY r.registration_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$registrations = $stmt->fetchAll();

// Fetch events list for filter dropdown
$allEvents = $pdo->query("SELECT id, title FROM events ORDER BY title ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Master Registrations Log</h1>
            <p class="page-subtitle">Complete audit trail of all student signups across college clubs and events.</p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button onclick="window.print()" class="btn btn-secondary btn-sm">&#128438; Print / Export Log</button>
            <a href="admin_dashboard.php" class="btn btn-primary btn-sm">&larr; Dashboard</a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" action="manage_registrations.php" style="display: flex; gap: 1rem; width: 100%; flex-wrap: wrap; align-items: center;">
            <div class="search-input-group">
                <input type="text" name="q" class="form-control" placeholder="Search student, enrollment, or event..." value="<?= e($search) ?>">
            </div>

            <div class="filter-group">
                <select name="event_id" class="form-control" onchange="this.form.submit()">
                    <option value="">All Events</option>
                    <?php foreach ($allEvents as $ev): ?>
                        <option value="<?= $ev['id'] ?>" <?= $eventIdFilter == $ev['id'] ? 'selected' : '' ?>><?= e(substr($ev['title'], 0, 35)) ?>...</option>
                    <?php endforeach; ?>
                </select>

                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                    <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                    <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>

                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            </div>
        </form>
    </div>

    <div style="margin-bottom: 1rem; font-size: 0.9rem; color: var(--text-muted);">
        Showing <strong><?= count($registrations) ?></strong> registration records
    </div>

    <?php if (!empty($registrations)): ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Pass ID</th>
                        <th>Student Details</th>
                        <th>Department &amp; Enrollment</th>
                        <th>Event &amp; Club</th>
                        <th>Event Date</th>
                        <th>Registered Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $r): ?>
                        <tr>
                            <td><code>#CC-REG-<?= str_pad($r['reg_id'], 4, '0', STR_PAD_LEFT) ?></code></td>
                            <td>
                                <strong><?= e($r['student_name']) ?></strong><br>
                                <small><a href="mailto:<?= e($r['student_email']) ?>"><?= e($r['student_email']) ?></a></small>
                            </td>
                            <td>
                                <?= e($r['department']) ?><br>
                                <small style="color: var(--text-muted);"><?= e($r['enrollment_no'] ?: '—') ?></small>
                            </td>
                            <td>
                                <strong><a href="../event_details.php?id=<?= $r['event_id'] ?>" target="_blank"><?= e($r['event_title']) ?></a></strong><br>
                                <small style="color: var(--text-muted);"><?= e($r['club_name']) ?> &bull; <?= e($r['category']) ?></small>
                            </td>
                            <td><?= date('M d, Y', strtotime($r['event_date'])) ?></td>
                            <td><?= date('M d, Y H:i', strtotime($r['registration_date'])) ?></td>
                            <td>
                                <?php if ($r['reg_status'] === 'confirmed'): ?>
                                    <span class="event-status-tag status-approved" style="position: static;">Confirmed</span>
                                <?php else: ?>
                                    <span class="event-status-tag status-rejected" style="position: static;">Cancelled</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 4rem; background: #fff; border: 1px solid var(--border); border-radius: var(--radius);">
            <p style="color: var(--text-muted);">No registration records found for this selection.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
