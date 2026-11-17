<?php
require_once __DIR__ . '/../includes/admin-auth.php';
$user = current_user();
$page_title = 'Manage Coupons — Digital Smart Class';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $type = $_POST['discount_type'] ?? 'percentage';
    $value = filter_var($_POST['discount_value'] ?? null, FILTER_VALIDATE_FLOAT);
    $maxUses = trim($_POST['max_uses'] ?? '');
    $expiresAt = trim($_POST['expires_at'] ?? '');
    $expiresAtValue = $expiresAt !== '' ? date('Y-m-d H:i:s', strtotime($expiresAt)) : null;
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Please try again.';
    } elseif (!preg_match('/^[A-Z0-9_-]{3,50}$/', $code) || !in_array($type, ['percentage', 'fixed'], true) || $value === false || $value <= 0 || ($type === 'percentage' && $value > 100)) {
        $error = 'Enter a valid code and discount value.';
    } elseif ($maxUses !== '' && (!ctype_digit($maxUses) || (int)$maxUses < 1)) {
        $error = 'Maximum uses must be a positive whole number.';
    } elseif (!$db_connected || !$pdo) {
        $error = 'Coupons are unavailable while the database is disconnected.';
    } else {
        try {
            $stmt = $pdo->prepare('
                INSERT INTO coupons (code, discount_type, discount_value, max_uses, expires_at, created_by)
                VALUES (:code, :type, :value, :max_uses, :expires_at, :created_by)
            ');
            $stmt->execute([
                'code' => $code,
                'type' => $type,
                'value' => $value,
                'max_uses' => $maxUses === '' ? null : (int)$maxUses,
                'expires_at' => $expiresAtValue,
                'created_by' => $user['id'],
            ]);
            audit_log('coupon.created', 'Created coupon ' . $code, $user['id']);
            set_flash('success', 'Coupon created successfully.');
            header('Location: ' . site_url('admin/coupons.php'));
            exit;
        } catch (PDOException $e) {
            log_database_error('Create coupon', $e);
            $error = 'The coupon code already exists or could not be saved.';
        }
    }
}

$coupons = [];
if ($db_connected && $pdo) {
    $coupons = $pdo->query('SELECT * FROM coupons ORDER BY created_at DESC')->fetchAll();
}
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-user"><div class="sidebar-user-avatar avatar-admin"><i class="fas fa-user-shield"></i></div><div class="sidebar-user-info"><h5><?= e($user['full_name']); ?></h5><span>Administrator</span></div></div>
        <ul class="sidebar-menu">
            <li class="sidebar-item"><a href="<?= site_url('admin/dashboard.php'); ?>"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
            <li class="sidebar-item active"><a href="<?= site_url('admin/coupons.php'); ?>"><i class="fas fa-tags"></i> Coupons</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/audit-logs.php'); ?>"><i class="fas fa-shield-alt"></i> Audit Logs</a></li>
        </ul>
    </aside>
    <main class="dashboard-main">
        <div class="dashboard-title-bar"><div><h2>Coupons & Promotions</h2><p class="page-subtitle">Create discounts students can use during checkout.</p></div></div>
        <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error); ?></div><?php endif; ?>
        <div class="panel" style="padding: 1.5rem; margin-bottom: 1.5rem;">
            <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; align-items: end;">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                <div class="form-group"><label class="form-label" for="code">Code</label><input class="form-control" id="code" name="code" required maxlength="50" placeholder="WELCOME10"></div>
                <div class="form-group"><label class="form-label" for="discount_type">Type</label><select class="form-control" id="discount_type" name="discount_type"><option value="percentage">Percentage</option><option value="fixed">Fixed RWF</option></select></div>
                <div class="form-group"><label class="form-label" for="discount_value">Discount</label><input class="form-control" id="discount_value" name="discount_value" type="number" min="0.01" step="0.01" required></div>
                <div class="form-group"><label class="form-label" for="max_uses">Max uses</label><input class="form-control" id="max_uses" name="max_uses" type="number" min="1"></div>
                <div class="form-group"><label class="form-label" for="expires_at">Expires</label><input class="form-control" id="expires_at" name="expires_at" type="datetime-local"></div>
                <button class="btn btn-primary" type="submit">Create coupon</button>
            </form>
        </div>
        <div class="table-card"><div class="table-responsive"><table><thead><tr><th>Code</th><th>Discount</th><th>Uses</th><th>Expires</th><th>Status</th></tr></thead><tbody>
        <?php if (empty($coupons)): ?><tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--gray-500);">No coupons created yet.</td></tr>
        <?php else: foreach ($coupons as $coupon): ?><tr><td><strong><?= e($coupon['code']); ?></strong></td><td><?= e($coupon['discount_type'] === 'percentage' ? $coupon['discount_value'] . '%' : format_money($coupon['discount_value'])); ?></td><td><?= (int)$coupon['used_count']; ?><?= $coupon['max_uses'] !== null ? ' / ' . (int)$coupon['max_uses'] : ''; ?></td><td><?= $coupon['expires_at'] ? e(date('d M Y H:i', strtotime($coupon['expires_at']))) : 'Never'; ?></td><td><span class="badge <?= $coupon['is_active'] ? 'badge-approved' : 'badge-rejected'; ?>"><?= $coupon['is_active'] ? 'Active' : 'Inactive'; ?></span></td></tr><?php endforeach; endif; ?>
        </tbody></table></div></div>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
