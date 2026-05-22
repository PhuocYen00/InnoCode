<?php
$pageTitle = 'Quà lưu niệm';
require_once __DIR__ . '/includes/header.php';

$statuses = [
    'pending' => 'Đang chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'shipping' => 'Đã giao cho vận chuyển',
    'delivered' => 'Đã vận chuyển',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'update_status') {
        $status = (string) ($_POST['status'] ?? 'pending');
        if (!array_key_exists($status, $statuses)) {
            $status = 'pending';
        }

        $stmt = db()->prepare('SELECT gift_requests.*, users.name AS user_name, users.email, courses.title AS course_title, physical_products.name AS gift_name
            FROM gift_requests
            JOIN users ON users.id = gift_requests.user_id
            JOIN courses ON courses.id = gift_requests.course_id
            JOIN physical_products ON physical_products.id = gift_requests.product_id
            WHERE gift_requests.id = ?');
        $stmt->execute([(int) $_POST['request_id']]);
        $request = $stmt->fetch();

        if (!$request) {
            flash('error', 'Không tìm thấy yêu cầu nhận quà.');
            redirect('admin/gifts.php');
        }

        $stmt = db()->prepare('UPDATE gift_requests SET status = ? WHERE id = ?');
        $stmt->execute([$status, (int) $_POST['request_id']]);

        try {
            $body = '<p>Chào ' . e($request['user_name']) . ',</p>'
                . '<p>Trạng thái quà lưu niệm của bạn đã được cập nhật.</p>'
                . '<p><strong>Khóa học:</strong> ' . e($request['course_title']) . '</p>'
                . '<p><strong>Quà tặng:</strong> ' . e($request['gift_name']) . '</p>'
                . '<p><strong>Trạng thái mới:</strong> ' . e($statuses[$status]) . '</p>'
                . '<p>Cảm ơn bạn đã học cùng ' . e(APP_NAME) . '.</p>';
            send_app_mail((string) $request['email'], 'Cập nhật trạng thái quà tặng - ' . APP_NAME, $body, (int) $request['user_id'], true);
            flash('success', 'Đã cập nhật trạng thái quà tặng và gửi email cho học viên.');
        } catch (Throwable $exception) {
            flash('error', 'Đã cập nhật trạng thái nhưng chưa gửi được email: ' . $exception->getMessage());
        }

        redirect('admin/gifts.php');
    }

    if ($action === 'save_gift') {
        $giftId = (int) ($_POST['gift_id'] ?? 0);
        $data = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'stock' => (int) ($_POST['stock'] ?? 0),
            'image_url' => trim((string) ($_POST['image_url'] ?? '')),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        if ($data['name'] === '') {
            flash('error', 'Vui lòng nhập tên quà lưu niệm.');
            redirect('admin/gifts.php');
        }

        if ($giftId > 0) {
            $data['id'] = $giftId;
            $stmt = db()->prepare("UPDATE physical_products SET name = :name, description = :description, stock = :stock, image_url = :image_url, is_active = :is_active WHERE id = :id AND product_type = 'souvenir'");
            $stmt->execute($data);
            flash('success', 'Đã cập nhật quà lưu niệm.');
        } else {
            $stmt = db()->prepare("INSERT INTO physical_products (name, product_type, description, price, stock, image_url, is_active) VALUES (:name, 'souvenir', :description, 0, :stock, :image_url, :is_active)");
            $stmt->execute($data);
            flash('success', 'Đã thêm quà lưu niệm.');
        }

        redirect('admin/gifts.php');
    }
}

$requests = db()->query("SELECT gift_requests.*, users.name AS user_name, users.email, courses.title AS course_title, physical_products.name AS gift_name
    FROM gift_requests
    JOIN users ON users.id = gift_requests.user_id
    JOIN courses ON courses.id = gift_requests.course_id
    JOIN physical_products ON physical_products.id = gift_requests.product_id
    ORDER BY gift_requests.created_at DESC, gift_requests.id DESC")->fetchAll();
$gifts = souvenir_products(false);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0">Quà lưu niệm</h1>
    <a class="btn btn-outline-secondary" href="<?= APP_URL ?>/admin/index.php">Dashboard</a>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <form class="bg-white rounded-2 p-4 shadow-sm">
            <h2 class="h5 mb-3">Danh mục quà</h2>
            <p class="text-muted mb-0">Chọn một quà trong bảng để sửa.</p>
        </form>
        <form class="bg-white rounded-2 p-4 shadow-sm mt-3" method="post">
            <input type="hidden" name="action" value="save_gift">
            <h2 class="h5 mb-3">Thêm quà lưu niệm</h2>
            <label class="form-label">Tên quà</label>
            <input class="form-control mb-3" name="name" required>
            <label class="form-label">Mô tả</label>
            <textarea class="form-control mb-3" name="description" rows="3"></textarea>
            <label class="form-label">Tồn kho</label>
            <input class="form-control mb-3" type="number" min="0" name="stock" value="0">
            <label class="form-label">Ảnh URL</label>
            <input class="form-control mb-3" name="image_url">
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="is_active" id="gift_active" checked>
                <label class="form-check-label" for="gift_active">Đang cho chọn</label>
            </div>
            <button class="btn btn-primary" type="submit">Thêm quà</button>
        </form>
    </div>
    <div class="col-lg-8">
        <div class="bg-white rounded-2 shadow-sm table-responsive mb-4">
            <table class="table mb-0">
                <thead><tr><th>Quà</th><th>Tồn</th><th>Trạng thái</th><th>Sửa nhanh</th></tr></thead>
                <tbody>
                <?php foreach ($gifts as $gift): ?>
                    <tr>
                        <td><?= e($gift['name']) ?></td>
                        <td><?= (int) $gift['stock'] ?></td>
                        <td><?= (int) $gift['is_active'] === 1 ? 'Đang cho chọn' : 'Ẩn' ?></td>
                        <td>
                            <form class="row g-2" method="post">
                                <input type="hidden" name="action" value="save_gift">
                                <input type="hidden" name="gift_id" value="<?= (int) $gift['id'] ?>">
                                <div class="col-md-4"><input class="form-control form-control-sm" name="name" value="<?= e($gift['name']) ?>"></div>
                                <div class="col-md-2"><input class="form-control form-control-sm" type="number" min="0" name="stock" value="<?= (int) $gift['stock'] ?>"></div>
                                <input type="hidden" name="description" value="<?= e($gift['description']) ?>">
                                <input type="hidden" name="image_url" value="<?= e($gift['image_url']) ?>">
                                <div class="col-md-3">
                                    <label class="small"><input type="checkbox" name="is_active" <?= (int) $gift['is_active'] === 1 ? 'checked' : '' ?>> Đang chọn</label>
                                </div>
                                <div class="col-md-3"><button class="btn btn-sm btn-outline-primary" type="submit">Lưu</button></div>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$gifts): ?>
                    <tr><td colspan="4" class="text-muted">Chưa có quà lưu niệm.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h2 class="h4 mb-3">Yêu cầu nhận quà</h2>
        <div class="bg-white rounded-2 shadow-sm table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Học viên</th><th>Khóa học</th><th>Quà</th><th>Địa chỉ</th><th>Trạng thái</th></tr></thead>
                <tbody>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?= e($request['user_name']) ?><br><small><?= e($request['email']) ?></small></td>
                        <td><?= e($request['course_title']) ?></td>
                        <td><?= e($request['gift_name']) ?></td>
                        <td><?= e($request['receiver_name']) ?> - <?= e($request['receiver_phone']) ?><br><small><?= e($request['address']) ?></small></td>
                        <td>
                            <form method="post" class="d-flex gap-2">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                                <select class="form-select form-select-sm" name="status">
                                    <?php foreach ($statuses as $key => $label): ?>
                                        <option value="<?= e($key) ?>" <?= $request['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-sm btn-outline-primary" type="submit">Lưu</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$requests): ?>
                    <tr><td colspan="5" class="text-muted">Chưa có yêu cầu nhận quà.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
