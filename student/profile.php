<?php
/**
 * CampusConnect - Student Profile Management
 */
$pageTitle = "My Profile";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('student');
$currentUser = currentUser();

// Fetch latest user details from DB
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$currentUser['id']]);
$userProfile = $stmt->fetch();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $enrollment = trim($_POST['enrollment_no'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';

    if (empty($name)) {
        $error = 'Name cannot be empty.';
    } else {
        try {
            if (!empty($newPassword)) {
                if (strlen($newPassword) < 6) {
                    $error = 'New password must be at least 6 characters.';
                } else {
                    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmtUpdate = $pdo->prepare("
                        UPDATE users 
                        SET name = ?, department = ?, phone = ?, enrollment_no = ?, password = ? 
                        WHERE id = ?
                    ");
                    $stmtUpdate->execute([$name, $department, $phone, $enrollment, $hashed, $currentUser['id']]);
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_dept'] = $department;
                    $_SESSION['user_enrollment'] = $enrollment;
                    setFlash('success', 'Profile and password updated successfully!');
                    header('Location: profile.php');
                    exit();
                }
            } else {
                $stmtUpdate = $pdo->prepare("
                    UPDATE users 
                    SET name = ?, department = ?, phone = ?, enrollment_no = ? 
                    WHERE id = ?
                ");
                $stmtUpdate->execute([$name, $department, $phone, $enrollment, $currentUser['id']]);
                $_SESSION['user_name'] = $name;
                $_SESSION['user_dept'] = $department;
                $_SESSION['user_enrollment'] = $enrollment;
                setFlash('success', 'Profile updated successfully!');
                header('Location: profile.php');
                exit();
            }
        } catch (PDOException $e) {
            $error = 'Failed to update profile. Please try again.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 650px;">
    <div class="page-header">
        <div>
            <h1 class="page-title">Student Profile</h1>
            <p class="page-subtitle">View and update your academic details and account settings.</p>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="flash-alert flash-error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="form-card" style="max-width: 100%;">
        <form method="POST" action="profile.php">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" value="<?= e($userProfile['name']) ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Email Address (Read-only)</label>
                    <input type="email" class="form-control" value="<?= e($userProfile['email']) ?>" readonly style="background: #f1f5f9; cursor: not-allowed;">
                </div>
                <div class="form-group">
                    <label class="form-label">Enrollment Number</label>
                    <input type="text" name="enrollment_no" class="form-control" value="<?= e($userProfile['enrollment_no']) ?>" placeholder="e.g. 01815603124">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Department</label>
                    <select name="department" class="form-control">
                        <?php
                        $depts = [
                            'Information Technology',
                            'Computer Science',
                            'Electronics & Communication',
                            'Mechanical Engineering',
                            'Civil Engineering',
                            'Applied Sciences'
                        ];
                        foreach ($depts as $d): ?>
                            <option value="<?= $d ?>" <?= $userProfile['department'] === $d ? 'selected' : '' ?>><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" class="form-control" value="<?= e($userProfile['phone']) ?>" placeholder="e.g. 9899001122">
                </div>
            </div>

            <div class="form-group" style="margin-top: 1.5rem; border-top: 1px solid var(--border); padding-top: 1.25rem;">
                <label class="form-label">Change Password <small style="color: var(--text-muted); font-weight: 400;">(leave blank to keep current)</small></label>
                <input type="password" name="new_password" class="form-control" placeholder="Enter new password (optional)" minlength="6">
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1.5rem;">
                Save Profile Changes
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
