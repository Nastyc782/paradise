<?php
require_once __DIR__ . '/../includes/teacher-auth.php';
$user = current_user();
$courseId = (int)($_GET['course_id'] ?? $_POST['course_id'] ?? 0);
$error = null;
if (!$db_connected || !$pdo) exit('Assignment management requires a database connection.');
$access = $pdo->prepare('SELECT id, title FROM courses WHERE id = :course AND (teacher_id = :teacher OR :is_admin = 1) LIMIT 1');
$access->execute(['course' => $courseId, 'teacher' => $user['id'], 'is_admin' => $user['role'] === 'admin' ? 1 : 0]);
$course = $access->fetch();
if (!$course) exit('Course not found or access denied.');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $type = $_POST['submission_type'] ?? 'text';
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) $error = 'Your session expired. Please try again.';
    elseif ($title === '' || $instructions === '' || !in_array($type, ['text', 'file', 'link'], true)) $error = 'Complete all assignment fields.';
    else {
        $stmt = $pdo->prepare('INSERT INTO course_assignments (course_id,title,instructions,submission_type) VALUES (:course,:title,:instructions,:type)');
        $stmt->execute(['course' => $courseId, 'title' => $title, 'instructions' => $instructions, 'type' => $type]);
        audit_log('assignment.created', 'Created assignment for course #' . $courseId, $user['id']);
        set_flash('success', 'Assignment published.');
        header('Location: ' . site_url('teacher/assignments.php?course_id=' . $courseId));
        exit;
    }
}
$stmt = $pdo->prepare('SELECT * FROM course_assignments WHERE course_id = :course ORDER BY id');
$stmt->execute(['course' => $courseId]);
$assignments = $stmt->fetchAll();
$page_title = 'Manage Assignments — Digital Smart Class';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout"><aside class="sidebar"><div class="sidebar-user"><div class="sidebar-user-avatar avatar-teacher"><i class="fas fa-chalkboard-teacher"></i></div><div class="sidebar-user-info"><h5><?= e($user['full_name']); ?></h5><span>Instructor</span></div></div><ul class="sidebar-menu"><li class="sidebar-item"><a href="<?= site_url('teacher/courses.php'); ?>"><i class="fas fa-book"></i> My Courses</a></li><li class="sidebar-item active"><a href="<?= site_url('teacher/assignments.php?course_id=' . $courseId); ?>"><i class="fas fa-tasks"></i> Assignments</a></li><li class="sidebar-item"><a href="<?= site_url('teacher/quizzes.php?course_id=' . $courseId); ?>"><i class="fas fa-question-circle"></i> Quizzes</a></li></ul></aside>
<main class="dashboard-main"><div class="dashboard-title-bar"><div><h2>Assignments</h2><p class="page-subtitle"><?= e($course['title']); ?></p></div></div>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error); ?></div><?php endif; ?>
<div class="panel" style="padding:1.5rem;margin-bottom:1.5rem;"><form method="POST"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>"><input type="hidden" name="course_id" value="<?= $courseId; ?>"><div class="form-group"><label class="form-label" for="title">Assignment title</label><input class="form-control" id="title" name="title" required maxlength="200"></div><div class="form-group"><label class="form-label" for="instructions">Instructions</label><textarea class="form-control" id="instructions" name="instructions" rows="5" required></textarea></div><div class="form-group"><label class="form-label" for="submission_type">Submission type</label><select class="form-control" id="submission_type" name="submission_type"><option value="text">Text / link</option><option value="link">Project link</option><option value="file">File</option></select></div><button class="btn btn-primary" type="submit">Publish assignment</button></form></div>
<div class="table-card"><div class="table-responsive"><table><thead><tr><th>Title</th><th>Submission</th><th>Instructions</th></tr></thead><tbody><?php if (!$assignments): ?><tr><td colspan="3">No assignments yet.</td></tr><?php else: foreach ($assignments as $assignment): ?><tr><td><?= e($assignment['title']); ?></td><td><?= e(ucfirst($assignment['submission_type'])); ?></td><td><?= e($assignment['instructions']); ?></td></tr><?php endforeach; endif; ?></tbody></table></div></div>
</main></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
