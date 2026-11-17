<?php
$page_title = 'Log In — Digital Smart Class';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . site_url(dashboard_for_role($_SESSION['user_role'] ?? 'student')));
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Refresh the page and try signing in again.';
    } elseif ($email === '' || $password === '') {
        $error = 'Please enter both your email address and password.';
    } elseif (!$db_connected || !$pdo) {
        $error = 'Sign in is temporarily unavailable because the database is not connected.';
    } else {
        try {
            $stmt = $pdo->prepare('
                SELECT u.*, r.name AS role_name
                FROM users u
                JOIN roles r ON r.id = u.role_id
                WHERE LOWER(u.email) = :email
                LIMIT 1
            ');
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                $error = 'Invalid email or password. Please try again.';
            } elseif ($user['role_name'] === 'teacher' && $user['status'] === 'pending') {
                login_user($user);
                $applicationStmt = $pdo->prepare('
                    SELECT status FROM teacher_applications
                    WHERE user_id = :uid
                    ORDER BY id DESC
                    LIMIT 1
                ');
                $applicationStmt->execute(['uid' => $user['id']]);
                $applicationStatus = $applicationStmt->fetchColumn();
                set_flash('info', $applicationStatus === 'rejected'
                    ? 'Your teacher application was not approved. You can review and resubmit your application.'
                    : 'Your teacher application is still awaiting administrator review.');
                header('Location: ' . site_url('teacher/application.php'));
                exit;
            } elseif ($user['status'] !== 'active') {
                $error = 'Your account is currently ' . $user['status'] . '. Please contact an administrator.';
            } else {
                login_user($user);
                audit_log('user.login', 'Successful login', $user['id']);
                set_flash('success', 'Welcome back, ' . $user['full_name'] . '!');
                header('Location: ' . site_url(dashboard_for_role($user['role_name'])));
                exit;
            }
        } catch (PDOException $e) {
            log_database_error('Authenticate user', $e);
            $error = 'Database authentication failed. Please try again later.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="hero-tagline" style="margin-bottom: 1rem;">Secure access</div>
            <h2>Welcome back</h2>
            <p>Sign in with the account you created on Digital Smart Class.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <?= e($error); ?>
            </div>
        <?php endif; ?>

        <form action="<?= site_url('login.php'); ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
            <div class="form-group">
                <label class="form-label" for="email">Email address</label>
                <input type="email" name="email" id="email" class="form-control" required autocomplete="email" placeholder="you@example.com" value="<?= e($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" required autocomplete="current-password" placeholder="Enter your password">
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1.5rem;">
                <i class="fas fa-sign-in-alt"></i> Sign in
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem;">
            Do not have an account? <a href="<?= site_url('register.php'); ?>" style="font-weight: 700;">Create a student account</a>
        </div>
        <div style="text-align: center; margin-top: 0.65rem; font-size: 0.9rem;">
            Want to teach? <a href="<?= site_url('teacher/application.php'); ?>" style="font-weight: 700;">Register as a teacher</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
