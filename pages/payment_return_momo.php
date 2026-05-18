<?php
require_once dirname(__DIR__) . '/core/init.php';

$orderId = (int) ($_GET['orderId'] ?? $_POST['orderId'] ?? 0);
$resultCode = (string) ($_GET['resultCode'] ?? $_POST['resultCode'] ?? '');

if ($orderId > 0 && $resultCode === '0') {
    complete_order($orderId);
    flash('success', 'MoMo xác nhận thanh toán thành công.');
} else {
    flash('error', 'Thanh toán MoMo chưa thành công.');
}

redirect('payment_success.php?id=' . $orderId);


