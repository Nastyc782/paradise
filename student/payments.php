<?php
require_once __DIR__ . '/../includes/student-auth.php';
$user = current_user();
$page_title = "My Payments — DIGITAL SMART CLASS";

$payments = [];
if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, c.title as course_title
            FROM payments p
            JOIN courses c ON p.course_id = c.id
            WHERE p.user_id = :uid
            ORDER BY p.created_at DESC
        ");
        $stmt->execute(['uid' => $user['id']]);
        $payments = $stmt->fetchAll();
    } catch (PDOException $e) {
        $payments = [];
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar avatar-student"><i class="fas fa-user-graduate"></i></div>
            <div class="sidebar-user-info">
                <h5><?= e($user['full_name']); ?></h5>
                <span>Student</span>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item"><a href="<?= site_url('student/dashboard.php'); ?>"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li class="sidebar-item"><a href="<?= site_url('student/courses.php'); ?>"><i class="fas fa-compass"></i> Courses</a></li>
            <li class="sidebar-item"><a href="<?= site_url('student/my-courses.php'); ?>"><i class="fas fa-play-circle"></i> My Courses</a></li>
            <li class="sidebar-item active"><a href="<?= site_url('student/payments.php'); ?>"><i class="fas fa-receipt"></i> Payments</a></li>
            <li class="sidebar-item"><a href="<?= site_url('student/progress.php'); ?>"><i class="fas fa-chart-line"></i> Progress</a></li>
            <li class="sidebar-item"><a href="<?= site_url('student/notifications.php'); ?>"><i class="fas fa-bell"></i> Notifications</a></li>
            <li class="sidebar-item"><a href="<?= site_url('student/profile.php'); ?>"><i class="fas fa-user-cog"></i> Profile</a></li>
            <li class="sidebar-item sidebar-item-divider">
                <a href="<?= site_url('logout.php'); ?>" class="sidebar-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-title-bar">
            <div>
                <h2>Payment History & Status</h2>
                <p class="page-subtitle">Track verification of MTN Mobile Money and bank tuition payments.</p>
            </div>
            <a href="<?= site_url('student/payment.php'); ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Submit New Payment
            </a>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Course</th>
                            <th>Amount (RWF)</th>
                            <th>Payment Method</th>
                            <th>Payment Reference</th>
                            <th>Transaction Ref</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2.5rem; color: #64748b;">
                                    No payment records submitted yet.<br>
                                    <a href="<?= site_url('student/courses.php'); ?>" class="btn btn-primary btn-sm" style="margin-top: 1rem;">
                                        Browse Available Courses
                                    </a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payments as $p): ?>
                                <tr>
                                    <td><?= date('d M Y, H:i', strtotime($p['created_at'])); ?></td>
                                    <td style="font-weight: 600; color: #0f172a;"><?= e($p['course_title']); ?></td>
                                    <td><?= format_money($p['amount'], $p['currency'] ?? 'RWF'); ?></td>
                                    <td><?= e($p['payment_method']); ?></td>
                                    <td><code><?= e($p['payment_reference'] ?: 'Pending generation'); ?></code></td>
                                    <td><code><?= e($p['transaction_ref']); ?></code></td>
                                    <td>
                                        <span class="badge badge-<?= $p['status']; ?>">
                                            <?= e(ucfirst($p['status'])); ?>
                                        </span>
                                        <?php if ($p['status'] === 'pending'): ?>
                                            <div class="form-hint">Awaiting administrator verification.</div>
                                        <?php elseif ($p['status'] === 'rejected' && !empty($p['accountant_note'])): ?>
                                            <div style="font-size: .78rem; color: var(--danger); margin-top: .35rem;">Reason: <?= e($p['accountant_note']); ?></div>
                                            <a href="<?= site_url('student/payment.php?course_id=' . (int)$p['course_id']); ?>" class="form-hint">Resubmit proof</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
