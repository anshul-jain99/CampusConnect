<?php
/**
 * CampusConnect - Browse Events Directory
 * Displays all approved events with search, category filtering, and status checks.
 */
$pageTitle = "Explore Events";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$selectedCategory = $_GET['category'] ?? 'all';
$searchQuery = trim($_GET['q'] ?? '');

// Build query for approved events
$sql = "
    SELECT e.*, c.club_name 
    FROM events e 
    JOIN clubs c ON e.club_id = c.id 
    WHERE e.status = 'approved'
";
$params = [];

if ($selectedCategory !== 'all' && !empty($selectedCategory)) {
    $sql .= " AND e.category = ?";
    $params[] = $selectedCategory;
}

if (!empty($searchQuery)) {
    $sql .= " AND (e.title LIKE ? OR e.venue LIKE ? OR e.description LIKE ?)";
    $searchWildcard = "%{$searchQuery}%";
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
}

$sql .= " ORDER BY e.event_date ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$eventsList = $stmt->fetchAll();

$categories = ['Technical', 'Cultural', 'Sports', 'Workshop', 'Competition', 'Seminar'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Campus Events Directory</h1>
            <p class="page-subtitle">Discover competitions, hackathons, and cultural activities approved across campus.</p>
        </div>
        <?php if (hasRole('organizer')): ?>
            <a href="organizer/create_event.php" class="btn btn-primary btn-sm">&#43; Create New Event</a>
        <?php endif; ?>
    </div>

    <!-- Category Pills Filter Bar -->
    <div class="category-pills">
        <button class="category-pill <?= $selectedCategory === 'all' ? 'active' : '' ?>" data-category="all">All Events</button>
        <?php foreach ($categories as $cat): ?>
            <button class="category-pill <?= $selectedCategory === $cat ? 'active' : '' ?>" data-category="<?= $cat ?>"><?= $cat ?></button>
        <?php endforeach; ?>
    </div>

    <!-- Search & Filter Controls -->
    <div class="filter-bar">
        <div class="search-input-group">
            <input type="text" id="eventSearchInput" class="form-control" placeholder="&#128269; Search by event title, venue, keywords..." value="<?= e($searchQuery) ?>">
        </div>
        <div class="filter-group">
            <select id="categoryFilterSelect" class="form-control" style="width: auto;">
                <option value="all" <?= $selectedCategory === 'all' ? 'selected' : '' ?>>All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat ?>" <?= $selectedCategory === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Events Grid -->
    <div id="noEventsFoundMessage" style="display: <?= empty($eventsList) ? 'block' : 'none' ?>; text-align: center; padding: 4rem 1.5rem; background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius);">
        <div style="font-size: 3rem; margin-bottom: 0.5rem;">&#128197;</div>
        <h3 style="color: var(--secondary); margin-bottom: 0.5rem;">No Events Found</h3>
        <p style="color: var(--text-muted);">Try selecting a different category or clearing your search keywords.</p>
        <a href="events.php" class="btn btn-secondary btn-sm" style="margin-top: 1rem;">Reset Filters</a>
    </div>

    <?php if (!empty($eventsList)): ?>
        <div class="card-grid" id="eventsGridContainer">
            <?php foreach ($eventsList as $ev): ?>
                <?php
                // Count confirmed registrations
                $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE event_id = ? AND status = 'confirmed'");
                $stmtCount->execute([$ev['id']]);
                $regCount = $stmtCount->fetchColumn();
                $seatsRemaining = max(0, $ev['max_participants'] - $regCount);

                $isPastDeadline = strtotime($ev['registration_deadline']) < strtotime(date('Y-m-d'));
                $isFull = $seatsRemaining <= 0;

                // Icons
                $catIcons = [
                    'Technical'   => '&#128187;',
                    'Cultural'    => '&#127917;',
                    'Sports'      => '&#9917;',
                    'Workshop'    => '&#128295;',
                    'Competition' => '&#127942;',
                    'Seminar'     => '&#127891;'
                ];
                $icon = $catIcons[$ev['category']] ?? '&#128197;';
                ?>
                <div class="card event-card-item" 
                     data-title="<?= strtolower(e($ev['title'])) ?>" 
                     data-category="<?= strtolower(e($ev['category'])) ?>" 
                     data-venue="<?= strtolower(e($ev['venue'])) ?>" 
                     data-desc="<?= strtolower(e($ev['description'])) ?>">
                    
                    <div class="event-card-banner">
                        <span class="event-category-tag"><?= e($ev['category']) ?></span>
                        <?php if ($isPastDeadline): ?>
                            <span class="event-status-tag status-rejected">Closed</span>
                        <?php elseif ($isFull): ?>
                            <span class="event-status-tag status-warning">Full</span>
                        <?php else: ?>
                            <span class="event-status-tag status-approved">Open</span>
                        <?php endif; ?>
                        <span style="font-size: 2.75rem; opacity: 0.85;"><?= $icon ?></span>
                    </div>

                    <div class="card-body">
                        <h3 class="event-card-title"><?= e($ev['title']) ?></h3>
                        <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 1rem; line-height: 1.5;">
                            <?= e(substr($ev['description'], 0, 115)) ?>...
                        </p>

                        <div class="event-meta">
                            <div class="event-meta-item">
                                <span>&#128197;</span>
                                <strong><?= date('D, M d, Y', strtotime($ev['event_date'])) ?></strong> at <?= date('h:i A', strtotime($ev['event_time'])) ?>
                            </div>
                            <div class="event-meta-item">
                                <span>&#128205;</span>
                                <span><?= e($ev['venue']) ?></span>
                            </div>
                            <div class="event-meta-item">
                                <span>&#127979;</span>
                                <span>Organizer: <strong><?= e($ev['club_name']) ?></strong></span>
                            </div>
                            <div class="event-meta-item">
                                <span>&#128101;</span>
                                <span>Seats: <strong><?= $seatsRemaining ?></strong> / <?= $ev['max_participants'] ?> remaining</span>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer" style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.82rem; color: var(--text-muted);">
                            Last Date: <?= date('M d, Y', strtotime($ev['registration_deadline'])) ?>
                        </span>
                        <a href="event_details.php?id=<?= $ev['id'] ?>" class="btn btn-primary btn-sm">View &amp; Register &rarr;</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
