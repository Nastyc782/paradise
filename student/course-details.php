<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$course = null;
$modules = [];
$user = current_user();
$enrollment = null;
$reviews = [];
$reviewSummary = ['average' => 0, 'count' => 0];
$reviewError = null;

if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, cat.name as category_name, u.full_name as instructor_name
            FROM courses c
            LEFT JOIN course_categories cat ON c.category_id = cat.id
            LEFT JOIN users u ON c.teacher_id = u.id
            WHERE c.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $courseId]);
        $course = $stmt->fetch();

        if ($course) {
            // Fetch modules & lessons
            $modStmt = $pdo->prepare("SELECT * FROM modules WHERE course_id = :cid ORDER BY order_num ASC");
            $modStmt->execute(['cid' => $courseId]);
            $rawModules = $modStmt->fetchAll();

            foreach ($rawModules as $m) {
                $lessStmt = $pdo->prepare("SELECT * FROM lessons WHERE module_id = :mid ORDER BY order_num ASC");
                $lessStmt->execute(['mid' => $m['id']]);
                $m['lessons'] = $lessStmt->fetchAll();
                $modules[] = $m;
            }

            // Check enrollment if logged in
            if ($user) {
                $enrStmt = $pdo->prepare("SELECT * FROM enrollments WHERE user_id = :uid AND course_id = :cid LIMIT 1");
                $enrStmt->execute(['uid' => $user['id'], 'cid' => $courseId]);
                $enrollment = $enrStmt->fetch();
            }

            $reviewStmt = $pdo->prepare("
                SELECT cr.rating, cr.review, cr.created_at, u.full_name
                FROM course_reviews cr
                JOIN users u ON u.id = cr.user_id
                WHERE cr.course_id = :cid AND cr.status = 'published'
                ORDER BY cr.created_at DESC
            ");
            $reviewStmt->execute(['cid' => $courseId]);
            $reviews = $reviewStmt->fetchAll();
            $reviewSummary['count'] = count($reviews);
            if ($reviewSummary['count'] > 0) {
                $reviewSummary['average'] = round(array_sum(array_column($reviews, 'rating')) / $reviewSummary['count'], 1);
            }
        }
    } catch (PDOException $e) {
        log_database_error('Load course details for student', $e);
        $course = null;
    }
}

if (!$course) {
    set_flash('error', 'That course does not exist or is no longer published.');
    header('Location: ' . site_url('student/courses.php'));
    exit;
}

// Handle Direct Enroll Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enroll') {
    require_login();
    if ($db_connected && $pdo) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO enrollments (user_id, course_id, status)
                VALUES (:uid, :cid, 'pending')
                ON DUPLICATE KEY UPDATE status = status
            ");
            $stmt->execute(['uid' => $user['id'], 'cid' => $course['id']]);
            set_flash('success', "You have enrolled! Please complete your payment below to unlock all lessons.");
            header("Location: " . site_url('student/payment.php?course_id=' . $course['id']));
            exit;
        } catch (PDOException $e) {
            log_database_error('Enroll in course', $e);
            set_flash('error', 'Enrollment could not be completed. Please try again.');
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'review') {
    require_login();
    $rating = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT);
    $reviewText = trim($_POST['review'] ?? '');
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $reviewError = 'Your session expired. Refresh the page and submit your review again.';
    } elseif (!$enrollment || !in_array($enrollment['status'], ['active', 'completed'], true)) {
        $reviewError = 'Only enrolled students can review this course.';
    } elseif (!$rating || $rating < 1 || $rating > 5 || strlen($reviewText) > 2000) {
        $reviewError = 'Choose a rating from 1 to 5 and keep the review under 2,000 characters.';
    } elseif (!$db_connected || !$pdo) {
        $reviewError = 'Reviews are temporarily unavailable.';
    } else {
        try {
            $reviewStmt = $pdo->prepare("
                INSERT INTO course_reviews (course_id, user_id, rating, review)
                VALUES (:course_id, :user_id, :rating, :review)
                ON DUPLICATE KEY UPDATE rating = VALUES(rating), review = VALUES(review), status = 'published'
            ");
            $reviewStmt->execute([
                'course_id' => $courseId,
                'user_id' => $user['id'],
                'rating' => $rating,
                'review' => $reviewText !== '' ? $reviewText : null,
            ]);
            audit_log('course.review', 'Submitted course review #' . $courseId, $user['id']);
            set_flash('success', 'Thank you. Your course review has been saved.');
            header('Location: ' . site_url('student/course-details.php?id=' . $courseId));
            exit;
        } catch (PDOException $e) {
            log_database_error('Save course review', $e);
            $reviewError = 'Your review could not be saved. Please try again.';
        }
    }
}

$page_title = e($course['title']) . " — Digital Smart Class";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 2.5rem; padding-bottom: 4rem;">
    <!-- Course Hero Header -->
    <div style="background: linear-gradient(135deg, var(--dark-surface), var(--dark)); color: var(--white); padding: 3rem; border-radius: var(--radius-lg); margin-bottom: 2.5rem; box-shadow: var(--shadow-md);">
        <div style="display: flex; flex-wrap: wrap; gap: 2rem; justify-content: space-between; align-items: center;">
            <div style="flex: 1; min-width: 300px;">
                <div style="display: inline-block; background: rgba(255,255,255,0.15); padding: 0.3rem 0.8rem; border-radius: var(--radius-full); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; margin-bottom: 1rem; color: #67e8f9;">
                    <i class="fas fa-tag"></i> <?= e($course['category_name'] ?? 'Uncategorized'); ?> • <?= e($course['level']); ?>
                </div>
                <h1 style="font-size: 2.2rem; font-weight: 800; line-height: 1.25; margin-bottom: 1rem;">
                    <?= e($course['title']); ?>
                </h1>
                <p style="font-size: 1.05rem; color: #cbd5e1; margin-bottom: 1.5rem; max-width: 750px;">
                    <?= e($course['subtitle']); ?>
                </p>

                <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; font-size: 0.9rem; color: #94a3b8;">
                    <div><i class="fas fa-user-tie" style="color: #38bdf8;"></i> Instructor: <?= e($course['instructor_name'] ?? 'Not assigned'); ?></div>
                    <div><i class="fas fa-book-open" style="color: #38bdf8;"></i> <?= e($course['total_lessons']); ?> Lessons</div>
                    <div><i class="fas fa-clock" style="color: #38bdf8;"></i> <?= e($course['duration_hours']); ?> Hours Content</div>
                </div>
            </div>

            <!-- Enrollment Card -->
            <div style="background: var(--white); color: var(--gray-900); padding: 2rem; border-radius: var(--radius-lg); width: 320px; box-shadow: var(--shadow-xl); text-align: center;">
                <div style="font-size: 0.85rem; color: var(--gray-500); font-weight: 600; text-transform: uppercase;">Course Tuition</div>
                <div style="font-size: 2rem; font-weight: 800; color: var(--primary); margin: 0.5rem 0 1.25rem;">
                    <?= format_money($course['price'], $course['currency'] ?? 'RWF'); ?>
                </div>

                <?php if ($enrollment && $enrollment['status'] === 'active'): ?>
                    <div class="alert alert-success" style="padding: 0.75rem; font-size: 0.9rem; margin-bottom: 1rem;">
                        <i class="fas fa-check-circle"></i> You are fully enrolled!
                    </div>
                    <a href="<?= site_url('student/learning.php?course_id=' . $course['id']); ?>" class="btn btn-primary btn-block">
                        <i class="fas fa-play"></i> Continue Learning
                    </a>
                <?php elseif ($enrollment && $enrollment['status'] === 'pending'): ?>
                    <div class="alert alert-info" style="padding: 0.75rem; font-size: 0.85rem; margin-bottom: 1rem;">
                        <i class="fas fa-clock"></i> Payment awaiting administrator approval
                    </div>
                    <a href="<?= site_url('student/payment.php?course_id=' . $course['id']); ?>" class="btn btn-outline btn-block">
                        <i class="fas fa-receipt"></i> View Payment Details
                    </a>
                <?php else: ?>
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="enroll">
                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                            <i class="fas fa-lock-open"></i> Enroll in Course
                        </button>
                    </form>
                    <div style="font-size: 0.75rem; color: var(--gray-500); margin-top: 0.75rem;">
                        Instant access upon Mobile Money approval
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <section aria-labelledby="reviews-heading" style="background: var(--white); padding: 2rem; border-radius: var(--radius-lg); border: 1px solid var(--gray-200); margin-top: 2.5rem;">
            <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 1rem; align-items: center; margin-bottom: 1.5rem;">
                <div>
                    <h2 id="reviews-heading" style="font-size: 1.4rem; font-weight: 800; color: var(--gray-900);">Student Reviews</h2>
                    <p style="color: var(--gray-600); margin-top: 0.25rem;">
                        <?= $reviewSummary['count'] ? e($reviewSummary['average']) . ' / 5 from ' . (int)$reviewSummary['count'] . ' review(s)' : 'Be the first to review this course.'; ?>
                    </p>
                </div>
                <?php if ($enrollment && in_array($enrollment['status'], ['active', 'completed'], true)): ?>
                    <form method="POST" class="course-details-review-form" style="display: grid; grid-template-columns: auto minmax(180px, 1fr) auto; gap: 0.6rem; align-items: center;">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                        <input type="hidden" name="action" value="review">
                        <label class="sr-only" for="rating">Rating</label>
                        <select class="form-control" id="rating" name="rating" required>
                            <option value="">Rating</option>
                            <?php for ($star = 5; $star >= 1; $star--): ?><option value="<?= $star; ?>"><?= $star; ?> star<?= $star === 1 ? '' : 's'; ?></option><?php endfor; ?>
                        </select>
                        <label class="sr-only" for="review">Review</label>
                        <input class="form-control" id="review" name="review" maxlength="2000" placeholder="Share your experience">
                        <button class="btn btn-primary" type="submit">Post review</button>
                    </form>
                <?php endif; ?>
            </div>
            <?php if ($reviewError): ?><div class="alert alert-danger" role="alert"><?= e($reviewError); ?></div><?php endif; ?>
            <?php if (empty($reviews)): ?>
                <p style="color: var(--gray-500);">No published reviews yet.</p>
            <?php else: ?>
                <div style="display: grid; gap: 1rem;">
                    <?php foreach ($reviews as $review): ?>
                        <article style="border-top: 1px solid var(--gray-200); padding-top: 1rem;">
                            <strong><?= e($review['full_name']); ?></strong>
                            <span aria-label="<?= (int)$review['rating']; ?> out of 5 stars" style="color: var(--accent); margin-left: 0.5rem;"><?= str_repeat('★', (int)$review['rating']); ?></span>
                            <small style="color: var(--gray-500); margin-left: 0.5rem;"><?= e(date('d M Y', strtotime($review['created_at']))); ?></small>
                            <?php if (!empty($review['review'])): ?><p style="color: var(--gray-700); margin-top: 0.35rem;"><?= e($review['review']); ?></p><?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Course Overview & Curriculum Grid -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2.5rem;" id="curriculum">
        <!-- Left Column: Details & Modules -->
        <div>
            <div style="background: var(--white); padding: 2rem; border-radius: var(--radius-lg); border: 1px solid var(--gray-200); margin-bottom: 2rem;">
                <h3 style="font-size: 1.4rem; font-weight: 800; color: var(--gray-900); margin-bottom: 1rem;">Course Overview</h3>
                <p style="color: var(--gray-700); line-height: 1.7; font-size: 1rem;">
                    <?= nl2br(e($course['description'])); ?>
                </p>
                <?php if (!empty($course['learning_objectives'])): ?>
                    <h4 style="font-size: 1.1rem; font-weight: 800; color: var(--gray-900); margin: 1.5rem 0 .75rem;">Learning objectives</h4>
                    <ul style="padding-left: 1.25rem; color: var(--gray-700); line-height: 1.8;">
                        <?php foreach (preg_split('/\r\n|\r|\n/', $course['learning_objectives']) as $objective): ?>
                            <?php if (trim($objective) !== ''): ?><li><?= e($objective); ?></li><?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <!-- Curriculum Modules -->
            <div style="background: var(--white); padding: 2rem; border-radius: var(--radius-lg); border: 1px solid var(--gray-200);">
                <h3 style="font-size: 1.4rem; font-weight: 800; color: var(--gray-900); margin-bottom: 1.5rem;">
                    <i class="fas fa-list-ul" style="color: var(--primary);"></i> Structured Curriculum
                </h3>

                <?php if (empty($modules)): ?>
                    <div class="dashboard-empty-state">The teacher has not published the course curriculum yet.</div>
                <?php else: ?>
                    <?php foreach ($modules as $mod): ?>
                        <div style="margin-bottom: 1.5rem; border: 1px solid var(--gray-200); border-radius: var(--radius-md); overflow: hidden;">
                            <div style="background: var(--gray-100); padding: 0.85rem 1.25rem; font-weight: 700; font-size: 0.95rem; color: var(--gray-800); display: flex; justify-content: space-between; align-items: center;">
                                <span><i class="fas fa-folder" style="color: var(--primary); margin-right: 0.5rem;"></i> <?= e($mod['title']); ?></span>
                                <span style="font-size: 0.8rem; color: var(--gray-500);"><?= count($mod['lessons'] ?? []); ?> Lessons</span>
                            </div>
                            <div style="padding: 0.5rem 1.25rem;">
                                <?php foreach (($mod['lessons'] ?? []) as $les): ?>
                                    <?php
                                        $canWatch = !empty($les['is_free_preview'])
                                            || ($enrollment && in_array($enrollment['status'], ['active', 'completed'], true));
                                        $lessonHref = site_url('student/learning.php?course_id=' . $course['id'] . '&lesson_id=' . $les['id']);
                                    ?>
                                    <div style="padding: 0.75rem 0; border-bottom: 1px solid var(--gray-100); display: flex; justify-content: space-between; align-items: center;">
                                        <div style="display: flex; align-items: center; gap: 0.65rem;">
                                            <i class="fas fa-play-circle" style="color: <?= $canWatch ? 'var(--primary)' : 'var(--gray-400)'; ?>;"></i>
                                            <?php if ($canWatch): ?>
                                                <a href="<?= e($lessonHref); ?>" style="font-size: 0.9rem; color: var(--gray-800); font-weight: 500; text-decoration: none;">
                                                    <?= e($les['title']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span style="font-size: 0.9rem; color: var(--gray-500); font-weight: 500;">
                                                    <?= e($les['title']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <?php if (!empty($les['is_free_preview'])): ?>
                                                <span class="badge" style="background: #e0f2fe; color: #0369a1;">Free Preview</span>
                                            <?php elseif (!$enrollment || !in_array($enrollment['status'], ['active', 'completed'], true)): ?>
                                                <span style="font-size: 0.75rem; color: var(--gray-400);"><i class="fas fa-lock"></i> Locked</span>
                                            <?php else: ?>
                                                <span class="badge badge-active">Available</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: Learning Highlights & Instructor -->
        <div>
            <div style="background: var(--white); padding: 1.75rem; border-radius: var(--radius-lg); border: 1px solid var(--gray-200); margin-bottom: 1.5rem;">
                <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: var(--gray-900);">What You Will Learn</h4>
                <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.75rem;">
                    <li style="display: flex; gap: 0.5rem; font-size: 0.9rem; color: var(--gray-700);">
                        <i class="fas fa-check" style="color: var(--success); margin-top: 0.2rem;"></i> High-converting video packaging and algorithms
                    </li>
                    <li style="display: flex; gap: 0.5rem; font-size: 0.9rem; color: var(--gray-700);">
                        <i class="fas fa-check" style="color: var(--success); margin-top: 0.2rem;"></i> Practical tools, mobile & desktop software
                    </li>
                    <li style="display: flex; gap: 0.5rem; font-size: 0.9rem; color: var(--gray-700);">
                        <i class="fas fa-check" style="color: var(--success); margin-top: 0.2rem;"></i> Monetization models and brand partnership scripts
                    </li>
                    <li style="display: flex; gap: 0.5rem; font-size: 0.9rem; color: var(--gray-700);">
                        <i class="fas fa-check" style="color: var(--success); margin-top: 0.2rem;"></i> Direct feedback on your channel and assignments
                    </li>
                </ul>
            </div>

            <div style="background: var(--white); padding: 1.75rem; border-radius: var(--radius-lg); border: 1px solid var(--gray-200);">
                <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--gray-900);">Instructor</h4>
                <p style="font-weight: 600; color: var(--primary); font-size: 0.95rem; margin-bottom: 0.35rem;">
                    <?= e($course['instructor_name'] ?? 'Not assigned'); ?>
                </p>
                <p style="font-size: 0.85rem; color: var(--gray-600); line-height: 1.5;">
                    Lead Content Specialist at Digital Smart Class with over 5 years experience scaling YouTube channels and automated video workflows across East Africa.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
