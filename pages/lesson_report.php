<?php
require_once dirname(__DIR__) . '/core/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('my_courses.php');
}

$courseId = (int) ($_POST['course_id'] ?? 0);
$lessonIndex = max(0, (int) ($_POST['lesson_index'] ?? 0));
$message = trim((string) ($_POST['message'] ?? ''));

if (!find_course($courseId) || !has_purchased_course($courseId)) {
    flash('error', 'Bạn chưa có quyền học khóa học này.');
    redirect('my_courses.php');
}

if ($message === '') {
    flash('error', 'Vui lòng nhập nội dung phản hồi.');
    redirect('learn.php?id=' . $courseId . '&lesson=' . $lessonIndex);
}

$stmt = db()->prepare('INSERT INTO lesson_reports (user_id, course_id, lesson_index, message) VALUES (?, ?, ?, ?)');
$stmt->execute([(int) current_user()['id'], $courseId, $lessonIndex, $message]);

flash('success', 'Đã gửi phản hồi lỗi nội dung.');
redirect('learn.php?id=' . $courseId . '&lesson=' . $lessonIndex);
