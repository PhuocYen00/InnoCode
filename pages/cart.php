<?php
require_once dirname(__DIR__) . '/core/init.php';
require_login();

$pageTitle = 'Giỏ hàng - ' . APP_NAME;
$wantsJson = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') || (($_POST['ajax'] ?? '') === '1');

function cart_post_response(bool $ok, string $message, ?string $redirect = null): void
{
    global $wantsJson;

    if ($wantsJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => $ok,
            'message' => $message,
            'cart_count' => cart_items_count(),
            'redirect' => $redirect,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    flash($ok ? 'success' : 'error', $message);
    redirect($redirect ?: ($_SERVER['HTTP_REFERER'] ?? 'cart.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? ($_POST['course_id'] ?? 0));

    if (isset($_POST['remove_key'])) {
        cart_remove((string) $_POST['remove_key']);
        flash('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
        redirect('cart.php');
    }

    if ($action === 'add') {
        if (find_course($id)) {
            cart_add('course', $id);
            cart_post_response(true, 'Đã thêm khóa học vào giỏ hàng.');
        }
        cart_post_response(false, 'Không tìm thấy khóa học.', 'courses.php');
    }

    if ($action === 'add_product') {
        $stmt = db()->prepare('SELECT id FROM physical_products WHERE id = ? AND is_active = 1');
        $stmt->execute([$id]);
        if ($stmt->fetch()) {
            cart_add('product', $id);
            cart_post_response(true, 'Đã thêm sản phẩm vào giỏ hàng.');
        }
        cart_post_response(false, 'Không tìm thấy sản phẩm.', 'courses.php');
    }

    if ($action === 'update') {
        cart_update($_POST['quantities'] ?? []);
        flash('success', 'Đã cập nhật giỏ hàng.');
        redirect('cart.php');
    }

    if ($action === 'apply_coupon') {
        $code = strtoupper(trim((string) ($_POST['coupon_code'] ?? '')));
        $items = cart_items();
        $subtotal = cart_total($items);
        $coupon = $code !== '' ? coupon_by_code($code) : null;

        if ($coupon && coupon_discount($coupon, $subtotal) > 0) {
            $_SESSION['coupon_code'] = $coupon['code'];
            flash('success', 'Đã áp dụng mã giảm giá ' . $coupon['code'] . '.');
        } else {
            unset($_SESSION['coupon_code']);
            flash('error', 'Mã giảm giá không hợp lệ hoặc đã hết hạn.');
        }

        redirect('cart.php');
    }

    if ($action === 'remove_coupon') {
        unset($_SESSION['coupon_code']);
        flash('success', 'Đã bỏ mã giảm giá.');
        redirect('cart.php');
    }

}

require_once dirname(__DIR__) . '/includes/header.php';

$items = cart_items();
$subtotal = cart_total($items);
$coupon = cart_coupon();
$discount = cart_discount($subtotal);
$total = max(0, $subtotal - $discount);
?>

<section class="container py-5">
    <h1 class="h2 mb-4">Giỏ hàng</h1>

    <?php if (!$items): ?>
        <div class="bg-white rounded-2 p-4">
            <p class="mb-3">Giỏ hàng của bạn đang trống.</p>
            <a class="btn btn-primary" href="<?= url('courses') ?>">Chọn khóa học</a>
        </div>
    <?php else: ?>
        <form method="post">
            <input type="hidden" name="action" value="update">
            <div class="bg-white rounded-2 shadow-sm table-responsive">
                <table class="table mb-0">
                    <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Đơn giá</th>
                        <th style="width: 150px;">Số lượng</th>
                        <th>Tạm tính</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e($item['title']) ?></div>
                                <div class="small text-muted"><?= e($item['description']) ?></div>
                            </td>
                            <td><?= money($item['price']) ?></td>
                            <td>
                                <?php if (!empty($item['is_quantity_editable'])): ?>
                                    <input class="form-control" type="number" min="0" name="quantities[<?= e($item['key']) ?>]" value="<?= (int) $item['quantity'] ?>">
                                <?php else: ?>
                                    <input type="hidden" name="quantities[<?= e($item['key']) ?>]" value="1">
                                    <span class="badge bg-primary">1 khóa học</span>
                                <?php endif; ?>
                            </td>
                            <td><?= money($item['line_total']) ?></td>
                            <td>
                                <button class="btn btn-link text-danger p-0" name="remove_key" value="<?= e($item['key']) ?>" type="submit">Xóa</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mt-4">
                <button class="btn btn-outline-primary" type="submit">Cập nhật giỏ hàng</button>
                <div class="bg-white rounded-2 p-4 shadow-sm cart-summary">
                    <div class="d-flex justify-content-between gap-5 mb-2">
                        <span>Tạm tính</span>
                        <strong><?= money($subtotal) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between gap-5 mb-2">
                        <span>Giảm giá<?= $coupon ? ' (' . e($coupon['code']) . ')' : '' ?></span>
                        <strong class="text-success">-<?= money($discount) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between gap-5 h4 mb-3">
                        <span>Tổng cộng</span>
                        <span class="price"><?= money($total) ?></span>
                    </div>
                    <a class="btn btn-primary w-100" href="<?= url('checkout') ?>">Thanh toán</a>
                </div>
            </div>
        </form>

        <form class="bg-white rounded-2 p-4 shadow-sm mt-3 coupon-form" method="post">
            <input type="hidden" name="action" value="apply_coupon">
            <label class="form-label fw-semibold">Mã giảm giá</label>
            <div class="d-flex gap-2">
                <input class="form-control" name="coupon_code" value="<?= e($coupon['code'] ?? '') ?>" placeholder="Nhập mã coupon">
                <button class="btn btn-outline-primary" type="submit">Áp dụng</button>
                <?php if ($coupon): ?>
                    <button class="btn btn-outline-danger" type="submit" name="action" value="remove_coupon">Bỏ mã</button>
                <?php endif; ?>
            </div>
        </form>
    <?php endif; ?>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
