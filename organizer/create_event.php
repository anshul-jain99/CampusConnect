<?php
/**
 * CampusConnect - Organizer: Create New Event
 * Newly created events enter 'pending' status awaiting Admin review.
 */
$pageTitle = "Create Event";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('organizer');
$organizer = currentUser();

// Fetch clubs managed by this organizer
$stmtClubs = $pdo->prepare("SELECT * FROM clubs WHERE organizer_id = ?");
$stmtClubs->execute([$organizer['id']]);
$clubs = $stmtClubs->fetchAll();

if (empty($clubs)) {
    setFlash('error', 'You do not have any registered clubs yet. Please contact the administrator.');
    header('Location: organizer_dashboard.php');
    exit();
}

$error = '';
$title = '';
$clubId = $clubs[0]['id'] ?? 0;
$category = 'Technical';
$eventDate = '';
$eventTime = '';
$venue = '';
$maxParticipants = 100;
$deadline = '';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $clubId = filter_input(INPUT_POST, 'club_id', FILTER_VALIDATE_INT);
    $category = trim($_POST['category'] ?? 'Technical');
    $eventDate = trim($_POST['event_date'] ?? '');
    $eventTime = trim($_POST['event_time'] ?? '');
    $venue = trim($_POST['venue'] ?? '');
    $maxParticipants = filter_input(INPUT_POST, 'max_participants', FILTER_VALIDATE_INT) ?: 100;
    $deadline = trim($_POST['registration_deadline'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Validation
    if (empty($title) || empty($eventDate) || empty($eventTime) || empty($venue) || empty($deadline) || empty($description)) {
        $error = 'Please fill out all required fields.';
    } elseif ($deadline > $eventDate) {
        $error = 'Registration deadline cannot be after the event date!';
    } elseif ($eventDate < date('Y-m-d')) {
        $error = 'Event date cannot be in the past.';
    } else {
        try {
            $stmtInsert = $pdo->prepare("
                INSERT INTO events (club_id, title, description, category, event_date, event_time, venue, max_participants, registration_deadline, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $stmtInsert->execute([
                $clubId,
                $title,
                $description,
                $category,
                $eventDate,
                $eventTime,
                $venue,
                $maxParticipants,
                $deadline
            ]);

            setFlash('success', "Event '{$title}' submitted successfully! It is now pending Administrator approval.");
            header('Location: manage_events.php');
            exit();
        } catch (PDOException $e) {
            $error = 'Failed to submit event: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 750px;">
    <div class="page-header">
        <div>
            <h1 class="page-title">Submit New Campus Event</h1>
            <p class="page-subtitle">Events will be submitted for Admin approval before becoming public.</p>
        </div>
        <a href="manage_events.php" class="btn btn-secondary btn-sm">&larr; Back to Events</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="flash-alert flash-error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="form-card" style="max-width: 100%;">
        <form method="POST" action="create_event.php">
            <div class="form-group">
                <label class="form-label" for="evTitle">Event Title *</label>
                <input type="text" name="title" id="evTitle" class="form-control" placeholder="e.g., National Web Development Hackathon 2026" value="<?= e($title) ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="evClub">Organizing Club / Society *</label>
                    <select name="club_id" id="evClub" class="form-control" required>
                        <?php foreach ($clubs as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $clubId == $c['id'] ? 'selected' : '' ?>><?= e($c['club_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="evCategory">Event Category *</label>
                    <select name="category" id="evCategory" class="form-control" required>
                        <option value="Technical" <?= $category === 'Technical' ? 'selected' : '' ?>>Technical</option>
                        <option value="Cultural" <?= $category === 'Cultural' ? 'selected' : '' ?>>Cultural</option>
                        <option value="Sports" <?= $category === 'Sports' ? 'selected' : '' ?>>Sports</option>
                        <option value="Workshop" <?= $category === 'Workshop' ? 'selected' : '' ?>>Workshop</option>
                        <option value="Competition" <?= $category === 'Competition' ? 'selected' : '' ?>>Competition</option>
                        <option value="Seminar" <?= $category === 'Seminar' ? 'selected' : '' ?>>Seminar</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="evDate">Event Date *</label>
                    <input type="date" name="event_date" id="evDate" class="form-control" value="<?= e($eventDate) ?>" required min="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="evTime">Event Time *</label>
                    <input type="time" name="event_time" id="evTime" class="form-control" value="<?= e($eventTime) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="evVenue">Venue / Campus Location *</label>
                <input type="text" name="venue" id="evVenue" class="form-control" placeholder="e.g., Auditorium Hall B or Computer Lab 4" value="<?= e($venue) ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="evCapacity">Max Participants (Capacity) *</label>
                    <input type="number" name="max_participants" id="evCapacity" class="form-control" min="5" max="1000" value="<?= e($maxParticipants) ?>" required>
                    <span class="form-help">Registration will automatically close when this limit is reached.</span>
                </div>
                <div class="form-group">
                    <label class="form-label" for="evDeadline">Registration Deadline *</label>
                    <input type="date" name="registration_deadline" id="evDeadline" class="form-control" value="<?= e($deadline) ?>" required min="<?= date('Y-m-d') ?>">
                    <span class="form-help">Last day for students to submit registrations.</span>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="evDesc">Complete Event Description &amp; Agenda *</label>
                <textarea name="description" id="evDesc" class="form-control" rows="5" placeholder="Provide complete event details, rules, eligibility, prizes, and schedule..." required><?= e($description) ?></textarea>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary btn-lg" style="flex: 1;">
                    &#128640; Submit for Approval
                </button>
                <a href="manage_events.php" class="btn btn-secondary btn-lg">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
