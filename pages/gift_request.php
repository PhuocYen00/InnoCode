<?php
require_once dirname(__DIR__) . '/core/init.php';
require_login();

$courseId = (int) ($_GET['course_id'] ?? $_POST['course_id'] ?? 0);
$course = find_course($courseId, false);
$user = current_user();

if (!$course || !has_purchased_course($courseId) || !course_is_completed($courseId, (int) $user['id'])) {
    flash('error', 'Bạn cần hoàn thành khóa học trước khi chọn quà lưu niệm.');
    redirect('my_courses.php');
}

if (gift_request_for((int) $user['id'], $courseId)) {
    flash('success', 'Hệ thống đã ghi nhận quà lưu niệm của khóa học này.');
    redirect('my_courses.php');
}

$souvenirs = souvenir_products();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int) ($_POST['product_id'] ?? 0);
    $receiverName = trim((string) ($_POST['receiver_name'] ?? ''));
    $receiverPhone = trim((string) ($_POST['receiver_phone'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));
    $note = trim((string) ($_POST['note'] ?? ''));

    $stmt = db()->prepare("SELECT id FROM physical_products WHERE id = ? AND product_type = 'souvenir' AND is_active = 1");
    $stmt->execute([$productId]);

    if (!$stmt->fetch() || $receiverName === '' || $receiverPhone === '' || $address === '') {
        flash('error', 'Vui lòng chọn quà và nhập đầy đủ thông tin nhận quà.');
        redirect('gift_request.php?course_id=' . $courseId);
    }

    $stmt = db()->prepare('INSERT INTO gift_requests (user_id, course_id, product_id, receiver_name, receiver_phone, address, note) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([(int) $user['id'], $courseId, $productId, $receiverName, $receiverPhone, $address, $note]);
    flash('success', 'Hệ thống đã ghi nhận quà lưu niệm bạn chọn. Admin sẽ xử lý và cập nhật trạng thái.');
    redirect('my_courses.php');
}

$pageTitle = 'Chọn quà hoàn thành khóa học - ' . APP_NAME;
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="container py-5">
    <div class="section-title">
        <h1 class="h2 mb-0">Chọn quà hoàn thành khóa học</h1>
        <a href="<?= url('my_courses') ?>">Quay lại</a>
    </div>

    <form class="bg-white rounded-2 p-4 shadow-sm" method="post">
        <input type="hidden" name="course_id" value="<?= $courseId ?>">
        <p class="text-muted">Khóa học: <?= e($course['title']) ?></p>
        <div class="row g-3 mb-3">
            <?php foreach ($souvenirs as $souvenir): ?>
                <div class="col-md-4">
                    <label class="gift-choice">
                        <input type="radio" name="product_id" value="<?= (int) $souvenir['id'] ?>" required>
                        <img src="<?= e($souvenir['image_url']) ?>" alt="<?= e($souvenir['name']) ?>">
                        <strong><?= e($souvenir['name']) ?></strong>
                        <span><?= e(excerpt((string) $souvenir['description'], 80)) ?></span>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (!$souvenirs): ?>
            <p class="empty-note">Hiện chưa có quà lưu niệm để chọn.</p>
        <?php endif; ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Người nhận</label>
                <input class="form-control" name="receiver_name" value="<?= e($user['name']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Số điện thoại</label>
                <input class="form-control" name="receiver_phone" value="<?= e($user['phone']) ?>" required>
            </div>
            <div class="col-12">
                <label class="form-label">Địa chỉ nhận quà</label>
                <textarea class="form-control" name="address" rows="3" required></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Ghi chú</label>
                <textarea class="form-control" name="note" rows="2"></textarea>
            </div>
        </div>
        <button class="btn btn-primary mt-3" type="submit" <?= $souvenirs ? '' : 'disabled' ?>>Ghi nhận quà</button>
    </form>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>

