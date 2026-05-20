<?php
require_once dirname(__DIR__) . '/core/init.php';

$pageTitle = 'Sách, giáo trình & quà lưu niệm - ' . APP_NAME;
$q = trim((string) ($_GET['q'] ?? ''));
$type = (string) ($_GET['type'] ?? '');
$types = [
    'pdf' => 'Sách/PDF',
    'printed_document' => 'Giáo trình giấy',
    'souvenir' => 'Quà lưu niệm',
];

if (!array_key_exists($type, $types)) {
    $type = '';
}

$params = [];
$where = ' WHERE is_active = 1';

if ($q !== '') {
    $where .= ' AND (name LIKE :q OR description LIKE :q)';
    $params['q'] = '%' . $q . '%';
}

if ($type !== '') {
    $where .= ' AND product_type = :type';
    $params['type'] = $type;
}

$stmt = db()->prepare('SELECT * FROM physical_products' . $where . ' ORDER BY created_at DESC, id DESC');
$stmt->execute($params);
$products = $stmt->fetchAll();

require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="container py-5">
    <div class="section-heading">
        <div>
            <span class="section-kicker">Cửa hàng học viên</span>
            <h1>Sách, giáo trình & quà lưu niệm</h1>
            <p>Tài liệu học tập, PDF, giáo trình in và vật phẩm InnoCode.</p>
        </div>
    </div>

    <form class="catalog-filter bg-white rounded-2 p-3 shadow-sm mb-4" method="get">
        <input type="hidden" name="page" value="products">
        <div class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Tìm sản phẩm</label>
                <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Tên sách, giáo trình, quà lưu niệm...">
            </div>
            <div class="col-md-4">
                <label class="form-label">Loại</label>
                <select class="form-select" name="type">
                    <option value="">Tất cả</option>
                    <?php foreach ($types as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $type === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-primary" type="submit">Lọc</button>
            </div>
        </div>
    </form>

    <?php if (!$products): ?>
        <p class="empty-note">Chưa có sản phẩm phù hợp.</p>
    <?php else: ?>
        <div class="product-grid product-listing-grid">
            <?php foreach ($products as $product): ?>
                <?php $inStock = (int) $product['stock'] > 0; ?>
                <article class="product-card">
                    <img src="<?= e($product['image_url'] ?: 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=900&q=80') ?>" alt="<?= e($product['name']) ?>">
                    <div>
                        <span><?= e($types[$product['product_type']] ?? product_type_label((string) $product['product_type'])) ?></span>
                        <h3><?= e($product['name']) ?></h3>
                        <p><?= e(excerpt((string) $product['description'], 120)) ?></p>
                        <small class="<?= $inStock ? 'text-success' : 'text-danger' ?>">
                            <?= $inStock ? 'Còn ' . (int) $product['stock'] . ' sản phẩm' : 'Tạm hết hàng' ?>
                        </small>
                        <strong><?= money((float) $product['price']) ?></strong>
                        <form method="post" action="<?= url('cart') ?>" class="mt-3 js-add-cart">
                            <input type="hidden" name="action" value="add_product">
                            <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                            <button class="btn btn-primary btn-sm w-100" type="submit" <?= $inStock ? '' : 'disabled' ?>>Thêm vào giỏ hàng</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
