<?php
require_once __DIR__ . '/../includes/admin-auth.php';
$user = current_user();
$page_title = 'Audit Logs — Digital Smart Class';
if (isset($_GET['export']) && $_GET['export'] === 'csv' && $db_connected && $pdo) {
    $stmt = $pdo->query('SELECT a.id, u.email, a.action, a.details, a.ip_address, a.created_at FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="digital-smart-class-audit-logs.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'User', 'Action', 'Details', 'IP address', 'Created']);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) fputcsv($out, $row);
    fclose($out);
    exit;
}
$logs = $db_connected && $pdo ? $pdo->query('SELECT a.*, u.email FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC LIMIT 200')->fetchAll() : [];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-user"><div class="sidebar-user-avatar avatar-admin"><i class="fas fa-user-shield"></i></div><div class="sidebar-user-info"><h5><?= e($user['full_name']); ?></h5><span>Administrator</span></div></div>
        <ul class="sidebar-menu">
            <li class="sidebar-item"><a href="<?= site_url('admin/dashboard.php'); ?>"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/coupons.php'); ?>"><i class="fas fa-tags"></i> Coupons</a></li>
            <li class="sidebar-item active"><a href="<?= site_url('admin/audit-logs.php'); ?>"><i class="fas fa-shield-alt"></i> Audit Logs</a></li>
        </ul>
    </aside>
    <main class="dashboard-main">
        <div class="dashboard-title-bar"><div><h2>Audit Logs</h2><p class="page-subtitle">Review recent sign-ins and administrative actions.</p></div><a class="btn btn-outline btn-sm" href="<?= site_url('admin/audit-logs.php?export=csv'); ?>"><i class="fas fa-file-csv"></i> Export CSV</a></div>
        <div class="table-card"><div class="table-responsive"><table><thead><tr><th>Date</th><th>User</th><th>Action</th><th>Details</th><th>IP address</th></tr></thead><tbody>
        <?php if (empty($logs)): ?><tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--gray-500);">No audit records yet.</td></tr>
        <?php else: foreach ($logs as $log): ?><tr><td><?= e(date('d M Y H:i', strtotime($log['created_at']))); ?></td><td><?= e($log['email'] ?: 'System'); ?></td><td><code><?= e($log['action']); ?></code></td><td><?= e($log['details']); ?></td><td><?= e($log['ip_address']); ?></td></tr><?php endforeach; endif; ?>
        </tbody></table></div></div>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
