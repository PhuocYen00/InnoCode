<?php
require_once dirname(__DIR__) . '/core/init.php';
require_login();

$pageTitle = 'Giỏ hàng - ' . APP_NAME;
require_once dirname(__DIR__) . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? ($_POST['course_id'] ?? 0));

    if ($action === 'add') {
        $course = find_course($id);

        if ($course) {
            cart_add('course', $id);
            flash('success', 'Đã thêm khóa học vào giỏ hàng.');
        }

        redirect('cart.php');
    }

    if ($action === 'update') {
        cart_update($_POST['quantities'] ?? []);
        flash('success', 'Đã cập nhật giỏ hàng.');
        redirect('cart.php');
    }

    if (isset($_POST['remove_key'])) {
        cart_remove((string) $_POST['remove_key']);
        flash('success', 'Đã xóa khóa học khỏi giỏ hàng.');
        redirect('cart.php');
    }
}

$items = cart_items();
$total = cart_total($items);
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
                        <th>Khóa học</th>
                        <th>Đơn giá</th>
                        <th style="width: 140px;">Số lượng</th>
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
                                <input class="form-control" type="number" min="0" name="quantities[<?= e($item['key']) ?>]" value="<?= (int) $item['quantity'] ?>">
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

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-4">
                <button class="btn btn-outline-primary" type="submit">Cập nhật giỏ hàng</button>
                <div class="bg-white rounded-2 p-4 shadow-sm">
                    <div class="d-flex justify-content-between gap-5 h4 mb-3">
                        <span>Tổng cộng</span>
                        <span class="price"><?= money($total) ?></span>
                    </div>
                    <a class="btn btn-primary w-100" href="<?= url('checkout') ?>">Thanh toán</a>
                </div>
            </div>
        </form>
    <?php endif; ?>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>


