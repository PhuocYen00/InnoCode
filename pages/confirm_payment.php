<?php
require_once dirname(__DIR__) . '/core/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('my_courses.php');
}

$orderId = (int) ($_POST['order_id'] ?? 0);
$order = find_order($orderId);

if (!$order || (int) $order['user_id'] !== (int) current_user()['id']) {
    flash('error', 'Không tìm thấy đơn hàng cần xác nhận.');
    redirect('my_courses.php');
}

if ($order['status'] !== 'paid') {
    complete_order($orderId);
}

flash('success', 'Thanh toán đã được xác nhận. Khóa học của bạn đã được mở.');
redirect('my_courses.php');


