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
$courseItems = [];
$productItems = [];
foreach ($items as $item) {
    if (($item['type'] ?? '') === 'course') {
        $courseItems[] = $item;
    } elseif (($item['type'] ?? '') === 'product') {
        $productItems[] = $item;
    }
}
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
    $paymentMethod = (string) ($_POST['payment_method'] ?? 'bank');
    $note = trim((string) ($_POST['note'] ?? ''));
    $payableItems = $items;
    $payableSubtotal = cart_total($payableItems);
    $discount = cart_discount($payableSubtotal);
    $total = max(0, $payableSubtotal - $discount);

    if ($customerName === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL) || $customerPhone === '') {
        flash('error', 'Vui lòng nhập đầy đủ họ tên, email và số điện thoại.');
        redirect('checkout.php');
    }

    $allowedPaymentMethods = ['bank', 'vnpay', 'momo'];
    if (!in_array($paymentMethod, $allowedPaymentMethods, true)) {
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
                $stmt->execute([$orderId, $item['id'], $item['title'], $item['price'], $item['quantity'], 'paid_online']);
            }
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
            flash('success', 'Đã tạo đơn hàng.');
            redirect('payment_success.php?id=' . $orderId);
        }

        if ($paymentMethod === 'bank') {
            flash('success', 'Đã tạo đơn hàng. Vui lòng chuyển khoản theo thông tin VietQR để admin xác nhận và mở khóa nội dung.');
            redirect('payment_success.php?id=' . $orderId);
        }

        $order = find_order($orderId);
        $checkoutUrl = $order ? payos_create_payment_link($order, $payableItems) : null;

        if ($checkoutUrl) {
            header('Location: ' . $checkoutUrl);
            exit;
        }

        flash('success', 'Đã tạo đơn hàng. Hiện chưa tạo được link PayOS, bạn có thể chuyển khoản VietQR để admin xác nhận.');
        redirect('payment_success.php?id=' . $orderId);
    } catch (Throwable $exception) {
        $pdo->rollBack();
        flash('error', 'Không thể tạo đơn hàng. Vui lòng thử lại.');
        redirect('checkout.php');
    }
}

require_once dirname(__DIR__) . '/includes/header.php';
$paymentOptions = $hasPhysicalItems
    ? ['bank' => payment_methods()['bank'], 'vnpay' => payment_methods()['vnpay'], 'momo' => payment_methods()['momo']]
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
                        <span>Sách/tài liệu trong giỏ</span>
                        <strong><?= money($productSubtotal) ?></strong>
                    </div>
                    <p class="small text-muted mt-2 mb-0">Sau khi thanh toán, sách và tài liệu sẽ nằm trong mục Tài liệu của tôi để xem và tải về.</p>
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

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
