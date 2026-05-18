<?php
require_once dirname(__DIR__) . '/core/init.php';
require_login();

$user = current_user();
$courses = user_courses((int) $user['id']);
$orders = user_orders((int) $user['id']);
$pageTitle = 'Khóa học của tôi - ' . APP_NAME;
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="container py-5">
    <div class="section-title">
        <h1 class="h2 mb-0">Khóa học của tôi</h1>
        <?php if (!$user['email_verified_at']): ?>
            <a href="<?= url('verify_notice') ?>">Xác thực email</a>
        <?php endif; ?>
    </div>

    <?php if (!$courses): ?>
        <div class="bg-white rounded-2 p-4 mb-4">
            <p class="mb-3">Bạn chưa có khóa học nào đã mở khóa.</p>
            <a class="btn btn-primary" href="<?= url('courses') ?>">Chọn khóa học</a>
        </div>
    <?php else: ?>
        <div class="course-grid course-grid-4 mb-5">
            <?php foreach ($courses as $course): ?>
                <div class="card course-card">
                    <a class="course-image-link" href="<?= url('learn') ?>&id=<?= (int) $course['id'] ?>">
                        <img src="<?= e($course['image_url']) ?>" class="card-img-top" alt="<?= e($course['title']) ?>">
                        <span class="course-level">Đã mua</span>
                    </a>
                    <div class="card-body">
                        <span class="course-category"><?= e($course['level']) ?></span>
                        <h2 class="course-card-title"><a href="<?= url('learn') ?>&id=<?= (int) $course['id'] ?>"><?= e($course['title']) ?></a></h2>
                        <a class="btn btn-primary btn-sm" href="<?= url('learn') ?>&id=<?= (int) $course['id'] ?>">Vào học</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h2 class="h4 mb-3">Lịch sử đơn hàng</h2>
    <div class="bg-white rounded-2 shadow-sm table-responsive">
        <table class="table mb-0">
            <thead>
            <tr>
                <th>Mã đơn</th>
                <th>Ngày tạo</th>
                <th>Thanh toán</th>
                <th>Trạng thái</th>
                <th>Tổng</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#<?= (int) $order['id'] ?></td>
                    <td><?= e($order['created_at']) ?></td>
                    <td><?= e(payment_methods()[$order['payment_method']] ?? $order['payment_method']) ?></td>
                    <td><?= e($order['status']) ?></td>
                    <td><?= money((float) $order['total_amount']) ?></td>
                    <td class="text-end">
                        <a href="<?= url('payment_success') ?>&id=<?= (int) $order['id'] ?>">Chi tiết</a>
                        · <a href="<?= url('receipt') ?>&id=<?= (int) $order['id'] ?>">Biên lai</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$orders): ?>
                <tr><td colspan="6" class="text-muted">Chưa có đơn hàng.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>


