<?php
require_once dirname(__DIR__) . '/core/init.php';
require_login();
require_verified_email();

$pageTitle = 'Thanh toán - ' . APP_NAME;
$user = current_user();
$items = cart_items();

if (!$items) {
    flash('error', 'Giỏ hàng đang trống.');
    redirect('courses.php');
}

$hasPhysicalItems = cart_has_physical_items($items);
$courseItems = array_values(array_filter($items, static fn (array $item): bool => ($item['type'] ?? '') === 'course'));
$productItems = array_values(array_filter($items, static fn (array $item): bool => ($item['type'] ?? '') === 'product'));
$courseSubtotal = cart_total($courseItems);
$productSubtotal = cart_total($productItems);
$subtotal = $courseSubtotal + $productSubtotal;
$coupon = cart_coupon();
$discountPreview = cart_discount($subtotal);
$total = max(0, $subtotal - $discountPreview);
$courseOnlyDiscount = cart_discount($courseSubtotal);
$courseOnlyTotal = max(0, $courseSubtotal - $courseOnlyDiscount);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = trim((string) ($_POST['customer_name'] ?? ''));
    $customerEmail = trim((string) ($_POST['customer_email'] ?? ''));
    $customerPhone = trim((string) ($_POST['customer_phone'] ?? ''));
    $receiverName = trim((string) ($_POST['receiver_name'] ?? $customerName));
    $receiverPhone = trim((string) ($_POST['receiver_phone'] ?? $customerPhone));
    $shippingAddress = trim((string) ($_POST['shipping_address'] ?? ''));
    $paymentMethod = (string) ($_POST['payment_method'] ?? 'bank');
    $productPaymentMode = (string) ($_POST['product_payment_mode'] ?? 'online');
    $note = trim((string) ($_POST['note'] ?? ''));
    $payProductsNow = !$hasPhysicalItems || $productPaymentMode === 'online';
    $payableItems = array_values(array_filter($items, static function (array $item) use ($payProductsNow): bool {
        return ($item['type'] ?? '') === 'course' || $payProductsNow;
    }));
    $payableSubtotal = cart_total($payableItems);
    $discount = cart_discount($payableSubtotal);
    $total = max(0, $payableSubtotal - $discount);

    if ($customerName === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL) || $customerPhone === '') {
        flash('error', 'Vui lòng nhập đầy đủ họ tên, email và số điện thoại.');
        redirect('checkout.php');
    }

    if ($hasPhysicalItems && ($receiverName === '' || $receiverPhone === '' || $shippingAddress === '')) {
        flash('error', 'Vui lòng nhập đầy đủ thông tin nhận hàng.');
        redirect('checkout.php');
    }

    $allowedPaymentMethods = ['bank', 'vnpay', 'momo'];
    if (!in_array($paymentMethod, $allowedPaymentMethods, true)) {
        $paymentMethod = 'bank';
    }

    if ($hasPhysicalItems && !$payProductsNow && $courseSubtotal <= 0) {
        $paymentMethod = 'cod';
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare('INSERT INTO orders (user_id, customer_name, customer_email, customer_phone, note, total_amount, coupon_code, discount_amount, payment_method, payment_provider, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            (int) $user['id'],
            $customerName,
            $customerEmail,
            $customerPhone,
            $note,
            $total,
            $coupon['code'] ?? null,
            $discount,
            $paymentMethod,
            $paymentMethod === 'cod' ? 'cod' : 'payos',
            'pending',
        ]);
        $orderId = (int) $pdo->lastInsertId();

        foreach ($items as $item) {
            if ($item['type'] === 'course') {
                $stmt = $pdo->prepare('INSERT INTO order_items (order_id, course_id, price, quantity) VALUES (?, ?, ?, 1)');
                $stmt->execute([$orderId, $item['id'], $item['price']]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO physical_order_items (order_id, product_id, product_name, price, quantity, payment_status) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$orderId, $item['id'], $item['title'], $item['price'], $item['quantity'], $payProductsNow ? 'paid_online' : 'pay_later']);
            }
        }

        if ($hasPhysicalItems) {
            $stmt = $pdo->prepare('INSERT INTO shipments (order_id, receiver_name, receiver_phone, address, status) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$orderId, $receiverName, $receiverPhone, $shippingAddress, 'pending']);
        }

        $stmt = $pdo->prepare('UPDATE orders SET payment_code = ?, payos_order_code = ? WHERE id = ?');
        $stmt->execute([order_payment_code($orderId), payos_order_code($orderId), $orderId]);

        clear_cart((int) $user['id']);
        unset($_SESSION['coupon_code']);
        $pdo->commit();

        if ($total <= 0 && $courseSubtotal > 0) {
            complete_order($orderId);
            flash('success', 'Đơn hàng đã được kích hoạt do tổng thanh toán bằng 0đ.');
            redirect('my_courses.php');
        }

        if ($total <= 0 || $paymentMethod === 'cod') {
            flash('success', 'Đã tạo đơn giao hàng. Phần sách/quà sẽ được xác nhận và tính toán sau.');
            redirect('payment_success.php?id=' . $orderId);
        }

        $order = find_order($orderId);
        $checkoutUrl = $order ? payos_create_payment_link($order, $payableItems) : null;

        if ($checkoutUrl) {
            header('Location: ' . $checkoutUrl);
            exit;
        }

        flash('error', 'Chưa tạo được link PayOS. Hệ thống hiển thị thông tin chuyển khoản VietQR để bạn thanh toán và chờ xác nhận.');
        redirect('payment_success.php?id=' . $orderId);
    } catch (Throwable $exception) {
        $pdo->rollBack();
        flash('error', 'Không thể tạo đơn hàng. Vui lòng thử lại.');
        redirect('checkout.php');
    }
}

require_once dirname(__DIR__) . '/includes/header.php';
$paymentOptions = $hasPhysicalItems
    ? ['bank' => 'Chuyển khoản VietQR qua PayOS', 'vnpay' => 'VNPay qua PayOS', 'momo' => 'MoMo qua PayOS']
    : array_intersect_key(payment_methods(), array_flip(['bank', 'vnpay', 'momo']));
?>

<section class="container py-5">
    <h1 class="h2 mb-4">Thanh toán</h1>
    <div class="row g-4">
        <div class="col-lg-7">
            <form class="bg-white rounded-2 p-4 shadow-sm" method="post">
                <h2 class="h5 mb-3">Thông tin người mua</h2>
                <div class="mb-3">
                    <label class="form-label">Họ tên</label>
                    <input class="form-control" name="customer_name" value="<?= e($user['name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email nhận hóa đơn</label>
                    <input class="form-control" type="email" name="customer_email" value="<?= e($user['email']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Số điện thoại</label>
                    <input class="form-control" name="customer_phone" value="<?= e($user['phone']) ?>" required>
                </div>

                <?php if ($hasPhysicalItems): ?>
                    <div class="checkout-shipping-box">
                        <h2 class="h5 mb-3">Thông tin nhận hàng</h2>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Người nhận</label>
                                <input class="form-control" name="receiver_name" value="<?= e($user['name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Số điện thoại nhận hàng</label>
                                <input class="form-control" name="receiver_phone" value="<?= e($user['phone']) ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Địa chỉ giao hàng</label>
                                <textarea class="form-control" name="shipping_address" rows="3" required></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="checkout-shipping-box">
                        <h2 class="h5 mb-3">Cách tính tiền sách/quà</h2>
                        <label class="d-block mb-2">
                            <input type="radio" name="product_payment_mode" value="online" checked>
                            <span>Thanh toán online cùng khóa học</span>
                        </label>
                        <label class="d-block mb-0">
                            <input type="radio" name="product_payment_mode" value="later">
                            <span>Chỉ thanh toán khóa học trước, sách/quà sẽ tính sau khi xác nhận giao hàng</span>
                        </label>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Phương thức thanh toán</label>
                    <div class="payment-options">
                        <?php foreach ($paymentOptions as $key => $label): ?>
                            <label>
                                <input type="radio" name="payment_method" value="<?= e($key) ?>" <?= $key === 'bank' ? 'checked' : '' ?>>
                                <span><?= e($label) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Ghi chú</label>
                    <textarea class="form-control" name="note" rows="4"></textarea>
                </div>
                <button class="btn btn-primary btn-lg" type="submit">Tạo đơn và thanh toán</button>
            </form>
        </div>

        <div class="col-lg-5">
            <div class="bg-white rounded-2 p-4 shadow-sm">
                <h2 class="h5 mb-3">Đơn hàng của bạn</h2>
                <?php foreach ($items as $item): ?>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span><?= e($item['title']) ?> x <?= (int) $item['quantity'] ?></span>
                        <strong><?= money($item['line_total']) ?></strong>
                    </div>
                <?php endforeach; ?>
                <?php if ($courseSubtotal > 0): ?>
                    <div class="d-flex justify-content-between pt-3">
                        <span>Tiền khóa học</span>
                        <strong><?= money($courseSubtotal) ?></strong>
                    </div>
                <?php endif; ?>
                <?php if ($productSubtotal > 0): ?>
                    <div class="d-flex justify-content-between pt-2">
                        <span>Sách/quà trong giỏ</span>
                        <strong><?= money($productSubtotal) ?></strong>
                    </div>
                    <p class="small text-muted mt-2 mb-0">Nếu chọn tính sau, tổng thanh toán hiện tại chỉ gồm khóa học. Sách/quà vẫn được lưu vào đơn để admin xử lý giao hàng.</p>
                <?php endif; ?>
                <div class="d-flex justify-content-between pt-3">
                    <span>Tạm tính tối đa</span>
                    <strong><?= money($subtotal) ?></strong>
                </div>
                <div class="d-flex justify-content-between pt-2">
                    <span>Giảm giá<?= $coupon ? ' (' . e($coupon['code']) . ')' : '' ?></span>
                    <strong class="text-success" id="checkout-discount" data-all="<?= (float) $discountPreview ?>" data-course="<?= (float) $courseOnlyDiscount ?>">-<?= money($discountPreview) ?></strong>
                </div>
                <div class="d-flex justify-content-between h4 pt-3">
                    <span>Tổng thanh toán hiện tại</span>
                    <span class="price" id="checkout-pay-now" data-all="<?= (float) $total ?>" data-course="<?= (float) $courseOnlyTotal ?>"><?= money($total) ?></span>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.querySelectorAll('input[name="product_payment_mode"]').forEach((radio) => {
    radio.addEventListener('change', () => {
        const payNow = document.getElementById('checkout-pay-now');
        const discount = document.getElementById('checkout-discount');
        const mode = document.querySelector('input[name="product_payment_mode"]:checked')?.value || 'online';
        const key = mode === 'later' ? 'course' : 'all';
        const money = new Intl.NumberFormat('vi-VN').format(Number(payNow?.dataset[key] || 0)) + 'đ';
        const discountMoney = '-' + new Intl.NumberFormat('vi-VN').format(Number(discount?.dataset[key] || 0)) + 'đ';
        if (payNow) payNow.textContent = money;
        if (discount) discount.textContent = discountMoney;
    });
});
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
