<?php
$page_title = "DIGITAL SMART CLASS — Build Your Skills. Create Your Future.";
require_once __DIR__ . '/includes/header.php';

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
        log_database_error('Load homepage courses', $e);
        $courses = [];
    }
} else {
    $courses = [];
}

$courseCount = count($courses);
?>

<!-- ================= Hero ================= -->
<section class="hero home-hero" aria-labelledby="home-hero-title">
    <div class="container hero-layout">
        <div class="hero-copy">
            <span class="hero-tagline">
                <i class="fas fa-certificate" aria-hidden="true"></i> Rwanda's creator academy
            </span>
            <h1 class="hero-title" id="home-hero-title">Learn. Create. <span>Grow.</span></h1>
            <p class="hero-lead">Practical digital skills from professional instructors.</p>
            <p class="hero-desc">Build a YouTube channel, master Shorts, and produce long-form video with AI — at a fair price in RWF.</p>

            <ul class="hero-proof" aria-label="What makes Smart Class different">
                <li><i class="fas fa-check" aria-hidden="true"></i> Beginner friendly</li>
                <li><i class="fas fa-check" aria-hidden="true"></i> Self-paced lessons</li>
                <li><i class="fas fa-check" aria-hidden="true"></i> MTN MoMo &amp; Airtel Money</li>
            </ul>

            <div class="hero-actions">
                <a href="<?= site_url('courses.php'); ?>" class="btn btn-primary btn-lg">
                    Explore courses <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
                <a href="<?= site_url('register.php'); ?>" class="btn btn-outline btn-lg">
                    Create free account
                </a>
            </div>

            <div class="hero-stats" role="list" aria-label="Platform highlights">
                <div class="stat-item" role="listitem">
                    <h3><?= $courseCount; ?>+</h3>
                    <p>Flagship programs</p>
                </div>
                <div class="stat-item" role="listitem">
                    <h3>100%</h3>
                    <p>Practical workflows</p>
                </div>
                <div class="stat-item" role="listitem">
                    <h3>MoMo</h3>
                    <p>Local payment support</p>
                </div>
            </div>
        </div>

        <div class="hero-visual" aria-hidden="true">
            <div class="hero-stage">
                <div class="stage-glow"></div>
                <div class="orbit-ring orbit-ring-a"></div>
                <div class="orbit-ring orbit-ring-b"></div>
                <div class="hero-scene">
                    <article class="scene-card scene-card--youtube" style="--i: 0;">
                        <span class="scene-card-icon"><i class="fab fa-youtube"></i></span>
                        <strong>YouTube</strong>
                        <small>Channel growth</small>
                    </article>
                    <article class="scene-card scene-card--shorts" style="--i: 1;">
                        <span class="scene-card-icon"><i class="fas fa-bolt"></i></span>
                        <strong>Shorts</strong>
                        <small>Viral formats</small>
                    </article>
                    <article class="scene-card scene-card--ai" style="--i: 2;">
                        <span class="scene-card-icon"><i class="fas fa-robot"></i></span>
                        <strong>AI Video</strong>
                        <small>Automated scale</small>
                    </article>
                </div>
            </div>
            <p class="scene-caption">An immersive studio for modern creators</p>
        </div>
    </div>
</section>

<!-- ================= Trust strip ================= -->
<section class="home-trust-bar" aria-label="Digital Smart Class benefits">
    <div class="container home-trust-grid">
        <div class="trust-intro">
            <span class="trust-kicker">Built for your next step</span>
            <strong>Learn with a clear plan.</strong>
        </div>
        <div class="trust-item">
            <span class="trust-icon"><i class="fas fa-video" aria-hidden="true"></i></span>
            <span><strong>Practical projects</strong><small>Learn by doing</small></span>
        </div>
        <div class="trust-item">
            <span class="trust-icon trust-icon--teal"><i class="fas fa-user-check" aria-hidden="true"></i></span>
            <span><strong>Trusted guidance</strong><small>Learn from creators</small></span>
        </div>
        <div class="trust-item">
            <span class="trust-icon trust-icon--gold"><i class="fas fa-mobile-alt" aria-hidden="true"></i></span>
            <span><strong>Easy local payments</strong><small>MoMo &amp; Airtel Money</small></span>
        </div>
    </div>
</section>

<!-- ================= Why Smart Class ================= -->
<section class="why-section" aria-labelledby="why-title">
    <div class="container">
        <div class="section-header">
            <div class="section-subtitle">Why learners choose us</div>
            <h2 class="section-title" id="why-title">A professional classroom for digital creators</h2>
            <p class="section-desc">Clear structure, vetted teachers, and protected content after payment approval.</p>
        </div>

        <div class="why-grid">
            <article class="feature-box">
                <div class="feature-icon green" aria-hidden="true"><i class="fas fa-bolt"></i></div>
                <h3>Practical learning</h3>
                <p>Actionable real-world workflows without unnecessary fluff.</p>
            </article>
            <article class="feature-box">
                <div class="feature-icon violet" aria-hidden="true"><i class="fas fa-chalkboard-teacher"></i></div>
                <h3>Expert teachers</h3>
                <p>Vetted, experienced creators verified by platform admin.</p>
            </article>
            <article class="feature-box">
                <div class="feature-icon blue" aria-hidden="true"><i class="fas fa-clock"></i></div>
                <h3>Flexible learning</h3>
                <p>Self-paced video modules, notes, and downloadable guides.</p>
            </article>
            <article class="feature-box">
                <div class="feature-icon amber" aria-hidden="true"><i class="fas fa-tag"></i></div>
                <h3>Affordable courses</h3>
                <p>High-value training priced fairly in local currency (RWF).</p>
            </article>
            <article class="feature-box">
                <div class="feature-icon" aria-hidden="true"><i class="fas fa-chart-line"></i></div>
                <h3>Progress tracking</h3>
                <p>Automatic lesson completion markers and watch percentage.</p>
            </article>
            <article class="feature-box">
                <div class="feature-icon rose" aria-hidden="true"><i class="fas fa-shield-alt"></i></div>
                <h3>Secure platform</h3>
                <p>MTN MoMo verification and protected course content.</p>
            </article>
        </div>
    </div>
</section>

<!-- ================= Featured courses ================= -->
<section class="home-courses" id="courses" aria-labelledby="courses-title">
    <div class="container">
        <div class="section-header home-section-head">
            <div class="home-section-head-copy">
                <div class="section-subtitle">Catalog</div>
                <h2 class="section-title" id="courses-title">Featured courses</h2>
                <p class="section-desc">Start with YouTube foundations, then scale into Shorts and AI-assisted long-form video.</p>
            </div>
            <a href="<?= site_url('courses.php'); ?>" class="btn btn-outline-primary home-section-link">
                Browse all courses <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </a>
        </div>

        <?php if (empty($courses)): ?>
            <div class="course-empty">
                <i class="fas fa-book-open" aria-hidden="true"></i>
                <h3>Courses are being prepared</h3>
                <p>New programs will appear here soon. In the meantime, contact our team for guidance.</p>
                <a href="<?= site_url('contact.php'); ?>" class="btn btn-primary btn-sm">Contact us</a>
            </div>
        <?php else: ?>
        <div class="course-grid">
            <?php foreach ($courses as $c): ?>
                <?php
                    $courseTitle = (string)($c['title'] ?? 'Untitled course');
                    $cardType = 'youtube';
                    if (stripos($courseTitle, 'Shorts') !== false) {
                        $cardType = 'shorts';
                    } elseif (stripos($courseTitle, 'AI') !== false) {
                        $cardType = 'ai';
                    }

                    $level = trim((string)($c['level'] ?? ''));
                    $level = $level !== '' ? $level : 'Beginner';

                    $category = trim((string)($c['category_name'] ?? ''));
                    $category = $category !== '' ? $category : 'Content Creation';

                    $subtitle = trim((string)($c['subtitle'] ?? ''));
                    if ($subtitle === '') {
                        $plainDescription = trim(preg_replace('/\s+/', ' ', strip_tags((string)($c['description'] ?? ''))));
                        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
                            $subtitle = mb_strlen($plainDescription) > 140
                                ? mb_substr($plainDescription, 0, 137) . '...'
                                : $plainDescription;
                        } else {
                            $subtitle = strlen($plainDescription) > 140
                                ? substr($plainDescription, 0, 137) . '...'
                                : $plainDescription;
                        }
                    }

                    $instructor = trim((string)($c['instructor_name'] ?? $c['instructor'] ?? ''));
                    $lessons = (int)($c['total_lessons'] ?? 0);

                    $duration = trim((string)($c['duration_weeks'] ?? ''));
                    if ($duration === '') {
                        $hours = (int)($c['duration_hours'] ?? 0);
                        $duration = $hours > 0 ? $hours . ' hours' : 'Self-paced';
                    }

                    $courseUrl = site_url('course-details.php?id=' . (int)($c['id'] ?? 0));
                    $coursePrice = format_money($c['price'] ?? 0, $c['currency'] ?? 'RWF');
                ?>

                <article class="course-card">
                    <div class="course-banner <?= $cardType; ?>">
                        <?php if (!empty($c['thumbnail'])): ?><img class="course-banner-image" src="<?= e(site_url($c['thumbnail'])); ?>" alt="<?= e($courseTitle); ?> course image" loading="lazy" decoding="async"><?php endif; ?>
                        <span class="course-level-badge"><?= e($level); ?></span>
                        <div class="course-banner-content">
                            <span class="course-banner-icon" aria-hidden="true">
                                <?php if ($cardType === 'youtube'): ?>
                                    <i class="fab fa-youtube"></i>
                                <?php elseif ($cardType === 'shorts'): ?>
                                    <i class="fas fa-bolt"></i>
                                <?php else: ?>
                                    <i class="fas fa-robot"></i>
                                <?php endif; ?>
                            </span>
                            <span class="course-banner-label"><?= e($category); ?></span>
                        </div>
                    </div>

                    <div class="course-body">
                        <h3 class="course-title">
                            <a href="<?= e($courseUrl); ?>"><?= e($courseTitle); ?></a>
                        </h3>

                        <?php if ($subtitle !== ''): ?>
                            <p class="course-subtitle"><?= e($subtitle); ?></p>
                        <?php endif; ?>

                        <?php if ($instructor !== ''): ?>
                            <p class="course-instructor">
                                <i class="fas fa-user-tie" aria-hidden="true"></i> <?= e($instructor); ?>
                            </p>
                        <?php endif; ?>

                        <div class="course-meta">
                            <span class="course-meta-item">
                                <i class="fas fa-layer-group" aria-hidden="true"></i>
                                <?= e($lessons > 0 ? $lessons . ' lessons' : 'Self-paced'); ?>
                            </span>
                            <span class="course-meta-item">
                                <i class="far fa-clock" aria-hidden="true"></i> <?= e($duration); ?>
                            </span>
                        </div>

                        <div class="course-footer">
                            <div class="course-price">
                                <span class="course-price-label">Course fee</span>
                                <strong class="course-price-value"><?= e($coursePrice); ?></strong>
                            </div>
                            <a href="<?= e($courseUrl); ?>" class="btn btn-primary btn-sm">
                                View course <i class="fas fa-arrow-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <p class="home-courses-note">
            <i class="fas fa-info-circle" aria-hidden="true"></i>
            All prices are listed in Rwandan francs (RWF). Course access is unlocked after payment verification.
        </p>
    </div>
</section>

<!-- ================= How it works ================= -->
<section class="home-steps" aria-labelledby="steps-title">
    <div class="container">
        <div class="section-header">
            <div class="section-subtitle">Simple enrollment</div>
            <h2 class="section-title" id="steps-title">From sign-up to your first lesson</h2>
            <p class="section-desc">A clear four-step path with local payment support at every stage.</p>
        </div>

        <ol class="steps-grid">
            <li class="step-card">
                <span class="step-number" aria-hidden="true">01</span>
                <span class="step-icon" aria-hidden="true"><i class="fas fa-user-plus"></i></span>
                <h3>Create your account</h3>
                <p>Register in a few minutes and access your personal student dashboard.</p>
            </li>
            <li class="step-card">
                <span class="step-number" aria-hidden="true">02</span>
                <span class="step-icon" aria-hidden="true"><i class="fas fa-compass"></i></span>
                <h3>Choose your course</h3>
                <p>Compare the YouTube, Shorts, and AI video programs and pick your starting point.</p>
            </li>
            <li class="step-card">
                <span class="step-number" aria-hidden="true">03</span>
                <span class="step-icon" aria-hidden="true"><i class="fas fa-receipt"></i></span>
                <h3>Submit your payment</h3>
                <p>Pay with MTN MoMo, Airtel Money, or bank transfer, then submit your reference.</p>
            </li>
            <li class="step-card">
                <span class="step-number" aria-hidden="true">04</span>
                <span class="step-icon" aria-hidden="true"><i class="fas fa-play"></i></span>
                <h3>Start learning</h3>
                <p>Access unlocks after verification, so you can learn at your own pace.</p>
            </li>
        </ol>

        <p class="steps-support">
            Need guidance before enrolling?
            <a href="<?= e(support_whatsapp_url('Hello Smart Class, I need help choosing a course.')); ?>" target="_blank" rel="noopener">
                Chat with our team on WhatsApp <i class="fab fa-whatsapp" aria-hidden="true"></i>
            </a>
        </p>
    </div>
</section>

<!-- ================= Channel rescue campaign ================= -->
<section class="promo-banner home-promo" aria-labelledby="rescue-title">
    <div class="container">
        <div class="promo-banner-inner">
            <div class="promo-copy">
                <span class="promo-kicker">
                    <i class="fas fa-shield-alt" aria-hidden="true"></i> Smart Class protection campaign
                </span>
                <h2 id="rescue-title">YouTube channel deleted? <span>Smart Class is to the rescue.</span></h2>
                <p>We help creators recover hacked, deleted, or suspended channels — and protect future growth with proactive security and recovery support.</p>

                <ul class="promo-points">
                    <li><i class="fas fa-lock" aria-hidden="true"></i> Hacked accounts</li>
                    <li><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> Suspended channels</li>
                    <li><i class="fas fa-user-shield" aria-hidden="true"></i> Proactive protection</li>
                </ul>

                <div class="promo-actions">
                    <a href="<?= site_url('register.php'); ?>" class="btn btn-primary btn-lg">Rescue my channel</a>
                    <a href="<?= site_url('contact.php'); ?>" class="btn btn-on-dark btn-lg">Talk to an expert</a>
                </div>
            </div>
            <div class="promo-visual">
                <img src="<?= site_url('assets/images/smart-class-channel-rescue.png'); ?>"
                     alt="Smart Class YouTube channel recovery and protection advertisement"
                     loading="lazy" decoding="async">
            </div>
        </div>
    </div>
</section>

<!-- ================= Final call to action ================= -->
<section class="cta-band home-cta" aria-labelledby="cta-title">
    <div class="cta-inner">
        <h2 id="cta-title">Start learning today</h2>
        <p>Create an account, choose a course, and enroll with MTN MoMo or Airtel Money.</p>
        <div class="cta-actions">
            <a href="<?= site_url('courses.php'); ?>" class="btn btn-light btn-lg">
                Browse all courses <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </a>
            <a href="<?= site_url('register.php'); ?>" class="btn btn-on-dark btn-lg">Create free account</a>
        </div>
        <p class="cta-note">Prices are listed in RWF. Payment is verified before course access is granted.</p>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
