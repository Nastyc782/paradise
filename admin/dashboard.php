<?php
require_once __DIR__ . '/../includes/admin-auth.php';
$user = current_user();
$page_title = "ADMIN DASHBOARD — DIGITAL SMART CLASS";

$studentsCount = 0;
$teachersCount = 0;
$coursesCount = 0;
$pendingTeachersCount = 0;
$pendingPaymentsCount = 0;
$totalRevenueFormatted = '0 RWF';
$activeEnrollmentsCount = 0;
$completedEnrollmentsCount = 0;
$recentPayments = [];
$registrationActivity = [];
$recentUsers = [];
$recentCourses = [];
$activityMax = 0;
$dashboardError = null;

if ($db_connected && $pdo) {
    try {
        $studentsCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 3")->fetchColumn();
        $teachersCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 2 AND status = 'active'")->fetchColumn();
        $coursesCount = (int)$pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
        $pendingTeachersCount = (int)$pdo->query("SELECT COUNT(*) FROM teacher_applications WHERE status = 'pending'")->fetchColumn();
        $pendingPaymentsCount = (int)$pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn();
        $revenue = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'approved'")->fetchColumn();
        $totalRevenueFormatted = number_format($revenue) . ' RWF';

        $activeEnrollmentsCount = (int)$pdo->query("SELECT COUNT(*) FROM enrollments WHERE status = 'active'")->fetchColumn();
        $completedEnrollmentsCount = (int)$pdo->query("SELECT COUNT(*) FROM enrollments WHERE status = 'completed'")->fetchColumn();
        $recentPayments = $pdo->query("
            SELECT p.amount, p.status, p.created_at, u.full_name, c.title
            FROM payments p
            JOIN users u ON u.id = p.user_id
            JOIN courses c ON c.id = p.course_id
            ORDER BY p.created_at DESC
            LIMIT 5
        ")->fetchAll();
        $registrationActivity = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_key, COUNT(*) AS registrations FROM users WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH) GROUP BY month_key ORDER BY month_key ASC")->fetchAll();
        $recentUsers = $pdo->query("SELECT full_name, email, role_id, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
        $recentCourses = $pdo->query("SELECT title, status, created_at FROM courses ORDER BY created_at DESC LIMIT 5")->fetchAll();
        foreach ($registrationActivity as $activity) {
            $activityMax = max($activityMax, (int)$activity['registrations']);
        }
    } catch (PDOException $e) {
        log_database_error('Load admin dashboard', $e);
        $dashboardError = 'Platform metrics could not be loaded. Please refresh the page.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <!-- Admin Sidebar (Matches Section 14 & 16) -->
    <aside class="sidebar">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar avatar-admin"><i class="fas fa-user-shield"></i></div>
            <div class="sidebar-user-info">
                <h5><?= e($user['full_name']); ?></h5>
                <span>Super Administrator</span>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item active"><a href="<?= site_url('admin/dashboard.php'); ?>"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/students.php'); ?>"><i class="fas fa-user-graduate"></i> Students</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/teachers.php'); ?>"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/teacher-applications.php'); ?>"><i class="fas fa-id-card"></i> Teacher Applications</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/courses.php'); ?>"><i class="fas fa-book"></i> Courses</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/categories.php'); ?>"><i class="fas fa-folder"></i> Categories</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/payments.php'); ?>"><i class="fas fa-money-check-alt"></i> Payments</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/enrollments.php'); ?>"><i class="fas fa-user-check"></i> Enrollments</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/progress.php'); ?>"><i class="fas fa-tasks"></i> Progress</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/accountants.php'); ?>"><i class="fas fa-calculator"></i> Accountants</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/reports.php'); ?>"><i class="fas fa-file-invoice-dollar"></i> Reports</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/notifications.php'); ?>"><i class="fas fa-bell"></i> Notifications</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/coupons.php'); ?>"><i class="fas fa-tags"></i> Coupons</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/audit-logs.php'); ?>"><i class="fas fa-shield-alt"></i> Audit Logs</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/settings.php'); ?>"><i class="fas fa-cog"></i> Settings</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/profile.php'); ?>"><i class="fas fa-user-cog"></i> Profile</a></li>
            <li class="sidebar-item sidebar-item-divider">
                <a href="<?= site_url('logout.php'); ?>" class="sidebar-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <?php if ($dashboardError): ?>
            <div class="alert alert-danger" role="alert"><?= e($dashboardError); ?></div>
        <?php endif; ?>
        <div class="dashboard-title-bar">
            <div>
                <h2>Welcome, Administrator</h2>
                <p class="page-subtitle">Full platform control: users, teacher applications, payments, and system health.</p>
            </div>
        </div>

        <div class="dashboard-grid-stats admin-dashboard-stats">
            <div class="stat-box">
                <div class="stat-box-title">Students</div>
                <div class="stat-box-value"><?= number_format($studentsCount); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-box-title">Teachers</div>
                <div class="stat-box-value"><?= number_format($teachersCount); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-box-title">Courses</div>
                <div class="stat-box-value"><?= number_format($coursesCount); ?></div>
            </div>
        </div>

        <div class="dashboard-grid-stats admin-dashboard-stats admin-dashboard-stats-spaced">
            <div class="stat-box">
                <div class="stat-box-title">Pending Teachers</div>
                <div class="stat-box-value text-accent"><?= number_format($pendingTeachersCount); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-box-title">Pending Payments</div>
                <div class="stat-box-value text-danger"><?= number_format($pendingPaymentsCount); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-box-title">Revenue</div>
                <div class="stat-box-value text-success"><?= $totalRevenueFormatted; ?></div>
            </div>
        </div>

        <div class="dashboard-grid-stats admin-dashboard-secondary-stats">
            <div class="stat-box">
                <div class="stat-box-title">Active Enrollments</div>
                <div class="stat-box-value text-secondary"><?= number_format($activeEnrollmentsCount); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-box-title">Completed Enrollments</div>
                <div class="stat-box-value text-violet"><?= number_format($completedEnrollmentsCount); ?></div>
            </div>
        </div>

        <section class="admin-insights-grid" aria-label="Live platform activity">
            <article class="admin-insight-card admin-activity-card">
                <div class="admin-insight-header">
                    <div><span class="admin-insight-kicker">Live data</span><h3>Registration activity</h3></div>
                    <span class="admin-insight-icon"><i class="fas fa-chart-line" aria-hidden="true"></i></span>
                </div>
                <?php if (empty($registrationActivity)): ?>
                    <div class="admin-insight-empty"><i class="fas fa-chart-simple"></i><span>Real registration activity will appear here as users join.</span></div>
                <?php else: ?>
                    <div class="admin-bars" aria-label="User registrations by month">
                        <?php foreach ($registrationActivity as $activity): $barHeight = $activityMax > 0 ? max(12, (int)round(((int)$activity['registrations'] / $activityMax) * 100)) : 12; ?>
                            <div class="admin-bar-item"><span class="admin-bar-value"><?= (int)$activity['registrations']; ?></span><span class="admin-bar" style="height: <?= $barHeight; ?>%;"></span><span class="admin-bar-label"><?= e(date('M', strtotime($activity['month_key'] . '-01'))); ?></span></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>

            <article class="admin-insight-card admin-records-card">
                <div class="admin-insight-header"><div><span class="admin-insight-kicker">Database records</span><h3>Recently added users</h3></div><a href="<?= site_url('admin/users.php'); ?>" class="admin-insight-link">View all</a></div>
                <?php if (empty($recentUsers)): ?>
                    <div class="admin-insight-empty"><i class="fas fa-users"></i><span>No user records yet.</span></div>
                <?php else: ?>
                    <div class="admin-record-list">
                        <?php foreach ($recentUsers as $recentUser): ?><div class="admin-record-row"><span class="admin-record-avatar"><i class="fas fa-user"></i></span><span class="admin-record-copy"><strong><?= e($recentUser['full_name']); ?></strong><small><?= e($recentUser['email']); ?></small></span><time><?= e(date('d M', strtotime($recentUser['created_at']))); ?></time></div><?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>

            <article class="admin-insight-card admin-records-card">
                <div class="admin-insight-header"><div><span class="admin-insight-kicker">Course catalog</span><h3>Recently added courses</h3></div><a href="<?= site_url('admin/courses.php'); ?>" class="admin-insight-link">View all</a></div>
                <?php if (empty($recentCourses)): ?>
                    <div class="admin-insight-empty"><i class="fas fa-book-open"></i><span>No course records yet.</span></div>
                <?php else: ?>
                    <div class="admin-record-list">
                        <?php foreach ($recentCourses as $recentCourse): ?><div class="admin-record-row"><span class="admin-record-avatar admin-record-avatar--course"><i class="fas fa-book"></i></span><span class="admin-record-copy"><strong><?= e($recentCourse['title']); ?></strong><small><?= e(ucfirst($recentCourse['status'])); ?></small></span><time><?= e(date('d M', strtotime($recentCourse['created_at']))); ?></time></div><?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>
        </section>

        <?php if (!empty($recentPayments)): ?>
            <div class="table-card" style="max-width: 900px; margin-bottom: 2.5rem;">
                <div class="table-card-header">
                    <h3 class="section-heading">Recent Payment Activity</h3>
                    <a href="<?= site_url('admin/payments.php'); ?>" class="btn btn-outline btn-sm">View all payments</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead><tr><th>Student</th><th>Course</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach ($recentPayments as $payment): ?>
                                <tr>
                                    <td><?= e($payment['full_name']); ?></td>
                                    <td><?= e($payment['title']); ?></td>
                                    <td><?= format_money($payment['amount'], 'RWF'); ?></td>
                                    <td><span class="badge badge-<?= e($payment['status']); ?>"><?= e(ucfirst($payment['status'])); ?></span></td>
                                    <td><?= date('d M Y', strtotime($payment['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- QUICK ACTIONS (Section 14 Mockup) -->
        <div style="margin-bottom: 2.5rem;">
            <h3 style="font-size: 1.25rem; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 1rem;">
                QUICK ACTIONS
            </h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
                <a href="<?= site_url('admin/students.php'); ?>" class="btn btn-outline" style="padding: 1rem; font-weight: 700;">
                    [Manage Students]
                </a>
                <a href="<?= site_url('admin/teacher-applications.php'); ?>" class="btn btn-outline" style="padding: 1rem; font-weight: 700;">
                    <i class="fas fa-id-card"></i> Review Teacher Applications
                </a>
                <a href="<?= site_url('admin/teachers.php'); ?>" class="btn btn-outline" style="padding: 1rem; font-weight: 700;">
                    [Manage Teachers]
                </a>
                <a href="<?= site_url('admin/courses.php'); ?>" class="btn btn-outline" style="padding: 1rem; font-weight: 700;">
                    [Manage Courses]
                </a>
                <a href="<?= site_url('admin/payments.php'); ?>" class="btn btn-outline" style="padding: 1rem; font-weight: 700;">
                    [Payment Management]
                </a>
                <a href="<?= site_url('admin/enrollments.php'); ?>" class="btn btn-outline" style="padding: 1rem; font-weight: 700;">
                    [Enrollments]
                </a>
                <a href="<?= site_url('admin/reports.php'); ?>" class="btn btn-outline" style="padding: 1rem; font-weight: 700;">
                    [Reports]
                </a>
                <a href="<?= site_url('admin/notifications.php'); ?>" class="btn btn-outline" style="padding: 1rem; font-weight: 700;">
                    [Notifications]
                </a>
                <a href="<?= site_url('admin/settings.php'); ?>" class="btn btn-outline" style="padding: 1rem; font-weight: 700;">
                    [Settings]
                </a>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
