<?php
/**
 * CampusConnect - Student: My Registrations
 * Allows viewing all registered events and cancelling if needed.
 */
$pageTitle = "My Registrations";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('student');
$student = currentUser();

// Handle Cancellation Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_cancel'])) {
    $regId = filter_input(INPUT_POST, 'registration_id', FILTER_VALIDATE_INT);
    if ($regId) {
        try {
            $stmtCancel = $pdo->prepare("
                UPDATE registrations 
                SET status = 'cancelled' 
                WHERE id = ? AND student_id = ?
            ");
            $stmtCancel->execute([$regId, $student['id']]);

            setFlash('info', 'Your event registration has been successfully cancelled.');
            header('Location: my_registrations.php');
            exit();
        } catch (PDOException $e) {
            setFlash('error', 'Could not cancel registration. Please try again.');
        }
    }
}

// Fetch all registrations for this student
$stmt = $pdo->prepare("
    SELECT r.id AS reg_id, r.registration_date, r.status AS reg_status,
           e.id AS event_id, e.title, e.category, e.event_date, e.event_time, e.venue,
           c.club_name
    FROM registrations r
    JOIN events e ON r.event_id = e.id
    JOIN clubs c ON e.club_id = c.id
    WHERE r.student_id = ?
    ORDER BY r.registration_date DESC
");
$stmt->execute([$student['id']]);
$registrations = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">My Registered Events</h1>
            <p class="page-subtitle">Track your event passes, schedules, and participation history.</p>
        </div>
        <a href="../events.php" class="btn btn-primary btn-sm">&#128269; Register for More Events</a>
    </div>

    <?php if (!empty($registrations)): ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Pass / Reg ID</th>
                        <th>Event Title</th>
                        <th>Category</th>
                        <th>Organizer</th>
                        <th>Schedule</th>
                        <th>Venue</th>
                        <th>Registration Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $reg): ?>
                        <?php
                        $isPast = strtotime($reg['event_date']) < strtotime(date('Y-m-d'));
                        ?>
                        <tr>
                            <td>
                                <code>#CC-REG-<?= str_pad($reg['reg_id'], 4, '0', STR_PAD_LEFT) ?></code><br>
                                <small style="color: var(--text-muted);"><?= date('M d, Y', strtotime($reg['registration_date'])) ?></small>
                            </td>
                            <td>
                                <strong><a href="../event_details.php?id=<?= $reg['event_id'] ?>"><?= e($reg['title']) ?></a></strong>
                            </td>
                            <td>
                                <span class="role-tag"><?= e($reg['category']) ?></span>
                            </td>
                            <td><?= e($reg['club_name']) ?></td>
                            <td>
                                <?= date('D, M d, Y', strtotime($reg['event_date'])) ?><br>
                                <small style="color: var(--text-muted);"><?= date('h:i A', strtotime($reg['event_time'])) ?></small>
                            </td>
                            <td><?= e($reg['venue']) ?></td>
                            <td>
                                <?php if ($reg['reg_status'] === 'confirmed'): ?>
                                    <?php if ($isPast): ?>
                                        <span class="event-status-tag status-completed" style="position: static;">Attended</span>
                                    <?php else: ?>
                                        <span class="event-status-tag status-approved" style="position: static;">Confirmed</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="event-status-tag status-rejected" style="position: static;">Cancelled</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <a href="../event_details.php?id=<?= $reg['event_id'] ?>" class="btn btn-secondary btn-sm">View</a>
                                    
                                    <?php if ($reg['reg_status'] === 'confirmed' && !$isPast): ?>
                                        <form method="POST" action="my_registrations.php" style="display: inline;">
                                            <input type="hidden" name="registration_id" value="<?= $reg['reg_id'] ?>">
                                            <button type="submit" name="action_cancel" class="btn btn-danger btn-sm" onclick="return confirmAction('Are you sure you want to cancel your registration for this event?');">
                                                Cancel
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 4rem 1.5rem; background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius);">
            <div style="font-size: 3rem; margin-bottom: 0.5rem;">&#127915;</div>
            <h3 style="color: var(--secondary); margin-bottom: 0.5rem;">No Registrations Found</h3>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem;">You haven't signed up for any events yet.</p>
            <a href="../events.php" class="btn btn-primary">Browse All Campus Events &rarr;</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
