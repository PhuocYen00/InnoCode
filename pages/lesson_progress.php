<?php
require_once dirname(__DIR__) . '/core/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('my_courses.php');
}

$courseId = (int) ($_POST['course_id'] ?? 0);
$lessonIndex = max(0, (int) ($_POST['lesson_index'] ?? 0));
$action = (string) ($_POST['action'] ?? 'save_note');

if (!find_course($courseId) || !has_purchased_course($courseId)) {
    flash('error', 'Bạn chưa có quyền học khóa học này.');
    redirect('my_courses.php');
}

$currentProgress = lesson_progress($courseId, $lessonIndex);
$note = array_key_exists('note', $_POST)
    ? trim((string) $_POST['note'])
    : (string) ($currentProgress['note'] ?? '');
$isCompleted = $action === 'complete' ? 1 : (int) ($currentProgress['is_completed'] ?? 0);
$completedAtSql = $isCompleted === 1 ? 'NOW()' : 'NULL';

$stmt = db()->prepare("INSERT INTO course_lesson_progress (user_id, course_id, lesson_index, is_completed, note, completed_at)
    VALUES (?, ?, ?, ?, ?, {$completedAtSql})
    ON DUPLICATE KEY UPDATE is_completed = VALUES(is_completed), note = VALUES(note), completed_at = IF(VALUES(is_completed) = 1, NOW(), completed_at)");
$stmt->execute([(int) current_user()['id'], $courseId, $lessonIndex, $isCompleted, $note]);

$wantsJson = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') || (($_POST['ajax'] ?? '') === '1');
if ($wantsJson) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => true,
        'message' => $action === 'complete' ? 'Đã đánh dấu hoàn thành bài học.' : 'Đã lưu ghi chú.',
        'is_completed' => $isCompleted,
        'note' => $note,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

flash('success', $action === 'complete' ? 'Đã đánh dấu hoàn thành bài học.' : 'Đã lưu ghi chú.');
redirect('learn.php?id=' . $courseId . '&lesson=' . $lessonIndex);
