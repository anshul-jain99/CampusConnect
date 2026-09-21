<?php
/**
 * CampusConnect - Organizer: Manage Events
 * View, edit, delete, and inspect registrations for organizer's events.
 */
$pageTitle = "Manage Events";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('organizer');
$organizer = currentUser();

// Fetch clubs managed by this organizer
$stmtClubs = $pdo->prepare("SELECT id FROM clubs WHERE organizer_id = ?");
$stmtClubs->execute([$organizer['id']]);
$clubIds = $stmtClubs->fetchAll(PDO::FETCH_COLUMN);

// Handle Event Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_delete_event'])) {
    $delId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
    if ($delId && !empty($clubIds)) {
        $inClause = implode(',', array_fill(0, count($clubIds), '?'));
        $stmtDel = $pdo->prepare("DELETE FROM events WHERE id = ? AND club_id IN ($inClause)");
        $stmtDel->execute(array_merge([$delId], $clubIds));

        setFlash('info', 'Event deleted successfully.');
        header('Location: manage_events.php');
        exit();
    }
}

// Fetch events
$events = [];
if (!empty($clubIds)) {
    $inClause = implode(',', array_fill(0, count($clubIds), '?'));
    $stmt = $pdo->prepare("
        SELECT e.*, c.club_name,
               (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id AND r.status = 'confirmed') AS attendee_count
        FROM events e
        JOIN clubs c ON e.club_id = c.id
        WHERE e.club_id IN ($inClause)
        ORDER BY e.event_date DESC
    ");
    $stmt->execute($clubIds);
    $events = $stmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Manage Club Events</h1>
            <p class="page-subtitle">Track publication status, update event agendas, and inspect registered participants.</p>
        </div>
        <a href="create_event.php" class="btn btn-primary btn-sm">&#43; Create New Event</a>
    </div>

    <?php if (!empty($events)): ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Event Title</th>
                        <th>Club</th>
                        <th>Category</th>
                        <th>Date &amp; Venue</th>
                        <th>Capacity</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $ev): ?>
                        <tr>
                            <td>
                                <strong><?= e($ev['title']) ?></strong>
                            </td>
                            <td><?= e($ev['club_name']) ?></td>
                            <td><span class="role-tag"><?= e($ev['category']) ?></span></td>
                            <td>
                                <?= date('M d, Y', strtotime($ev['event_date'])) ?><br>
                                <small style="color: var(--text-muted);"><?= e($ev['venue']) ?></small>
                            </td>
                            <td>
                                <strong><?= $ev['attendee_count'] ?></strong> / <?= $ev['max_participants'] ?>
                            </td>
                            <td>
                                <?= date('M d, Y', strtotime($ev['registration_deadline'])) ?>
                            </td>
                            <td>
                                <?php if ($ev['status'] === 'approved'): ?>
                                    <span class="event-status-tag status-approved" style="position: static;">Approved &amp; Live</span>
                                <?php elseif ($ev['status'] === 'pending'): ?>
                                    <span class="event-status-tag status-pending" style="position: static;">Pending Review</span>
                                <?php elseif ($ev['status'] === 'rejected'): ?>
                                    <span class="event-status-tag status-rejected" style="position: static;">Rejected</span>
                                <?php else: ?>
                                    <span class="event-status-tag status-completed" style="position: static;">Completed</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.4rem; align-items: center;">
                                    <a href="participants.php?event_id=<?= $ev['id'] ?>" class="btn btn-secondary btn-sm" title="View Roster">
                                        &#128101; (<?= $ev['attendee_count'] ?>)
                                    </a>
                                    <a href="edit_event.php?id=<?= $ev['id'] ?>" class="btn btn-secondary btn-sm" title="Edit Event">
                                        &#9998;
                                    </a>
                                    <form method="POST" action="manage_events.php" style="display: inline;">
                                        <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                                        <button type="submit" name="action_delete_event" class="btn btn-danger btn-sm" onclick="return confirmAction('Are you sure you want to delete this event? All associated registrations will also be removed.');" title="Delete">
                                            &#128465;
                                        </button>
                                    </form>
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
            <h3 style="color: var(--secondary); margin-bottom: 0.5rem;">No Events Found</h3>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem;">You haven't posted any events for your club yet.</p>
            <a href="create_event.php" class="btn btn-primary">&#43; Create Your First Event</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
