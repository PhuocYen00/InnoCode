<?php
$pageTitle = 'Danh mục khóa học';
require_once __DIR__ . '/includes/header.php';

function category_slug_from_name(string $name, int $id = 0): string
{
    $map = [
        'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a',
        'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a',
        'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
        'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e',
        'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
        'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o',
        'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o',
        'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
        'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u',
        'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
        'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
        'đ' => 'd',
    ];
    $slug = strtolower(strtr($name, $map));
    $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', $slug), '-');
    return $slug !== '' ? $slug : 'danh-muc-' . ($id ?: time());
}

$id = (int) ($_GET['id'] ?? 0);
$category = null;
if ($id) {
    $editing = db()->prepare('SELECT * FROM categories WHERE id = ?');
    $editing->execute([$id]);
    $category = $editing->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'save');
    $categoryId = (int) ($_POST['id'] ?? 0);

    if ($action === 'delete' && $categoryId) {
        $stmt = db()->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->execute([$categoryId]);
        flash('success', 'Đã xóa danh mục.');
        redirect('admin/categories.php');
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    if ($name === '') {
        flash('error', 'Vui lòng nhập tên danh mục.');
        redirect('admin/categories.php' . ($categoryId ? '?id=' . $categoryId : ''));
    }

    $slug = category_slug_from_name($name, $categoryId);
    $suffix = 2;
    while (true) {
        $stmt = db()->prepare('SELECT id FROM categories WHERE slug = ? AND id <> ? LIMIT 1');
        $stmt->execute([$slug, $categoryId]);
        if (!$stmt->fetch()) {
            break;
        }
        $slug = category_slug_from_name($name, $categoryId) . '-' . $suffix++;
    }

    if ($categoryId) {
        $stmt = db()->prepare('UPDATE categories SET name = ?, slug = ? WHERE id = ?');
        $stmt->execute([$name, $slug, $categoryId]);
        flash('success', 'Đã cập nhật danh mục.');
    } else {
        $stmt = db()->prepare('INSERT INTO categories (name, slug) VALUES (?, ?)');
        $stmt->execute([$name, $slug]);
        flash('success', 'Đã thêm danh mục.');
    }

    redirect('admin/categories.php');
}

$categories = db()->query('SELECT categories.*, COUNT(courses.id) AS course_count
    FROM categories
    LEFT JOIN courses ON courses.category_id = categories.id
    GROUP BY categories.id
    ORDER BY categories.name')->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0">Danh mục khóa học</h1>
    <a class="btn btn-outline-secondary" href="<?= APP_URL ?>/admin/courses.php">Quay lại khóa học</a>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <form class="bg-white rounded-2 p-4 shadow-sm" method="post">
            <input type="hidden" name="id" value="<?= (int) ($category['id'] ?? 0) ?>">
            <h2 class="h5 mb-3"><?= $category ? 'Sửa danh mục' : 'Thêm danh mục' ?></h2>
            <label class="form-label">Tên danh mục</label>
            <input class="form-control mb-3" name="name" value="<?= e($category['name'] ?? '') ?>" required>
            <button class="btn btn-primary" type="submit">Lưu danh mục</button>
            <?php if ($category): ?>
                <a class="btn btn-outline-secondary" href="<?= APP_URL ?>/admin/categories.php">Hủy sửa</a>
            <?php endif; ?>
        </form>
    </div>
    <div class="col-lg-8">
        <div class="bg-white rounded-2 shadow-sm table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Tên danh mục</th><th>Khóa học</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($categories as $item): ?>
                    <tr>
                        <td><?= e($item['name']) ?></td>
                        <td><?= (int) $item['course_count'] ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="<?= APP_URL ?>/admin/categories.php?id=<?= (int) $item['id'] ?>">Sửa</a>
                            <form class="d-inline" method="post" onsubmit="return confirm('Xóa danh mục này?')">
                                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" name="action" value="delete" type="submit" <?= (int) $item['course_count'] > 0 ? 'disabled' : '' ?>>Xóa</button>
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
