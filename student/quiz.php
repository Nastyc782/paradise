<?php
require_once __DIR__ . '/../includes/student-auth.php';
$user = current_user();
$quizId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$quiz = null;
$questions = [];
$result = null;
$error = null;

if (!$db_connected || !$pdo) {
    set_flash('error', 'The quiz is unavailable while the database is disconnected.');
    header('Location: ' . site_url('student/progress.php'));
    exit;
}

$stmt = $pdo->prepare('
    SELECT q.*, c.title AS course_title
    FROM quizzes q JOIN courses c ON c.id = q.course_id
    JOIN enrollments e ON e.course_id = c.id AND e.user_id = :user_id AND e.status IN ("active", "completed")
    WHERE q.id = :quiz_id LIMIT 1
');
$stmt->execute(['user_id' => $user['id'], 'quiz_id' => $quizId]);
$quiz = $stmt->fetch();
if (!$quiz) {
    set_flash('error', 'Quiz not found or your enrollment is not active.');
    header('Location: ' . site_url('student/progress.php'));
    exit;
}

$questionStmt = $pdo->prepare('SELECT * FROM quiz_questions WHERE quiz_id = :quiz_id ORDER BY order_num, id');
$questionStmt->execute(['quiz_id' => $quizId]);
$questions = $questionStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Please submit the quiz again.';
    } elseif (empty($questions)) {
        $error = 'This quiz has no questions yet.';
    } else {
        $answers = $_POST['answers'] ?? [];
        $earned = 0;
        $total = 0;
        foreach ($questions as $question) {
            $points = max(1, (int)$question['points']);
            $total += $points;
            if (isset($answers[$question['id']]) && (int)$answers[$question['id']] === (int)$question['correct_option']) {
                $earned += $points;
            }
        }
        $score = $total > 0 ? round(($earned / $total) * 100, 2) : 0;
        $passed = $score >= (float)$quiz['passing_score'] ? 1 : 0;
        $attemptStmt = $pdo->prepare('INSERT INTO quiz_attempts (quiz_id, user_id, score, passed, answers_json) VALUES (:quiz, :user, :score, :passed, :answers)');
        $attemptStmt->execute(['quiz' => $quizId, 'user' => $user['id'], 'score' => $score, 'passed' => $passed, 'answers' => json_encode($answers)]);
        audit_log('quiz.completed', 'Completed quiz #' . $quizId . ' with score ' . $score, $user['id']);
        if ($passed && (int)$quiz['is_final_exam'] === 1) {
            $lessonStmt = $pdo->prepare('SELECT COUNT(*) FROM lessons l JOIN modules m ON m.id = l.module_id WHERE m.course_id = :course');
            $lessonStmt->execute(['course' => $quiz['course_id']]);
            $lessonTotal = (int)$lessonStmt->fetchColumn();
            $completedStmt = $pdo->prepare('SELECT COUNT(*) FROM lesson_progress WHERE course_id = :course AND user_id = :user AND is_completed = 1');
            $completedStmt->execute(['course' => $quiz['course_id'], 'user' => $user['id']]);
            if ($lessonTotal > 0 && (int)$completedStmt->fetchColumn() >= $lessonTotal) {
                $certificateCode = 'DSC-' . strtoupper(bin2hex(random_bytes(6)));
                $certificateStmt = $pdo->prepare('
                    INSERT INTO course_certificates (course_id, user_id, certificate_code)
                    VALUES (:course, :user, :code)
                    ON DUPLICATE KEY UPDATE certificate_code = certificate_code
                ');
                $certificateStmt->execute(['course' => $quiz['course_id'], 'user' => $user['id'], 'code' => $certificateCode]);
            }
        }
        $result = ['score' => $score, 'passed' => $passed];
    }
}

$page_title = e($quiz['title']) . ' — Digital Smart Class';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container" style="padding: 2rem 1.5rem 4rem; max-width: 900px;">
    <a href="<?= site_url('student/progress.php'); ?>" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to progress</a>
    <div class="panel" style="padding: 2rem; margin-top: 1.5rem;">
        <div class="section-subtitle"><?= e($quiz['course_title']); ?></div>
        <h1 style="font-size: 1.8rem; color: var(--gray-900);"><?= e($quiz['title']); ?></h1>
        <p style="color: var(--gray-600); margin: .5rem 0 1.5rem;"><?= e($quiz['description']); ?> Passing score: <?= (int)$quiz['passing_score']; ?>%.</p>
        <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error); ?></div><?php endif; ?>
        <?php if ($result): ?>
            <div class="alert <?= $result['passed'] ? 'alert-success' : 'alert-danger'; ?>" role="status">
                You scored <?= e($result['score']); ?>%. <?= $result['passed'] ? 'Passed.' : 'Please review the lessons and try again.'; ?>
                <?php if ($result['passed'] && (int)$quiz['is_final_exam'] === 1): ?>
                    <a href="<?= site_url('student/certificate.php?course_id=' . (int)$quiz['course_id']); ?>">View certificate status</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if (empty($questions)): ?>
            <p>No questions have been published yet.</p>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                <?php foreach ($questions as $index => $question): $options = json_decode($question['options_json'], true) ?: []; ?>
                    <fieldset style="border: 0; border-top: 1px solid var(--gray-200); padding: 1.25rem 0; margin: 0;">
                        <legend style="font-weight: 700; color: var(--gray-900);"><?= ($index + 1); ?>. <?= e($question['question']); ?></legend>
                        <?php foreach ($options as $optionIndex => $option): ?>
                            <label style="display: block; padding: .5rem 0; color: var(--gray-700);"><input type="radio" name="answers[<?= (int)$question['id']; ?>]" value="<?= (int)$optionIndex; ?>" required> <?= e($option); ?></label>
                        <?php endforeach; ?>
                    </fieldset>
                <?php endforeach; ?>
                <button class="btn btn-primary" type="submit"><i class="fas fa-check"></i> Submit answers</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
