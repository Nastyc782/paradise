<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$course = null;
$modules = [];
$user = current_user();
$enrollment = null;

if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, cat.name as category_name, u.full_name as teacher_name 
            FROM courses c
            LEFT JOIN course_categories cat ON c.category_id = cat.id
            LEFT JOIN users u ON c.teacher_id = u.id
            WHERE c.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $courseId]);
        $course = $stmt->fetch();

        if ($course) {
            // Check enrollment status if logged in
            if ($user) {
                $enrStmt = $pdo->prepare("SELECT * FROM enrollments WHERE user_id = :uid AND course_id = :cid LIMIT 1");
                $enrStmt->execute(['uid' => $user['id'], 'cid' => $courseId]);
                $enrollment = $enrStmt->fetch();
            }

            // Fetch syllabus
            $mStmt = $pdo->prepare("SELECT * FROM modules WHERE course_id = :cid ORDER BY order_num ASC");
            $mStmt->execute(['cid' => $courseId]);
            $rawModules = $mStmt->fetchAll();
            foreach ($rawModules as $m) {
                $lStmt = $pdo->prepare("SELECT * FROM lessons WHERE module_id = :mid ORDER BY order_num ASC");
                $lStmt->execute(['mid' => $m['id']]);
                $m['lessons'] = $lStmt->fetchAll();
                $modules[] = $m;
            }
        }
    } catch (PDOException $e) {
        $course = null;
    }
}

if (!$course) {
    set_flash('error', 'That course does not exist or is no longer published.');
    header('Location: ' . site_url('courses.php'));
    exit;
}

$page_title = e($course['title']) . " — DIGITAL SMART CLASS";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container page-body page-body--narrow">
    <div class="content-card">
        <div class="section-subtitle" style="margin-bottom: 0.6rem;">Course details</div>
        <h1 style="font-size: 2rem; font-weight: 800; color: #0f172a; margin-bottom: 1.4rem; letter-spacing: -0.03em;">
            <?= e($course['title']); ?>
        </h1>

        <div class="course-hero-art" style="overflow: hidden; padding: 0;">
            <?php if (!empty($course['thumbnail'])): ?>
                <img src="<?= e(site_url($course['thumbnail'])); ?>" alt="<?= e($course['title']); ?> course thumbnail" style="width: 100%; max-height: 260px; object-fit: cover;">
            <?php else: ?>
            <div style="font-size: 4rem; margin-bottom: 0.5rem; opacity: 0.95;">
                <?php if (stripos($course['title'], 'Shorts') !== false): ?>
                    <i class="fas fa-bolt" style="color: #38bdf8;"></i>
                <?php elseif (stripos($course['title'], 'AI') !== false): ?>
                    <i class="fas fa-robot" style="color: #a855f7;"></i>
                <?php else: ?>
                    <i class="fab fa-youtube" style="color: #ef4444;"></i>
                <?php endif; ?>
            </div>
            <div style="font-size: 1.05rem; font-weight: 800; letter-spacing: 0.04em;">
                <?= e($course['category_name'] ?? 'Uncategorized'); ?>
            </div>
            <?php endif; ?>
        </div>

        <p style="font-size: 1.15rem; color: #334155; line-height: 1.6; margin-bottom: 2rem;">
            <?= nl2br(e($course['description'])); ?>
        </p>

        <?php if (!empty($course['learning_objectives'])): ?>
            <div style="margin-bottom: 2.5rem;">
                <h3 class="section-heading">Learning objectives</h3>
                <ul style="padding-left: 1.25rem; color: #334155; line-height: 1.8;">
                    <?php foreach (preg_split('/\r\n|\r|\n/', $course['learning_objectives']) as $objective): ?>
                        <?php if (trim($objective) !== ''): ?><li><?= e($objective); ?></li><?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Course Meta Info -->
        <div class="course-meta-panel">
            <div>
                <strong style="color: #64748b; font-size: 0.85rem; text-transform: uppercase; display: block;">Teacher</strong>
                <span style="font-weight: 700; color: #0f172a; font-size: 1.05rem;">
                    <?= e($course['teacher_name'] ?? 'Not assigned'); ?>
                </span>
            </div>

            <div>
                <strong style="color: #64748b; font-size: 0.85rem; text-transform: uppercase; display: block;">Level</strong>
                <span style="font-weight: 700; color: #0f172a; font-size: 1.05rem;"><?= e($course['level']); ?></span>
            </div>
            <div>
                <strong style="color: #64748b; font-size: 0.85rem; text-transform: uppercase; display: block;">Duration</strong>
                <span style="font-weight: 700; color: #0f172a; font-size: 1.05rem;"><?= e($course['duration_weeks'] ?? 'Not specified'); ?></span>
            </div>
            <div>
                <strong style="color: #64748b; font-size: 0.85rem; text-transform: uppercase; display: block;">Lessons</strong>
                <span style="font-weight: 700; color: #0f172a; font-size: 1.05rem;"><?= e($course['total_lessons']); ?></span>
            </div>
        </div>

        <div style="margin-bottom: 2.5rem;">
            <h3 class="section-heading">Course curriculum</h3>
            <?php foreach ($modules as $module): ?>
                <details style="border: 1px solid #e2e8f0; border-radius: 12px; padding: .85rem 1rem; margin-bottom: .75rem;">
                    <summary style="font-weight: 700; color: #0f172a; cursor: pointer;"><?= e($module['title']); ?> <span style="color:#64748b;font-weight:400;">(<?= count($module['lessons']); ?> lessons)</span></summary>
                    <ul style="padding: .75rem 0 0 1.25rem; color: #475569; line-height: 1.8;">
                        <?php foreach ($module['lessons'] as $lesson): ?><li><?= e($lesson['title']); ?></li><?php endforeach; ?>
                    </ul>
                </details>
            <?php endforeach; ?>
            <p style="color:#64748b;font-size:.9rem;">The learning room includes educational video resources, downloadable workbooks, assignments, automatic quizzes, a final examination, and a practical final project.</p>
        </div>

        <!-- What You Will Learn (Section 8 Checklist) -->
        <div style="margin-bottom: 2.5rem;">
            <h3 class="section-heading">What You Will Learn</h3>
            <div class="learn-grid">
                <?php if (stripos($course['title'], 'Shorts') !== false): ?>
                    <div class="learn-item"><i class="fas fa-check"></i> Shorts ideas</div>
                    <div class="learn-item"><i class="fas fa-check"></i> Scripts</div>
                    <div class="learn-item"><i class="fas fa-check"></i> Editing</div>
                    <div class="learn-item"><i class="fas fa-check"></i> Captions</div>
                    <div class="learn-item"><i class="fas fa-check"></i> Publishing</div>
                    <div class="learn-item"><i class="fas fa-check"></i> Shorts analytics</div>
                <?php elseif (stripos($course['title'], 'AI') !== false): ?>
                    <div class="learn-item"><i class="fas fa-check"></i> Finding long-video ideas</div>
                    <div class="learn-item"><i class="fas fa-check"></i> Planning content</div>
                    <div class="learn-item"><i class="fas fa-check"></i> Creating scripts with AI</div>
                    <div class="learn-item"><i class="fas fa-check"></i> Voice-over creation</div>
                    <div class="learn-item"><i class="fas fa-check"></i> AI-generated visuals</div>
                    <div class="learn-item"><i class="fas fa-check"></i> Video editing &amp; thumbnails</div>
                <?php else: ?>
                    <div class="learn-item"><i class="fas fa-check"></i> Understanding YouTube</div>
                    <div class="learn-item"><i class="fas fa-check"></i> Creating a YouTube channel</div>
                    <div class="learn-item"><i class="fas fa-check"></i> Setting up your profile</div>
                    <div class="learn-item"><i class="fas fa-check"></i> YouTube Studio</div>
                    <div class="learn-item"><i class="fas fa-check"></i> Finding content ideas</div>
                    <div class="learn-item"><i class="fas fa-check"></i> Creating thumbnails &amp; growth</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Price and CTA -->
        <div style="text-align: center; padding-top: 1.5rem; border-top: 1px solid #e2e8f0;">
            <div style="font-size: 2.25rem; font-weight: 800; color: #0f172a; margin-bottom: 1.25rem;">
                Price: <?= format_money($course['price'], $course['currency'] ?? 'RWF'); ?>
            </div>

            <?php if ($enrollment && $enrollment['status'] === 'active'): ?>
                <div class="alert alert-success" style="display: inline-block; padding: 0.75rem 1.5rem; margin-bottom: 1rem;">
                    <i class="fas fa-check-circle"></i> You have purchased and unlocked this course!
                </div>
                <div>
                    <a href="<?= site_url('student/learning.php?course_id=' . $course['id']); ?>" class="btn btn-primary btn-lg" style="padding: 1rem 3rem; font-size: 1.15rem; font-weight: 800;">
                        <i class="fas fa-play"></i> GO TO LEARNING ROOM
                    </a>
                </div>
            <?php elseif ($enrollment && $enrollment['status'] === 'pending'): ?>
                <div class="alert alert-info" style="display: inline-block; padding: 0.75rem 1.5rem; margin-bottom: 1rem;">
                    <i class="fas fa-clock"></i> Payment submitted — awaiting administrator approval
                </div>
                <div>
                    <a href="<?= site_url('student/payment.php?course_id=' . $course['id']); ?>" class="btn btn-outline btn-lg">
                        <i class="fas fa-receipt"></i> View Payment Status
                    </a>
                </div>
            <?php else: ?>
                <a href="<?= site_url('student/buy-course.php?course_id=' . $course['id']); ?>" class="btn btn-primary btn-lg" style="padding: 1rem 3.5rem; font-size: 1.2rem; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase;">
                    BUY COURSE
                </a>
                <p style="font-size: 0.85rem; color: #64748b; margin-top: 0.75rem;">
                    <i class="fas fa-lock"></i> Videos, notes, files, and presentations remain locked until payment approval.
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
