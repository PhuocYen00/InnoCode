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

$status = (string) ($_GET['status'] ?? '');
$q = admin_search_term();
$params = [];
$whereParts = [];

if ($status !== '') {
    $whereParts[] = 'orders.status = ?';
    $params[] = $status;
}
if ($q !== '') {
    $numericQ = preg_replace('/\D+/', '', $q);
    $whereParts[] = '(orders.id = ?
        OR orders.payment_code LIKE ?
        OR orders.payos_order_code LIKE ?
        OR orders.payos_payment_link_id LIKE ?
        OR orders.customer_name LIKE ?
        OR orders.customer_email LIKE ?
        OR orders.customer_phone LIKE ?
        OR orders.note LIKE ?
        OR orders.coupon_code LIKE ?
        OR orders.payment_method LIKE ?
        OR orders.payment_provider LIKE ?
        OR orders.status LIKE ?
        OR CAST(orders.total_amount AS CHAR) LIKE ?
        OR users.name LIKE ?
        OR EXISTS (
            SELECT 1 FROM order_items
            JOIN courses ON courses.id = order_items.course_id
            WHERE order_items.order_id = orders.id AND courses.title LIKE ?
        )
        OR EXISTS (
            SELECT 1 FROM physical_order_items
            WHERE physical_order_items.order_id = orders.id AND physical_order_items.product_name LIKE ?
        ))';
    $like = '%' . $q . '%';
    $params[] = $numericQ !== '' ? (int) $numericQ : 0;
    array_push($params, $like, $like, $like, $like, $like, $like, $like, $like, $like, $like, $like, $like, $like, $like, $like);
}

$whereSql = $whereParts ? ' WHERE ' . implode(' AND ', $whereParts) : '';

$countStmt = db()->prepare('SELECT COUNT(*)
    FROM orders
    LEFT JOIN users ON users.id = orders.user_id' . $whereSql);
$countStmt->execute($params);
$totalOrders = (int) $countStmt->fetchColumn();

$sql = 'SELECT orders.*, users.name AS user_name
    FROM orders
    LEFT JOIN users ON users.id = orders.user_id' . $whereSql . '
    ORDER BY orders.created_at DESC
    LIMIT ' . admin_per_page() . ' OFFSET ' . admin_offset();
$stmt = db()->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0">Quản lý đơn hàng</h1>
    <div class="btn-group">
        <a class="btn btn-outline-primary <?= $status === '' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/orders.php">Tất cả</a>
        <a class="btn btn-outline-primary <?= $status === 'pending' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/orders.php?status=pending">Pending</a>
        <a class="btn btn-outline-primary <?= $status === 'paid' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/orders.php?status=paid">Paid</a>
        <a class="btn btn-outline-primary <?= $status === 'cancelled' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/orders.php?status=cancelled">Cancelled</a>
    </div>
</div>

<?php admin_render_search('Tìm mã đơn, học viên, email, coupon...', ['status' => $status]); ?>

<div class="bg-white rounded-2 shadow-sm table-responsive">
    <table class="table mb-0">
        <thead>
        <tr>
            <th>Mã đơn</th>
            <th>Học viên</th>
            <th>Thanh toán</th>
            <th>Coupon</th>
            <th>Trạng thái</th>
            <th>Tổng</th>
            <th>Ngày tạo</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td>
                    #<?= (int) $order['id'] ?><br>
                    <small class="text-muted"><?= e($order['payment_code']) ?></small>
                    <?php if (!empty($order['payos_order_code'])): ?>
                        <br><small class="text-muted">PayOS: <?= e($order['payos_order_code']) ?></small>
                    <?php endif; ?>
                </td>
                <td><?= e($order['customer_name']) ?><br><small class="text-muted"><?= e($order['customer_email']) ?></small></td>
                <td><?= e(payment_methods()[$order['payment_method']] ?? $order['payment_method']) ?></td>
                <td>
                    <?= e($order['coupon_code'] ?: '-') ?>
                    <?php if ((float) $order['discount_amount'] > 0): ?>
                        <br><small class="text-success">-<?= money((float) $order['discount_amount']) ?></small>
                    <?php endif; ?>
                </td>
                <td><span class="badge bg-<?= $order['status'] === 'paid' ? 'success' : ($order['status'] === 'pending' ? 'warning text-dark' : 'secondary') ?>"><?= e($order['status']) ?></span></td>
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
            <tr><td colspan="8" class="text-muted">Chưa có đơn hàng.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php admin_render_pagination($totalOrders, 'admin/orders.php', ['status' => $status]); ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
