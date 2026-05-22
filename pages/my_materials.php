<?php
require_once dirname(__DIR__) . '/core/init.php';
require_login();

$user = current_user();
$materials = user_materials((int) $user['id']);
$pageTitle = 'Tài liệu của tôi - ' . APP_NAME;
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="container py-5">
    <div class="section-title">
        <h1 class="h2 mb-0">Tài liệu của tôi</h1>
        <a href="<?= url('products') ?>">Mua thêm tài liệu</a>
    </div>

    <?php if (!$materials): ?>
        <div class="bg-white rounded-2 p-4">
            <p class="mb-3">Bạn chưa có sách hoặc tài liệu đã thanh toán.</p>
            <a class="btn btn-primary" href="<?= url('products') ?>">Xem cửa hàng</a>
        </div>
    <?php else: ?>
        <div class="product-grid product-listing-grid">
            <?php foreach ($materials as $material): ?>
                <article class="product-card">
                    <img src="<?= e($material['image_url'] ?: 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=900&q=80') ?>" alt="<?= e($material['product_name']) ?>">
                    <div>
                        <span>Đã mua <?= e($material['paid_at'] ?? '') ?></span>
                        <h3><?= e($material['product_name']) ?></h3>
                        <p><?= e(excerpt((string) $material['description'], 120)) ?></p>
                        <a class="btn btn-primary btn-sm w-100" href="<?= url('download_product') ?>&item_id=<?= (int) $material['id'] ?>">Tải tài liệu</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>

