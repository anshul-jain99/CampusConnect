<?php
/**
 * CampusConnect - Organizer: Event Participants Roster
 * Displays registered attendees with print / export functionality.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('organizer');
$organizer = currentUser();

$eventId = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
if (!$eventId) {
    setFlash('error', 'Please select an event to view its participant roster.');
    header('Location: manage_events.php');
    exit();
}

// Verify ownership
$stmtEv = $pdo->prepare("
    SELECT e.*, c.club_name 
    FROM events e 
    JOIN clubs c ON e.club_id = c.id 
    WHERE e.id = ? AND c.organizer_id = ?
");
$stmtEv->execute([$eventId, $organizer['id']]);
$event = $stmtEv->fetch();

if (!$event) {
    setFlash('error', 'Event not found or unauthorized.');
    header('Location: manage_events.php');
    exit();
}

$pageTitle = "Participants: " . $event['title'];

// Fetch all registered students for this event
$stmtRoster = $pdo->prepare("
    SELECT r.id AS reg_id, r.registration_date, r.status AS reg_status,
           u.name AS student_name, u.email, u.department, u.phone, u.enrollment_no
    FROM registrations r
    JOIN users u ON r.student_id = u.id
    WHERE r.event_id = ? AND r.status = 'confirmed'
    ORDER BY r.registration_date ASC
");
$stmtRoster->execute([$eventId]);
$participants = $stmtRoster->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Participant Attendance Roster</h1>
            <p class="page-subtitle">
                Event: <strong><?= e($event['title']) ?></strong> &bull; <?= date('M d, Y', strtotime($event['event_date'])) ?> (<?= e($event['venue']) ?>)
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button onclick="window.print()" class="btn btn-secondary btn-sm">&#128438; Print / Export Roster</button>
            <a href="manage_events.php" class="btn btn-primary btn-sm">&larr; Back to Events</a>
        </div>
    </div>

    <!-- Summary Box -->
    <div class="dash-kpis" style="margin-bottom: 1.5rem;">
        <div class="kpi-card">
            <div class="kpi-icon success">&#128101;</div>
            <div class="kpi-info">
                <h4>Confirmed Attendees</h4>
                <div class="number"><?= count($participants) ?></div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">&#127915;</div>
            <div class="kpi-info">
                <h4>Capacity Allocation</h4>
                <div class="number"><?= count($participants) ?> / <?= $event['max_participants'] ?></div>
            </div>
        </div>
    </div>

    <?php if (!empty($participants)): ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Reg ID</th>
                        <th>Student Name</th>
                        <th>Enrollment No</th>
                        <th>Department</th>
                        <th>Email Address</th>
                        <th>Phone</th>
                        <th>Registered On</th>
                        <th>Attendance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($participants as $p): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><code>#CC-REG-<?= str_pad($p['reg_id'], 4, '0', STR_PAD_LEFT) ?></code></td>
                            <td><strong><?= e($p['student_name']) ?></strong></td>
                            <td><?= e($p['enrollment_no'] ?: 'N/A') ?></td>
                            <td><?= e($p['department']) ?></td>
                            <td><a href="mailto:<?= e($p['email']) ?>"><?= e($p['email']) ?></a></td>
                            <td><?= e($p['phone'] ?: 'N/A') ?></td>
                            <td><?= date('M d, Y H:i', strtotime($p['registration_date'])) ?></td>
                            <td>
                                <span style="display: inline-block; width: 14px; height: 14px; border: 2px solid #94a3b8; border-radius: 3px;"></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 4rem 1.5rem; background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius);">
            <div style="font-size: 3rem; margin-bottom: 0.5rem;">&#128101;</div>
            <h3 style="color: var(--secondary); margin-bottom: 0.5rem;">No Participants Registered Yet</h3>
            <p style="color: var(--text-muted);">As soon as students register for this event, their names and contact details will appear here.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
