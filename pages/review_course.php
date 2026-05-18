<?php
require_once dirname(__DIR__) . '/core/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('courses.php');
}

$courseId = (int) ($_POST['course_id'] ?? 0);
$rating = max(1, min(5, (int) ($_POST['rating'] ?? 5)));
$comment = trim((string) ($_POST['comment'] ?? ''));

if (!find_course($courseId)) {
    flash('error', 'Không tìm thấy khóa học.');
    redirect('courses.php');
}

if (!has_purchased_course($courseId)) {
    flash('error', 'Bạn cần mua khóa học trước khi đánh giá.');
    redirect('course.php?id=' . $courseId);
}

$stmt = db()->prepare('INSERT INTO course_reviews (user_id, course_id, rating, comment) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), is_visible = 1');
$stmt->execute([(int) current_user()['id'], $courseId, $rating, $comment]);

flash('success', 'Đã lưu đánh giá của bạn.');
redirect('course.php?id=' . $courseId);
