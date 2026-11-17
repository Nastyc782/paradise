<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$page_title = 'Initial Administrator Setup — Digital Smart Class';
$error = null;
$setupComplete = false;

if (!$db_connected || !$pdo) {
    $error = 'Administrator setup requires a working database connection.';
} else {
    $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE r.name = 'admin'")->fetchColumn();
    if ($adminCount > 0) {
        $setupComplete = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$setupComplete && !$error) {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $setupKey = (string)($_POST['setup_key'] ?? '');
    $requiredSetupKey = (string)(getenv('ADMIN_SETUP_KEY') ?: '');

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif ($requiredSetupKey !== '' && !hash_equals($requiredSetupKey, $setupKey)) {
        $error = 'The administrator setup key is invalid.';
    } elseif ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid administrator name and email address.';
    } elseif (strlen($password) < 8 || $password !== $confirmPassword) {
        $error = 'Passwords must match and contain at least 8 characters.';
    } else {
        try {
            $pdo->beginTransaction();
            $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE r.name = 'admin' FOR UPDATE")->fetchColumn();
            if ($adminCount > 0) {
                throw new RuntimeException('Administrator setup has already been completed.');
            }
            $roleId = $pdo->query("SELECT id FROM roles WHERE name = 'admin' LIMIT 1")->fetchColumn();
            if (!$roleId) {
                throw new RuntimeException('The administrator role is not configured. Import the database schema first.');
            }
            $stmt = $pdo->prepare('
                INSERT INTO users (role_id, full_name, email, phone, password, status)
                VALUES (:role_id, :full_name, :email, :phone, :password, \'active\')
            ');
            $stmt->execute([
                'role_id' => $roleId,
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone !== '' ? $phone : null,
                'password' => password_hash($password, PASSWORD_DEFAULT)
            ]);
            $newId = $pdo->lastInsertId();
            $pdo->commit();
            login_user(['id' => $newId, 'full_name' => $fullName, 'email' => $email, 'role_name' => 'admin', 'role_id' => $roleId]);
            set_flash('success', 'Your administrator account has been created securely.');
            header('Location: ' . site_url('admin/dashboard.php'));
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            log_database_error('Create initial administrator', $e);
            $error = 'The administrator account could not be created. Check that the email is unique.';
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="hero-tagline" style="margin-bottom:1rem;">First-run setup</div>
            <h2>Create the first administrator</h2>
            <p>This page is available only until the first administrator account exists.</p>
        </div>

        <?php if ($setupComplete): ?>
            <div class="alert alert-info" role="status">Administrator setup is already complete. Sign in with an existing administrator account.</div>
            <a href="<?= site_url('login.php'); ?>" class="btn btn-primary btn-block">Go to sign in</a>
        <?php elseif ($error): ?>
            <div class="alert alert-danger" role="alert"><i class="fas fa-exclamation-triangle"></i> <?= e($error); ?></div>
        <?php endif; ?>

        <?php if (!$setupComplete && $db_connected && $pdo): ?>
            <form method="POST" action="<?= site_url('setup-admin.php'); ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                <?php if (getenv('ADMIN_SETUP_KEY')): ?><div class="form-group"><label class="form-label" for="setup_key">Setup key *</label><input class="form-control" type="password" id="setup_key" name="setup_key" required></div><?php endif; ?>
                <div class="form-group"><label class="form-label" for="full_name">Full name *</label><input class="form-control" id="full_name" name="full_name" required maxlength="120" value="<?= e($_POST['full_name'] ?? ''); ?>"></div>
                <div class="form-group"><label class="form-label" for="email">Email address *</label><input class="form-control" type="email" id="email" name="email" required maxlength="120" value="<?= e($_POST['email'] ?? ''); ?>"></div>
                <div class="form-group"><label class="form-label" for="phone">Phone</label><input class="form-control" type="tel" id="phone" name="phone" maxlength="30" value="<?= e($_POST['phone'] ?? ''); ?>"></div>
                <div class="form-group"><label class="form-label" for="password">Password *</label><input class="form-control" type="password" id="password" name="password" minlength="8" required autocomplete="new-password"></div>
                <div class="form-group"><label class="form-label" for="confirm_password">Confirm password *</label><input class="form-control" type="password" id="confirm_password" name="confirm_password" minlength="8" required autocomplete="new-password"></div>
                <button class="btn btn-primary btn-block" type="submit"><i class="fas fa-user-shield"></i> Create administrator account</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
