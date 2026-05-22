<?php
require_once dirname(__DIR__) . '/core/init.php';
require_login();

$courseId = (int) ($_GET['course_id'] ?? $_POST['course_id'] ?? 0);
$lessonIndex = max(0, (int) ($_GET['lesson'] ?? $_POST['lesson_index'] ?? 0));
$course = find_course($courseId);

if (!$course || !has_purchased_course($courseId)) {
    flash('error', 'Bạn chưa có quyền làm quiz của khóa học này.');
    redirect('my_courses.php');
}

$questions = quiz_questions_for($courseId, $lessonIndex);
$result = null;

if (!$questions) {
    flash('error', 'Bài học này chưa có quiz.');
    redirect('learn.php?id=' . $courseId . '&lesson=' . $lessonIndex);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $score = 0;
    $total = 0;

    foreach ($questions as $index => $question) {
        if ($question['type'] !== 'choice') {
            continue;
        }

        $total++;
        if (($_POST['answers'][$index] ?? '') === $question['answer']) {
            $score++;
        }
    }

    $essay = trim((string) ($_POST['essay_answer'] ?? ''));
    $stmt = db()->prepare('INSERT INTO quiz_attempts (user_id, course_id, lesson_index, score, total, essay_answer, feedback) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([(int) current_user()['id'], $courseId, $lessonIndex, $score, $total, $essay, 'Bài tự luận đã được ghi nhận, admin/giảng viên có thể xem lại trong CSDL.']);
    $result = ['score' => $score, 'total' => $total];
}

$pageTitle = 'Quiz bài học - ' . APP_NAME;
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1">Quiz bài học</h1>
            <p class="text-muted mb-0"><?= e($course['title']) ?> · Bài <?= $lessonIndex + 1 ?></p>
        </div>
        <a class="btn btn-outline-primary" href="<?= url('learn') ?>&id=<?= $courseId ?>&lesson=<?= $lessonIndex ?>">Quay lại học</a>
    </div>

    <?php if ($result): ?>
        <div class="alert alert-success">Bạn trả lời đúng <?= $result['score'] ?>/<?= $result['total'] ?> câu trắc nghiệm. Phần tự luận đã được lưu.</div>
    <?php endif; ?>

    <form class="bg-white rounded-2 p-4 shadow-sm" method="post">
        <input type="hidden" name="course_id" value="<?= $courseId ?>">
        <input type="hidden" name="lesson_index" value="<?= $lessonIndex ?>">
        <?php foreach ($questions as $index => $question): ?>
            <div class="mb-4">
                <h2 class="h5"><?= $index + 1 ?>. <?= e($question['question']) ?></h2>
                <?php if ($question['type'] === 'choice'): ?>
                    <?php foreach ($question['options'] as $key => $option): ?>
                        <label class="d-block mb-2">
                            <input type="radio" name="answers[<?= $index ?>]" value="<?= e($key) ?>" required>
                            <?= e($option) ?>
                        </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <textarea class="form-control" name="essay_answer" rows="5" placeholder="Nhập câu trả lời tự luận"></textarea>
                    <?php if (!empty($question['hint'])): ?>
                        <p class="text-muted small mt-2 mb-0">Gợi ý: <?= e($question['hint']) ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <button class="btn btn-primary" type="submit">Nộp bài quiz</button>
    </form>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
