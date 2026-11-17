<?php
require_once __DIR__ . '/../includes/student-auth.php';
$user = current_user();
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$stmt = $pdo->prepare('SELECT cc.*, c.title, u.full_name FROM course_certificates cc JOIN courses c ON c.id=cc.course_id JOIN users u ON u.id=cc.user_id WHERE cc.course_id=:course AND cc.user_id=:user LIMIT 1');
$stmt->execute(['course' => $courseId, 'user' => $user['id']]);
$certificate = $stmt->fetch();
$page_title = 'Course Certificate — Digital Smart Class';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container" style="padding: 3rem 1.5rem 5rem; max-width: 900px;">
    <div class="panel" style="padding: 3rem; text-align: center; border: 8px double var(--primary); background: linear-gradient(135deg, #fff, #eff6ff);">
        <?php if ($certificate): ?>
            <div class="section-subtitle">Digital Smart Class Academy</div>
            <h1 style="font-size: 2.4rem; color: var(--gray-900); margin: .75rem 0;">Certificate of Completion</h1>
            <p style="color: var(--gray-600);">This certifies that</p>
            <h2 style="font-size: 2rem; color: var(--primary); margin: .5rem 0;"><?= e($certificate['full_name']); ?></h2>
            <p style="color: var(--gray-600);">successfully completed</p>
            <h3 style="font-size: 1.5rem; color: var(--gray-900); margin: .5rem 0 1.5rem;"><?= e($certificate['title']); ?></h3>
            <p style="color: var(--gray-600);">Issued <?= e(date('d M Y', strtotime($certificate['issued_at']))); ?> · Certificate <?= e($certificate['certificate_code']); ?></p>
            <button class="btn btn-primary" onclick="window.print()" style="margin-top: 1.5rem;"><i class="fas fa-print"></i> Print certificate</button>
        <?php else: ?>
            <h1 style="color: var(--gray-900);">Certificate not ready</h1>
            <p style="color: var(--gray-600); margin: 1rem 0;">Complete every lesson and pass the final examination to receive your certificate.</p>
            <a class="btn btn-primary" href="<?= site_url('student/progress.php'); ?>">View progress</a>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
