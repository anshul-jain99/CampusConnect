<?php
/**
 * CampusConnect - Unified Portal Login
 * Authenticates Students, Organizers, and Admins via session
 */
$pageTitle = "Login";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect to respective dashboard
if (isLoggedIn()) {
    $role = $_SESSION['user_role'] ?? 'student';
    if ($role === 'admin') header('Location: admin/admin_dashboard.php');
    elseif ($role === 'organizer') header('Location: organizer/organizer_dashboard.php');
    else header('Location: student/student_dashboard.php');
    exit();
}

$redirectUrl = $_GET['redirect'] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirect = $_POST['redirect'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both your college email and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_dept'] = $user['department'];
                $_SESSION['user_enrollment'] = $user['enrollment_no'];

                setFlash('success', "Welcome back, {$user['name']}!");

                // If a specific redirect was requested and is safe, redirect there
                if (!empty($redirect) && strpos($redirect, '..') === false) {
                    header("Location: {$redirect}");
                    exit();
                }

                // Default role redirect
                if ($user['role'] === 'admin') {
                    header('Location: admin/admin_dashboard.php');
                } elseif ($user['role'] === 'organizer') {
                    header('Location: organizer/organizer_dashboard.php');
                } else {
                    header('Location: student/student_dashboard.php');
                }
                exit();
            } else {
                $error = 'Invalid email or password. Please verify your credentials.';
            }
        } catch (PDOException $e) {
            $error = 'A database error occurred. Please make sure MySQL is running.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 550px; margin-top: 2rem;">
    <div class="form-card">
        <div style="text-align: center; margin-bottom: 2rem;">
            <div class="brand-icon" style="margin: 0 auto 0.75rem; width: 48px; height: 48px; font-size: 1.4rem;">CC</div>
            <h1 style="font-size: 1.75rem; color: var(--secondary);">Portal Login</h1>
            <p style="font-size: 0.9rem; color: var(--text-muted); margin-top: 0.25rem;">
                Sign in to access your student, organizer, or admin dashboard.
            </p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="flash-alert flash-error" style="margin-bottom: 1.5rem;">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <input type="hidden" name="redirect" value="<?= e($redirectUrl) ?>">

            <div class="form-group">
                <label class="form-label" for="loginEmail">College Email Address</label>
                <input type="email" name="email" id="loginEmail" class="form-control" placeholder="e.g., anshul@student.edu" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="loginPassword">Password</label>
                <input type="password" name="password" id="loginPassword" class="form-control" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 1rem;">
                Sign In to CampusConnect &rarr;
            </button>
        </form>

        <!-- Quick Demo Credentials for Viva / Evaluation -->
        <div style="margin-top: 2rem; padding: 1.25rem; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: var(--radius-sm);">
            <p style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.5rem; text-align: center;">
                Demo Credentials (Click to Auto-fill)
            </p>
            <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                <button type="button" id="fillStudentLogin" class="btn btn-secondary btn-sm">&#127891; Student (Anshul)</button>
                <button type="button" id="fillOrgLogin" class="btn btn-secondary btn-sm">&#128187; Club Organizer</button>
                <button type="button" id="fillAdminLogin" class="btn btn-secondary btn-sm">&#128272; Admin</button>
            </div>
        </div>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-muted);">
            Don't have a student account yet? 
            <a href="register.php" style="font-weight: 600;">Register Here</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
