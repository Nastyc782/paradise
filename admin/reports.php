<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin', 'accountant']);
$user = current_user();
$page_title = 'Financial Reports — Digital Smart Class';

$methodTotals = [];
$courseTotals = [];
$totalProcessed = 0.0;
$loadError = null;
if ($db_connected && $pdo) {
    try {
        $methodTotals = $pdo->query("SELECT payment_method, COALESCE(SUM(amount), 0) AS total FROM payments WHERE status = 'approved' GROUP BY payment_method ORDER BY total DESC")->fetchAll();
        $courseTotals = $pdo->query("SELECT c.title, c.price, c.currency, COUNT(DISTINCT p.user_id) AS paid_students, COALESCE(SUM(p.amount), 0) AS total FROM payments p JOIN courses c ON c.id = p.course_id WHERE p.status = 'approved' GROUP BY c.id, c.title, c.price, c.currency ORDER BY total DESC")->fetchAll();
        $totalProcessed = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'approved'")->fetchColumn();
    } catch (PDOException $e) {
        log_database_error('Load financial reports', $e);
        $loadError = 'Financial records could not be loaded.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-user"><div class="sidebar-user-avatar avatar-admin"><i class="fas fa-user-shield"></i></div><div class="sidebar-user-info"><h5><?= e($user['full_name']); ?></h5><span><?= e(ucfirst($user['role'])); ?></span></div></div>
        <?php if ($user['role'] === 'accountant'): ?>
            <ul class="sidebar-menu">
                <li class="sidebar-item"><a href="<?= site_url('accountant/dashboard.php'); ?>"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
                <li class="sidebar-item"><a href="<?= site_url('accountant/payments.php'); ?>"><i class="fas fa-receipt"></i> Payments</a></li>
                <li class="sidebar-item"><a href="<?= site_url('accountant/approved.php'); ?>"><i class="fas fa-check-circle"></i> Approved</a></li>
                <li class="sidebar-item"><a href="<?= site_url('accountant/rejected.php'); ?>"><i class="fas fa-times-circle"></i> Rejected</a></li>
                <li class="sidebar-item active"><a href="<?= site_url('accountant/reports.php'); ?>"><i class="fas fa-file-invoice"></i> Reports</a></li>
                <li class="sidebar-item"><a href="<?= site_url('accountant/profile.php'); ?>"><i class="fas fa-user-cog"></i> Profile</a></li>
                <li class="sidebar-item sidebar-item-divider"><a href="<?= site_url('logout.php'); ?>" class="sidebar-logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        <?php else: ?>
            <ul class="sidebar-menu">
                <li class="sidebar-item"><a href="<?= site_url('admin/dashboard.php'); ?>"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
                <li class="sidebar-item"><a href="<?= site_url('admin/users.php'); ?>"><i class="fas fa-users-cog"></i> Manage Users</a></li>
                <li class="sidebar-item"><a href="<?= site_url('admin/courses.php'); ?>"><i class="fas fa-graduation-cap"></i> Manage Courses</a></li>
                <li class="sidebar-item"><a href="<?= site_url('admin/payments.php'); ?>"><i class="fas fa-money-check-alt"></i> Payments Verification</a></li>
                <li class="sidebar-item active"><a href="<?= site_url('admin/reports.php'); ?>"><i class="fas fa-file-invoice-dollar"></i> Financial Reports</a></li>
                <li class="sidebar-item"><a href="<?= site_url('admin/profile.php'); ?>"><i class="fas fa-user-cog"></i> Profile</a></li>
                <li class="sidebar-item sidebar-item-divider"><a href="<?= site_url('logout.php'); ?>" class="sidebar-logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        <?php endif; ?>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-title-bar"><div><h2>Revenue and enrollment reports</h2><p class="page-subtitle">Verified payment analytics generated from live database records.</p></div><button onclick="window.print()" class="btn btn-outline btn-sm"><i class="fas fa-print"></i> Print report</button></div>
        <?php if ($loadError): ?><div class="alert alert-danger"><?= e($loadError); ?></div><?php endif; ?>
        <div class="stats-grid">
            <?php foreach ($methodTotals as $method): ?><div class="stat-card"><div class="stat-card-title"><?= e($method['payment_method']); ?></div><div class="stat-card-value text-primary"><?= e(format_money($method['total'], 'RWF')); ?></div></div><?php endforeach; ?>
            <div class="stat-card"><div class="stat-card-title">Total processed</div><div class="stat-card-value text-success"><?= e(format_money($totalProcessed, 'RWF')); ?></div></div>
        </div>
        <div class="table-card"><div class="table-card-header"><h3 class="section-heading">Revenue generated by course</h3></div><div class="table-responsive"><table><thead><tr><th>Course name</th><th>Paid students</th><th>Tuition fee</th><th>Total earned</th></tr></thead><tbody>
            <?php if (empty($courseTotals)): ?><tr><td colspan="4" class="table-empty-state">No approved payment records exist yet.</td></tr>
            <?php else: foreach ($courseTotals as $row): ?><tr><td><strong><?= e($row['title']); ?></strong></td><td><?= (int)$row['paid_students']; ?></td><td><?= e(format_money($row['price'], $row['currency'] ?? 'RWF')); ?></td><td><strong class="text-success"><?= e(format_money($row['total'], $row['currency'] ?? 'RWF')); ?></strong></td></tr><?php endforeach; endif; ?>
        </tbody></table></div></div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
