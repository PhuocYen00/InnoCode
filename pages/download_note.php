<?php
require_once dirname(__DIR__) . '/core/init.php';
require_login();

$courseId = (int) ($_GET['course_id'] ?? 0);
$lessonIndex = max(0, (int) ($_GET['lesson'] ?? 0));
$course = find_course($courseId);

if (!$course || !has_purchased_course($courseId)) {
    http_response_code(403);
    exit('Bạn chưa có quyền tải ghi chú này.');
}

$progress = lesson_progress($courseId, $lessonIndex);
$note = trim((string) ($progress['note'] ?? ''));

if ($note === '') {
    $note = 'Chưa có ghi chú.';
}

$filename = 'ghichu-khoa-' . $courseId . '-bai-' . $lessonIndex . '.txt';
header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo "InnoCode - Ghi chú bài học\n";
echo "Khóa học: " . $course['title'] . "\n";
echo "Bài số: " . ($lessonIndex + 1) . "\n\n";
echo $note;
exit;
