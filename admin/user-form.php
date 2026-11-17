<?php
require_once __DIR__ . '/../includes/admin-auth.php';

$admin = current_user();
$page_title = 'User Account — DIGITAL SMART CLASS';
$userId = (int)($_GET['id'] ?? $_POST['user_id'] ?? 0);
$isEdit = $userId > 0;
$error = null;
$target = null;

$roles = [];
if ($db_connected && $pdo) {
    $roles = $pdo->query('SELECT id, name, display_name FROM roles ORDER BY id')->fetchAll();
    if ($isEdit) {
        $stmt = $pdo->prepare('
            SELECT u.*, r.name AS role_name
            FROM users u
            JOIN roles r ON r.id = u.role_id
            WHERE u.id = :id
            LIMIT 1
        ');
        $stmt->execute(['id' => $userId]);
        $target = $stmt->fetch() ?: null;
        if (!$target) {
            set_flash('error', 'That user account could not be found.');
            header('Location: ' . site_url('admin/users.php'));
            exit;
        }
    }
}

$formValues = [
    'full_name' => $_POST['full_name'] ?? ($target['full_name'] ?? ''),
    'email' => $_POST['email'] ?? ($target['email'] ?? ''),
    'phone' => $_POST['phone'] ?? ($target['phone'] ?? ''),
    'qualification' => $_POST['qualification'] ?? ($target['qualification'] ?? ''),
    'experience' => $_POST['experience'] ?? ($target['experience'] ?? ''),
    'address' => $_POST['address'] ?? ($target['address'] ?? ''),
    'role_id' => (int)($_POST['role_id'] ?? ($target['role_id'] ?? 3)),
    'status' => $_POST['status'] ?? ($target['status'] ?? 'active')
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $fullName = trim((string)$formValues['full_name']);
    $email = strtolower(trim((string)$formValues['email']));
    $phone = trim((string)$formValues['phone']);
    $qualification = trim((string)$formValues['qualification']);
    $experience = trim((string)$formValues['experience']);
    $address = trim((string)$formValues['address']);
    $roleId = (int)$formValues['role_id'];
    $status = (string)$formValues['status'];

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Refresh the page and submit the form again.';
    } elseif (!$db_connected || !$pdo) {
        $error = 'User management is unavailable while the database is disconnected.';
    } elseif ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid full name and email address.';
    } elseif (strlen($fullName) > 120 || strlen($email) > 120 || strlen($phone) > 30 || strlen($qualification) > 200 || strlen($experience) > 100 || strlen($address) > 255) {
        $error = 'One or more fields exceed the allowed length.';
    } elseif (!in_array($status, ['active', 'inactive', 'suspended', 'pending'], true)) {
        $error = 'Choose a valid account status.';
    } elseif (!$isEdit && strlen($password) < 8) {
        $error = 'A new account password must be at least 8 characters.';
    } elseif ($password !== '' && $password !== $confirmPassword) {
        $error = 'Password confirmation does not match.';
    } elseif ($password !== '' && strlen($password) < 8) {
        $error = 'Passwords must be at least 8 characters long.';
    } else {
        try {
            $roleStmt = $pdo->prepare('SELECT id, name FROM roles WHERE id = :id LIMIT 1');
            $roleStmt->execute(['id' => $roleId]);
            $role = $roleStmt->fetch();
            if (!$role) {
                throw new RuntimeException('Choose a valid system role.');
            }

            $duplicate = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = :email AND id <> :id LIMIT 1');
            $duplicate->execute(['email' => $email, 'id' => $userId]);
            if ($duplicate->fetch()) {
                throw new RuntimeException('Another account already uses this email address.');
            }

            if ($isEdit && $userId === (int)$admin['id'] && ($role['name'] !== 'admin' || $status !== 'active')) {
                throw new RuntimeException('You cannot remove your own administrator access or deactivate your current account.');
            }

            if ($isEdit && $target && $target['role_name'] === 'admin' && ($role['name'] !== 'admin' || $status !== 'active')) {
                $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE r.name = 'admin' AND u.status = 'active'")->fetchColumn();
                if ($adminCount < 2) {
                    throw new RuntimeException('The last active administrator cannot be removed or deactivated.');
                }
            }

            if ($isEdit) {
                $sql = '
                    UPDATE users
                    SET role_id = :role_id, full_name = :full_name, email = :email, phone = :phone,
                        qualification = :qualification, experience = :experience, address = :address, status = :status
                ';
                $params = [
                    'role_id' => $roleId,
                    'full_name' => $fullName,
                    'email' => $email,
                    'phone' => $phone !== '' ? $phone : null,
                    'qualification' => $qualification !== '' ? $qualification : null,
                    'experience' => $experience !== '' ? $experience : null,
                    'address' => $address !== '' ? $address : null,
                    'status' => $status,
                    'id' => $userId
                ];
                if ($password !== '') {
                    $sql .= ', password = :password';
                    $params['password'] = password_hash($password, PASSWORD_DEFAULT);
                }
                $sql .= ' WHERE id = :id';
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                set_flash('success', 'The user account has been updated.');
            } else {
                $stmt = $pdo->prepare('
                    INSERT INTO users (role_id, full_name, email, phone, password, qualification, experience, address, status)
                    VALUES (:role_id, :full_name, :email, :phone, :password, :qualification, :experience, :address, :status)
                ');
                $stmt->execute([
                    'role_id' => $roleId,
                    'full_name' => $fullName,
                    'email' => $email,
                    'phone' => $phone !== '' ? $phone : null,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'qualification' => $qualification !== '' ? $qualification : null,
                    'experience' => $experience !== '' ? $experience : null,
                    'address' => $address !== '' ? $address : null,
                    'status' => $status
                ]);
                set_flash('success', 'The user account has been created.');
            }

            header('Location: ' . site_url('admin/users.php'));
            exit;
        } catch (PDOException $e) {
            log_database_error('Save user account', $e);
            $error = 'The user account could not be saved. Please try again.';
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar avatar-admin"><i class="fas fa-user-shield"></i></div>
            <div class="sidebar-user-info"><h5><?= e($admin['full_name']); ?></h5><span>Administrator</span></div>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item"><a href="<?= site_url('admin/dashboard.php'); ?>"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
            <li class="sidebar-item active"><a href="<?= site_url('admin/users.php'); ?>"><i class="fas fa-users-cog"></i> Manage Users</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/teacher-applications.php'); ?>"><i class="fas fa-id-card"></i> Teacher Applications</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/courses.php'); ?>"><i class="fas fa-book"></i> Courses</a></li>
            <li class="sidebar-item sidebar-item-divider"><a href="<?= site_url('logout.php'); ?>" class="sidebar-logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-title-bar">
            <div>
                <h2><?= $isEdit ? 'Edit user account' : 'Create user account'; ?></h2>
                <p class="page-subtitle">Manage real database-backed identity, role, access status, and credentials.</p>
            </div>
            <a href="<?= site_url('admin/users.php'); ?>" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to users</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert"><i class="fas fa-exclamation-triangle"></i> <?= e($error); ?></div>
        <?php endif; ?>

        <div class="panel" style="max-width: 860px;">
            <form method="POST" action="<?= site_url('admin/user-form.php' . ($isEdit ? '?id=' . $userId : '')); ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                <?php if ($isEdit): ?><input type="hidden" name="user_id" value="<?= $userId; ?>"><?php endif; ?>
                <div class="form-grid-2">
                    <div class="form-group"><label class="form-label" for="full_name">Full name *</label><input class="form-control" id="full_name" name="full_name" required maxlength="120" value="<?= e($formValues['full_name']); ?>"></div>
                    <div class="form-group"><label class="form-label" for="email">Email address *</label><input class="form-control" type="email" id="email" name="email" required maxlength="120" value="<?= e($formValues['email']); ?>"></div>
                    <div class="form-group"><label class="form-label" for="phone">Phone</label><input class="form-control" id="phone" name="phone" maxlength="30" value="<?= e($formValues['phone']); ?>"></div>
                    <div class="form-group"><label class="form-label" for="role_id">Role *</label><select class="form-control" id="role_id" name="role_id" required><?php foreach ($roles as $role): ?><option value="<?= (int)$role['id']; ?>" <?= (int)$formValues['role_id'] === (int)$role['id'] ? 'selected' : ''; ?>><?= e($role['display_name']); ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label class="form-label" for="status">Account status *</label><select class="form-control" id="status" name="status" required><?php foreach (['active', 'inactive', 'suspended', 'pending'] as $status): ?><option value="<?= $status; ?>" <?= $formValues['status'] === $status ? 'selected' : ''; ?>><?= e(ucfirst($status)); ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label class="form-label" for="qualification">Qualification</label><input class="form-control" id="qualification" name="qualification" maxlength="200" value="<?= e($formValues['qualification']); ?>"></div>
                    <div class="form-group"><label class="form-label" for="experience">Experience</label><input class="form-control" id="experience" name="experience" maxlength="100" value="<?= e($formValues['experience']); ?>"></div>
                    <div class="form-group"><label class="form-label" for="address">Address</label><input class="form-control" id="address" name="address" maxlength="255" value="<?= e($formValues['address']); ?>"></div>
                    <div class="form-group"><label class="form-label" for="password"><?= $isEdit ? 'New password' : 'Password *'; ?></label><input class="form-control" type="password" id="password" name="password" minlength="8" <?= $isEdit ? '' : 'required'; ?> autocomplete="new-password"><span class="form-hint"><?= $isEdit ? 'Leave blank to keep the existing password.' : 'At least 8 characters.'; ?></span></div>
                    <div class="form-group"><label class="form-label" for="confirm_password">Confirm password<?= $isEdit ? '' : ' *'; ?></label><input class="form-control" type="password" id="confirm_password" name="confirm_password" minlength="8" <?= $isEdit ? '' : 'required'; ?> autocomplete="new-password"></div>
                </div>
                <div style="display:flex; gap:.75rem; margin-top:1.25rem;"><button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Save account</button><a class="btn btn-outline" href="<?= site_url('admin/users.php'); ?>">Cancel</a></div>
            </form>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
