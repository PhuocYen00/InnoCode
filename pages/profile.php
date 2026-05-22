<?php
require_once dirname(__DIR__) . '/core/init.php';
require_login();

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $avatarUrl = (string) ($user['avatar_url'] ?? '');

    if (!empty($_FILES['avatar_file']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $extension = strtolower(pathinfo((string) $_FILES['avatar_file']['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowed, true)) {
            flash('error', 'Ảnh đại diện chỉ hỗ trợ JPG, PNG, WEBP hoặc GIF.');
            redirect('profile.php');
        }

        $uploadDir = dirname(__DIR__) . '/storage/uploads/avatars';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = 'avatar_' . (int) $user['id'] . '_' . date('YmdHis') . '.' . $extension;
        if (!move_uploaded_file($_FILES['avatar_file']['tmp_name'], $uploadDir . '/' . $fileName)) {
            flash('error', 'Không thể tải ảnh đại diện lên.');
            redirect('profile.php');
        }

        $avatarUrl = APP_URL . '/storage/uploads/avatars/' . $fileName;
    }

    if ($name === '') {
        flash('error', 'Vui lòng nhập họ tên.');
        redirect('profile.php');
    }

    $stmt = db()->prepare('UPDATE users SET name = ?, phone = ?, avatar_url = ? WHERE id = ?');
    $stmt->execute([$name, $phone, $avatarUrl ?: null, (int) $user['id']]);
    flash('success', 'Đã cập nhật trang cá nhân.');
    redirect('profile.php');
}

$user = current_user();
$courses = user_courses((int) $user['id']);
$orders = user_orders((int) $user['id']);
$paidOrders = [];
$totalSpent = 0;
foreach ($orders as $order) {
    if ($order['status'] === 'paid') {
        $paidOrders[] = $order;
        $totalSpent += (float) $order['total_amount'];
    }
}
$pageTitle = 'Trang cá nhân - ' . APP_NAME;
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="container py-5">
    <div class="profile-hero">
        <?php if (!empty($user['avatar_url'])): ?>
            <img class="profile-avatar-img" src="<?= e($user['avatar_url']) ?>" alt="<?= e($user['name']) ?>">
        <?php else: ?>
            <div class="profile-avatar"><?= e(mb_strtoupper(mb_substr((string) $user['name'], 0, 1))) ?></div>
        <?php endif; ?>
        <div>
            <span class="hero-kicker">Hồ sơ học viên</span>
            <h1><?= e($user['name']) ?></h1>
            <p><?= e($user['email']) ?> · <?= $user['email_verified_at'] ? 'Email đã xác thực' : 'Chưa xác thực email' ?></p>
        </div>
        <div class="profile-hero-actions">
            <?php if (!$user['email_verified_at']): ?>
                <a class="btn btn-light text-primary fw-semibold" href="<?= url('verify_notice') ?>">Xác thực email</a>
            <?php endif; ?>
            <a class="btn btn-outline-light" href="<?= url('logout') ?>">Đăng xuất</a>
        </div>
    </div>

    <div class="profile-stats">
        <div><span>Khóa học sở hữu</span><strong><?= count($courses) ?></strong></div>
        <div><span>Đơn đã thanh toán</span><strong><?= count($paidOrders) ?></strong></div>
        <div><span>Tổng chi tiêu</span><strong><?= money($totalSpent) ?></strong></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <form class="bg-white rounded-2 p-4 shadow-sm h-100" method="post" enctype="multipart/form-data">
                <h2 class="h5 mb-3">Cập nhật thông tin</h2>
                <label class="form-label">Họ tên</label>
                <input class="form-control mb-3" name="name" value="<?= e($user['name']) ?>" required>
                <label class="form-label">Số điện thoại</label>
                <input class="form-control mb-3" name="phone" value="<?= e($user['phone'] ?? '') ?>">
                <label class="form-label">Ảnh học viên</label>
                <input class="form-control mb-3" type="file" name="avatar_file" accept="image/*">
                <button class="btn btn-primary" type="submit">Lưu thông tin</button>
            </form>
        </div>
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
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>

