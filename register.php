<?php
$page_title = 'Create Student Account — Digital Smart Class';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . site_url(dashboard_for_role($_SESSION['user_role'] ?? 'student')));
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Refresh the page and submit the form again.';
    } elseif (!$db_connected || !$pdo) {
        $error = 'Registration is temporarily unavailable because the database is not connected.';
    } elseif ($fullName === '' || $email === '' || $password === '') {
        $error = 'Please fill in all required fields.';
    } elseif (strlen($fullName) > 120 || strlen($phone) > 30) {
        $error = 'Please shorten the information in one or more fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $studentRole = $pdo->query("SELECT id FROM roles WHERE name = 'student' LIMIT 1")->fetchColumn();
            if (!$studentRole) {
                throw new RuntimeException('The student role is not configured.');
            }

            $check = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = :email LIMIT 1');
            $check->execute(['email' => $email]);
            if ($check->fetch()) {
                $error = 'An account with this email address already exists. Please log in.';
            } else {
                $stmt = $pdo->prepare('
                    INSERT INTO users (role_id, full_name, email, phone, password, status)
                    VALUES (:role_id, :full_name, :email, :phone, :password, \'active\')
                ');
                $stmt->execute([
                    'role_id' => $studentRole,
                    'full_name' => $fullName,
                    'email' => $email,
                    'phone' => $phone !== '' ? $phone : null,
                    'password' => password_hash($password, PASSWORD_DEFAULT)
                ]);

                $newUser = [
                    'id' => $pdo->lastInsertId(),
                    'full_name' => $fullName,
                    'email' => $email,
                    'role_name' => 'student',
                    'role_id' => $studentRole
                ];
                login_user($newUser);
                set_flash('success', 'Your student account has been created. Welcome to Digital Smart Class!');
                header('Location: ' . site_url('student/dashboard.php'));
                exit;
            }
        } catch (PDOException $e) {
            log_database_error('Register student account', $e);
            $error = 'Your account could not be created. Please check your details and try again.';
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="hero-tagline" style="margin-bottom: 1rem;">Student registration</div>
            <h2>Create your account</h2>
            <p>Start learning practical digital and video-creation skills.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <?= e($error); ?>
            </div>
        <?php endif; ?>

        <form action="<?= site_url('register.php'); ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
            <div class="form-group">
                <label class="form-label" for="full_name">Full name *</label>
                <input type="text" name="full_name" id="full_name" class="form-control" required maxlength="120" autocomplete="name" placeholder="e.g. Jean Eric Ndayisaba" value="<?= e($_POST['full_name'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email address *</label>
                <input type="email" name="email" id="email" class="form-control" required maxlength="120" autocomplete="email" placeholder="e.g. eric@example.com" value="<?= e($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">Phone / WhatsApp number</label>
                <input type="tel" name="phone" id="phone" class="form-control" maxlength="30" autocomplete="tel" placeholder="+250 788 000 000" value="<?= e($_POST['phone'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password (minimum 8 characters) *</label>
                <input type="password" name="password" id="password" class="form-control" required minlength="8" autocomplete="new-password" placeholder="Create a secure password">
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm password *</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="8" autocomplete="new-password" placeholder="Repeat your password">
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1.5rem;">
                <i class="fas fa-user-plus"></i> Create student account
            </button>
        </form>

        <div class="auth-role-callout" style="margin-top: 1.5rem;">
            <strong><i class="fas fa-chalkboard-teacher"></i> Want to teach?</strong>
            <p>Register as a teacher, upload your credentials, and wait for administrator approval.</p>
            <a href="<?= site_url('teacher/application.php'); ?>" class="btn btn-outline btn-sm">Register as a teacher</a>
        </div>

        <div style="text-align: center; margin-top: 1.25rem; font-size: 0.9rem;">
            Already have an account? <a href="<?= site_url('login.php'); ?>" style="font-weight: 700;">Sign in here</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
