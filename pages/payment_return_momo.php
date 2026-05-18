<?php
require_once dirname(__DIR__) . '/core/init.php';

$orderId = (int) ($_GET['orderId'] ?? $_POST['orderId'] ?? 0);
$resultCode = (string) ($_GET['resultCode'] ?? $_POST['resultCode'] ?? '');

if ($orderId > 0 && $resultCode === '0') {
    flash('success', 'MoMo đã ghi nhận giao dịch. Đơn hàng đang chờ admin xác nhận để mở khóa học.');
} else {
    flash('error', 'Thanh toán MoMo chưa thành công.');
}

redirect('payment_success.php?id=' . $orderId);
