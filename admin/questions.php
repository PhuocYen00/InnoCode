<?php
$pageTitle = 'Quản lý hỏi đáp';
require_once __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $answer = trim((string) ($_POST['answer'] ?? ''));
    $status = $answer !== '' ? 'answered' : 'open';

    $stmt = db()->prepare('UPDATE course_questions SET answer = ?, status = ? WHERE id = ?');
    $stmt->execute([$answer !== '' ? $answer : null, $status, $id]);
    flash('success', 'Đã cập nhật câu trả lời.');
    redirect('admin/questions.php');
}

$questions = db()->query('SELECT course_questions.*, users.name AS user_name, courses.title AS course_title
    FROM course_questions
    JOIN users ON users.id = course_questions.user_id
    JOIN courses ON courses.id = course_questions.course_id
    ORDER BY course_questions.created_at DESC')->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0">Hỏi đáp học viên</h1>
    <span class="badge bg-primary"><?= count($questions) ?> câu hỏi</span>
</div>

<div class="bg-white rounded-2 p-4 shadow-sm">
    <?php if (!$questions): ?>
        <p class="text-muted mb-0">Chưa có câu hỏi nào.</p>
    <?php endif; ?>

    <?php foreach ($questions as $question): ?>
        <form class="border-bottom pb-4 mb-4" method="post">
            <input type="hidden" name="id" value="<?= (int) $question['id'] ?>">
            <div class="d-flex justify-content-between gap-3">
                <div>
                    <h2 class="h5 mb-1"><?= e($question['course_title']) ?> · Bài <?= (int) $question['lesson_index'] + 1 ?></h2>
                    <p class="text-muted mb-2"><?= e($question['user_name']) ?> hỏi lúc <?= e($question['created_at']) ?></p>
                </div>
                <span class="badge <?= $question['status'] === 'answered' ? 'bg-success' : 'bg-warning text-dark' ?> align-self-start">
                    <?= e($question['status']) ?>
                </span>
            </div>
            <p class="fw-semibold"><?= e($question['question']) ?></p>
            <label class="form-label">Trả lời</label>
            <textarea class="form-control" name="answer" rows="3"><?= e($question['answer']) ?></textarea>
            <button class="btn btn-primary mt-2" type="submit">Lưu câu trả lời</button>
        </form>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
