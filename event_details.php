<?php
/**
 * CampusConnect - Event Details & Registration Handler
 * Handles detailed view and student registration submission with validation.
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$eventId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$eventId) {
    setFlash('error', 'Invalid event selected.');
    header('Location: events.php');
    exit();
}

// Fetch event details along with club and organizer contact
$stmt = $pdo->prepare("
    SELECT e.*, c.club_name, c.description AS club_desc, u.name AS organizer_name, u.email AS organizer_email
    FROM events e
    JOIN clubs c ON e.club_id = c.id
    JOIN users u ON c.organizer_id = u.id
    WHERE e.id = ?
");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    setFlash('error', 'Event not found.');
    header('Location: events.php');
    exit();
}

$pageTitle = $event['title'];
$user = currentUser();

// Registration capacity & deadline checks
$stmtReg = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE event_id = ? AND status = 'confirmed'");
$stmtReg->execute([$eventId]);
$confirmedCount = $stmtReg->fetchColumn();
$seatsLeft = max(0, $event['max_participants'] - $confirmedCount);

$today = date('Y-m-d');
$isDeadlinePassed = ($today > $event['registration_deadline']);
$isEventPassed = ($today > $event['event_date']);
$isCapacityFull = ($seatsLeft <= 0);

// Check if current user is registered
$isAlreadyRegistered = false;
$userRegistrationId = null;
if (isLoggedIn() && $user['role'] === 'student') {
    $stmtCheck = $pdo->prepare("SELECT id, status FROM registrations WHERE event_id = ? AND student_id = ?");
    $stmtCheck->execute([$eventId, $user['id']]);
    $existingReg = $stmtCheck->fetch();
    if ($existingReg && $existingReg['status'] === 'confirmed') {
        $isAlreadyRegistered = true;
        $userRegistrationId = $existingReg['id'];
    }
}

// Handle Event Registration Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_register'])) {
    if (!isLoggedIn()) {
        setFlash('warning', 'Please log in to register for this event.');
        header("Location: login.php?redirect=" . urlencode("event_details.php?id={$eventId}"));
        exit();
    }

    if ($user['role'] !== 'student') {
        setFlash('error', 'Only student accounts can register for campus events.');
        header("Location: event_details.php?id={$eventId}");
        exit();
    }

    if ($isDeadlinePassed) {
        setFlash('error', 'Registration deadline has passed for this event.');
        header("Location: event_details.php?id={$eventId}");
        exit();
    }

    if ($isCapacityFull) {
        setFlash('error', 'Sorry, registration capacity is full for this event.');
        header("Location: event_details.php?id={$eventId}");
        exit();
    }

    if ($isAlreadyRegistered) {
        setFlash('info', 'You are already registered for this event!');
        header("Location: student/my_registrations.php");
        exit();
    }

    try {
        // Insert or update registration
        $stmtInsert = $pdo->prepare("
            INSERT INTO registrations (event_id, student_id, registration_date, status) 
            VALUES (?, ?, NOW(), 'confirmed') 
            ON DUPLICATE KEY UPDATE status = 'confirmed', registration_date = NOW()
        ");
        $stmtInsert->execute([$eventId, $user['id']]);

        setFlash('success', "Registration successful! You are confirmed for '{$event['title']}'.");
        header("Location: student/my_registrations.php");
        exit();
    } catch (PDOException $e) {
        setFlash('error', 'An error occurred while saving your registration. Please try again.');
        header("Location: event_details.php?id={$eventId}");
        exit();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div style="margin-bottom: 1.5rem;">
        <a href="events.php" style="color: var(--text-muted); font-size: 0.9rem;">&larr; Back to Events Directory</a>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;" class="event-details-layout">
        <!-- Main Event Content -->
        <div>
            <div style="background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow);">
                <div class="event-card-banner" style="height: 180px;">
                    <span class="event-category-tag"><?= e($event['category']) ?></span>
                    <span style="font-size: 3.5rem; opacity: 0.9;">
                        <?php
                        $catIcons = [
                            'Technical'   => '&#128187;',
                            'Cultural'    => '&#127917;',
                            'Sports'      => '&#9917;',
                            'Workshop'    => '&#128295;',
                            'Competition' => '&#127942;',
                            'Seminar'     => '&#127891;'
                        ];
                        echo $catIcons[$event['category']] ?? '&#128197;';
                        ?>
                    </span>
                </div>

                <div style="padding: 2rem;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem;">
                        <h1 style="font-size: 1.85rem; color: var(--secondary);"><?= e($event['title']) ?></h1>
                        <span class="badge" style="padding: 0.35rem 0.85rem; border-radius: 20px; font-size: 0.82rem; font-weight: 600; background: var(--primary-light); color: var(--primary);">
                            <?= e($event['category']) ?>
                        </span>
                    </div>

                    <p style="font-size: 1rem; color: var(--text-main); white-space: pre-line; line-height: 1.8; margin-bottom: 2rem;">
                        <?= e($event['description']) ?>
                    </p>

                    <!-- Organizer / Club Section -->
                    <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 1.25rem;">
                        <h3 style="font-size: 1.1rem; color: var(--secondary); margin-bottom: 0.5rem;">Organized by <?= e($event['club_name']) ?></h3>
                        <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 0.5rem;">
                            <?= e($event['club_desc']) ?>
                        </p>
                        <p style="font-size: 0.85rem; color: var(--text-muted);">
                            <strong>Club Lead:</strong> <?= e($event['organizer_name']) ?> &bull; 
                            <a href="mailto:<?= e($event['organizer_email']) ?>"><?= e($event['organizer_email']) ?></a>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar / Action Card -->
        <div>
            <div style="background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius); padding: 1.75rem; box-shadow: var(--shadow); position: sticky; top: 90px;">
                <h3 style="font-size: 1.2rem; color: var(--secondary); margin-bottom: 1.25rem; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                    Event Schedule &amp; Venue
                </h3>

                <div class="event-meta" style="font-size: 0.95rem; gap: 0.9rem; margin-bottom: 1.75rem;">
                    <div class="event-meta-item">
                        <span style="font-size: 1.25rem;">&#128197;</span>
                        <div>
                            <strong>Event Date:</strong><br>
                            <?= date('l, F d, Y', strtotime($event['event_date'])) ?>
                        </div>
                    </div>
                    <div class="event-meta-item">
                        <span style="font-size: 1.25rem;">&#9200;</span>
                        <div>
                            <strong>Event Time:</strong><br>
                            <?= date('h:i A', strtotime($event['event_time'])) ?>
                        </div>
                    </div>
                    <div class="event-meta-item">
                        <span style="font-size: 1.25rem;">&#128205;</span>
                        <div>
                            <strong>Venue / Location:</strong><br>
                            <?= e($event['venue']) ?>
                        </div>
                    </div>
                    <div class="event-meta-item">
                        <span style="font-size: 1.25rem;">&#128336;</span>
                        <div>
                            <strong>Registration Deadline:</strong><br>
                            <?= date('F d, Y', strtotime($event['registration_deadline'])) ?>
                        </div>
                    </div>
                    <div class="event-meta-item">
                        <span style="font-size: 1.25rem;">&#128101;</span>
                        <div>
                            <strong>Participation Capacity:</strong><br>
                            <?= $confirmedCount ?> registered / <?= $event['max_participants'] ?> maximum
                            <div style="background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden; margin-top: 5px;">
                                <?php $fillPct = min(100, round(($confirmedCount / $event['max_participants']) * 100)); ?>
                                <div style="background: var(--primary); width: <?= $fillPct ?>%; height: 100%;"></div>
                            </div>
                            <span style="font-size: 0.78rem; color: var(--text-muted);"><?= $seatsLeft ?> seats remaining</span>
                        </div>
                    </div>
                </div>

                <!-- Registration Action Box -->
                <div style="border-top: 1px solid var(--border); padding-top: 1.25rem;">
                    <?php if ($isAlreadyRegistered): ?>
                        <div style="background: var(--success-bg); border: 1px solid #a7f3d0; border-radius: var(--radius-sm); padding: 1rem; text-align: center; margin-bottom: 1rem;">
                            <span style="color: #065f46; font-weight: 700;">&#10004; You Are Registered!</span>
                            <p style="font-size: 0.82rem; color: #065f46; margin-top: 0.25rem;">Your seat is confirmed.</p>
                        </div>
                        <a href="student/my_registrations.php" class="btn btn-secondary btn-block">View in My Registrations</a>

                    <?php elseif ($isEventPassed): ?>
                        <div style="background: #f1f5f9; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 1rem; text-align: center;">
                            <strong style="color: #475569;">Event Concluded</strong>
                            <p style="font-size: 0.82rem; color: #64748b; margin-top: 0.25rem;">This event has already taken place.</p>
                        </div>

                    <?php elseif ($isDeadlinePassed): ?>
                        <div style="background: var(--danger-bg); border: 1px solid #fecaca; border-radius: var(--radius-sm); padding: 1rem; text-align: center;">
                            <strong style="color: #991b1b;">Registration Closed</strong>
                            <p style="font-size: 0.82rem; color: #991b1b; margin-top: 0.25rem;">The deadline for this event has passed.</p>
                        </div>

                    <?php elseif ($isCapacityFull): ?>
                        <div style="background: var(--warning-bg); border: 1px solid #fde68a; border-radius: var(--radius-sm); padding: 1rem; text-align: center;">
                            <strong style="color: #92400e;">Event Full</strong>
                            <p style="font-size: 0.82rem; color: #92400e; margin-top: 0.25rem;">Maximum capacity of <?= $event['max_participants'] ?> attendees reached.</p>
                        </div>

                    <?php else: ?>
                        <!-- User can register -->
                        <form method="POST" action="event_details.php?id=<?= $eventId ?>">
                            <button type="submit" name="action_register" class="btn btn-primary btn-lg btn-block" onclick="return confirmAction('Confirm your registration for <?= addslashes($event['title']) ?>?');">
                                &#9997; Register Now (Free)
                            </button>
                        </form>
                        <?php if (!isLoggedIn()): ?>
                            <p style="font-size: 0.8rem; color: var(--text-muted); text-align: center; margin-top: 0.5rem;">
                                You will be prompted to log in or register.
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media (max-width: 800px) {
    .event-details-layout {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
