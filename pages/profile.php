<?php
require_once dirname(__DIR__) . '/core/init.php';
require_login();

$user = current_user();
$courses = user_courses((int) $user['id']);
$orders = user_orders((int) $user['id']);
$paidOrders = array_filter($orders, static fn (array $order): bool => $order['status'] === 'paid');
$totalSpent = array_sum(array_map(static fn (array $order): float => (float) $order['total_amount'], $paidOrders));
$pageTitle = 'Trang cá nhân - ' . APP_NAME;
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="container py-5">
    <div class="profile-hero">
        <div class="profile-avatar"><?= e(mb_strtoupper(mb_substr((string) $user['name'], 0, 1))) ?></div>
        <div>
            <span class="hero-kicker">Hồ sơ học viên</span>
            <h1><?= e($user['name']) ?></h1>
            <p><?= e($user['email']) ?> · <?= $user['email_verified_at'] ? 'Email đã xác thực' : 'Chưa xác thực email' ?></p>
        </div>
        <?php if (!$user['email_verified_at']): ?>
            <a class="btn btn-light text-primary fw-semibold" href="<?= url('verify_notice') ?>">Xác thực email</a>
        <?php endif; ?>
    </div>

    <div class="profile-stats">
        <div><span>Khóa học sở hữu</span><strong><?= count($courses) ?></strong></div>
        <div><span>Đơn đã thanh toán</span><strong><?= count($paidOrders) ?></strong></div>
        <div><span>Tổng chi tiêu</span><strong><?= money($totalSpent) ?></strong></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="bg-white rounded-2 p-4 shadow-sm h-100">
                <div class="section-title">
                    <h2>Khóa học gần đây</h2>
                    <a href="<?= url('my_courses') ?>">Xem tất cả</a>
                </div>
                <?php foreach (array_slice($courses, 0, 4) as $course): ?>
                    <a class="profile-course-row" href="<?= url('learn') ?>&id=<?= (int) $course['id'] ?>">
                        <img src="<?= e($course['image_url']) ?>" alt="<?= e($course['title']) ?>">
                        <span><?= e($course['title']) ?><small><?= e($course['level']) ?> · <?= (int) $course['duration_hours'] ?>h</small></span>
                    </a>
                <?php endforeach; ?>
                <?php if (!$courses): ?>
                    <p class="text-muted mb-0">Bạn chưa sở hữu khóa học nào.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="bg-white rounded-2 p-4 shadow-sm h-100">
                <div class="section-title">
                    <h2>Đơn hàng mới</h2>
                    <a href="<?= url('my_courses') ?>">Lịch sử</a>
                </div>
                <?php foreach (array_slice($orders, 0, 5) as $order): ?>
                    <div class="profile-order-row">
                        <span>#<?= (int) $order['id'] ?> · <?= e($order['status']) ?><small><?= e($order['created_at']) ?></small></span>
                        <strong><?= money((float) $order['total_amount']) ?></strong>
                    </div>
                <?php endforeach; ?>
                <?php if (!$orders): ?>
                    <p class="text-muted mb-0">Chưa có đơn hàng.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>

