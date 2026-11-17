<?php
require_once __DIR__ . '/../includes/student-auth.php';
$user = current_user();
$assignmentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = null;
$assignment = null;
if (!$db_connected || !$pdo) {
    set_flash('error', 'Assignments are unavailable while the database is disconnected.');
    header('Location: ' . site_url('student/progress.php'));
    exit;
}
$stmt = $pdo->prepare('
    SELECT a.*, c.title AS course_title
    FROM course_assignments a JOIN courses c ON c.id = a.course_id
    JOIN enrollments e ON e.course_id = c.id AND e.user_id = :user_id AND e.status IN ("active", "completed")
    WHERE a.id = :assignment_id LIMIT 1
');
$stmt->execute(['user_id' => $user['id'], 'assignment_id' => $assignmentId]);
$assignment = $stmt->fetch();
if (!$assignment) {
    set_flash('error', 'Assignment not found or your enrollment is not active.');
    header('Location: ' . site_url('student/progress.php'));
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission = trim($_POST['submission_text'] ?? '');
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) $error = 'Your session expired. Please submit again.';
    elseif ($submission === '' || strlen($submission) > 10000) $error = 'Submit your project link or reflection, up to 10,000 characters.';
    else {
        $save = $pdo->prepare('
            INSERT INTO assignment_submissions (assignment_id, user_id, submission_text)
            VALUES (:assignment, :user, :submission)
            ON DUPLICATE KEY UPDATE submission_text = VALUES(submission_text), status = "submitted", submitted_at = CURRENT_TIMESTAMP
        ');
        $save->execute(['assignment' => $assignmentId, 'user' => $user['id'], 'submission' => $submission]);
        audit_log('assignment.submitted', 'Submitted assignment #' . $assignmentId, $user['id']);
        set_flash('success', 'Assignment submitted successfully.');
        header('Location: ' . site_url('student/assignment.php?id=' . $assignmentId));
        exit;
    }
}
$page_title = e($assignment['title']) . ' — Digital Smart Class';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container" style="padding: 2rem 1.5rem 4rem; max-width: 900px;">
    <a href="<?= site_url('student/progress.php'); ?>" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to progress</a>
    <div class="panel" style="padding: 2rem; margin-top: 1.5rem;">
        <div class="section-subtitle"><?= e($assignment['course_title']); ?></div>
        <h1 style="font-size: 1.8rem; color: var(--gray-900);"><?= e($assignment['title']); ?></h1>
        <p style="white-space: pre-line; color: var(--gray-700); margin: 1rem 0 1.5rem;"><?= e($assignment['instructions']); ?></p>
        <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error); ?></div><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
            <label class="form-label" for="submission_text">Project link and reflection</label>
            <textarea class="form-control" id="submission_text" name="submission_text" rows="10" maxlength="10000" required placeholder="Paste an unlisted link, describe your work, and list your original/licensed assets."></textarea>
            <button class="btn btn-primary" type="submit" style="margin-top: 1rem;"><i class="fas fa-paper-plane"></i> Submit assignment</button>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
