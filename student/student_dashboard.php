<?php
/**
 * CampusConnect - Student Dashboard
 */
$pageTitle = "Student Dashboard";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('student');
$student = currentUser();

// Fetch statistics
$myRegCount = 0;
$totalAvailableEvents = 0;
$myUpcomingEvents = [];
$recentEvents = [];

try {
    // Registered count
    $stmt1 = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE student_id = ? AND status = 'confirmed'");
    $stmt1->execute([$student['id']]);
    $myRegCount = $stmt1->fetchColumn();

    // Available upcoming events count
    $stmt2 = $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'approved' AND event_date >= CURDATE()");
    $totalAvailableEvents = $stmt2->fetchColumn();

    // Student's upcoming registered events
    $stmt3 = $pdo->prepare("
        SELECT e.*, c.club_name, r.id AS reg_id, r.registration_date 
        FROM registrations r 
        JOIN events e ON r.event_id = e.id 
        JOIN clubs c ON e.club_id = c.id 
        WHERE r.student_id = ? AND r.status = 'confirmed' AND e.event_date >= CURDATE()
        ORDER BY e.event_date ASC
        LIMIT 5
    ");
    $stmt3->execute([$student['id']]);
    $myUpcomingEvents = $stmt3->fetchAll();

    // Recent campus events to discover
    $stmt4 = $pdo->prepare("
        SELECT e.*, c.club_name 
        FROM events e 
        JOIN clubs c ON e.club_id = c.id 
        WHERE e.status = 'approved' AND e.event_date >= CURDATE()
          AND e.id NOT IN (SELECT event_id FROM registrations WHERE student_id = ? AND status = 'confirmed')
        ORDER BY e.created_at DESC 
        LIMIT 4
    ");
    $stmt4->execute([$student['id']]);
    $recentEvents = $stmt4->fetchAll();

} catch (PDOException $e) {
    // Handle error
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <!-- Welcome Header -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Welcome back, <?= e($student['name']) ?>! &#128075;</h1>
            <p class="page-subtitle">
                Department of <?= e($student['department']) ?> &bull; Enrollment: <strong><?= e($student['enrollment_no'] ?: 'Not Set') ?></strong>
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="../events.php" class="btn btn-primary btn-sm">&#128269; Browse Events</a>
            <a href="my_registrations.php" class="btn btn-secondary btn-sm">&#127915; My Registrations</a>
        </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="dash-kpis">
        <div class="kpi-card">
            <div class="kpi-icon success">&#127915;</div>
            <div class="kpi-info">
                <h4>My Registrations</h4>
                <div class="number"><?= number_format($myRegCount) ?></div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon">&#128197;</div>
            <div class="kpi-info">
                <h4>Upcoming Events</h4>
                <div class="number"><?= number_format($totalAvailableEvents) ?></div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon info">&#9989;</div>
            <div class="kpi-info">
                <h4>Scheduled This Month</h4>
                <div class="number"><?= count($myUpcomingEvents) ?></div>
            </div>
        </div>
    </div>

    <!-- My Registered Events Table -->
    <div style="margin-bottom: 2.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h2 style="font-size: 1.3rem; color: var(--secondary);">My Upcoming Registered Events</h2>
            <a href="my_registrations.php" style="font-size: 0.88rem; font-weight: 600;">View All Registrations &rarr;</a>
        </div>

        <?php if (!empty($myUpcomingEvents)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Event Name</th>
                            <th>Category</th>
                            <th>Organizer Club</th>
                            <th>Date &amp; Time</th>
                            <th>Venue</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myUpcomingEvents as $ev): ?>
                            <tr>
                                <td>
                                    <strong><a href="../event_details.php?id=<?= $ev['id'] ?>"><?= e($ev['title']) ?></a></strong>
                                </td>
                                <td><span class="role-tag"><?= e($ev['category']) ?></span></td>
                                <td><?= e($ev['club_name']) ?></td>
                                <td>
                                    <?= date('M d, Y', strtotime($ev['event_date'])) ?><br>
                                    <small style="color: var(--text-muted);"><?= date('h:i A', strtotime($ev['event_time'])) ?></small>
                                </td>
                                <td><?= e($ev['venue']) ?></td>
                                <td><span class="event-status-tag status-approved" style="position: static;">Confirmed</span></td>
                                <td>
                                    <a href="../event_details.php?id=<?= $ev['id'] ?>" class="btn btn-secondary btn-sm">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 2.5rem; background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius);">
                <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1rem;">
                    You haven't registered for any upcoming events yet.
                </p>
                <a href="../events.php" class="btn btn-primary btn-sm">&#128269; Explore Upcoming Events</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Suggested Events You Might Like -->
    <?php if (!empty($recentEvents)): ?>
        <div>
            <h2 style="font-size: 1.3rem; color: var(--secondary); margin-bottom: 1rem;">New Events to Explore</h2>
            <div class="card-grid">
                <?php foreach ($recentEvents as $rev): ?>
                    <div class="card event-card-item">
                        <div class="event-card-banner" style="height: 110px; font-size: 2rem;">
                            <span class="event-category-tag"><?= e($rev['category']) ?></span>
                            <span>&#127891;</span>
                        </div>
                        <div class="card-body" style="padding: 1.25rem;">
                            <h4 class="event-card-title" style="font-size: 1.05rem; margin-bottom: 0.4rem;"><?= e($rev['title']) ?></h4>
                            <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 0.75rem;">
                                <?= date('M d, Y', strtotime($rev['event_date'])) ?> &bull; <?= e($rev['venue']) ?>
                            </p>
                            <a href="../event_details.php?id=<?= $rev['id'] ?>" class="btn btn-primary btn-sm btn-block">Quick Register</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
