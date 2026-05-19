<?php
require_once dirname(__DIR__) . '/core/init.php';

$wantsJson = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') || (($_POST['ajax'] ?? '') === '1');

function purchase_response(bool $ok, string $message, ?string $redirect = null): void
{
    global $wantsJson;

    if ($wantsJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => $ok,
            'message' => $message,
            'cart_count' => cart_items_count(),
            'redirect' => $redirect,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    flash($ok ? 'success' : 'error', $message);
    redirect($redirect ?: ($_SERVER['HTTP_REFERER'] ?? 'courses.php'));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('courses.php');
}

if (!is_logged_in()) {
    purchase_response(false, 'Vui lòng đăng nhập để thêm khóa học vào giỏ hàng.', url('login', ['next' => 'courses.php']));
}

$courseId = (int) ($_POST['course_id'] ?? 0);
$course = find_course($courseId);

if (!$course) {
    purchase_response(false, 'Không tìm thấy khóa học.', 'courses.php');
}

if (has_purchased_course($courseId)) {
    purchase_response(true, 'Bạn đã sở hữu khóa học này.', 'learn.php?id=' . $courseId);
}

if (is_free_course($course)) {
    mark_course_purchased($courseId);
    purchase_response(true, 'Bạn đã đăng ký khóa học miễn phí.', 'learn.php?id=' . $courseId);
}

cart_add('course', $courseId);
purchase_response(true, 'Đã thêm khóa học vào giỏ hàng.');
