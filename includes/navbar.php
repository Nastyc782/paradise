<?php
/**
 * Persistent application sidebar.
 * This replaces the former horizontal navigation bar on every page.
 */
$currentUser = current_user();
$role = $currentUser['role'] ?? 'guest';
$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$currentPage = basename($scriptPath);
$isActive = static function (string $href) use ($currentPage): string {
    return basename(parse_url($href, PHP_URL_PATH) ?: '') === $currentPage ? ' active' : '';
};

$unreadNotifications = 0;
if ($currentUser && $db_connected && $pdo) {
    try {
        $nStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0");
        $nStmt->execute(['uid' => $currentUser['id']]);
        $unreadNotifications = (int)$nStmt->fetchColumn();
    } catch (PDOException $e) {
        log_database_error('Load unread notification count', $e);
    }
}

$dashboardHref = $currentUser
    ? site_url(dashboard_for_role($role))
    : site_url('login.php');
$profileHref = $currentUser
    ? site_url($role === 'student' ? 'student/profile.php' : $role . '/profile.php')
    : site_url('login.php');
$userLabel = $currentUser['full_name'] ?? 'Digital Smart Class';
$roleLabel = $currentUser ? ucfirst($role) : 'Guest';
?>
<aside class="site-sidebar" aria-label="Primary navigation">
    <div class="site-sidebar-top">
        <a href="<?= site_url('index.php'); ?>" class="site-brand" aria-label="Digital Smart Class home">
            <span class="site-brand-icon"><i class="fas fa-graduation-cap" aria-hidden="true"></i></span>
            <span class="site-brand-text">DIGITAL<span>SMART</span>CLASS</span>
        </a>

        <a href="<?= $dashboardHref; ?>" class="site-user-card">
            <span class="site-user-avatar avatar-<?= e($role); ?>">
                <i class="fas <?= $role === 'admin' ? 'fa-user-shield' : ($role === 'teacher' ? 'fa-chalkboard-teacher' : ($role === 'accountant' ? 'fa-calculator' : 'fa-user-graduate')); ?>" aria-hidden="true"></i>
            </span>
            <span class="site-user-details">
                <strong><?= e($userLabel); ?></strong>
                <small><?= e($roleLabel); ?></small>
            </span>
        </a>
    </div>

    <nav class="site-sidebar-middle">
        <div class="site-nav-group">
            <span class="site-nav-heading">Dashboard Menu</span>
            <a href="<?= $dashboardHref; ?>" class="site-nav-link<?= $isActive($dashboardHref); ?>" title="Dashboard">
                <i class="fas fa-th-large" aria-hidden="true"></i><span>Dashboard</span>
            </a>
            <?php if ($role === 'student'): ?>
                <a href="<?= site_url('student/my-courses.php'); ?>" class="site-nav-link<?= $isActive(site_url('student/my-courses.php')); ?>" title="My Courses"><i class="fas fa-play-circle"></i><span>My Courses</span></a>
                <a href="<?= site_url('student/payments.php'); ?>" class="site-nav-link<?= $isActive(site_url('student/payments.php')); ?>" title="Payments"><i class="fas fa-receipt"></i><span>Payments</span></a>
                <a href="<?= site_url('student/progress.php'); ?>" class="site-nav-link<?= $isActive(site_url('student/progress.php')); ?>" title="Progress"><i class="fas fa-chart-line"></i><span>Progress</span></a>
                <a href="<?= site_url('student/notifications.php'); ?>" class="site-nav-link<?= $isActive(site_url('student/notifications.php')); ?>" title="Notifications"><i class="fas fa-bell"></i><span>Notifications</span><?php if ($unreadNotifications > 0): ?><b class="site-notification-badge"><?= $unreadNotifications; ?></b><?php endif; ?></a>
                <a href="<?= $profileHref; ?>" class="site-nav-link<?= $isActive($profileHref); ?>" title="Profile"><i class="fas fa-user-cog"></i><span>Profile</span></a>
            <?php elseif ($role === 'teacher'): ?>
                <a href="<?= site_url('teacher/courses.php'); ?>" class="site-nav-link<?= $isActive(site_url('teacher/courses.php')); ?>" title="My Courses"><i class="fas fa-book"></i><span>My Courses</span></a>
                <a href="<?= site_url('teacher/students.php'); ?>" class="site-nav-link<?= $isActive(site_url('teacher/students.php')); ?>" title="Students"><i class="fas fa-users"></i><span>Students</span></a>
                <a href="<?= site_url('teacher/announcements.php'); ?>" class="site-nav-link<?= $isActive(site_url('teacher/announcements.php')); ?>" title="Announcements"><i class="fas fa-bell"></i><span>Announcements</span></a>
                <a href="<?= $profileHref; ?>" class="site-nav-link<?= $isActive($profileHref); ?>" title="Profile"><i class="fas fa-user-cog"></i><span>Profile</span></a>
            <?php elseif ($role === 'admin'): ?>
                <a href="<?= site_url('admin/users.php'); ?>" class="site-nav-link<?= $isActive(site_url('admin/users.php')); ?>" title="Manage Users"><i class="fas fa-users-cog"></i><span>Manage Users</span></a>
                <a href="<?= site_url('admin/courses.php'); ?>" class="site-nav-link<?= $isActive(site_url('admin/courses.php')); ?>" title="Manage Courses"><i class="fas fa-graduation-cap"></i><span>Manage Courses</span></a>
                <a href="<?= site_url('admin/payments.php'); ?>" class="site-nav-link<?= $isActive(site_url('admin/payments.php')); ?>" title="Payments"><i class="fas fa-money-check-alt"></i><span>Payments</span></a>
                <a href="<?= site_url('admin/notifications.php'); ?>" class="site-nav-link<?= $isActive(site_url('admin/notifications.php')); ?>" title="Notifications"><i class="fas fa-bell"></i><span>Notifications</span><?php if ($unreadNotifications > 0): ?><b class="site-notification-badge"><?= $unreadNotifications; ?></b><?php endif; ?></a>
                <a href="<?= $profileHref; ?>" class="site-nav-link<?= $isActive($profileHref); ?>" title="Profile"><i class="fas fa-user-cog"></i><span>Profile</span></a>
            <?php elseif ($role === 'accountant'): ?>
                <a href="<?= site_url('accountant/payments.php'); ?>" class="site-nav-link<?= $isActive(site_url('accountant/payments.php')); ?>" title="Payments"><i class="fas fa-receipt"></i><span>Payments</span></a>
                <a href="<?= site_url('accountant/revenue.php'); ?>" class="site-nav-link<?= $isActive(site_url('accountant/revenue.php')); ?>" title="Revenue"><i class="fas fa-coins"></i><span>Revenue</span></a>
                <a href="<?= $profileHref; ?>" class="site-nav-link<?= $isActive($profileHref); ?>" title="Profile"><i class="fas fa-user-cog"></i><span>Profile</span></a>
            <?php else: ?>
                <a href="<?= site_url('login.php'); ?>" class="site-nav-link<?= $isActive(site_url('login.php')); ?>" title="Login"><i class="fas fa-sign-in-alt"></i><span>Login</span></a>
                <a href="<?= site_url('register.php'); ?>" class="site-nav-link<?= $isActive(site_url('register.php')); ?>" title="Register"><i class="fas fa-user-plus"></i><span>Register</span></a>
            <?php endif; ?>
        </div>

        <div class="site-nav-group site-nav-site-links">
            <span class="site-nav-heading">Site Navigation</span>
            <a href="<?= site_url('index.php'); ?>" class="site-nav-link<?= $isActive(site_url('index.php')); ?>" title="Home"><i class="fas fa-home"></i><span>Home</span></a>
            <a href="<?= site_url('courses.php'); ?>" class="site-nav-link<?= $isActive(site_url('courses.php')); ?>" title="Courses"><i class="fas fa-compass"></i><span>Courses</span></a>
            <a href="<?= site_url('about.php'); ?>" class="site-nav-link<?= $isActive(site_url('about.php')); ?>" title="About"><i class="fas fa-circle-info"></i><span>About</span></a>
            <a href="<?= site_url('contact.php'); ?>" class="site-nav-link<?= $isActive(site_url('contact.php')); ?>" title="Contact"><i class="fas fa-envelope"></i><span>Contact</span></a>
            <a href="<?= site_url('teacher/application.php'); ?>" class="site-nav-link<?= $isActive(site_url('teacher/application.php')); ?>" title="Teach with us"><i class="fas fa-chalkboard-user"></i><span>Teach with us</span></a>
        </div>
    </nav>

    <div class="site-sidebar-bottom">
        <?php if ($currentUser): ?>
            <a href="<?= site_url('logout.php'); ?>" class="site-logout" title="Logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        <?php else: ?>
            <a href="<?= site_url('login.php'); ?>" class="site-logout site-login-action" title="Login"><i class="fas fa-sign-in-alt"></i><span>Login</span></a>
        <?php endif; ?>
    </div>
</aside>
