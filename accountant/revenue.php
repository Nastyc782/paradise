<?php
require_once __DIR__ . '/../includes/accountant-auth.php';
$user = current_user();
$page_title = 'Revenue Audit — DIGITAL SMART CLASS';

$methodRevenue = [];
$courseRevenue = [];
$totalRevenue = 0.0;
$loadError = null;
if ($db_connected && $pdo) {
    try {
        $methodRevenue = $pdo->query("SELECT payment_method, COALESCE(SUM(amount), 0) AS total FROM payments WHERE status = 'approved' GROUP BY payment_method ORDER BY total DESC")->fetchAll();
        $courseRevenue = $pdo->query("SELECT c.title, c.price, c.currency, COUNT(DISTINCT p.user_id) AS paid_students, COALESCE(SUM(p.amount), 0) AS total FROM payments p JOIN courses c ON c.id = p.course_id WHERE p.status = 'approved' GROUP BY c.id, c.title, c.price, c.currency ORDER BY total DESC")->fetchAll();
        $totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'approved'")->fetchColumn();
    } catch (PDOException $e) {
        log_database_error('Load revenue audit', $e);
        $loadError = 'Revenue records could not be loaded.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-user"><div class="sidebar-user-avatar avatar-accountant"><i class="fas fa-calculator"></i></div><div class="sidebar-user-info"><h5><?= e($user['full_name']); ?></h5><span>Finance &amp; Accounting</span></div></div>
        <ul class="sidebar-menu">
            <li class="sidebar-item"><a href="<?= site_url('accountant/dashboard.php'); ?>"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li class="sidebar-item"><a href="<?= site_url('accountant/payments.php'); ?>"><i class="fas fa-receipt"></i> Payments</a></li>
            <li class="sidebar-item"><a href="<?= site_url('accountant/approved.php'); ?>"><i class="fas fa-check-circle"></i> Approved</a></li>
            <li class="sidebar-item"><a href="<?= site_url('accountant/rejected.php'); ?>"><i class="fas fa-times-circle"></i> Rejected</a></li>
            <li class="sidebar-item active"><a href="<?= site_url('accountant/revenue.php'); ?>"><i class="fas fa-coins"></i> Revenue</a></li>
            <li class="sidebar-item"><a href="<?= site_url('accountant/reports.php'); ?>"><i class="fas fa-file-invoice"></i> Reports</a></li>
            <li class="sidebar-item sidebar-item-divider"><a href="<?= site_url('logout.php'); ?>" class="sidebar-logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-title-bar"><div><h2>Platform revenue analysis</h2><p class="page-subtitle">Verified payment totals from the live transaction ledger.</p></div></div>
        <?php if ($loadError): ?><div class="alert alert-danger"><?= e($loadError); ?></div><?php endif; ?>
        <div class="stats-grid">
            <?php foreach ($methodRevenue as $method): ?><div class="stat-card"><div class="stat-card-title"><?= e($method['payment_method']); ?></div><div class="stat-card-value text-primary"><?= e(format_money($method['total'], 'RWF')); ?></div></div><?php endforeach; ?>
            <div class="stat-card"><div class="stat-card-title">Total approved revenue</div><div class="stat-card-value text-success"><?= e(format_money($totalRevenue, 'RWF')); ?></div></div>
        </div>

        <div class="table-card">
            <div class="table-card-header"><h3 class="section-heading">Revenue per course</h3></div>
            <div class="table-responsive"><table><thead><tr><th>Course</th><th>Tuition fee</th><th>Verified students</th><th>Total earned</th></tr></thead><tbody>
                <?php if (empty($courseRevenue)): ?><tr><td colspan="4" class="table-empty-state">No approved payments have been recorded yet.</td></tr>
                <?php else: foreach ($courseRevenue as $row): ?><tr><td><strong><?= e($row['title']); ?></strong></td><td><?= e(format_money($row['price'], $row['currency'] ?? 'RWF')); ?></td><td><?= (int)$row['paid_students']; ?></td><td><strong class="text-success"><?= e(format_money($row['total'], $row['currency'] ?? 'RWF')); ?></strong></td></tr><?php endforeach; endif; ?>
            </tbody></table></div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
