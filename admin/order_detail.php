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
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0">Đơn hàng #<?= $orderId ?></h1>
    <a class="btn btn-outline-secondary" href="<?= APP_URL ?>/admin/orders.php">Quay lại</a>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="bg-white rounded-2 p-4 shadow-sm">
            <h2 class="h5">Thông tin khách hàng</h2>
            <p><?= e($order['customer_name']) ?><br><?= e($order['customer_email']) ?><br><?= e($order['customer_phone']) ?></p>
            <p class="mb-0">Trạng thái: <strong><?= e($order['status']) ?></strong><br>Thanh toán: <?= e(payment_methods()[$order['payment_method']] ?? $order['payment_method']) ?><br>Mã: <?= e($order['payment_code']) ?></p>
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
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

