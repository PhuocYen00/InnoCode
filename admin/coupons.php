<?php
$pageTitle = 'Quản lý coupon';
require_once __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'save');
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'delete') {
        $stmt = db()->prepare('UPDATE coupons SET is_active = 0 WHERE id = ?');
        $stmt->execute([$id]);
        flash('success', 'Đã tắt coupon.');
        redirect('admin/coupons.php');
    }

    $data = [
        'code' => strtoupper(trim((string) ($_POST['code'] ?? ''))),
        'discount_type' => in_array($_POST['discount_type'] ?? 'percent', ['percent', 'fixed'], true) ? $_POST['discount_type'] : 'percent',
        'discount_value' => (float) ($_POST['discount_value'] ?? 0),
        'usage_limit' => ($_POST['usage_limit'] ?? '') === '' ? null : (int) $_POST['usage_limit'],
        'starts_at' => ($_POST['starts_at'] ?? '') === '' ? null : $_POST['starts_at'],
        'expires_at' => ($_POST['expires_at'] ?? '') === '' ? null : $_POST['expires_at'],
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];

    if ($data['code'] === '' || $data['discount_value'] <= 0) {
        flash('error', 'Vui lòng nhập mã coupon và giá trị giảm hợp lệ.');
        redirect('admin/coupons.php');
    }

    if ($id > 0) {
        $stmt = db()->prepare('UPDATE coupons SET code = :code, discount_type = :discount_type, discount_value = :discount_value, usage_limit = :usage_limit, starts_at = :starts_at, expires_at = :expires_at, is_active = :is_active WHERE id = :id');
        $data['id'] = $id;
        $stmt->execute($data);
        flash('success', 'Đã cập nhật coupon.');
    } else {
        $stmt = db()->prepare('INSERT INTO coupons (code, discount_type, discount_value, usage_limit, starts_at, expires_at, is_active) VALUES (:code, :discount_type, :discount_value, :usage_limit, :starts_at, :expires_at, :is_active)');
        $stmt->execute($data);
        flash('success', 'Đã tạo coupon.');
    }

    redirect('admin/coupons.php');
}

$edit = null;
if (isset($_GET['id'])) {
    $stmt = db()->prepare('SELECT * FROM coupons WHERE id = ?');
    $stmt->execute([(int) $_GET['id']]);
    $edit = $stmt->fetch() ?: null;
}

$q = admin_search_term();
$params = [];
$where = '';
if ($q !== '') {
    $where = ' WHERE code LIKE ? OR discount_type LIKE ?';
    $like = '%' . $q . '%';
    $params = [$like, $like];
}

$countStmt = db()->prepare('SELECT COUNT(*) FROM coupons' . $where);
$countStmt->execute($params);
$totalCoupons = (int) $countStmt->fetchColumn();

$stmt = db()->prepare('SELECT * FROM coupons' . $where . '
    ORDER BY created_at DESC
    LIMIT ' . admin_per_page() . ' OFFSET ' . admin_offset());
$stmt->execute($params);
$coupons = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0">Quản lý coupon</h1>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <form class="bg-white rounded-2 p-4 shadow-sm" method="post">
            <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
            <h2 class="h5 mb-3"><?= $edit ? 'Sửa coupon' : 'Thêm coupon' ?></h2>
            <div class="mb-3">
                <label class="form-label">Mã</label>
                <input class="form-control" name="code" value="<?= e($edit['code'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Loại giảm</label>
                <select class="form-select" name="discount_type">
                    <option value="percent" <?= ($edit['discount_type'] ?? '') === 'percent' ? 'selected' : '' ?>>Phần trăm</option>
                    <option value="fixed" <?= ($edit['discount_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Số tiền</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Giá trị</label>
                <input class="form-control" type="number" min="1" name="discount_value" value="<?= e($edit['discount_value'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Giới hạn lượt dùng</label>
                <input class="form-control" type="number" min="0" name="usage_limit" value="<?= e($edit['usage_limit'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Bắt đầu</label>
                <input class="form-control" type="datetime-local" name="starts_at" value="<?= e(isset($edit['starts_at']) && $edit['starts_at'] ? date('Y-m-d\TH:i', strtotime((string) $edit['starts_at'])) : '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Hết hạn</label>
                <input class="form-control" type="datetime-local" name="expires_at" value="<?= e(isset($edit['expires_at']) && $edit['expires_at'] ? date('Y-m-d\TH:i', strtotime((string) $edit['expires_at'])) : '') ?>">
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="is_active" id="coupon_active" <?= (int) ($edit['is_active'] ?? 1) === 1 ? 'checked' : '' ?>>
                <label class="form-check-label" for="coupon_active">Đang bật</label>
            </div>
            <button class="btn btn-primary" type="submit">Lưu coupon</button>
            <?php if ($edit): ?>
                <a class="btn btn-outline-secondary" href="<?= APP_URL ?>/admin/coupons.php">Hủy sửa</a>
            <?php endif; ?>
        </form>
    </div>
    <div class="col-lg-8">
        <?php admin_render_search('Tìm theo mã coupon hoặc loại giảm...'); ?>
        <div class="bg-white rounded-2 shadow-sm table-responsive">
            <table class="table mb-0">
                <thead>
                <tr>
                    <th>Mã</th>
                    <th>Giảm</th>
                    <th>Đã dùng</th>
                    <th>Hạn dùng</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($coupons as $coupon): ?>
                    <tr>
                        <td><strong><?= e($coupon['code']) ?></strong></td>
                        <td><?= $coupon['discount_type'] === 'percent' ? e((float) $coupon['discount_value']) . '%' : money((float) $coupon['discount_value']) ?></td>
                        <td><?= (int) $coupon['used_count'] ?><?= $coupon['usage_limit'] !== null ? '/' . (int) $coupon['usage_limit'] : '' ?></td>
                        <td><?= e($coupon['expires_at'] ?: 'Không giới hạn') ?></td>
                        <td><?= $coupon['is_active'] ? 'Đang bật' : 'Đã tắt' ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="<?= APP_URL ?>/admin/coupons.php?id=<?= (int) $coupon['id'] ?>">Sửa</a>
                            <?php if ($coupon['is_active']): ?>
                                <form class="d-inline" method="post">
                                    <input type="hidden" name="id" value="<?= (int) $coupon['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" name="action" value="delete" type="submit">Tắt</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$coupons): ?>
                    <tr><td colspan="6" class="text-muted">Không tìm thấy coupon phù hợp.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php admin_render_pagination($totalCoupons, 'admin/coupons.php', ['id' => null]); ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
