<?php
$pageTitle = 'Quản lý quiz';
require_once __DIR__ . '/includes/header.php';

$courseId = (int) ($_GET['course_id'] ?? $_POST['course_id'] ?? 0);
$lessonId = (int) ($_GET['lesson_id'] ?? $_POST['lesson_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $lessonId = (int) ($_POST['lesson_id'] ?? 0);

    if ($action === 'save_question') {
        $quizTitle = trim((string) ($_POST['quiz_title'] ?? 'Quiz bài học'));
        $questionType = ($_POST['question_type'] ?? 'choice') === 'essay' ? 'essay' : 'choice';
        $question = trim((string) ($_POST['question'] ?? ''));
        $questionId = (int) ($_POST['question_id'] ?? 0);

        if ($lessonId <= 0 || $question === '') {
            flash('error', 'Vui lòng chọn bài học và nhập câu hỏi.');
            redirect('admin/quizzes.php?course_id=' . $courseId . '&lesson_id=' . $lessonId);
        }

        $stmt = db()->prepare('SELECT id FROM quizzes WHERE lesson_id = ? LIMIT 1');
        $stmt->execute([$lessonId]);
        $quizId = (int) ($stmt->fetchColumn() ?: 0);

        if ($quizId === 0) {
            $stmt = db()->prepare('INSERT INTO quizzes (lesson_id, title) VALUES (?, ?)');
            $stmt->execute([$lessonId, $quizTitle]);
            $quizId = (int) db()->lastInsertId();
        } else {
            $stmt = db()->prepare('UPDATE quizzes SET title = ? WHERE id = ?');
            $stmt->execute([$quizTitle, $quizId]);
        }

        $data = [
            'quiz_id' => $quizId,
            'question_type' => $questionType,
            'question' => $question,
            'option_a' => trim((string) ($_POST['option_a'] ?? '')),
            'option_b' => trim((string) ($_POST['option_b'] ?? '')),
            'option_c' => trim((string) ($_POST['option_c'] ?? '')),
            'option_d' => trim((string) ($_POST['option_d'] ?? '')),
            'correct_option' => strtolower((string) ($_POST['correct_option'] ?? 'a')),
            'sample_answer' => trim((string) ($_POST['sample_answer'] ?? '')),
        ];

        if ($questionType === 'essay') {
            if ($data['option_a'] === '') {
                flash('error', 'Câu tự luận cần nhập đáp án đúng.');
                redirect('admin/quizzes.php?course_id=' . $courseId . '&lesson_id=' . $lessonId);
            }

            $data['option_b'] = '-';
            $data['option_c'] = '';
            $data['option_d'] = '';
            $data['correct_option'] = 'a';
        } elseif ($data['option_a'] === '' || $data['option_b'] === '') {
            flash('error', 'Câu trắc nghiệm cần ít nhất đáp án A và B.');
            redirect('admin/quizzes.php?course_id=' . $courseId . '&lesson_id=' . $lessonId);
        }

        if ($questionId > 0) {
            $stmt = db()->prepare('UPDATE quiz_questions SET question_type = :question_type, question = :question, option_a = :option_a, option_b = :option_b, option_c = :option_c, option_d = :option_d, correct_option = :correct_option, sample_answer = :sample_answer WHERE id = :id');
            $data['id'] = $questionId;
            unset($data['quiz_id']);
            $stmt->execute($data);
            flash('success', 'Đã cập nhật câu hỏi.');
        } else {
            $stmt = db()->prepare('INSERT INTO quiz_questions (quiz_id, question_type, question, option_a, option_b, option_c, option_d, correct_option, sample_answer) VALUES (:quiz_id, :question_type, :question, :option_a, :option_b, :option_c, :option_d, :correct_option, :sample_answer)');
            $stmt->execute($data);
            flash('success', 'Đã thêm câu hỏi quiz.');
        }

        redirect('admin/quizzes.php?course_id=' . $courseId . '&lesson_id=' . $lessonId);
    }

    if ($action === 'delete_question') {
        $stmt = db()->prepare('DELETE FROM quiz_questions WHERE id = ?');
        $stmt->execute([(int) $_POST['question_id']]);
        flash('success', 'Đã xóa câu hỏi.');
        redirect('admin/quizzes.php?course_id=' . $courseId . '&lesson_id=' . $lessonId);
    }
}

$courses = db()->query('SELECT * FROM courses ORDER BY title')->fetchAll();
$selectedCourse = $courseId ? find_course($courseId, false) : ($courses[0] ?? null);
$courseId = (int) ($selectedCourse['id'] ?? 0);
$lessons = $selectedCourse ? array_filter(course_flat_lessons($selectedCourse), static fn (array $lesson): bool => !empty($lesson['id'])) : [];

if ($lessonId === 0 && $lessons) {
    $firstLesson = reset($lessons);
    $lessonId = (int) $firstLesson['id'];
}

$quiz = null;
$questions = [];
if ($lessonId > 0) {
    $stmt = db()->prepare('SELECT * FROM quizzes WHERE lesson_id = ? LIMIT 1');
    $stmt->execute([$lessonId]);
    $quiz = $stmt->fetch() ?: null;

    if ($quiz) {
        $stmt = db()->prepare('SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id');
        $stmt->execute([(int) $quiz['id']]);
        $questions = $stmt->fetchAll();
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0">Quản lý quiz</h1>
    <a class="btn btn-outline-primary" href="<?= APP_URL ?>/admin/quiz_attempts.php">Xem bài làm học viên</a>
</div>

<div class="alert alert-info">
    Quy trình: chọn khóa học → chọn bài học → tạo câu trắc nghiệm hoặc tự luận. Quiz gắn trực tiếp với bài học nên học viên vào đúng bài sẽ thấy đúng câu hỏi.
</div>

<form class="bg-white rounded-2 p-4 shadow-sm mb-4" method="get">
    <div class="row g-3 align-items-end">
        <div class="col-md-5">
            <label class="form-label">Khóa học</label>
            <select class="form-select" name="course_id" onchange="this.form.submit()">
                <?php foreach ($courses as $course): ?>
                    <option value="<?= (int) $course['id'] ?>" <?= (int) $course['id'] === $courseId ? 'selected' : '' ?>><?= e($course['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-5">
            <label class="form-label">Bài học</label>
            <select class="form-select" name="lesson_id">
                <?php foreach ($lessons as $index => $lesson): ?>
                    <option value="<?= (int) $lesson['id'] ?>" <?= (int) $lesson['id'] === $lessonId ? 'selected' : '' ?>>
                        Bài <?= $index + 1 ?> - <?= e($lesson['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100" type="submit">Chọn</button>
        </div>
    </div>
    <?php if (!$lessons): ?>
        <p class="text-muted mt-3 mb-0">Khóa học này chưa có bài học trong database. Hãy vào trang Nội dung của khóa học để thêm chương/bài trước.</p>
    <?php endif; ?>
</form>

<?php if ($lessonId > 0): ?>
    <section class="bg-white rounded-2 p-4 shadow-sm mb-4">
        <h2 class="h5 mb-3">Thêm câu hỏi mới</h2>
        <form method="post" data-quiz-question-form>
            <input type="hidden" name="course_id" value="<?= $courseId ?>">
            <input type="hidden" name="lesson_id" value="<?= $lessonId ?>">
            <input type="hidden" name="action" value="save_question">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Tên quiz</label>
                    <input class="form-control" name="quiz_title" value="<?= e($quiz['title'] ?? 'Quiz bài học') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Loại câu hỏi</label>
                    <select class="form-select" name="question_type" data-question-type>
                        <option value="choice">Trắc nghiệm</option>
                        <option value="essay">Tự luận</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Câu hỏi</label>
                    <textarea class="form-control" name="question" rows="3" required></textarea>
                </div>
                <div class="col-md-3" data-choice-field><input class="form-control" name="option_a" placeholder="Đáp án A"></div>
                <div class="col-md-3" data-choice-field><input class="form-control" name="option_b" placeholder="Đáp án B"></div>
                <div class="col-md-3" data-choice-field><input class="form-control" name="option_c" placeholder="Đáp án C"></div>
                <div class="col-md-3" data-choice-field><input class="form-control" name="option_d" placeholder="Đáp án D"></div>
                <div class="col-md-6 d-none" data-essay-field>
                    <label class="form-label">Đáp án đúng</label>
                    <input class="form-control" name="option_a" placeholder="Nhập đáp án đúng cho câu tự luận" disabled>
                </div>
                <div class="col-md-3" data-choice-answer>
                    <label class="form-label">Đáp án đúng</label>
                    <select class="form-select" name="correct_option">
                        <option value="a">A</option>
                        <option value="b">B</option>
                        <option value="c">C</option>
                        <option value="d">D</option>
                    </select>
                </div>
                <div class="col-md-6 d-none" data-essay-hint>
                    <label class="form-label">Gợi ý</label>
                    <input class="form-control" name="sample_answer" placeholder="Gợi ý ngắn để học viên định hướng câu trả lời">
                </div>
            </div>
            <button class="btn btn-primary mt-3" type="submit">Thêm câu hỏi</button>
        </form>
    </section>

    <section class="bg-white rounded-2 p-4 shadow-sm">
        <h2 class="h5 mb-3">Danh sách câu hỏi</h2>
        <?php if (!$questions): ?>
            <p class="text-muted">Chưa có câu hỏi nào cho bài học này.</p>
        <?php endif; ?>
        <?php foreach ($questions as $question): ?>
            <?php $isEssay = ($question['question_type'] ?? 'choice') === 'essay'; ?>
            <form class="border rounded-2 p-3 mb-3" method="post" data-quiz-question-form>
                <input type="hidden" name="course_id" value="<?= $courseId ?>">
                <input type="hidden" name="lesson_id" value="<?= $lessonId ?>">
                <input type="hidden" name="question_id" value="<?= (int) $question['id'] ?>">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Loại</label>
                        <select class="form-select" name="question_type" data-question-type>
                            <option value="choice" <?= $question['question_type'] === 'choice' ? 'selected' : '' ?>>Trắc nghiệm</option>
                            <option value="essay" <?= $question['question_type'] === 'essay' ? 'selected' : '' ?>>Tự luận</option>
                        </select>
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">Câu hỏi</label>
                        <input class="form-control" name="question" value="<?= e($question['question']) ?>">
                    </div>
                    <div class="col-md-3" data-choice-field><input class="form-control" name="option_a" value="<?= e($isEssay ? '' : $question['option_a']) ?>" placeholder="Đáp án A" <?= $isEssay ? 'disabled' : '' ?>></div>
                    <div class="col-md-3" data-choice-field><input class="form-control" name="option_b" value="<?= e($isEssay ? '' : $question['option_b']) ?>" placeholder="Đáp án B" <?= $isEssay ? 'disabled' : '' ?>></div>
                    <div class="col-md-3" data-choice-field><input class="form-control" name="option_c" value="<?= e($isEssay ? '' : $question['option_c']) ?>" placeholder="Đáp án C" <?= $isEssay ? 'disabled' : '' ?>></div>
                    <div class="col-md-3" data-choice-field><input class="form-control" name="option_d" value="<?= e($isEssay ? '' : $question['option_d']) ?>" placeholder="Đáp án D" <?= $isEssay ? 'disabled' : '' ?>></div>
                    <div class="col-md-6 <?= $isEssay ? '' : 'd-none' ?>" data-essay-field>
                        <label class="form-label">Đáp án đúng</label>
                        <input class="form-control" name="option_a" value="<?= e($isEssay ? $question['option_a'] : '') ?>" placeholder="Nhập đáp án đúng cho câu tự luận" <?= $isEssay ? '' : 'disabled' ?>>
                    </div>
                    <div class="col-md-3 <?= $isEssay ? 'd-none' : '' ?>" data-choice-answer>
                        <select class="form-select" name="correct_option" <?= $isEssay ? 'disabled' : '' ?>>
                            <?php foreach (['a', 'b', 'c', 'd'] as $option): ?>
                                <option value="<?= $option ?>" <?= strtolower((string) $question['correct_option']) === $option ? 'selected' : '' ?>>Đáp án <?= strtoupper($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 <?= $isEssay ? '' : 'd-none' ?>" data-essay-hint>
                        <input class="form-control" name="sample_answer" value="<?= e($question['sample_answer']) ?>" placeholder="Gợi ý">
                    </div>
                </div>
                <div class="mt-3 d-flex gap-2">
                    <button class="btn btn-outline-primary" name="action" value="save_question" type="submit">Lưu câu hỏi</button>
                    <button class="btn btn-outline-danger" name="action" value="delete_question" type="submit" onclick="return confirm('Xóa câu hỏi này?')">Xóa</button>
                </div>
            </form>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<script>
document.querySelectorAll('[data-quiz-question-form]').forEach((form) => {
    const typeSelect = form.querySelector('[data-question-type]');

    if (!typeSelect) {
        return;
    }

    const toggleControl = (field, visible) => {
        if (!field) {
            return;
        }

        field.classList.toggle('d-none', !visible);
        field.querySelectorAll('input, select, textarea').forEach((control) => {
            control.disabled = !visible;
        });
    };

    const syncQuestionType = () => {
        const isEssay = typeSelect.value === 'essay';

        form.querySelectorAll('[data-choice-field]').forEach((field) => toggleControl(field, !isEssay));
        toggleControl(form.querySelector('[data-choice-answer]'), !isEssay);
        toggleControl(form.querySelector('[data-essay-field]'), isEssay);
        toggleControl(form.querySelector('[data-essay-hint]'), isEssay);
    };

    typeSelect.addEventListener('change', syncQuestionType);
    syncQuestionType();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
