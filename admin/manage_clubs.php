<?php
/**
 * CampusConnect - Admin: Manage Clubs & Societies
 */
$pageTitle = "Manage Clubs";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

// Handle New Club Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_create_club'])) {
    $clubName = trim($_POST['club_name'] ?? '');
    $category = trim($_POST['category'] ?? 'Technical');
    $organizerId = filter_input(INPUT_POST, 'organizer_id', FILTER_VALIDATE_INT);
    $description = trim($_POST['description'] ?? '');

    if (empty($clubName) || !$organizerId) {
        setFlash('error', 'Please provide a club name and assign an organizer.');
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO clubs (club_name, description, category, organizer_id, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$clubName, $description, $category, $organizerId]);
            setFlash('success', "Club '{$clubName}' registered successfully!");
            header('Location: manage_clubs.php');
            exit();
        } catch (PDOException $e) {
            setFlash('error', 'Could not create club: ' . $e->getMessage());
        }
    }
}

// Handle Club Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_delete_club'])) {
    $clubId = filter_input(INPUT_POST, 'club_id', FILTER_VALIDATE_INT);
    if ($clubId) {
        $stmt = $pdo->prepare("DELETE FROM clubs WHERE id = ?");
        $stmt->execute([$clubId]);
        setFlash('info', 'Club and its associated events deleted.');
        header('Location: manage_clubs.php');
        exit();
    }
}

// Fetch organizers for dropdown
$organizers = $pdo->query("SELECT id, name, email FROM users WHERE role = 'organizer' ORDER BY name ASC")->fetchAll();

// Fetch clubs with organizer details and event counts
$stmtClubs = $pdo->query("
    SELECT c.*, u.name AS organizer_name, u.email AS organizer_email,
           (SELECT COUNT(*) FROM events e WHERE e.club_id = c.id) AS event_count
    FROM clubs c
    JOIN users u ON c.organizer_id = u.id
    ORDER BY c.club_name ASC
");
$clubs = $stmtClubs->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Manage Campus Clubs &amp; Societies</h1>
            <p class="page-subtitle">Recognize student bodies, assign faculty/student club leads, and monitor event volume.</p>
        </div>
        <a href="admin_dashboard.php" class="btn btn-secondary btn-sm">&larr; Back to Dashboard</a>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;" class="admin-clubs-grid">
        <!-- Clubs Directory Table -->
        <div>
            <h2 style="font-size: 1.25rem; color: var(--secondary); margin-bottom: 1rem;">Active Clubs Roster</h2>

            <?php if (!empty($clubs)): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Club Name</th>
                                <th>Category</th>
                                <th>Designated Lead</th>
                                <th>Events Hosted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clubs as $c): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($c['club_name']) ?></strong><br>
                                        <small style="color: var(--text-muted);"><?= e(substr($c['description'], 0, 75)) ?>...</small>
                                    </td>
                                    <td><span class="role-tag"><?= e($c['category']) ?></span></td>
                                    <td>
                                        <?= e($c['organizer_name']) ?><br>
                                        <small style="color: var(--text-muted);"><?= e($c['organizer_email']) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= $c['event_count'] ?></strong> events
                                    </td>
                                    <td>
                                        <form method="POST" action="manage_clubs.php" style="display: inline;">
                                            <input type="hidden" name="club_id" value="<?= $c['id'] ?>">
                                            <button type="submit" name="action_delete_club" class="btn btn-danger btn-sm" onclick="return confirmAction('Delete <?= addslashes($c['club_name']) ?>? All its events and registrations will be erased.');">
                                                &#128465; Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem; background: #fff; border: 1px solid var(--border); border-radius: var(--radius);">
                    <p style="color: var(--text-muted);">No clubs found in the database.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Add New Club Form -->
        <div>
            <div style="background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius); padding: 1.75rem; box-shadow: var(--shadow);">
                <h3 style="font-size: 1.15rem; color: var(--secondary); margin-bottom: 1.25rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">
                    &#43; Register New Club
                </h3>

                <form method="POST" action="manage_clubs.php">
                    <div class="form-group">
                        <label class="form-label" for="clubName">Club / Society Name *</label>
                        <input type="text" name="club_name" id="clubName" class="form-control" placeholder="e.g., Debate Society" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="clubCat">Category *</label>
                        <select name="category" id="clubCat" class="form-control">
                            <option value="Technical">Technical</option>
                            <option value="Cultural">Cultural</option>
                            <option value="Sports">Sports</option>
                            <option value="Literary">Literary / Debate</option>
                            <option value="Social">Social / Volunteering</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="clubOrg">Assign Organizer Lead *</label>
                        <select name="organizer_id" id="clubOrg" class="form-control" required>
                            <?php if (empty($organizers)): ?>
                                <option value="">No organizer accounts available</option>
                            <?php else: ?>
                                <?php foreach ($organizers as $org): ?>
                                    <option value="<?= $org['id'] ?>"><?= e($org['name']) ?> (<?= e($org['email']) ?>)</option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <span class="form-help">Only users with role 'organizer' can lead clubs.</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="clubDesc">Description &amp; Mission</label>
                        <textarea name="description" id="clubDesc" class="form-control" rows="3" placeholder="Brief outline of club objectives and scope..."></textarea>
                    </div>

                    <button type="submit" name="action_create_club" class="btn btn-primary btn-block">
                        Create Club &rarr;
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
@media (max-width: 900px) {
    .admin-clubs-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
