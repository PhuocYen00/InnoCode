<?php
$pageTitle = 'Sách & quà lưu niệm';
require_once __DIR__ . '/includes/header.php';

$types = [
    'pdf' => 'Sách/PDF',
    'printed_document' => 'Tài liệu giấy',
    'souvenir' => 'Quà lưu niệm',
];
$id = (int) ($_GET['id'] ?? 0);
$product = null;
if ($id) {
    $stmt = db()->prepare('SELECT * FROM physical_products WHERE id = ?');
    $stmt->execute([$id]);
    $product = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'save');
    $productId = (int) ($_POST['id'] ?? 0);

    if ($action === 'delete' && $productId) {
        $stmt = db()->prepare('DELETE FROM physical_products WHERE id = ?');
        $stmt->execute([$productId]);
        flash('success', 'Đã xóa sản phẩm.');
        redirect('admin/products.php');
    }

    $imageUrl = trim((string) ($_POST['image_url'] ?? ''));
    if (!empty($_FILES['image_file']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $extension = strtolower(pathinfo((string) $_FILES['image_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowed, true)) {
            flash('error', 'Ảnh sản phẩm chỉ hỗ trợ JPG, PNG, WEBP hoặc GIF.');
            redirect('admin/products.php' . ($productId ? '?id=' . $productId : ''));
        }
        $uploadDir = dirname(__DIR__) . '/storage/uploads/products';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = 'product_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        if (!move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadDir . '/' . $fileName)) {
            flash('error', 'Không thể tải ảnh sản phẩm lên.');
            redirect('admin/products.php' . ($productId ? '?id=' . $productId : ''));
        }
        $imageUrl = APP_URL . '/storage/uploads/products/' . $fileName;
    }

    $data = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'product_type' => (string) ($_POST['product_type'] ?? 'pdf'),
        'description' => trim((string) ($_POST['description'] ?? '')),
        'price' => (float) ($_POST['price'] ?? 0),
        'stock' => (int) ($_POST['stock'] ?? 0),
        'image_url' => $imageUrl,
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];

    if ($data['name'] === '') {
        flash('error', 'Vui lòng nhập tên sản phẩm.');
        redirect('admin/products.php' . ($productId ? '?id=' . $productId : ''));
    }

    if ($productId) {
        $data['id'] = $productId;
        $stmt = db()->prepare('UPDATE physical_products SET name = :name, product_type = :product_type, description = :description, price = :price, stock = :stock, image_url = :image_url, is_active = :is_active WHERE id = :id');
        $stmt->execute($data);
        flash('success', 'Đã cập nhật sản phẩm.');
    } else {
        $stmt = db()->prepare('INSERT INTO physical_products (name, product_type, description, price, stock, image_url, is_active) VALUES (:name, :product_type, :description, :price, :stock, :image_url, :is_active)');
        $stmt->execute($data);
        flash('success', 'Đã thêm sản phẩm.');
    }

    redirect('admin/products.php');
}

$products = all_physical_products(false);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0">Sách & quà lưu niệm</h1>
    <a class="btn btn-outline-secondary" href="<?= APP_URL ?>/admin/index.php">Dashboard</a>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <form class="bg-white rounded-2 p-4 shadow-sm" method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= (int) ($product['id'] ?? 0) ?>">
            <h2 class="h5 mb-3"><?= $product ? 'Sửa sản phẩm' : 'Thêm sản phẩm' ?></h2>
            <label class="form-label">Tên sản phẩm</label>
            <input class="form-control mb-3" name="name" value="<?= e($product['name'] ?? '') ?>" required>
            <label class="form-label">Loại</label>
            <select class="form-select mb-3" name="product_type">
                <?php foreach ($types as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= ($product['product_type'] ?? 'pdf') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <label class="form-label">Mô tả</label>
            <textarea class="form-control mb-3" name="description" rows="3"><?= e($product['description'] ?? '') ?></textarea>
            <div class="row g-2">
                <div class="col-6">
                    <label class="form-label">Giá</label>
                    <input class="form-control mb-3" type="number" min="0" name="price" value="<?= e($product['price'] ?? 0) ?>">
                </div>
                <div class="col-6">
                    <label class="form-label">Tồn kho</label>
                    <input class="form-control mb-3" type="number" min="0" name="stock" value="<?= e($product['stock'] ?? 0) ?>">
                </div>
            </div>
            <label class="form-label">Ảnh URL</label>
            <input class="form-control mb-3" name="image_url" value="<?= e($product['image_url'] ?? '') ?>">
            <label class="form-label">Hoặc chọn ảnh từ máy</label>
            <input class="form-control mb-3" type="file" name="image_file" accept="image/*">
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="is_active" id="product_active" <?= (int) ($product['is_active'] ?? 1) === 1 ? 'checked' : '' ?>>
                <label class="form-check-label" for="product_active">Đang bán</label>
            </div>
            <button class="btn btn-primary" type="submit">Lưu sản phẩm</button>
        </form>
    </div>
    <div class="col-lg-8">
        <div class="bg-white rounded-2 shadow-sm table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Sản phẩm</th><th>Loại</th><th>Giá</th><th>Tồn</th><th>Trạng thái</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($products as $item): ?>
                    <tr>
                        <td><?= e($item['name']) ?></td>
                        <td><?= e($types[$item['product_type']] ?? $item['product_type']) ?></td>
                        <td><?= money((float) $item['price']) ?></td>
                        <td><?= (int) $item['stock'] ?></td>
                        <td><?= (int) $item['is_active'] === 1 ? 'Đang bán' : 'Ẩn' ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="<?= APP_URL ?>/admin/products.php?id=<?= (int) $item['id'] ?>">Sửa</a>
                            <form class="d-inline" method="post" onsubmit="return confirm('Xóa sản phẩm này?')">
                                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" name="action" value="delete" type="submit">Xóa</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
