<?php
$pageTitle = 'Chi tiết đơn hàng';
require_once __DIR__ . '/includes/header.php';

$orderId = (int) ($_GET['id'] ?? 0);
$order = find_order($orderId);

if (!$order) {
    flash('error', 'Không tìm thấy đơn hàng.');
    redirect('admin/orders.php');
}

$items = order_items($orderId);
$subtotal = array_sum(array_map(static fn (array $item): float => (float) $item['price'] * (int) $item['quantity'], $items));
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0">Đơn hàng #<?= $orderId ?></h1>
    <a class="btn btn-outline-secondary" href="<?= APP_URL ?>/admin/orders.php">Quay lại</a>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="bg-white rounded-2 p-4 shadow-sm mb-4">
            <h2 class="h5">Thông tin khách hàng</h2>
            <p><?= e($order['customer_name']) ?><br><?= e($order['customer_email']) ?><br><?= e($order['customer_phone']) ?></p>
            <p class="mb-0">
                Trạng thái: <strong><?= e($order['status']) ?></strong><br>
                Thanh toán: <?= e(payment_methods()[$order['payment_method']] ?? $order['payment_method']) ?><br>
                Mã: <?= e($order['payment_code']) ?><br>
                Paid at: <?= e($order['paid_at'] ?: '-') ?>
            </p>
        </div>
        <div class="bg-white rounded-2 p-4 shadow-sm">
            <h2 class="h5">PayOS & giảm giá</h2>
            <p class="mb-0">
                Provider: <?= e($order['payment_provider'] ?? '-') ?><br>
                Order code: <?= e($order['payos_order_code'] ?? '-') ?><br>
                Payment link ID: <?= e($order['payos_payment_link_id'] ?? '-') ?><br>
                Coupon: <?= e($order['coupon_code'] ?: '-') ?><br>
                Giảm giá: <?= money((float) ($order['discount_amount'] ?? 0)) ?>
            </p>
            <?php if (!empty($order['payos_checkout_url'])): ?>
                <a class="btn btn-sm btn-outline-primary mt-3" href="<?= e($order['payos_checkout_url']) ?>" target="_blank">Mở link PayOS</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="bg-white rounded-2 shadow-sm table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Khóa học</th><th>Số lượng</th><th>Giá</th><th>Tổng</th></tr></thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= e($item['title']) ?></td>
                        <td><?= (int) $item['quantity'] ?></td>
                        <td><?= money((float) $item['price']) ?></td>
                        <td><?= money((float) $item['price'] * (int) $item['quantity']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                <tr><th colspan="3" class="text-end">Tạm tính</th><th><?= money($subtotal) ?></th></tr>
                <tr><th colspan="3" class="text-end">Giảm giá</th><th class="text-success">-<?= money((float) ($order['discount_amount'] ?? 0)) ?></th></tr>
                <tr><th colspan="3" class="text-end">Tổng thanh toán</th><th><?= money((float) $order['total_amount']) ?></th></tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
