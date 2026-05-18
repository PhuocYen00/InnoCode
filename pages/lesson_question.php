<?php
require_once dirname(__DIR__) . '/core/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('my_courses.php');
}

$courseId = (int) ($_POST['course_id'] ?? 0);
$lessonIndex = max(0, (int) ($_POST['lesson_index'] ?? 0));
$question = trim((string) ($_POST['question'] ?? ''));

if (!find_course($courseId) || !has_purchased_course($courseId)) {
    flash('error', 'Bạn chưa có quyền học khóa học này.');
    redirect('my_courses.php');
}

if ($question === '') {
    flash('error', 'Vui lòng nhập câu hỏi.');
    redirect('learn.php?id=' . $courseId . '&lesson=' . $lessonIndex);
}

$stmt = db()->prepare('INSERT INTO course_questions (user_id, course_id, lesson_index, question) VALUES (?, ?, ?, ?)');
$stmt->execute([(int) current_user()['id'], $courseId, $lessonIndex, $question]);

flash('success', 'Đã gửi câu hỏi của bạn.');
redirect('learn.php?id=' . $courseId . '&lesson=' . $lessonIndex);
