<?php
/**
 * CampusConnect - Student Registration Page
 * Allows new students to create an account with department and enrollment details.
 */
$pageTitle = "Student Registration";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect
if (isLoggedIn()) {
    header('Location: student/student_dashboard.php');
    exit();
}

$error = '';
$name = '';
$email = '';
$enrollment = '';
$department = 'Information Technology';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $enrollment = trim($_POST['enrollment_no'] ?? '');
    $department = trim($_POST['department'] ?? 'Information Technology');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill out all required fields (Name, Email, Password).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Check for duplicate email
            $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmtCheck->execute([$email]);
            if ($stmtCheck->fetch()) {
                $error = 'An account with this email already exists. Please login instead.';
            } else {
                // Securely hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmtInsert = $pdo->prepare("
                    INSERT INTO users (name, email, password, role, department, phone, enrollment_no, created_at)
                    VALUES (?, ?, ?, 'student', ?, ?, ?, NOW())
                ");
                $stmtInsert->execute([$name, $email, $hashedPassword, $department, $phone, $enrollment]);

                $newUserId = $pdo->lastInsertId();

                // Auto-login newly registered student
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'student';
                $_SESSION['user_dept'] = $department;
                $_SESSION['user_enrollment'] = $enrollment;

                setFlash('success', 'Registration successful! Welcome to CampusConnect.');
                header('Location: student/student_dashboard.php');
                exit();
            }
        } catch (PDOException $e) {
            $error = 'Database error: Could not complete registration. Please try again.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 620px; margin-top: 1.5rem;">
    <div class="form-card">
        <div style="text-align: center; margin-bottom: 2rem;">
            <div class="brand-icon" style="margin: 0 auto 0.75rem; width: 48px; height: 48px; font-size: 1.4rem;">CC</div>
            <h1 style="font-size: 1.75rem; color: var(--secondary);">Student Registration</h1>
            <p style="font-size: 0.9rem; color: var(--text-muted); margin-top: 0.25rem;">
                Create your student account to register for college events and workshops.
            </p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="flash-alert flash-error" style="margin-bottom: 1.5rem;">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="form-group">
                <label class="form-label" for="regName">Full Name *</label>
                <input type="text" name="name" id="regName" class="form-control" placeholder="e.g., Anshul Jain" value="<?= e($name) ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="regEmail">College Email *</label>
                    <input type="email" name="email" id="regEmail" class="form-control" placeholder="e.g., anshul@student.edu" value="<?= e($email) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="regEnroll">Enrollment Number</label>
                    <input type="text" name="enrollment_no" id="regEnroll" class="form-control" placeholder="e.g., 01815603124" value="<?= e($enrollment) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="regDept">Department</label>
                    <select name="department" id="regDept" class="form-control">
                        <option value="Information Technology" <?= $department === 'Information Technology' ? 'selected' : '' ?>>Information Technology</option>
                        <option value="Computer Science" <?= $department === 'Computer Science' ? 'selected' : '' ?>>Computer Science &amp; Engg.</option>
                        <option value="Electronics & Communication" <?= $department === 'Electronics & Communication' ? 'selected' : '' ?>>Electronics &amp; Comm.</option>
                        <option value="Mechanical Engineering" <?= $department === 'Mechanical Engineering' ? 'selected' : '' ?>>Mechanical Engineering</option>
                        <option value="Civil Engineering" <?= $department === 'Civil Engineering' ? 'selected' : '' ?>>Civil Engineering</option>
                        <option value="Applied Sciences" <?= $department === 'Applied Sciences' ? 'selected' : '' ?>>Applied Sciences</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="regPhone">Mobile Number</label>
                    <input type="tel" name="phone" id="regPhone" class="form-control" placeholder="e.g., 9899001122" value="<?= e($phone) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="regPass">Password (min 6 characters) *</label>
                    <input type="password" name="password" id="regPass" class="form-control" placeholder="Create a password" required minlength="6">
                </div>
                <div class="form-group">
                    <label class="form-label" for="regConfirmPass">Confirm Password *</label>
                    <input type="password" name="confirm_password" id="regConfirmPass" class="form-control" placeholder="Repeat your password" required minlength="6">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 1rem;">
                Complete Registration &rarr;
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-muted);">
            Already have an account? 
            <a href="login.php" style="font-weight: 600;">Sign In Here</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
