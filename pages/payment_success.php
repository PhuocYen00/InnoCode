<?php
require_once dirname(__DIR__) . '/core/init.php';
require_login();

$orderId = (int) ($_GET['id'] ?? 0);
$order = find_order($orderId);

if (!$order || (int) $order['user_id'] !== (int) current_user()['id']) {
    flash('error', 'Không tìm thấy đơn hàng.');
    redirect('my_courses.php');
}

$pageTitle = 'Thông tin thanh toán - ' . APP_NAME;
$method = $order['payment_method'] ?? 'bank';
$methodName = payment_methods()[$method] ?? 'Thanh toán';
$amount = (float) $order['total_amount'];
$qrUrl = payment_qr_url($method, $orderId, $amount);
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="container py-5">
    <div class="payment-result">
        <div>
            <span class="badge badge-soft mb-3">Đơn hàng #<?= $orderId ?></span>
            <h1 class="h2 <?= $order['status'] === 'paid' ? 'text-success' : '' ?>">
                <?= $order['status'] === 'paid' ? 'Thanh toán thành công' : 'Đơn hàng đang chờ thanh toán' ?>
            </h1>
            <p class="text-muted">Phương thức: <?= e($methodName) ?>. Mã thanh toán của bạn là <strong><?= e($order['payment_code'] ?: order_payment_code($orderId)) ?></strong>.</p>

            <div class="payment-info">
                <div>
                    <span>Số tiền</span>
                    <strong><?= money($amount) ?></strong>
                </div>
                <div>
                    <span>Nội dung</span>
                    <strong><?= e($order['payment_code'] ?: order_payment_code($orderId)) ?></strong>
                </div>
                <div>
                    <span>Người nhận</span>
                    <strong><?= e(BANK_ACCOUNT_NAME) ?></strong>
                </div>
                <div>
                    <span>Ngân hàng</span>
                    <strong><?= e(BANK_NAME) ?> - <?= e(BANK_ACCOUNT_NUMBER) ?></strong>
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap mt-4">
                <?php if ($order['status'] === 'paid'): ?>
                    <a class="btn btn-primary" href="<?= url('my_courses') ?>">Vào khóa học của tôi</a>
                <?php else: ?>
                    <form method="post" action="<?= url('confirm_payment') ?>">
                        <input type="hidden" name="order_id" value="<?= $orderId ?>">
                        <button class="btn btn-success" type="submit">Tôi đã thanh toán</button>
                    </form>
                    <a class="btn btn-primary" href="<?= url('courses') ?>">Xem thêm khóa học</a>
                <?php endif; ?>
                <a class="btn btn-outline-primary" href="<?= url('receipt') ?>&id=<?= $orderId ?>">In biên lai</a>
            </div>
        </div>

        <div class="qr-box">
            <?php if ($qrUrl): ?>
                <img src="<?= e($qrUrl) ?>" alt="Mã QR thanh toán đơn hàng #<?= $orderId ?>">
                <p>Quét mã VietQR để chuyển khoản thanh toán</p>
            <?php else: ?>
                <div class="cod-box">PAY</div>
                <p>Cổng <?= e($methodName) ?> chưa cấu hình merchant trong <code>core/config.php</code>.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>


