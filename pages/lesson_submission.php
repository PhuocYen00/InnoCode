<?php
require_once dirname(__DIR__) . '/core/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('my_courses.php');
}

$courseId = (int) ($_POST['course_id'] ?? 0);
$lessonIndex = max(0, (int) ($_POST['lesson_index'] ?? 0));
$note = trim((string) ($_POST['note'] ?? ''));
$course = find_course($courseId);
$user = current_user();

if (!$course || !has_purchased_course($courseId)) {
    flash('error', 'Bạn chưa có quyền nộp bài cho khóa học này.');
    redirect('courses.php');
}

$flatLessons = course_flat_lessons($course);
$lesson = $flatLessons[$lessonIndex] ?? null;

if (!$lesson) {
    flash('error', 'Không tìm thấy bài học cần nộp.');
    redirect('learn.php?id=' . $courseId);
}

if (!is_lesson_unlocked($courseId, $lessonIndex)) {
    flash('error', 'Bạn cần hoàn thành bài học trước đó trước khi nộp bài này.');
    redirect('learn.php?id=' . $courseId);
}

if (!lesson_practice_for($courseId, $lessonIndex)) {
    flash('error', 'Bài học này chưa có bài thực hành để nộp.');
    redirect('learn.php?id=' . $courseId . '&lesson=' . $lessonIndex);
}

if (!isset($_FILES['source_file']) || ($_FILES['source_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    flash('error', 'Vui lòng chọn file bài làm trước khi nộp.');
    redirect('learn.php?id=' . $courseId . '&lesson=' . $lessonIndex);
}

if ($_FILES['source_file']['error'] !== UPLOAD_ERR_OK) {
    flash('error', 'Upload file không thành công. Vui lòng thử lại.');
    redirect('learn.php?id=' . $courseId . '&lesson=' . $lessonIndex);
}

$maxSize = 10 * 1024 * 1024;
$fileSize = (int) ($_FILES['source_file']['size'] ?? 0);
if ($fileSize <= 0 || $fileSize > $maxSize) {
    flash('error', 'File bài làm phải nhỏ hơn 10MB.');
    redirect('learn.php?id=' . $courseId . '&lesson=' . $lessonIndex);
}

$originalName = basename((string) $_FILES['source_file']['name']);
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$allowed = ['zip', 'rar', '7z', 'txt', 'pdf', 'doc', 'docx', 'php', 'js', 'ts', 'py', 'java', 'c', 'cpp', 'cs', 'html', 'css', 'sql'];

if (!in_array($extension, $allowed, true)) {
    flash('error', 'Định dạng file chưa được hỗ trợ. Hãy nộp source code, tài liệu hoặc file nén.');
    redirect('learn.php?id=' . $courseId . '&lesson=' . $lessonIndex);
}

$uploadDir = dirname(__DIR__) . '/storage/submissions';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$safeBase = preg_replace('/[^a-zA-Z0-9._-]/', '-', pathinfo($originalName, PATHINFO_FILENAME));
$storedName = 'submission_u' . (int) $user['id'] . '_c' . $courseId . '_l' . $lessonIndex . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
$targetPath = $uploadDir . '/' . $storedName;

if (!move_uploaded_file($_FILES['source_file']['tmp_name'], $targetPath)) {
    flash('error', 'Không thể lưu file bài làm. Vui lòng thử lại.');
    redirect('learn.php?id=' . $courseId . '&lesson=' . $lessonIndex);
}

$stmt = db()->prepare('INSERT INTO lesson_submissions (user_id, course_id, lesson_index, lesson_title, original_name, stored_name, file_size, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->execute([
    (int) $user['id'],
    $courseId,
    $lessonIndex,
    (string) $lesson['title'],
    $originalName ?: ($safeBase . '.' . $extension),
    $storedName,
    $fileSize,
    $note !== '' ? $note : null,
]);

flash('success', 'Đã nộp bài thực hành. Giảng viên/admin sẽ xem và nhận xét.');
redirect('learn.php?id=' . $courseId . '&lesson=' . $lessonIndex);
