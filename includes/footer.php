<?php
/**
 * CampusConnect - Common Page Footer
 */
$currentDir = basename(dirname($_SERVER['SCRIPT_NAME']));
$isSubDir = in_array($currentDir, ['student', 'organizer', 'admin']);
$basePath = $isSubDir ? '../' : '';
?>
<footer class="footer">
    <div class="footer-container">
        <div class="footer-col">
            <h4>CampusConnect</h4>
            <p style="font-size: 0.9rem; color: #cbd5e1; margin-bottom: 1rem;">
                A centralized, role-based college event and club management portal designed to streamline student participation, club activities, and administrative approvals.
            </p>
            <p style="font-size: 0.85rem; color: #94a3b8;">
                Developed as a 4-Week Summer Training Internship Project.
            </p>
        </div>

        <div class="footer-col">
            <h4>Quick Navigation</h4>
            <ul>
                <li><a href="<?= $basePath ?>index.php">Home</a></li>
                <li><a href="<?= $basePath ?>events.php">Browse Events</a></li>
                <li><a href="<?= $basePath ?>login.php">Portal Login</a></li>
                <li><a href="<?= $basePath ?>register.php">Student Registration</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Event Categories</h4>
            <ul>
                <li><a href="<?= $basePath ?>events.php?category=Technical">Technical & Hackathons</a></li>
                <li><a href="<?= $basePath ?>events.php?category=Cultural">Cultural Fests & Drama</a></li>
                <li><a href="<?= $basePath ?>events.php?category=Sports">Sports Tournaments</a></li>
                <li><a href="<?= $basePath ?>events.php?category=Workshop">Hands-on Workshops</a></li>
                <li><a href="<?= $basePath ?>events.php?category=Seminar">Academic Seminars</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Internship Details</h4>
            <p style="font-size: 0.88rem; color: #cbd5e1; margin-bottom: 0.4rem;">
                <strong>Student:</strong> Anshul Jain
            </p>
            <p style="font-size: 0.88rem; color: #cbd5e1; margin-bottom: 0.4rem;">
                <strong>Enrollment:</strong> 01815603124
            </p>
            <p style="font-size: 0.88rem; color: #cbd5e1; margin-bottom: 0.4rem;">
                <strong>Department:</strong> Information Technology
            </p>
            <p style="font-size: 0.88rem; color: #cbd5e1;">
                <strong>Tech Stack:</strong> PHP, MySQL, HTML5, CSS3, JS
            </p>
        </div>
    </div>

    <div class="footer-bottom">
        <div>
            &copy; <?= date('Y') ?> CampusConnect Portal. All rights reserved.
        </div>
        <div>
            Built with pure HTML5 &bull; CSS3 &bull; Vanilla JS &bull; PHP &bull; MySQL (XAMPP)
        </div>
    </div>
</footer>

<script src="<?= $basePath ?>js/script.js"></script>
</body>
</html>
