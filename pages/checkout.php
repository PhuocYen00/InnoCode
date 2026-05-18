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

$subtotal = cart_total($items);
$coupon = cart_coupon();
$discount = cart_discount($subtotal);
$total = max(0, $subtotal - $discount);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = trim((string) ($_POST['customer_name'] ?? ''));
    $customerEmail = trim((string) ($_POST['customer_email'] ?? ''));
    $customerPhone = trim((string) ($_POST['customer_phone'] ?? ''));
    $paymentMethod = (string) ($_POST['payment_method'] ?? 'bank');
    $note = trim((string) ($_POST['note'] ?? ''));

    if ($customerName === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL) || $customerPhone === '') {
        flash('error', 'Vui lòng nhập đầy đủ họ tên, email và số điện thoại.');
        redirect('checkout.php');
    }

    if (!array_key_exists($paymentMethod, payment_methods())) {
        $paymentMethod = 'bank';
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
            'payos',
            'pending',
        ]);
        $orderId = (int) $pdo->lastInsertId();

        foreach ($items as $item) {
            $stmt = $pdo->prepare('INSERT INTO order_items (order_id, course_id, price, quantity) VALUES (?, ?, ?, ?)');
            $stmt->execute([$orderId, $item['id'], $item['price'], $item['quantity']]);
        }

        $stmt = $pdo->prepare('UPDATE orders SET payment_code = ?, payos_order_code = ? WHERE id = ?');
        $stmt->execute([order_payment_code($orderId), payos_order_code($orderId), $orderId]);

        clear_cart((int) $user['id']);
        unset($_SESSION['coupon_code']);
        $pdo->commit();

        if ($total <= 0) {
            complete_order($orderId);
            flash('success', 'Đơn hàng đã được kích hoạt do tổng thanh toán bằng 0đ.');
            redirect('my_courses.php');
        }

        $order = find_order($orderId);
        $checkoutUrl = $order ? payos_create_payment_link($order, $items) : null;

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
?>

<section class="container py-5">
    <h1 class="h2 mb-4">Thanh toán</h1>
    <div class="row g-4">
        <div class="col-lg-7">
            <form class="bg-white rounded-2 p-4 shadow-sm" method="post">
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
                <div class="mb-3">
                    <label class="form-label">Phương thức thanh toán</label>
                    <div class="payment-options">
                        <?php foreach (payment_methods() as $key => $label): ?>
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
                <div class="d-flex justify-content-between pt-3">
                    <span>Tạm tính</span>
                    <strong><?= money($subtotal) ?></strong>
                </div>
                <div class="d-flex justify-content-between pt-2">
                    <span>Giảm giá<?= $coupon ? ' (' . e($coupon['code']) . ')' : '' ?></span>
                    <strong class="text-success">-<?= money($discount) ?></strong>
                </div>
                <div class="d-flex justify-content-between h4 pt-3">
                    <span>Tổng</span>
                    <span class="price"><?= money($total) ?></span>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
