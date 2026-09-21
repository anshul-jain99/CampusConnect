<?php
/**
 * CampusConnect - Organizer Dashboard
 */
$pageTitle = "Organizer Dashboard";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('organizer');
$organizer = currentUser();

// Fetch organizer's managed clubs
$stmtClubs = $pdo->prepare("SELECT * FROM clubs WHERE organizer_id = ?");
$stmtClubs->execute([$organizer['id']]);
$myClubs = $stmtClubs->fetchAll();
$clubIds = array_column($myClubs, 'id');

$totalClubEvents = 0;
$totalRegistrations = 0;
$pendingEvents = 0;
$approvedEvents = 0;
$recentEvents = [];

if (!empty($clubIds)) {
    $inClause = implode(',', array_fill(0, count($clubIds), '?'));

    // Total events
    $stmt1 = $pdo->prepare("SELECT COUNT(*) FROM events WHERE club_id IN ($inClause)");
    $stmt1->execute($clubIds);
    $totalClubEvents = $stmt1->fetchColumn();

    // Pending events
    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM events WHERE club_id IN ($inClause) AND status = 'pending'");
    $stmt2->execute($clubIds);
    $pendingEvents = $stmt2->fetchColumn();

    // Approved events
    $stmt3 = $pdo->prepare("SELECT COUNT(*) FROM events WHERE club_id IN ($inClause) AND status = 'approved'");
    $stmt3->execute($clubIds);
    $approvedEvents = $stmt3->fetchColumn();

    // Total registrations across club events
    $stmt4 = $pdo->prepare("
        SELECT COUNT(r.id) 
        FROM registrations r 
        JOIN events e ON r.event_id = e.id 
        WHERE e.club_id IN ($inClause) AND r.status = 'confirmed'
    ");
    $stmt4->execute($clubIds);
    $totalRegistrations = $stmt4->fetchColumn();

    // Recent events list
    $stmt5 = $pdo->prepare("
        SELECT e.*, c.club_name,
               (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id AND r.status = 'confirmed') AS attendee_count
        FROM events e
        JOIN clubs c ON e.club_id = c.id
        WHERE e.club_id IN ($inClause)
        ORDER BY e.created_at DESC
        LIMIT 6
    ");
    $stmt5->execute($clubIds);
    $recentEvents = $stmt5->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Organizer Workspace</h1>
            <p class="page-subtitle">
                Lead: <strong><?= e($organizer['name']) ?></strong> &bull; 
                Managing: 
                <?php 
                if (!empty($myClubs)) {
                    echo implode(', ', array_map(function($c) { return e($c['club_name']); }, $myClubs));
                } else {
                    echo 'No clubs currently assigned';
                }
                ?>
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="create_event.php" class="btn btn-primary btn-sm">&#43; Create New Event</a>
            <a href="manage_events.php" class="btn btn-secondary btn-sm">&#9881; Manage Events</a>
        </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="dash-kpis">
        <div class="kpi-card">
            <div class="kpi-icon">&#128197;</div>
            <div class="kpi-info">
                <h4>Total Events</h4>
                <div class="number"><?= number_format($totalClubEvents) ?></div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon success">&#128101;</div>
            <div class="kpi-info">
                <h4>Total Registrations</h4>
                <div class="number"><?= number_format($totalRegistrations) ?></div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon warning">&#9203;</div>
            <div class="kpi-info">
                <h4>Pending Approvals</h4>
                <div class="number"><?= number_format($pendingEvents) ?></div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon info">&#10004;</div>
            <div class="kpi-info">
                <h4>Approved &amp; Live</h4>
                <div class="number"><?= number_format($approvedEvents) ?></div>
            </div>
        </div>
    </div>

    <?php if ($pendingEvents > 0): ?>
        <div class="flash-alert flash-warning" style="margin-bottom: 2rem;">
            <span>&#9888; You have <strong><?= $pendingEvents ?></strong> event(s) waiting for College Admin approval before publishing.</span>
            <a href="manage_events.php" class="btn btn-secondary btn-sm" style="margin-left: auto;">Review Events</a>
        </div>
    <?php endif; ?>

    <!-- Recent Club Events -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2 style="font-size: 1.3rem; color: var(--secondary);">Recent Club Events</h2>
        <a href="manage_events.php" style="font-size: 0.88rem; font-weight: 600;">View All &rarr;</a>
    </div>

    <?php if (!empty($recentEvents)): ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Event Title</th>
                        <th>Category</th>
                        <th>Date &amp; Time</th>
                        <th>Venue</th>
                        <th>Registrations</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentEvents as $ev): ?>
                        <tr>
                            <td>
                                <strong><?= e($ev['title']) ?></strong><br>
                                <small style="color: var(--text-muted);"><?= e($ev['club_name']) ?></small>
                            </td>
                            <td><span class="role-tag"><?= e($ev['category']) ?></span></td>
                            <td>
                                <?= date('M d, Y', strtotime($ev['event_date'])) ?><br>
                                <small style="color: var(--text-muted);"><?= date('h:i A', strtotime($ev['event_time'])) ?></small>
                            </td>
                            <td><?= e($ev['venue']) ?></td>
                            <td>
                                <strong><?= $ev['attendee_count'] ?></strong> / <?= $ev['max_participants'] ?>
                                <a href="participants.php?event_id=<?= $ev['id'] ?>" style="font-size: 0.78rem; display: block; color: var(--primary);">View Roster &rarr;</a>
                            </td>
                            <td>
                                <?php if ($ev['status'] === 'approved'): ?>
                                    <span class="event-status-tag status-approved" style="position: static;">Approved</span>
                                <?php elseif ($ev['status'] === 'pending'): ?>
                                    <span class="event-status-tag status-pending" style="position: static;">Pending Approval</span>
                                <?php elseif ($ev['status'] === 'rejected'): ?>
                                    <span class="event-status-tag status-rejected" style="position: static;">Rejected</span>
                                <?php else: ?>
                                    <span class="event-status-tag status-completed" style="position: static;">Completed</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.4rem;">
                                    <a href="edit_event.php?id=<?= $ev['id'] ?>" class="btn btn-secondary btn-sm">&#9998; Edit</a>
                                    <a href="participants.php?event_id=<?= $ev['id'] ?>" class="btn btn-primary btn-sm">&#128101; Participants</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 4rem 1.5rem; background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius);">
            <div style="font-size: 3rem; margin-bottom: 0.5rem;">&#128197;</div>
            <h3 style="color: var(--secondary); margin-bottom: 0.5rem;">No Events Created Yet</h3>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Start by creating your club's first upcoming workshop, competition, or fest.</p>
            <a href="create_event.php" class="btn btn-primary">&#43; Create Your First Event</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
