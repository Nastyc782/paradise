<?php
require_once __DIR__ . '/../includes/student-auth.php';
$user = current_user();

$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$course = null;

if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $courseId]);
        $course = $stmt->fetch();
    } catch (PDOException $e) {
        $course = null;
    }
}
if (!$course) {
    set_flash('error', 'That course does not exist.');
    header('Location: ' . site_url('student/courses.php'));
    exit;
}

$page_title = "Buy Course: " . e($course['title']) . " — DIGITAL SMART CLASS";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 4rem; max-width: 800px;">
    <!-- Course Purchase Header -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 2.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <div style="text-align: center; margin-bottom: 2rem;">
            <span class="badge" style="background: #eef2ff; color: #4f46e5; font-size: 0.85rem; padding: 0.35rem 1rem; border-radius: 9999px;">
                Payment checkout
            </span>
            <h1 style="font-size: 1.85rem; font-weight: 800; color: #0f172a; margin-top: 0.75rem;">
                <?= e($course['title']); ?>
            </h1>
            <div style="font-size: 2.25rem; font-weight: 800; color: var(--primary); margin-top: 0.5rem;">
                <?= format_money($course['price'], $course['currency'] ?? 'RWF'); ?>
            </div>
        </div>

        <!-- Manual payment instructions -->
        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 1.75rem; margin-bottom: 2rem;">
            <h3 style="font-size: 1.15rem; font-weight: 800; color: #92400e; margin-bottom: 1rem;">
                <i class="fas fa-mobile-alt"></i> Choose a payment method
            </h3>

            <ol style="padding-left: 1.5rem; line-height: 1.8; color: #78350f; font-size: 0.95rem;">
                <li>For a real payment, use the official Mobile Money or bank instructions from support.</li>
                <li>Submit your transaction reference and optional proof on the next screen.</li>
                <li>Every payment is reviewed by an administrator before course access is activated.</li>
            </ol>
        </div>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.75rem; margin-bottom: 2rem;">
            <h3 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin-bottom: 0.75rem;">
                <i class="fas fa-shield-alt" style="color: var(--success);"></i> Step 2: Submit Proof for Approval
            </h3>
            <p style="color: #64748b; font-size: 0.95rem; line-height: 1.6;">
                After sending your payment, click the button below to submit your transaction ID. An authorized administrator will review your enrollment and unlock all course videos, notes, files, and presentations after approval.
            </p>
            <p style="margin-top: 0.85rem; font-size: 0.9rem; color: #475569;">
                <i class="fab fa-whatsapp" style="color: #25d366;"></i>
                Need help paying? Chat with our support team on WhatsApp at
                <a href="<?= e(support_whatsapp_url('Hello support, I need help enrolling in: ' . $course['title'])); ?>" target="_blank" rel="noopener" style="color: var(--primary); font-weight: 700;"><?= e(support_phone_display()); ?></a>.
            </p>
        </div>

        <div style="text-align: center;">
            <a href="<?= site_url('student/payment.php?course_id=' . $course['id']); ?>" class="btn btn-primary btn-lg" style="padding: 1rem 3rem; font-size: 1.1rem; font-weight: 800;">
                <i class="fas fa-check-circle"></i> SUBMIT PAYMENT PROOF NOW
            </a>
            <div style="margin-top: 1rem;">
                <a href="<?= site_url('courses.php'); ?>" style="color: #64748b; font-size: 0.9rem;">
                    <i class="fas fa-arrow-left"></i> Cancel and return to courses
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
