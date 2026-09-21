<?php
/**
 * CampusConnect - Public Landing Page
 */
$pageTitle = "Home";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Fetch dynamic counters for the stats strip
$totalEvents = 0;
$totalStudents = 0;
$totalClubs = 0;
$featuredEvents = [];

try {
    $stmt1 = $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'approved'");
    $totalEvents = $stmt1->fetchColumn();

    $stmt2 = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
    $totalStudents = $stmt2->fetchColumn();

    $stmt3 = $pdo->query("SELECT COUNT(*) FROM clubs");
    $totalClubs = $stmt3->fetchColumn();

    // Fetch up to 3 upcoming approved events
    $stmt4 = $pdo->query("
        SELECT e.*, c.club_name 
        FROM events e 
        JOIN clubs c ON e.club_id = c.id 
        WHERE e.status = 'approved' AND e.event_date >= CURDATE() 
        ORDER BY e.event_date ASC 
        LIMIT 3
    ");
    $featuredEvents = $stmt4->fetchAll();
} catch (PDOException $e) {
    // If tables not ready, fallback gracefully
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <span class="hero-badge">Centralized Campus Platform</span>
        <h1 class="hero-title">Discover, Participate &amp; Lead <span>College Events</span></h1>
        <p class="hero-desc">
            No more missed deadlines on WhatsApp groups or buried bulletin notices. CampusConnect unifies every college hackathon, cultural fest, sports duel, and workshop into one interactive hub.
        </p>
        <div class="hero-actions">
            <a href="events.php" class="btn btn-primary btn-lg">&#128269; Explore All Events</a>
            <?php if (!isLoggedIn()): ?>
                <a href="register.php" class="btn btn-secondary btn-lg">&#127891; Create Student Account</a>
            <?php else: ?>
                <a href="student/student_dashboard.php" class="btn btn-secondary btn-lg">&#128202; Go to My Dashboard</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Stats Strip -->
<section class="stats-strip">
    <div class="stats-grid">
        <div class="stat-item">
            <h3><?= number_format($totalEvents) ?>+</h3>
            <p>Active Events</p>
        </div>
        <div class="stat-item">
            <h3><?= number_format($totalStudents) ?>+</h3>
            <p>Registered Students</p>
        </div>
        <div class="stat-item">
            <h3><?= number_format($totalClubs) ?></h3>
            <p>Campus Clubs &amp; Societies</p>
        </div>
        <div class="stat-item">
            <h3>100%</h3>
            <p>Verified College Approvals</p>
        </div>
    </div>
</section>

<div class="container">
    <!-- Featured Upcoming Events -->
    <div class="page-header" style="margin-top: 1rem;">
        <div>
            <h2 class="page-title">Featured Upcoming Events</h2>
            <p class="page-subtitle">Don't miss out on the latest campus competitions, workshops, and fests.</p>
        </div>
        <a href="events.php" class="btn btn-secondary btn-sm">View All Events &rarr;</a>
    </div>

    <?php if (!empty($featuredEvents)): ?>
        <div class="card-grid">
            <?php foreach ($featuredEvents as $ev): ?>
                <?php
                // Count registered participants
                $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE event_id = ? AND status = 'confirmed'");
                $stmtCount->execute([$ev['id']]);
                $regCount = $stmtCount->fetchColumn();
                $seatsLeft = max(0, $ev['max_participants'] - $regCount);
                ?>
                <div class="card event-card-item">
                    <div class="event-card-banner">
                        <span class="event-category-tag"><?= e($ev['category']) ?></span>
                        <span class="event-status-tag status-approved">Approved</span>
                        <span style="font-size: 2.5rem; opacity: 0.85;">
                            <?php
                            $catIcons = [
                                'Technical'   => '&#128187;',
                                'Cultural'    => '&#127917;',
                                'Sports'      => '&#9917;',
                                'Workshop'    => '&#128295;',
                                'Competition' => '&#127942;',
                                'Seminar'     => '&#127891;'
                            ];
                            echo $catIcons[$ev['category']] ?? '&#128197;';
                            ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <h3 class="event-card-title"><?= e($ev['title']) ?></h3>
                        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">
                            <?= e(substr($ev['description'], 0, 110)) ?>...
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
                                <span>By: <strong><?= e($ev['club_name']) ?></strong></span>
                            </div>
                            <div class="event-meta-item">
                                <span>&#128101;</span>
                                <span><strong><?= $seatsLeft ?></strong> of <?= $ev['max_participants'] ?> seats remaining</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer" style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.82rem; color: var(--text-muted);">
                            Deadline: <?= date('M d', strtotime($ev['registration_deadline'])) ?>
                        </span>
                        <a href="event_details.php?id=<?= $ev['id'] ?>" class="btn btn-primary btn-sm">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 3rem; background: #fff; border-radius: var(--radius); border: 1px solid var(--border);">
            <p style="font-size: 1.1rem; color: var(--text-muted);">No upcoming featured events at the moment.</p>
            <a href="events.php" class="btn btn-primary" style="margin-top: 1rem;">Browse Full Event Directory</a>
        </div>
    <?php endif; ?>

    <!-- Event Categories -->
    <div style="margin-top: 3.5rem;">
        <h2 class="page-title" style="text-align: center;">Explore by Interest</h2>
        <p class="page-subtitle" style="text-align: center; margin-bottom: 2rem;">Choose your field and see what's happening on campus</p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.25rem; text-align: center;">
            <a href="events.php?category=Technical" class="card" style="padding: 1.5rem; text-decoration: none;">
                <div style="font-size: 2.25rem; margin-bottom: 0.5rem;">&#128187;</div>
                <h4 style="color: var(--secondary); margin-bottom: 0.25rem;">Technical</h4>
                <p style="font-size: 0.82rem; color: var(--text-muted);">Coding &amp; Hackathons</p>
            </a>
            <a href="events.php?category=Cultural" class="card" style="padding: 1.5rem; text-decoration: none;">
                <div style="font-size: 2.25rem; margin-bottom: 0.5rem;">&#127917;</div>
                <h4 style="color: var(--secondary); margin-bottom: 0.25rem;">Cultural</h4>
                <p style="font-size: 0.82rem; color: var(--text-muted);">Music, Dance &amp; Arts</p>
            </a>
            <a href="events.php?category=Sports" class="card" style="padding: 1.5rem; text-decoration: none;">
                <div style="font-size: 2.25rem; margin-bottom: 0.5rem;">&#9917;</div>
                <h4 style="color: var(--secondary); margin-bottom: 0.25rem;">Sports</h4>
                <p style="font-size: 0.82rem; color: var(--text-muted);">Athletics &amp; Games</p>
            </a>
            <a href="events.php?category=Workshop" class="card" style="padding: 1.5rem; text-decoration: none;">
                <div style="font-size: 2.25rem; margin-bottom: 0.5rem;">&#128295;</div>
                <h4 style="color: var(--secondary); margin-bottom: 0.25rem;">Workshop</h4>
                <p style="font-size: 0.82rem; color: var(--text-muted);">Skill-Building Sessions</p>
            </a>
            <a href="events.php?category=Seminar" class="card" style="padding: 1.5rem; text-decoration: none;">
                <div style="font-size: 2.25rem; margin-bottom: 0.5rem;">&#127891;</div>
                <h4 style="color: var(--secondary); margin-bottom: 0.25rem;">Seminar</h4>
                <p style="font-size: 0.82rem; color: var(--text-muted);">Industry &amp; Academic Talks</p>
            </a>
        </div>
    </div>

    <!-- How CampusConnect Works -->
    <div style="margin-top: 4rem; margin-bottom: 2rem; background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius); padding: 2.5rem; box-shadow: var(--shadow);">
        <h2 class="page-title" style="text-align: center; margin-bottom: 0.5rem;">How CampusConnect Works</h2>
        <p class="page-subtitle" style="text-align: center; margin-bottom: 2.5rem;">A streamlined workflow for students, organizers, and campus administration</p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 2rem;">
            <div style="text-align: center;">
                <div style="width: 60px; height: 60px; border-radius: 50%; background: #eff6ff; color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700; margin: 0 auto 1rem;">1</div>
                <h3 style="font-size: 1.15rem; color: var(--secondary); margin-bottom: 0.5rem;">Discover &amp; Explore</h3>
                <p style="font-size: 0.9rem; color: var(--text-muted);">Students browse upcoming approved events with real-time search, category filters, and venue schedules.</p>
            </div>
            <div style="text-align: center;">
                <div style="width: 60px; height: 60px; border-radius: 50%; background: #ecfdf5; color: var(--success); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700; margin: 0 auto 1rem;">2</div>
                <h3 style="font-size: 1.15rem; color: var(--secondary); margin-bottom: 0.5rem;">One-Click Registration</h3>
                <p style="font-size: 0.9rem; color: var(--text-muted);">Reserve your seat before capacity fills up or deadlines pass. Duplicate registrations are securely prevented.</p>
            </div>
            <div style="text-align: center;">
                <div style="width: 60px; height: 60px; border-radius: 50%; background: #fef3c7; color: var(--warning); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700; margin: 0 auto 1rem;">3</div>
                <h3 style="font-size: 1.15rem; color: var(--secondary); margin-bottom: 0.5rem;">Club &amp; Admin Management</h3>
                <p style="font-size: 0.9rem; color: var(--text-muted);">Clubs create events, Admins verify and approve them, and organizers manage live participant rosters effortlessly.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
