<?php
require_once dirname(__DIR__) . '/core/init.php';

$params = $_GET;
$secureHash = $params['vnp_SecureHash'] ?? '';
unset($params['vnp_SecureHash'], $params['vnp_SecureHashType']);
ksort($params);

$hashData = [];
foreach ($params as $key => $value) {
    $hashData[] = $key . '=' . urlencode((string) $value);
}

$valid = VNPAY_HASH_SECRET !== '' && hash_hmac('sha512', implode('&', $hashData), VNPAY_HASH_SECRET) === $secureHash;
$orderId = (int) ($params['vnp_TxnRef'] ?? 0);
$responseCode = $params['vnp_ResponseCode'] ?? '';

if ($valid && $responseCode === '00') {
    flash('success', 'VNPay đã ghi nhận giao dịch. Đơn hàng đang chờ admin xác nhận để mở khóa học.');
} else {
    flash('error', 'Thanh toán VNPay chưa thành công hoặc chữ ký không hợp lệ.');
}

redirect('payment_success.php?id=' . $orderId);
