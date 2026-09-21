<?php
/**
 * CampusConnect - Administrator Dashboard
 * System-wide metrics, event approvals, and category visualization.
 */
$pageTitle = "Admin Dashboard";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');
$admin = currentUser();

// Handle quick approval/rejection from dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $evId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
    $action = $_POST['admin_action'] ?? '';

    if ($evId && in_array($action, ['approve', 'reject'])) {
        $newStatus = ($action === 'approve') ? 'approved' : 'rejected';
        $stmtUpdate = $pdo->prepare("UPDATE events SET status = ? WHERE id = ?");
        $stmtUpdate->execute([$newStatus, $evId]);

        setFlash('success', "Event #{$evId} has been successfully " . ucfirst($newStatus) . ".");
        header('Location: admin_dashboard.php');
        exit();
    }
}

// Fetch 5 core metrics
$totalStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$totalClubs = $pdo->query("SELECT COUNT(*) FROM clubs")->fetchColumn();
$totalEvents = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$pendingEventsCount = $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'pending'")->fetchColumn();
$totalRegistrations = $pdo->query("SELECT COUNT(*) FROM registrations WHERE status = 'confirmed'")->fetchColumn();

// Fetch pending events for immediate review
$stmtPending = $pdo->query("
    SELECT e.*, c.club_name, u.name AS organizer_name 
    FROM events e 
    JOIN clubs c ON e.club_id = c.id 
    JOIN users u ON c.organizer_id = u.id 
    WHERE e.status = 'pending' 
    ORDER BY e.created_at ASC
");
$pendingList = $stmtPending->fetchAll();

// Category distribution for Canvas chart
$stmtCat = $pdo->query("
    SELECT category, COUNT(*) as count 
    FROM events 
    GROUP BY category
");
$catCounts = $stmtCat->fetchAll(PDO::FETCH_KEY_PAIR);
$allCats = ['Technical' => 0, 'Cultural' => 0, 'Sports' => 0, 'Workshop' => 0, 'Competition' => 0, 'Seminar' => 0];
$chartData = array_merge($allCats, $catCounts);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Campus Administrator Portal</h1>
            <p class="page-subtitle">Centralized oversight of college clubs, student event registrations, and moderation.</p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="manage_events.php" class="btn btn-primary btn-sm">&#9989; Event Approvals (<?= $pendingEventsCount ?>)</a>
            <a href="manage_clubs.php" class="btn btn-secondary btn-sm">&#127979; Manage Clubs</a>
        </div>
    </div>

    <!-- 5 KPI Cards -->
    <div class="dash-kpis">
        <div class="kpi-card">
            <div class="kpi-icon">&#127891;</div>
            <div class="kpi-info">
                <h4>Total Students</h4>
                <div class="number"><?= number_format($totalStudents) ?></div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon info">&#127979;</div>
            <div class="kpi-info">
                <h4>Active Clubs</h4>
                <div class="number"><?= number_format($totalClubs) ?></div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon">&#128197;</div>
            <div class="kpi-info">
                <h4>Total Events</h4>
                <div class="number"><?= number_format($totalEvents) ?></div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon warning">&#9203;</div>
            <div class="kpi-info">
                <h4>Pending Review</h4>
                <div class="number" style="color: #d97706;"><?= number_format($pendingEventsCount) ?></div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon success">&#127915;</div>
            <div class="kpi-info">
                <h4>Registrations</h4>
                <div class="number" style="color: #059669;"><?= number_format($totalRegistrations) ?></div>
            </div>
        </div>
    </div>

    <!-- Layout: Pending Queue + Category Stats Chart -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-top: 2rem;" class="admin-dash-grid">
        <!-- Pending Events Queue -->
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2 style="font-size: 1.25rem; color: var(--secondary);">Events Awaiting Administrative Approval</h2>
                <span class="role-tag" style="background: var(--warning);"><?= $pendingEventsCount ?> Pending</span>
            </div>

            <?php if (!empty($pendingList)): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Event Details</th>
                                <th>Club / Organizer</th>
                                <th>Schedule</th>
                                <th>Venue</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingList as $ev): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($ev['title']) ?></strong><br>
                                        <span class="role-tag"><?= e($ev['category']) ?></span>
                                    </td>
                                    <td>
                                        <?= e($ev['club_name']) ?><br>
                                        <small style="color: var(--text-muted);">Lead: <?= e($ev['organizer_name']) ?></small>
                                    </td>
                                    <td>
                                        <?= date('M d, Y', strtotime($ev['event_date'])) ?><br>
                                        <small style="color: var(--text-muted);"><?= date('h:i A', strtotime($ev['event_time'])) ?></small>
                                    </td>
                                    <td><?= e($ev['venue']) ?></td>
                                    <td>
                                        <form method="POST" action="admin_dashboard.php" style="display: flex; gap: 0.35rem;">
                                            <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                                            <button type="submit" name="admin_action" value="approve" class="btn btn-success btn-sm" onclick="return confirmAction('Approve and publish this event?');">
                                                &#10004; Approve
                                            </button>
                                            <button type="submit" name="admin_action" value="reject" class="btn btn-danger btn-sm" onclick="return confirmAction('Reject this event submission?');">
                                                &#10008; Reject
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius); padding: 3rem; text-align: center;">
                    <div style="font-size: 2.5rem; color: var(--success); margin-bottom: 0.5rem;">&#10004;</div>
                    <h3 style="color: var(--secondary); margin-bottom: 0.25rem;">All Caught Up!</h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">There are no event proposals awaiting review at this time.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Category Breakdown Visualization -->
        <div>
            <div style="background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius); padding: 1.5rem; box-shadow: var(--shadow);">
                <h3 style="font-size: 1.1rem; color: var(--secondary); margin-bottom: 0.5rem;">Event Distribution by Category</h3>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1rem;">Visual analytics rendered via HTML5 Canvas</p>

                <canvas id="adminStatsCanvas" width="340" height="240" data-chart-data='<?= json_encode($chartData) ?>' style="max-width: 100%; height: auto;"></canvas>

                <div style="margin-top: 1rem; border-top: 1px solid var(--border); padding-top: 0.75rem;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.3rem;">
                        <span>Technical:</span> <strong><?= $chartData['Technical'] ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.3rem;">
                        <span>Cultural:</span> <strong><?= $chartData['Cultural'] ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.3rem;">
                        <span>Sports:</span> <strong><?= $chartData['Sports'] ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.3rem;">
                        <span>Workshops:</span> <strong><?= $chartData['Workshop'] ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem;">
                        <span>Seminars &amp; Competitions:</span> <strong><?= $chartData['Seminar'] + $chartData['Competition'] ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media (max-width: 900px) {
    .admin-dash-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
