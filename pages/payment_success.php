<?php
require_once dirname(__DIR__) . '/core/init.php';
require_login();

$payosOrderCode = (int) ($_GET['orderCode'] ?? 0);
$order = $payosOrderCode > 0 ? find_order_by_payos_code($payosOrderCode) : null;
$orderId = $order ? (int) $order['id'] : (int) ($_GET['id'] ?? 0);
$order = $order ?: find_order($orderId);
$statusParam = strtoupper((string) ($_GET['status'] ?? ''));

if ($order && (string) ($_GET['code'] ?? '') === '00' && $statusParam === 'PAID') {
    complete_order((int) $order['id']);
    $order = find_order((int) $order['id']);
    $orderId = (int) $order['id'];
}

if ($order && $statusParam === 'CANCELLED') {
    cancel_order((int) $order['id']);
    $order = find_order((int) $order['id']);
    $orderId = (int) $order['id'];
}

if (!$order || (int) $order['user_id'] !== (int) current_user()['id']) {
    flash('error', 'Không tìm thấy đơn hàng.');
    redirect('my_courses.php');
}

$pageTitle = 'Thông tin thanh toán - ' . APP_NAME;
$method = $order['payment_method'] ?? 'bank';
$methodName = payment_methods()[$method] ?? 'Thanh toán';
$amount = (float) $order['total_amount'];
$status = (string) $order['status'];
$qrUrl = payment_qr_url($method, $orderId, $amount);
$payosError = $_SESSION['payos_last_error'] ?? null;
unset($_SESSION['payos_last_error']);
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="container py-5">
    <div class="payment-result">
        <div>
            <span class="badge badge-soft mb-3">Đơn hàng #<?= $orderId ?></span>
            <h1 class="h2 <?= $status === 'paid' ? 'text-success' : ($status === 'cancelled' ? 'text-danger' : '') ?>">
                <?php if ($status === 'paid'): ?>
                    Thanh toán thành công
                <?php elseif ($status === 'cancelled'): ?>
                    Thanh toán đã hủy
                <?php elseif (!empty($order['payos_checkout_url'])): ?>
                    Đơn hàng đang chờ thanh toán PayOS
                <?php else: ?>
                    Đơn hàng đang chờ chuyển khoản
                <?php endif; ?>
            </h1>
            <p class="text-muted">Phương thức: <?= e($methodName) ?>.</p>
            <?php if ($payosError && $status === 'pending' && empty($order['payos_checkout_url'])): ?>
                <div class="alert alert-warning">Không tạo được link PayOS: <?= e($payosError) ?>. Bạn vẫn có thể thanh toán bằng VietQR bên dưới.</div>
            <?php endif; ?>

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
                    <span>Trạng thái</span>
                    <strong><?= e(strtoupper($status)) ?></strong>
                </div>
                <div>
                    <span>Mã đơn</span>
                    <strong>#<?= $orderId ?></strong>
                </div>
            </div>

            <?php if ($status === 'pending' && empty($order['payos_checkout_url'])): ?>
                <div class="row g-4 mt-4 align-items-center">
                    <div class="col-md-4">
                        <img class="img-fluid rounded-2 border" src="<?= e($qrUrl ?: '') ?>" alt="VietQR thanh toán">
                    </div>
                    <div class="col-md-8">
                        <h2 class="h5">Thông tin chuyển khoản</h2>
                        <p class="mb-1"><strong>Ngân hàng:</strong> <?= e(BANK_NAME) ?></p>
                        <p class="mb-1"><strong>Số tài khoản:</strong> <?= e(BANK_ACCOUNT_NUMBER) ?></p>
                        <p class="mb-1"><strong>Chủ tài khoản:</strong> <?= e(BANK_ACCOUNT_NAME) ?></p>
                        <p class="mb-1"><strong>Số tiền:</strong> <?= money($amount) ?></p>
                        <p class="mb-0"><strong>Nội dung:</strong> <?= e($order['payment_code'] ?: order_payment_code($orderId)) ?></p>
                        <p class="text-muted small mt-2 mb-0">Sau khi bạn chuyển khoản, admin xác nhận đơn trong trang quản trị để mở khóa khóa học hoặc tài liệu.</p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="d-flex gap-2 flex-wrap mt-4">
                <?php if ($status === 'paid'): ?>
                    <a class="btn btn-primary" href="<?= url('my_courses') ?>">Vào khóa học của tôi</a>
                    <a class="btn btn-outline-primary" href="<?= url('my_materials') ?>">Tài liệu của tôi</a>
                    <a class="btn btn-outline-primary" href="<?= url('receipt') ?>&id=<?= $orderId ?>">In biên lai</a>
                <?php elseif ($status === 'cancelled'): ?>
                    <a class="btn btn-primary" href="<?= url('cart') ?>">Quay lại giỏ hàng</a>
                    <a class="btn btn-outline-primary" href="<?= url('courses') ?>">Xem khóa học khác</a>
                <?php elseif (!empty($order['payos_checkout_url'])): ?>
                    <a class="btn btn-primary" href="<?= e($order['payos_checkout_url']) ?>">Tiếp tục thanh toán PayOS</a>
                    <a class="btn btn-outline-primary" href="<?= url('courses') ?>">Xem thêm khóa học</a>
                <?php else: ?>
                    <a class="btn btn-primary" href="<?= url('courses') ?>">Xem thêm khóa học</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
