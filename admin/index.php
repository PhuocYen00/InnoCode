<?php
$pageTitle = 'Bảng điều khiển';
require_once __DIR__ . '/includes/header.php';

$stats = [
    'courses' => (int) db()->query('SELECT COUNT(*) FROM courses')->fetchColumn(),
    'orders' => (int) db()->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'revenue' => (float) db()->query('SELECT COALESCE(SUM(total_amount), 0) FROM orders')->fetchColumn(),
];
$latestOrders = db()->query('SELECT * FROM orders ORDER BY created_at DESC LIMIT 5')->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0">Bảng điều khiển</h1>
    <a class="btn btn-primary" href="<?= APP_URL ?>/admin/course_form.php">Thêm khóa học</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="bg-white rounded-2 p-4 shadow-sm"><span class="text-muted">Khóa học</span><div class="h2"><?= $stats['courses'] ?></div></div></div>
    <div class="col-md-4"><div class="bg-white rounded-2 p-4 shadow-sm"><span class="text-muted">Đơn hàng</span><div class="h2"><?= $stats['orders'] ?></div></div></div>
    <div class="col-md-4"><div class="bg-white rounded-2 p-4 shadow-sm"><span class="text-muted">Doanh thu</span><div class="h2"><?= money($stats['revenue']) ?></div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="bg-white rounded-2 p-4 shadow-sm">
            <div class="d-flex justify-content-between mb-3">
                <h2 class="h5 mb-0">Khóa học</h2>
                <a href="<?= APP_URL ?>/admin/courses.php">Quản lý</a>
            </div>
            <p class="text-muted mb-0">Thêm, sửa, ẩn/hiện các khóa học đang bán trên website.</p>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="bg-white rounded-2 p-4 shadow-sm">
            <h2 class="h5 mb-3">Đơn mới nhất</h2>
            <?php if (!$latestOrders): ?>
                <p class="text-muted mb-0">Chưa có đơn hàng.</p>
            <?php endif; ?>
            <?php foreach ($latestOrders as $order): ?>
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span>#<?= (int) $order['id'] ?> · <?= e($order['customer_name']) ?></span>
                    <strong><?= money((float) $order['total_amount']) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


