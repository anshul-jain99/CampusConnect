<?php
/**
 * CampusConnect - Organizer: Edit Event
 */
$pageTitle = "Edit Event";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('organizer');
$organizer = currentUser();

$eventId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$eventId) {
    setFlash('error', 'Invalid event selected.');
    header('Location: manage_events.php');
    exit();
}

// Ensure the event belongs to this organizer's clubs
$stmt = $pdo->prepare("
    SELECT e.* 
    FROM events e 
    JOIN clubs c ON e.club_id = c.id 
    WHERE e.id = ? AND c.organizer_id = ?
");
$stmt->execute([$eventId, $organizer['id']]);
$event = $stmt->fetch();

if (!$event) {
    setFlash('error', 'Event not found or you do not have permission to edit it.');
    header('Location: manage_events.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? 'Technical');
    $eventDate = trim($_POST['event_date'] ?? '');
    $eventTime = trim($_POST['event_time'] ?? '');
    $venue = trim($_POST['venue'] ?? '');
    $maxParticipants = filter_input(INPUT_POST, 'max_participants', FILTER_VALIDATE_INT) ?: 100;
    $deadline = trim($_POST['registration_deadline'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($title) || empty($eventDate) || empty($eventTime) || empty($venue) || empty($deadline) || empty($description)) {
        $error = 'Please fill out all required fields.';
    } elseif ($deadline > $eventDate) {
        $error = 'Registration deadline cannot be after the event date.';
    } else {
        try {
            $stmtUpdate = $pdo->prepare("
                UPDATE events 
                SET title = ?, category = ?, event_date = ?, event_time = ?, venue = ?, 
                    max_participants = ?, registration_deadline = ?, description = ?
                WHERE id = ?
            ");
            $stmtUpdate->execute([
                $title, $category, $eventDate, $eventTime, $venue,
                $maxParticipants, $deadline, $description, $eventId
            ]);

            setFlash('success', 'Event updated successfully!');
            header('Location: manage_events.php');
            exit();
        } catch (PDOException $e) {
            $error = 'Failed to update event: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 750px;">
    <div class="page-header">
        <div>
            <h1 class="page-title">Edit Event Details</h1>
            <p class="page-subtitle">Modifying: <strong><?= e($event['title']) ?></strong></p>
        </div>
        <a href="manage_events.php" class="btn btn-secondary btn-sm">&larr; Back to Events</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="flash-alert flash-error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="form-card" style="max-width: 100%;">
        <form method="POST" action="edit_event.php?id=<?= $eventId ?>">
            <div class="form-group">
                <label class="form-label" for="evTitle">Event Title *</label>
                <input type="text" name="title" id="evTitle" class="form-control" value="<?= e($event['title']) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="evCategory">Event Category *</label>
                <select name="category" id="evCategory" class="form-control" required>
                    <?php 
                    $cats = ['Technical', 'Cultural', 'Sports', 'Workshop', 'Competition', 'Seminar'];
                    foreach ($cats as $cat): ?>
                        <option value="<?= $cat ?>" <?= $event['category'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="evDate">Event Date *</label>
                    <input type="date" name="event_date" id="evDate" class="form-control" value="<?= e($event['event_date']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="evTime">Event Time *</label>
                    <input type="time" name="event_time" id="evTime" class="form-control" value="<?= e($event['event_time']) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="evVenue">Venue / Campus Location *</label>
                <input type="text" name="venue" id="evVenue" class="form-control" value="<?= e($event['venue']) ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="evCapacity">Max Participants (Capacity) *</label>
                    <input type="number" name="max_participants" id="evCapacity" class="form-control" min="5" max="1000" value="<?= e($event['max_participants']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="evDeadline">Registration Deadline *</label>
                    <input type="date" name="registration_deadline" id="evDeadline" class="form-control" value="<?= e($event['registration_deadline']) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="evDesc">Complete Event Description &amp; Agenda *</label>
                <textarea name="description" id="evDesc" class="form-control" rows="6" required><?= e($event['description']) ?></textarea>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary btn-lg" style="flex: 1;">
                    Save Changes
                </button>
                <a href="manage_events.php" class="btn btn-secondary btn-lg">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
