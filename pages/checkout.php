<?php
require_once dirname(__DIR__) . '/core/init.php';
require_login();
require_verified_email();

$pageTitle = 'Thanh toán - ' . APP_NAME;
require_once dirname(__DIR__) . '/includes/header.php';

$user = current_user();
$items = cart_items();

if (!$items) {
    flash('error', 'Giỏ hàng đang trống.');
    redirect('courses.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = trim($_POST['customer_name'] ?? '');
    $customerEmail = trim($_POST['customer_email'] ?? '');
    $customerPhone = trim($_POST['customer_phone'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'bank';
    $note = trim($_POST['note'] ?? '');

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
        $total = cart_total($items);
        $stmt = $pdo->prepare('INSERT INTO orders (user_id, customer_name, customer_email, customer_phone, note, total_amount, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([(int) $user['id'], $customerName, $customerEmail, $customerPhone, $note, $total, $paymentMethod, 'pending']);
        $orderId = (int) $pdo->lastInsertId();

        foreach ($items as $item) {
            $stmt = $pdo->prepare('INSERT INTO order_items (order_id, course_id, price, quantity) VALUES (?, ?, ?, ?)');
            $stmt->execute([$orderId, $item['id'], $item['price'], $item['quantity']]);
        }

        $stmt = $pdo->prepare('UPDATE orders SET payment_code = ? WHERE id = ?');
        $stmt->execute([order_payment_code($orderId), $orderId]);

        clear_cart((int) $user['id']);
        $pdo->commit();

        $order = find_order($orderId);

        if ($paymentMethod === 'vnpay' && ($url = vnpay_payment_url($order))) {
            header('Location: ' . $url);
            exit;
        }

        if ($paymentMethod === 'momo' && ($url = momo_payment_url($order))) {
            header('Location: ' . $url);
            exit;
        }

        redirect('payment_success.php?id=' . $orderId);
    } catch (Throwable $exception) {
        $pdo->rollBack();
        flash('error', 'Không thể tạo đơn hàng. Vui lòng thử lại.');
        redirect('checkout.php');
    }
}
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
                    <p class="text-muted small mt-2 mb-0">VNPay/MoMo sẽ chuyển sang cổng thật khi bạn điền mã merchant trong <code>core/config.php</code>; nếu chưa cấu hình, hệ thống hiển thị QR thanh toán để test đồ án.</p>
                </div>
                <div class="mb-3">
                    <label class="form-label">Ghi chú</label>
                    <textarea class="form-control" name="note" rows="4"></textarea>
                </div>
                <button class="btn btn-primary btn-lg" type="submit">Tạo đơn thanh toán</button>
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
                <div class="d-flex justify-content-between h4 pt-3">
                    <span>Tổng</span>
                    <span class="price"><?= money(cart_total($items)) ?></span>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>


