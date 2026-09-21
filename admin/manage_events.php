<?php
/**
 * CampusConnect - Admin: Manage & Moderate All Events
 */
$pageTitle = "Moderate Events";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

// Handle status updates and deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? '';

    if ($eventId) {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE events SET status = 'approved' WHERE id = ?");
            $stmt->execute([$eventId]);
            setFlash('success', "Event #{$eventId} approved and published to the event catalog.");
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE events SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$eventId]);
            setFlash('warning', "Event #{$eventId} rejected.");
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
            $stmt->execute([$eventId]);
            setFlash('info', "Event #{$eventId} and all related registrations deleted.");
        }
        header('Location: manage_events.php');
        exit();
    }
}

$statusFilter = $_GET['status'] ?? 'all';
$sql = "
    SELECT e.*, c.club_name, u.name AS organizer_name,
           (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id AND r.status = 'confirmed') AS attendee_count
    FROM events e
    JOIN clubs c ON e.club_id = c.id
    JOIN users u ON c.organizer_id = u.id
";
$params = [];

if ($statusFilter !== 'all' && in_array($statusFilter, ['pending', 'approved', 'rejected', 'completed'])) {
    $sql .= " WHERE e.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY (e.status = 'pending') DESC, e.event_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Manage &amp; Moderate Events</h1>
            <p class="page-subtitle">Review event submissions, enforce guidelines, and publish campus activities.</p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="admin_dashboard.php" class="btn btn-secondary btn-sm">&larr; Admin Home</a>
        </div>
    </div>

    <!-- Filter Pills -->
    <div class="category-pills" style="margin-bottom: 1.5rem;">
        <a href="manage_events.php?status=all" class="category-pill <?= $statusFilter === 'all' ? 'active' : '' ?>">All Statuses</a>
        <a href="manage_events.php?status=pending" class="category-pill <?= $statusFilter === 'pending' ? 'active' : '' ?>">Pending Review</a>
        <a href="manage_events.php?status=approved" class="category-pill <?= $statusFilter === 'approved' ? 'active' : '' ?>">Approved</a>
        <a href="manage_events.php?status=rejected" class="category-pill <?= $statusFilter === 'rejected' ? 'active' : '' ?>">Rejected</a>
    </div>

    <?php if (!empty($events)): ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Event Title</th>
                        <th>Club / Lead</th>
                        <th>Category</th>
                        <th>Date &amp; Venue</th>
                        <th>Capacity</th>
                        <th>Status</th>
                        <th>Moderation Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $ev): ?>
                        <tr>
                            <td>#<?= $ev['id'] ?></td>
                            <td>
                                <strong><a href="../event_details.php?id=<?= $ev['id'] ?>" target="_blank"><?= e($ev['title']) ?></a></strong>
                            </td>
                            <td>
                                <?= e($ev['club_name']) ?><br>
                                <small style="color: var(--text-muted);"><?= e($ev['organizer_name']) ?></small>
                            </td>
                            <td><span class="role-tag"><?= e($ev['category']) ?></span></td>
                            <td>
                                <?= date('M d, Y', strtotime($ev['event_date'])) ?><br>
                                <small style="color: var(--text-muted);"><?= e($ev['venue']) ?></small>
                            </td>
                            <td>
                                <?= $ev['attendee_count'] ?> / <?= $ev['max_participants'] ?>
                            </td>
                            <td>
                                <?php if ($ev['status'] === 'approved'): ?>
                                    <span class="event-status-tag status-approved" style="position: static;">Approved</span>
                                <?php elseif ($ev['status'] === 'pending'): ?>
                                    <span class="event-status-tag status-pending" style="position: static;">Pending</span>
                                <?php elseif ($ev['status'] === 'rejected'): ?>
                                    <span class="event-status-tag status-rejected" style="position: static;">Rejected</span>
                                <?php else: ?>
                                    <span class="event-status-tag status-completed" style="position: static;">Completed</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" action="manage_events.php" style="display: flex; gap: 0.35rem; align-items: center;">
                                    <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                                    <?php if ($ev['status'] !== 'approved'): ?>
                                        <button type="submit" name="action" value="approve" class="btn btn-success btn-sm" title="Approve">&#10004; Approve</button>
                                    <?php endif; ?>
                                    <?php if ($ev['status'] !== 'rejected'): ?>
                                        <button type="submit" name="action" value="reject" class="btn btn-secondary btn-sm" title="Reject">&#10008; Reject</button>
                                    <?php endif; ?>
                                    <button type="submit" name="action" value="delete" class="btn btn-danger btn-sm" onclick="return confirmAction('Permanently delete this event?');" title="Delete">&#128465;</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 4rem; background: #fff; border: 1px solid var(--border); border-radius: var(--radius);">
            <p style="color: var(--text-muted); font-size: 1.05rem;">No events match the selected criteria.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
