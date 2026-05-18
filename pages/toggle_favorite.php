<?php
require_once dirname(__DIR__) . '/core/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('courses.php');
}

$courseId = (int) ($_POST['course_id'] ?? 0);
$course = find_course($courseId);

if (!$course) {
    flash('error', 'Không tìm thấy khóa học.');
    redirect('courses.php');
}

$userId = (int) current_user()['id'];

if (is_favorite_course($courseId, $userId)) {
    $stmt = db()->prepare('DELETE FROM favorites WHERE user_id = ? AND course_id = ?');
    $stmt->execute([$userId, $courseId]);
    flash('success', 'Đã bỏ khóa học khỏi danh sách yêu thích.');
} else {
    $stmt = db()->prepare('INSERT IGNORE INTO favorites (user_id, course_id) VALUES (?, ?)');
    $stmt->execute([$userId, $courseId]);
    flash('success', 'Đã thêm khóa học vào danh sách yêu thích.');
}

redirect('course.php?id=' . $courseId);
