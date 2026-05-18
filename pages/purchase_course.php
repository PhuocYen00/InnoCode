<?php
require_once dirname(__DIR__) . '/core/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('courses.php');
}

require_login();

$courseId = (int) ($_POST['course_id'] ?? 0);
$course = find_course($courseId);

if (!$course) {
    flash('error', 'Không tìm thấy khóa học.');
    redirect('courses.php');
}

if (has_purchased_course($courseId)) {
    redirect('learn.php?id=' . $courseId);
}

if (is_free_course($course)) {
    mark_course_purchased($courseId);
    flash('success', 'Bạn đã đăng ký khóa học miễn phí.');
    redirect('learn.php?id=' . $courseId);
}

cart_add('course', $courseId);
flash('success', 'Đã thêm khóa học vào giỏ hàng. Vui lòng thanh toán để mở khóa bài học.');
redirect('cart.php');


