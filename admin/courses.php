<?php
$pageTitle = 'Quản lý khóa học';
require_once __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $stmt = db()->prepare('UPDATE courses SET is_active = 0 WHERE id = ?');
        $stmt->execute([$id]);
        flash('success', 'Đã ẩn khóa học.');
        redirect('admin/courses.php');
    }
}

$courses = db()->query('SELECT courses.*, categories.name AS category_name FROM courses JOIN categories ON categories.id = courses.category_id ORDER BY courses.created_at DESC')->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0">Quản lý khóa học</h1>
    <a class="btn btn-primary" href="<?= APP_URL ?>/admin/course_form.php">Thêm khóa học</a>
</div>

<div class="bg-white rounded-2 shadow-sm table-responsive">
    <table class="table mb-0">
        <thead>
        <tr>
            <th>Tên khóa học</th>
            <th>Danh mục</th>
            <th>Giá</th>
            <th>Trạng thái</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($courses as $course): ?>
            <tr>
                <td><?= e($course['title']) ?></td>
                <td><?= e($course['category_name']) ?></td>
                <td><?= money((float) $course['price']) ?></td>
                <td><?= $course['is_active'] ? 'Đang bán' : 'Đã ẩn' ?></td>
                <td class="text-end">
                    <a class="btn btn-sm btn-outline-primary" href="<?= APP_URL ?>/admin/course_form.php?id=<?= (int) $course['id'] ?>">Sửa</a>
                    <form class="d-inline" method="post" onsubmit="return confirm('Ẩn khóa học này?')">
                        <input type="hidden" name="id" value="<?= (int) $course['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger" name="action" value="delete" type="submit">Ẩn</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


