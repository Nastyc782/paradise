<?php
require_once __DIR__ . '/../includes/admin-auth.php';
$user = current_user();
$page_title = "Student Progress — DIGITAL SMART CLASS";

$progressRows = [];
if ($db_connected && $pdo) {
    try {
        $progressRows = $pdo->query('
            SELECT u.full_name, u.email, c.title,
                   (SELECT COUNT(*) FROM lessons l JOIN modules m ON m.id = l.module_id WHERE m.course_id = c.id) AS total_lessons,
                   (SELECT COUNT(*) FROM lesson_progress lp WHERE lp.user_id = u.id AND lp.course_id = c.id AND lp.is_completed = 1) AS completed_lessons
            FROM enrollments e
            JOIN users u ON u.id = e.user_id
            JOIN courses c ON c.id = e.course_id
            WHERE e.status IN (\'active\', \'completed\')
            ORDER BY e.enrolled_at DESC
        ')->fetchAll();
    } catch (PDOException $e) {
        log_database_error('Load student progress audit', $e);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar avatar-admin"><i class="fas fa-user-shield"></i></div>
            <div class="sidebar-user-info">
                <h5><?= e($user['full_name']); ?></h5>
                <span>Super Administrator</span>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item"><a href="<?= site_url('admin/dashboard.php'); ?>"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/students.php'); ?>"><i class="fas fa-user-graduate"></i> Students</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/teachers.php'); ?>"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/teacher-applications.php'); ?>"><i class="fas fa-id-card"></i> Teacher Applications</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/courses.php'); ?>"><i class="fas fa-book"></i> Courses</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/categories.php'); ?>"><i class="fas fa-folder"></i> Categories</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/payments.php'); ?>"><i class="fas fa-money-check-alt"></i> Payments</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/enrollments.php'); ?>"><i class="fas fa-user-check"></i> Enrollments</a></li>
            <li class="sidebar-item active"><a href="<?= site_url('admin/progress.php'); ?>"><i class="fas fa-tasks"></i> Progress</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/accountants.php'); ?>"><i class="fas fa-calculator"></i> Accountants</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/reports.php'); ?>"><i class="fas fa-file-invoice-dollar"></i> Reports</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/notifications.php'); ?>"><i class="fas fa-bell"></i> Notifications</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/settings.php'); ?>"><i class="fas fa-cog"></i> Settings</a></li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-title-bar">
            <div>
                <h2>Student Completion & Progress Audit</h2>
                <p class="page-subtitle">Monitor student lesson progress, video watch minutes, and completion certificates.</p>
            </div>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Progress %</th>
                            <th>Completed Lessons</th>
                            <th>Certificate Eligibility</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($progressRows)): ?>
                            <tr><td colspan="5" class="table-empty-state">No active student progress records exist yet.</td></tr>
                        <?php else: foreach ($progressRows as $row): $total = (int)$row['total_lessons']; $completed = (int)$row['completed_lessons']; $percentage = $total > 0 ? min(100, (int)round(($completed / $total) * 100)) : 0; ?>
                            <tr>
                                <td><strong><?= e($row['full_name']); ?></strong><br><span class="text-sm"><?= e($row['email']); ?></span></td>
                                <td><?= e($row['title']); ?></td>
                                <td><div style="display:flex;align-items:center;gap:.75rem;"><div class="progress-container" style="width:120px;margin:0;"><div class="progress-bar-fill" style="width:<?= $percentage; ?>%;"></div></div><span style="font-weight:700;color:var(--primary);"><?= $percentage; ?>%</span></div></td>
                                <td><?= $completed; ?> / <?= $total; ?> Lessons</td>
                                <td><span class="badge <?= $percentage >= 90 ? 'badge-active' : 'badge-pending'; ?>"><?= $percentage >= 90 ? 'Eligible' : 'In progress'; ?></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
