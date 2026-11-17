<?php
require_once __DIR__ . '/../includes/teacher-auth.php';
$user = current_user();
$courseId = (int)($_GET['course_id'] ?? $_POST['course_id'] ?? 0);
$error = null;
if (!$db_connected || !$pdo) exit('Quiz management requires a database connection.');
$access = $pdo->prepare('SELECT id, title FROM courses WHERE id = :course AND (teacher_id = :teacher OR :is_admin = 1) LIMIT 1');
$access->execute(['course' => $courseId, 'teacher' => $user['id'], 'is_admin' => $user['role'] === 'admin' ? 1 : 0]);
$course = $access->fetch();
if (!$course) exit('Course not found or access denied.');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $question = trim($_POST['question'] ?? '');
    $options = array_values(array_filter(array_map('trim', [$_POST['option_a'] ?? '', $_POST['option_b'] ?? '', $_POST['option_c'] ?? '', $_POST['option_d'] ?? '']), static function ($value) { return $value !== ''; }));
    $correct = (int)($_POST['correct_option'] ?? -1);
    $isFinal = !empty($_POST['is_final_exam']) ? 1 : 0;
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) $error = 'Your session expired. Please try again.';
    elseif ($title === '' || $question === '' || count($options) < 2 || !isset($options[$correct])) $error = 'Provide a quiz title, question, at least two options, and a valid answer.';
    else {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO quizzes (course_id,title,description,passing_score,is_final_exam) VALUES (:course,:title,:description,70,:final)');
        $stmt->execute(['course' => $courseId, 'title' => $title, 'description' => 'Automatic quiz managed by the instructor.', 'final' => $isFinal]);
        $quizId = (int)$pdo->lastInsertId();
        $stmt = $pdo->prepare('INSERT INTO quiz_questions (quiz_id,question,options_json,correct_option,points,order_num) VALUES (:quiz,:question,:options,:correct,1,1)');
        $stmt->execute(['quiz' => $quizId, 'question' => $question, 'options' => json_encode($options), 'correct' => $correct]);
        $pdo->commit();
        audit_log('quiz.created', 'Created quiz for course #' . $courseId, $user['id']);
        set_flash('success', 'Quiz and first question published.');
        header('Location: ' . site_url('teacher/quizzes.php?course_id=' . $courseId));
        exit;
    }
}
$stmt = $pdo->prepare('SELECT q.*, (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id=q.id) AS question_count FROM quizzes q WHERE q.course_id=:course ORDER BY q.is_final_exam, q.id');
$stmt->execute(['course' => $courseId]);
$quizzes = $stmt->fetchAll();
$page_title = 'Manage Quizzes — Digital Smart Class';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout"><aside class="sidebar"><div class="sidebar-user"><div class="sidebar-user-avatar avatar-teacher"><i class="fas fa-chalkboard-teacher"></i></div><div class="sidebar-user-info"><h5><?= e($user['full_name']); ?></h5><span>Instructor</span></div></div><ul class="sidebar-menu"><li class="sidebar-item"><a href="<?= site_url('teacher/courses.php'); ?>"><i class="fas fa-book"></i> My Courses</a></li><li class="sidebar-item"><a href="<?= site_url('teacher/assignments.php?course_id=' . $courseId); ?>"><i class="fas fa-tasks"></i> Assignments</a></li><li class="sidebar-item active"><a href="<?= site_url('teacher/quizzes.php?course_id=' . $courseId); ?>"><i class="fas fa-question-circle"></i> Quizzes</a></li></ul></aside>
<main class="dashboard-main"><div class="dashboard-title-bar"><div><h2>Quizzes & Exams</h2><p class="page-subtitle"><?= e($course['title']); ?> · automatic marking</p></div></div>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error); ?></div><?php endif; ?>
<div class="panel" style="padding:1.5rem;margin-bottom:1.5rem;"><form method="POST"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>"><input type="hidden" name="course_id" value="<?= $courseId; ?>"><div class="form-group"><label class="form-label" for="title">Quiz or exam title</label><input class="form-control" id="title" name="title" required maxlength="200"></div><div class="form-group"><label class="form-label" for="question">First question</label><textarea class="form-control" id="question" name="question" rows="3" required></textarea></div><div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;"><?php foreach (['a','b','c','d'] as $letter): ?><div class="form-group"><label class="form-label" for="option_<?= $letter; ?>">Option <?= strtoupper($letter); ?></label><input class="form-control" id="option_<?= $letter; ?>" name="option_<?= $letter; ?>"></div><?php endforeach; ?></div><div class="form-group"><label class="form-label" for="correct_option">Correct option</label><select class="form-control" id="correct_option" name="correct_option"><option value="0">Option A</option><option value="1">Option B</option><option value="2">Option C</option><option value="3">Option D</option></select></div><label style="display:flex;gap:.5rem;align-items:center;margin-bottom:1rem;"><input type="checkbox" name="is_final_exam" value="1"> Mark as final examination</label><button class="btn btn-primary" type="submit">Publish quiz</button></form></div>
<div class="table-card"><div class="table-responsive"><table><thead><tr><th>Quiz</th><th>Questions</th><th>Type</th></tr></thead><tbody><?php if (!$quizzes): ?><tr><td colspan="3">No quizzes yet.</td></tr><?php else: foreach ($quizzes as $quiz): ?><tr><td><?= e($quiz['title']); ?></td><td><?= (int)$quiz['question_count']; ?></td><td><?= $quiz['is_final_exam'] ? 'Final exam' : 'Knowledge check'; ?></td></tr><?php endforeach; endif; ?></tbody></table></div></div>
</main></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
