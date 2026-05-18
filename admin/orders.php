<?php
$pageTitle = 'Quản lý đơn hàng';
require_once __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'mark_paid') {
        complete_order($id);
        flash('success', 'Đã xác nhận thanh toán và mở khóa học cho học viên.');
        redirect('admin/orders.php');
    }

    if ($action === 'cancel') {
        $stmt = db()->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND status <> 'paid'");
        $stmt->execute([$id]);
        flash('success', 'Đã hủy đơn hàng.');
        redirect('admin/orders.php');
    }
}

$orders = db()->query('SELECT orders.*, users.name AS user_name FROM orders LEFT JOIN users ON users.id = orders.user_id ORDER BY orders.created_at DESC')->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0">Quản lý đơn hàng</h1>
</div>

<div class="bg-white rounded-2 shadow-sm table-responsive">
    <table class="table mb-0">
        <thead>
        <tr>
            <th>Mã đơn</th>
            <th>Học viên</th>
            <th>Thanh toán</th>
            <th>Trạng thái</th>
            <th>Tổng</th>
            <th>Ngày tạo</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td>#<?= (int) $order['id'] ?><br><small class="text-muted"><?= e($order['payment_code']) ?></small></td>
                <td><?= e($order['customer_name']) ?><br><small class="text-muted"><?= e($order['customer_email']) ?></small></td>
                <td><?= e(payment_methods()[$order['payment_method']] ?? $order['payment_method']) ?></td>
                <td><?= e($order['status']) ?></td>
                <td><?= money((float) $order['total_amount']) ?></td>
                <td><?= e($order['created_at']) ?></td>
                <td class="text-end">
                    <a class="btn btn-sm btn-outline-primary" href="<?= APP_URL ?>/admin/order_detail.php?id=<?= (int) $order['id'] ?>">Chi tiết</a>
                    <?php if ($order['status'] === 'pending'): ?>
                        <form class="d-inline" method="post">
                            <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
                            <button class="btn btn-sm btn-success" name="action" value="mark_paid" type="submit">Xác nhận paid</button>
                            <button class="btn btn-sm btn-outline-danger" name="action" value="cancel" type="submit">Hủy</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$orders): ?>
            <tr><td colspan="7" class="text-muted">Chưa có đơn hàng.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

