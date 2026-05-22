<?php
require_once dirname(__DIR__) . '/core/init.php';
require_login();

$pageTitle = 'Học khóa học - ' . APP_NAME;
$course = find_course((int) ($_GET['id'] ?? 0));

if (!$course) {
    flash('error', 'Không tìm thấy khóa học.');
    redirect('courses.php');
}

if (!has_purchased_course((int) $course['id'])) {
    flash('error', 'Vui lòng đăng ký khóa học trước khi xem video bài giảng.');
    redirect('course.php?id=' . (int) $course['id']);
}

$sections = course_sections($course);
$flatLessons = course_flat_lessons($course);

if (!$flatLessons) {
    flash('error', 'Khóa học này chưa có nội dung bài học. Vui lòng quay lại sau khi quản trị viên cập nhật nội dung.');
    redirect('course.php?id=' . (int) $course['id']);
}

$lessonIndex = max(0, min((int) ($_GET['lesson'] ?? 0), count($flatLessons) - 1));

if (!is_lesson_unlocked((int) $course['id'], $lessonIndex)) {
    flash('error', 'Bạn cần hoàn thành bài học trước đó để mở bài tiếp theo.');
    redirect('learn.php?id=' . (int) $course['id'] . '&lesson=' . ($lessonIndex - 1));
}

$currentLesson = $flatLessons[$lessonIndex];
$progress = lesson_progress((int) $course['id'], $lessonIndex);
$questions = lesson_questions((int) $course['id'], $lessonIndex);
$materials = lesson_materials_for((int) $course['id'], $lessonIndex);
$practice = lesson_practice_for((int) $course['id'], $lessonIndex);
$quizQuestions = quiz_questions_for((int) $course['id'], $lessonIndex);
$submissionStmt = db()->prepare('SELECT * FROM lesson_submissions WHERE user_id = ? AND course_id = ? AND lesson_index = ? ORDER BY created_at DESC, id DESC LIMIT 1');
$submissionStmt->execute([(int) current_user()['id'], (int) $course['id'], $lessonIndex]);
$latestSubmission = $submissionStmt->fetch() ?: null;
$compilerResult = $_SESSION['compiler_result'] ?? null;
$compilerLanguage = (string) ($_SESSION['compiler_language'] ?? 'php');
$compilerCode = compiler_sample_code($compilerLanguage);
$compilerSamples = [];
foreach (array_keys(compiler_available_languages()) as $languageKey) {
    $compilerSamples[$languageKey] = compiler_sample_code($languageKey);
}
$isPreviewResult = is_array($compilerResult) && !empty($compilerResult['preview']);
unset($_SESSION['compiler_result'], $_SESSION['compiler_code'], $_SESSION['compiler_stdin']);

require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="learning-shell">
    <div class="learning-video">
        <div class="video-frame learning-frame">
            <iframe src="<?= e(lesson_embed_url($course, $lessonIndex)) ?>" title="<?= e($currentLesson['title']) ?>" allowfullscreen></iframe>
        </div>

        <div class="learning-info">
            <div class="lesson-titlebar">
                <div>
                    <span class="badge badge-soft"><?= e($currentLesson['section']) ?></span>
                    <h1><?= e($currentLesson['title']) ?></h1>
                    <p><?= e($course['title']) ?> · <?= e($currentLesson['duration']) ?></p>
                </div>
                <div class="lesson-title-actions">
                    <form method="post" action="<?= url('lesson_progress') ?>">
                        <input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>">
                        <input type="hidden" name="lesson_index" value="<?= $lessonIndex ?>">
                        <button class="btn btn-success" name="action" value="complete" type="submit">Đánh dấu đã hoàn thành</button>
                    </form>
                    <a class="btn btn-outline-primary" href="<?= url('course') ?>&id=<?= (int) $course['id'] ?>">Xem chi tiết khóa học</a>
                </div>
            </div>

            <form class="lesson-card note-card js-note-form" method="post" action="<?= url('lesson_progress') ?>">
                <input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>">
                <input type="hidden" name="lesson_index" value="<?= $lessonIndex ?>">
                <div class="lesson-card-head">
                    <div>
                        <span class="lesson-icon">✎</span>
                        <h2>Ghi chú trong lúc học</h2>
                    </div>
                    <small>Lưu lại ý chính, lỗi cần hỏi hoặc đoạn code quan trọng.</small>
                </div>
                <textarea class="form-control note-textarea" name="note" rows="5" placeholder="Ghi chú tại đây"><?= e($progress['note'] ?? '') ?></textarea>
                <div class="lesson-action-row">
                    <button class="btn btn-primary" name="action" value="save_note" type="submit">Lưu ghi chú</button>
                    <a class="btn btn-outline-primary" data-note-download href="<?= url('download_note') ?>&course_id=<?= (int) $course['id'] ?>&lesson=<?= $lessonIndex ?>">Lưu file .txt</a>
                </div>
            </form>

            <?php if (!empty($currentLesson['theory_content'])): ?>
                <section class="lesson-card">
                    <div class="lesson-card-head">
                        <div>
                            <span class="lesson-icon">i</span>
                            <h2>Lý thuyết bài học</h2>
                        </div>
                    </div>
                    <div class="lesson-theory"><?= nl2br(e((string) $currentLesson['theory_content'])) ?></div>
                </section>
            <?php endif; ?>

            <div class="lesson-tool-grid">
                <?php if ($materials): ?>
                    <section class="lesson-card materials-card">
                        <div class="lesson-card-head">
                            <div>
                                <span class="lesson-icon">⇩</span>
                                <h2>Tài liệu bài học</h2>
                            </div>
                            <small>PDF, source code và slide được giảng viên đính kèm.</small>
                        </div>
                        <div class="material-list">
                            <?php foreach ($materials as $material): ?>
                                <a class="material-item" href="<?= url('download_material') ?>&course_id=<?= (int) $course['id'] ?>&lesson=<?= $lessonIndex ?>&type=<?= e($material['type']) ?>">
                                    <span><?= e($material['title']) ?></span>
                                    <small><?= strtoupper(e($material['type'])) ?></small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($practice): ?>
                    <section class="lesson-card practice-card">
                        <div class="lesson-card-head">
                            <div>
                                <span class="lesson-icon">⌘</span>
                                <h2>Nộp bài thực hành</h2>
                            </div>
                            <small>Gửi source code để giảng viên kiểm tra.</small>
                        </div>
                        <h3 class="h6"><?= e((string) $practice['title']) ?></h3>
                        <p><?= nl2br(e((string) $practice['instruction'])) ?></p>
                        <?php if (!empty($practice['starter_code'])): ?>
                            <pre class="compiler-output"><?= e((string) $practice['starter_code']) ?></pre>
                        <?php endif; ?>
                        <form class="practice-submit" method="post" action="<?= url('lesson_submission') ?>" enctype="multipart/form-data">
                            <input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>">
                            <input type="hidden" name="lesson_index" value="<?= $lessonIndex ?>">
                            <input class="form-control" type="file" name="source_file" required>
                            <textarea class="form-control mt-2" name="note" rows="3" placeholder="Ghi chú thêm cho giảng viên nếu cần"></textarea>
                            <button class="btn btn-outline-primary mt-2" type="submit">Nộp bài</button>
                        </form>
                        <?php if ($latestSubmission): ?>
                            <div class="submission-status mt-3">
                                <small class="text-muted">Bài đã nộp gần nhất: <?= e($latestSubmission['original_name']) ?> · <?= e($latestSubmission['created_at']) ?></small>
                                <?php if (!empty($latestSubmission['feedback'])): ?>
                                    <p class="answer-box mt-2 mb-0">Nhận xét: <?= e($latestSubmission['feedback']) ?></p>
                                <?php else: ?>
                                    <p class="text-muted small mb-0">Đang chờ admin/giảng viên nhận xét.</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>

                <?php if ($quizQuestions): ?>
                    <section class="lesson-card quiz-card">
                        <div class="lesson-card-head">
                            <div>
                                <span class="lesson-icon">?</span>
                                <h2>Quiz bài học</h2>
                            </div>
                            <small>Trắc nghiệm và câu hỏi tự luận trên trang riêng.</small>
                        </div>
                        <p>Kiểm tra lại kiến thức của bài học trước khi mở các nội dung tiếp theo.</p>
                        <a class="btn btn-primary" href="<?= url('quiz') ?>&course_id=<?= (int) $course['id'] ?>&lesson=<?= $lessonIndex ?>">Vào làm quiz</a>
                    </section>
                <?php endif; ?>

                <section class="lesson-card compiler-card" id="compiler">
                    <div class="lesson-card-head">
                        <div>
                            <span class="lesson-icon">▶</span>
                            <h2>Trình biên dịch code</h2>
                        </div>
                        <small>Chọn ngôn ngữ, chạy thử code và xem kết quả thật.</small>
                    </div>
                    <p class="compiler-hint">Với chương trình có input, nhập mỗi giá trị trên một dòng. Ngôn ngữ nào thiếu runtime sẽ được ẩn khỏi danh sách.</p>
                    <form method="post" action="<?= url('compiler_run') ?>">
                        <input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>">
                        <input type="hidden" name="lesson_index" value="<?= $lessonIndex ?>">
                        <label class="form-label">Ngôn ngữ</label>
                        <select class="form-select mb-2 js-lesson-compiler-language" name="language">
                            <?php foreach (compiler_available_languages() as $key => $language): ?>
                                <option value="<?= e($key) ?>" <?= $key === $compilerLanguage ? 'selected' : '' ?>><?= e($language['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <textarea class="form-control code-area js-lesson-compiler-code" name="code" rows="7"><?= e($compilerCode) ?></textarea>
                        <button class="btn btn-primary mt-3" type="submit">Chạy code</button>
                    </form>
                    <?php if ($isPreviewResult): ?>
                        <iframe class="compiler-preview-frame lesson-preview-frame" sandbox="allow-scripts allow-forms" srcdoc="<?= e($compilerResult['output']) ?>" title="Preview"></iframe>
                    <?php else: ?>
                        <pre class="compiler-output js-lesson-compiler-output"><?= e(is_array($compilerResult) ? $compilerResult['output'] : 'Kết quả chạy code sẽ hiển thị tại đây.') ?></pre>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>

    <aside class="learning-sidebar">
        <div class="learning-sidebar-head">
            <h2><?= e($course['title']) ?></h2>
            <a href="<?= url('course') ?>&id=<?= (int) $course['id'] ?>">Chi tiết</a>
        </div>
        <?php $counter = 0; ?>
        <?php foreach ($sections as $section): ?>
            <div class="learn-section">
                <h3><?= e($section['title']) ?></h3>
                <?php foreach ($section['lessons'] as $lesson): ?>
                    <?php $unlocked = is_lesson_unlocked((int) $course['id'], $counter); ?>
                    <?php $done = (int) (lesson_progress((int) $course['id'], $counter)['is_completed'] ?? 0) === 1; ?>
                    <a class="learn-lesson <?= $counter === $lessonIndex ? 'active' : '' ?> <?= !$unlocked ? 'locked' : '' ?>" href="<?= $unlocked ? url('learn') . '&id=' . (int) $course['id'] . '&lesson=' . $counter : '#' ?>">
                        <span><?= $done ? '✓' : ($unlocked ? '▶' : '🔒') ?> <?= e($lesson['title']) ?></span>
                        <small><?= e($lesson['duration']) ?></small>
                    </a>
                    <?php $counter++; ?>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <div class="learn-section">
            <h3>Hỏi đáp bài học</h3>
            <form method="post" action="<?= url('lesson_question') ?>">
                <input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>">
                <input type="hidden" name="lesson_index" value="<?= $lessonIndex ?>">
                <textarea class="form-control" name="question" rows="3" placeholder="Nhập câu hỏi của bạn"></textarea>
                <button class="btn btn-primary btn-sm mt-2" type="submit">Gửi câu hỏi</button>
            </form>
            <?php foreach (array_slice($questions, 0, 5) as $question): ?>
                <div class="question-item">
                    <strong><?= e($question['name']) ?></strong>
                    <p><?= e($question['question']) ?></p>
                    <?php if ($question['answer']): ?>
                        <small class="answer-box">Trả lời: <?= e($question['answer']) ?></small>
                    <?php else: ?>
                        <small class="text-muted">Đang chờ admin/giảng viên trả lời.</small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="learn-section">
            <h3>Báo cáo lỗi nội dung</h3>
            <form method="post" action="<?= url('lesson_report') ?>">
                <input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>">
                <input type="hidden" name="lesson_index" value="<?= $lessonIndex ?>">
                <textarea class="form-control" name="message" rows="3" placeholder="Mô tả lỗi hoặc góp ý"></textarea>
                <button class="btn btn-outline-primary btn-sm mt-2" type="submit">Gửi phản hồi</button>
            </form>
        </div>
    </aside>
</section>

<script>
const lessonCompilerSamples = <?= json_encode($compilerSamples, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
document.querySelectorAll('.js-lesson-compiler-language').forEach(function (select) {
    select.addEventListener('change', function () {
        const card = select.closest('.compiler-card');
        const editor = card?.querySelector('.js-lesson-compiler-code');
        if (editor) {
            editor.value = lessonCompilerSamples[select.value] || '';
        }

        const stdin = card?.querySelector('textarea[name="stdin"]');
        if (stdin) {
            stdin.value = '';
        }

        card?.querySelector('.lesson-preview-frame')?.remove();
        let output = card?.querySelector('.js-lesson-compiler-output');
        if (!output && card) {
            output = document.createElement('pre');
            output.className = 'compiler-output js-lesson-compiler-output';
            card.appendChild(output);
        }
        if (output) {
            output.textContent = 'Kết quả chạy code sẽ hiển thị tại đây.';
        }
    });
});
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
