<?php
$page_title = "Courses — DIGITAL SMART CLASS";
require_once __DIR__ . '/includes/header.php';

// Fetch courses
$courses = [];
if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->query("
            SELECT c.*, cat.name as category_name, u.full_name as instructor_name 
            FROM courses c
            LEFT JOIN course_categories cat ON c.category_id = cat.id
            LEFT JOIN users u ON c.teacher_id = u.id
            WHERE c.status = 'published'
            ORDER BY c.id ASC
        ");
        $courses = $stmt->fetchAll();
    } catch (PDOException $e) {
        log_database_error('Load public course catalog', $e);
        $courses = [];
    }
} else {
    $courses = [];
}
?>

<section class="page-hero">
    <div class="container">
        <div class="section-subtitle">Learn by doing</div>
        <h1>Course catalog</h1>
        <p>Practical, high-yield digital video and content creation masterclasses.</p>
    </div>
</section>

<div class="container page-body">

    <?php if (empty($courses)): ?>
        <div class="course-empty"><i class="fas fa-book-open" aria-hidden="true"></i><h3>No published courses yet</h3><p>Published courses will appear here when an approved teacher or administrator creates them.</p></div>
    <?php else: ?>
    <div class="course-grid">
        <?php foreach ($courses as $c): ?>
            <?php 
                $cardType = 'youtube';
                if (stripos($c['title'], 'Shorts') !== false) {
                    $cardType = 'shorts';
                } elseif (stripos($c['title'], 'AI') !== false) {
                    $cardType = 'ai';
                }
                $imageAlt = 'Professional course artwork for ' . $c['title'];
                if ($c['slug'] === 'youtube-content-creation-video-production') {
                    $imageAlt = 'YouTube creator recording with a camera, microphone, studio lights, and editing workstation';
                } elseif ($c['slug'] === 'youtube-channel-growth-digital-marketing') {
                    $imageAlt = 'YouTube analytics dashboard showing subscriber growth and digital marketing performance';
                } elseif ($c['slug'] === 'youtube-monetization-online-business') {
                    $imageAlt = 'Content creator business dashboard showing YouTube monetization and digital income growth';
                }
            ?>
            <article class="course-card">
                <div class="course-banner <?= $cardType; ?>">
                    <?php if (!empty($c['thumbnail'])): ?><img class="course-banner-image" src="<?= e(site_url($c['thumbnail'])); ?>" alt="<?= e($imageAlt); ?>" width="1200" height="675" loading="eager" decoding="async"><?php endif; ?>
                    <span class="course-level-badge"><?= e($c['level']); ?></span>
                    <div>
                        <div class="course-banner-icon">
                            <?php if ($cardType === 'youtube'): ?>
                                <i class="fab fa-youtube"></i>
                            <?php elseif ($cardType === 'shorts'): ?>
                                <i class="fas fa-bolt"></i>
                            <?php else: ?>
                                <i class="fas fa-robot"></i>
                            <?php endif; ?>
                        </div>
                        <div style="font-weight: 700; font-size: 0.85rem; text-transform: uppercase;">
                            <?= e($c['category_name'] ?? 'Content Creation'); ?>
                        </div>
                    </div>
                </div>

                <div class="course-body">
                    <div class="course-category">
                        <?= e($c['category_name'] ?? 'Video Production'); ?>
                    </div>
                    <h3 class="course-title">
                        <?= e($c['title']); ?>
                    </h3>
                    <p class="course-subtitle">
                        <?= e($c['subtitle'] ?? substr(strip_tags($c['description']), 0, 110) . '...'); ?>
                    </p>

                    <div class="course-meta">
                        <div class="course-meta-item">
                            <i class="fas fa-book-open"></i> <?= e($c['total_lessons'] ?? 'Not specified'); ?> Lessons
                        </div>
                        <div class="course-meta-item">
                            <i class="fas fa-calendar-alt"></i> <?= e($c['duration_weeks'] ?? 'Not specified'); ?>
                        </div>
                    </div>

                    <div class="course-footer">
                        <div class="course-price">
                            <?= format_money($c['price'], $c['currency'] ?? 'RWF'); ?>
                        </div>
                        <a href="<?= site_url('course-details.php?id=' . $c['id']); ?>" class="btn btn-primary btn-sm">
                            View course &amp; enroll
                        </a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
