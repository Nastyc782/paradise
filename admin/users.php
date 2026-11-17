<?php
require_once __DIR__ . '/../includes/admin-auth.php';

$user = current_user();
$page_title = 'Manage Users — Digital Smart Class';
$actionError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $targetId = (int)($_POST['user_id'] ?? 0);

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $actionError = 'Your session expired. Refresh the page and try again.';
    } elseif (!$db_connected || !$pdo || $targetId < 1) {
        $actionError = 'This user action is unavailable right now.';
    } else {
        try {
            $targetStmt = $pdo->prepare('
                SELECT u.*, r.name AS role_name
                FROM users u JOIN roles r ON r.id = u.role_id
                WHERE u.id = :id LIMIT 1
            ');
            $targetStmt->execute(['id' => $targetId]);
            $target = $targetStmt->fetch();
            if (!$target) {
                throw new RuntimeException('The selected user account no longer exists.');
            }

            if ($action === 'status') {
                $newStatus = $_POST['status'] ?? '';
                if (!in_array($newStatus, ['active', 'inactive', 'suspended', 'pending'], true)) {
                    throw new RuntimeException('Choose a valid account status.');
                }
                if ($targetId === (int)$user['id']) {
                    throw new RuntimeException('You cannot change the status of your current administrator account.');
                }
                if ($target['role_name'] === 'admin' && $newStatus !== 'active') {
                    $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE r.name = 'admin' AND u.status = 'active'")->fetchColumn();
                    if ($adminCount < 2) {
                        throw new RuntimeException('The last active administrator cannot be deactivated.');
                    }
                }
                $stmt = $pdo->prepare('UPDATE users SET status = :status WHERE id = :id');
                $stmt->execute(['status' => $newStatus, 'id' => $targetId]);
                set_flash('success', 'The account status has been updated.');
            } elseif ($action === 'reset_password') {
                $password = $_POST['password'] ?? '';
                $confirm = $_POST['confirm_password'] ?? '';
                if (strlen($password) < 8 || $password !== $confirm) {
                    throw new RuntimeException('Passwords must match and contain at least 8 characters.');
                }
                $stmt = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
                $stmt->execute(['password' => password_hash($password, PASSWORD_DEFAULT), 'id' => $targetId]);
                set_flash('success', 'The password has been reset securely.');
            } elseif ($action === 'delete') {
                if ($targetId === (int)$user['id']) {
                    throw new RuntimeException('You cannot delete your current administrator account.');
                }
                if ($target['role_name'] === 'admin') {
                    $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE r.name = 'admin' AND u.status = 'active'")->fetchColumn();
                    if ($adminCount < 2) {
                        throw new RuntimeException('The last administrator cannot be deleted.');
                    }
                }

                $dependencies = [];
                foreach ([
                    'courses' => 'teacher_id',
                    'enrollments' => 'user_id',
                    'payments' => 'user_id',
                    'teacher_applications' => 'user_id',
                    'notifications' => 'user_id',
                    'lesson_progress' => 'user_id',
                    'video_progress' => 'user_id'
                ] as $table => $column) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = :id");
                    $stmt->execute(['id' => $targetId]);
                    if ((int)$stmt->fetchColumn() > 0) {
                        $dependencies[] = $table;
                    }
                }
                if ($dependencies) {
                    throw new RuntimeException('This account has related records (' . implode(', ', $dependencies) . '). Deactivate it instead of deleting it.');
                }
                $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
                $stmt->execute(['id' => $targetId]);
                set_flash('success', 'The user account was permanently deleted.');
            } else {
                throw new RuntimeException('Unknown user action.');
            }
            header('Location: ' . site_url('admin/users.php'));
            exit;
        } catch (PDOException $e) {
            log_database_error('Manage user account', $e);
            $actionError = 'The requested user action could not be completed.';
        } catch (RuntimeException $e) {
            $actionError = $e->getMessage();
        }
    }
}

$search = trim($_GET['q'] ?? '');
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$usersList = [];
$loadError = null;

if ($db_connected && $pdo) {
    try {
        $where = [];
        $params = [];
        if ($search !== '') {
            $where[] = '(u.full_name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }
        if (in_array($roleFilter, ['admin', 'teacher', 'student', 'accountant'], true)) {
            $where[] = 'r.name = :role';
            $params['role'] = $roleFilter;
        } else {
            $roleFilter = '';
        }
        if (in_array($statusFilter, ['active', 'inactive', 'suspended', 'pending'], true)) {
            $where[] = 'u.status = :status';
            $params['status'] = $statusFilter;
        } else {
            $statusFilter = '';
        }
        $sql = '
            SELECT u.*, r.name AS role_name, r.display_name AS role_label
            FROM users u JOIN roles r ON r.id = u.role_id
        ';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY u.created_at DESC, u.id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $usersList = $stmt->fetchAll();
    } catch (PDOException $e) {
        log_database_error('Load user management list', $e);
        $loadError = 'User records could not be loaded. Please refresh the page.';
    }
} else {
    $loadError = 'User records are unavailable while the database is disconnected.';
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar avatar-admin"><i class="fas fa-user-shield"></i></div>
            <div class="sidebar-user-info"><h5><?= e($user['full_name']); ?></h5><span>Administrator</span></div>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item"><a href="<?= site_url('admin/dashboard.php'); ?>"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
            <li class="sidebar-item active"><a href="<?= site_url('admin/users.php'); ?>"><i class="fas fa-users-cog"></i> Manage Users</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/students.php'); ?>"><i class="fas fa-user-graduate"></i> Students</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/teachers.php'); ?>"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/teacher-applications.php'); ?>"><i class="fas fa-id-card"></i> Teacher Applications</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/accountants.php'); ?>"><i class="fas fa-calculator"></i> Accountants</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/courses.php'); ?>"><i class="fas fa-book"></i> Courses</a></li>
            <li class="sidebar-item sidebar-item-divider"><a href="<?= site_url('logout.php'); ?>" class="sidebar-logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-title-bar">
            <div>
                <h2>Manage users</h2>
                <p class="page-subtitle">Create, search, edit, secure, activate, and deactivate real platform accounts.</p>
            </div>
            <a href="<?= site_url('admin/user-form.php'); ?>" class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i> Create user</a>
        </div>

        <?php if ($actionError): ?><div class="alert alert-danger" role="alert"><i class="fas fa-exclamation-triangle"></i> <?= e($actionError); ?></div><?php endif; ?>
        <?php if ($loadError): ?><div class="alert alert-danger" role="alert"><?= e($loadError); ?></div><?php endif; ?>

        <form class="user-filter-bar" method="GET" action="<?= site_url('admin/users.php'); ?>">
            <input class="form-control" type="search" name="q" value="<?= e($search); ?>" placeholder="Search name, email, or phone" aria-label="Search users">
            <select class="form-control" name="role" aria-label="Filter by role"><option value="">All roles</option><?php foreach (['admin' => 'Administrator', 'teacher' => 'Teacher', 'student' => 'Student', 'accountant' => 'Accountant'] as $key => $label): ?><option value="<?= $key; ?>" <?= $roleFilter === $key ? 'selected' : ''; ?>><?= $label; ?></option><?php endforeach; ?></select>
            <select class="form-control" name="status" aria-label="Filter by status"><option value="">All statuses</option><?php foreach (['active', 'pending', 'inactive', 'suspended'] as $status): ?><option value="<?= $status; ?>" <?= $statusFilter === $status ? 'selected' : ''; ?>><?= ucfirst($status); ?></option><?php endforeach; ?></select>
            <button class="btn btn-outline-primary" type="submit"><i class="fas fa-filter"></i> Filter</button>
            <?php if ($search !== '' || $roleFilter !== '' || $statusFilter !== ''): ?><a class="btn btn-outline" href="<?= site_url('admin/users.php'); ?>">Clear</a><?php endif; ?>
        </form>

        <div class="table-card">
            <div class="table-card-header"><h3 class="section-heading">Registered accounts</h3><span class="text-sm"><?= count($usersList); ?> record<?= count($usersList) === 1 ? '' : 's'; ?></span></div>
            <div class="table-responsive">
                <table>
                    <thead><tr><th>User</th><th>Contact</th><th>Role</th><th>Registered</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php if (!$usersList): ?>
                        <tr><td colspan="6" class="table-empty-state">No user accounts match the current filters.</td></tr>
                    <?php else: foreach ($usersList as $account): ?>
                        <tr>
                            <td><strong><?= e($account['full_name']); ?></strong><br><span class="text-sm">#<?= (int)$account['id']; ?></span></td>
                            <td><?= e($account['email']); ?><br><span class="text-sm"><?= e($account['phone'] ?: 'No phone'); ?></span></td>
                            <td><span class="badge badge-role-<?= e($account['role_name']); ?>"><?= e($account['role_label']); ?></span></td>
                            <td><?= e(date('d M Y', strtotime($account['created_at']))); ?></td>
                            <td><span class="badge badge-<?= e($account['status']); ?>"><?= e(ucfirst($account['status'])); ?></span></td>
                            <td>
                                <div class="user-actions">
                                    <a class="btn btn-outline btn-sm" href="<?= site_url('admin/user-form.php?id=' . (int)$account['id']); ?>"><i class="fas fa-edit"></i> Edit</a>
                                    <?php if ((int)$account['id'] !== (int)$user['id']): ?>
                                        <form method="POST" action="<?= site_url('admin/users.php'); ?>" onsubmit="return confirm('Change this account status?');">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>"><input type="hidden" name="user_id" value="<?= (int)$account['id']; ?>"><input type="hidden" name="action" value="status"><input type="hidden" name="status" value="<?= $account['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                            <button class="btn btn-sm <?= $account['status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-primary'; ?>" type="submit"><?= $account['status'] === 'active' ? 'Deactivate' : 'Activate'; ?></button>
                                        </form>
                                        <details class="user-reset-details"><summary class="btn btn-outline btn-sm">Reset password</summary><form method="POST" class="user-reset-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>"><input type="hidden" name="user_id" value="<?= (int)$account['id']; ?>"><input type="hidden" name="action" value="reset_password"><input class="form-control" type="password" name="password" minlength="8" placeholder="New password" required><input class="form-control" type="password" name="confirm_password" minlength="8" placeholder="Confirm password" required><button class="btn btn-primary btn-sm" type="submit">Save password</button></form></details>
                                        <form method="POST" action="<?= site_url('admin/users.php'); ?>" onsubmit="return confirm('Delete this account permanently? Accounts with related records cannot be deleted.');"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>"><input type="hidden" name="user_id" value="<?= (int)$account['id']; ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-sm btn-outline-danger" type="submit" title="Delete account"><i class="fas fa-trash"></i></button></form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
