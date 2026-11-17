<?php
require_once __DIR__ . '/../includes/accountant-auth.php';
$user = current_user();
$page_title = 'Accountant Profile — Digital Smart Class';
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['new_password'] ?? '';
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Please try again.';
    } elseif ($fullName === '' || strlen($fullName) > 120 || strlen($phone) > 30) {
        $error = 'Enter a valid name and phone number.';
    } elseif ($password !== '' && strlen($password) < 8) {
        $error = 'New passwords must contain at least 8 characters.';
    } elseif (!$db_connected || !$pdo) {
        $error = 'Profile updates require a database connection.';
    } else {
        try {
            $sql = 'UPDATE users SET full_name = :full_name, phone = :phone';
            $params = ['full_name' => $fullName, 'phone' => $phone !== '' ? $phone : null, 'id' => $user['id']];
            if ($password !== '') { $sql .= ', password = :password'; $params['password'] = password_hash($password, PASSWORD_DEFAULT); }
            $sql .= ' WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $_SESSION['user_name'] = $fullName;
            $user['full_name'] = $fullName;
            $success = 'Your accountant profile has been updated.';
        } catch (PDOException $e) {
            log_database_error('Update accountant profile', $e);
            $error = 'Your profile could not be updated.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <aside class="sidebar"><div class="sidebar-user"><div class="sidebar-user-avatar avatar-accountant"><i class="fas fa-calculator"></i></div><div class="sidebar-user-info"><h5><?= e($user['full_name']); ?></h5><span>Finance &amp; Accounting</span></div></div><ul class="sidebar-menu"><li class="sidebar-item"><a href="<?= site_url('accountant/dashboard.php'); ?>"><i class="fas fa-th-large"></i> Dashboard</a></li><li class="sidebar-item"><a href="<?= site_url('accountant/payments.php'); ?>"><i class="fas fa-receipt"></i> Payments</a></li><li class="sidebar-item"><a href="<?= site_url('accountant/revenue.php'); ?>"><i class="fas fa-coins"></i> Revenue</a></li><li class="sidebar-item active"><a href="<?= site_url('accountant/profile.php'); ?>"><i class="fas fa-user-cog"></i> Profile</a></li><li class="sidebar-item sidebar-item-divider"><a href="<?= site_url('logout.php'); ?>" class="sidebar-logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li></ul></aside>
    <main class="dashboard-main"><div class="dashboard-title-bar"><div><h2>Accountant profile</h2><p class="page-subtitle">Update your personal details and account password.</p></div></div><?php if ($success): ?><div class="alert alert-success"><?= e($success); ?></div><?php endif; ?><?php if ($error): ?><div class="alert alert-danger"><?= e($error); ?></div><?php endif; ?><div class="panel" style="max-width:640px;"><form method="POST"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>"><div class="form-group"><label class="form-label" for="full_name">Full name</label><input class="form-control" id="full_name" name="full_name" required maxlength="120" value="<?= e($user['full_name']); ?>"></div><div class="form-group"><label class="form-label" for="email">Email</label><input class="form-control" id="email" value="<?= e($user['email']); ?>" disabled></div><div class="form-group"><label class="form-label" for="phone">Phone</label><input class="form-control" id="phone" name="phone" maxlength="30" value="<?= e($_POST['phone'] ?? $user['phone'] ?? ''); ?>"></div><div class="form-group"><label class="form-label" for="new_password">New password</label><input class="form-control" type="password" id="new_password" name="new_password" minlength="8" autocomplete="new-password"><span class="form-hint">Leave blank to keep the existing password.</span></div><button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Save changes</button></form></div></main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
