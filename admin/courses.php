<?php
$pageTitle = 'Quản lý khóa học';
require_once __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $listPath = admin_relative_url('admin/courses.php');

    if ($action === 'hide') {
        $stmt = db()->prepare('UPDATE courses SET is_active = 0 WHERE id = ?');
        $stmt->execute([$id]);
        flash('success', 'Đã ẩn khóa học.');
        redirect($listPath);
    }

    if ($action === 'show') {
        $stmt = db()->prepare('UPDATE courses SET is_active = 1 WHERE id = ?');
        $stmt->execute([$id]);
        flash('success', 'Đã mở bán lại khóa học.');
        redirect($listPath);
    }

    if ($action === 'delete') {
        $stmt = db()->prepare('SELECT COUNT(*) FROM order_items WHERE course_id = ?');
        $stmt->execute([$id]);
        if ((int) $stmt->fetchColumn() > 0) {
            flash('error', 'Khóa học đã phát sinh đơn hàng, hãy ẩn thay vì xóa.');
            redirect($listPath);
        }

        $stmt = db()->prepare('DELETE FROM courses WHERE id = ?');
        $stmt->execute([$id]);
        flash('success', 'Đã xóa khóa học.');
        redirect($listPath);
    }
}

$q = admin_search_term();
$params = [];
$where = '';
if ($q !== '') {
    $where = ' WHERE courses.title LIKE ? OR categories.name LIKE ?';
    $like = '%' . $q . '%';
    $params = [$like, $like];
}

$countStmt = db()->prepare('SELECT COUNT(DISTINCT courses.id)
    FROM courses
    JOIN categories ON categories.id = courses.category_id' . $where);
$countStmt->execute($params);
$totalCourses = (int) $countStmt->fetchColumn();
$pageCount = max(1, (int) ceil($totalCourses / admin_per_page()));
$offset = (min(admin_page_number(), $pageCount) - 1) * admin_per_page();

$stmt = db()->prepare('SELECT courses.*, categories.name AS category_name,
    COUNT(DISTINCT course_chapters.id) AS chapter_count,
    COUNT(DISTINCT course_lessons.id) AS lesson_count
    FROM courses
    JOIN categories ON categories.id = courses.category_id
    LEFT JOIN course_chapters ON course_chapters.course_id = courses.id
    LEFT JOIN course_lessons ON course_lessons.chapter_id = course_chapters.id
    ' . $where . '
    GROUP BY courses.id
    ORDER BY courses.created_at DESC, courses.id DESC
    LIMIT ' . admin_per_page() . ' OFFSET ' . $offset);
$stmt->execute($params);
$courses = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0">Quản lý khóa học</h1>
    <a class="btn btn-primary" href="<?= APP_URL ?>/<?= e(admin_relative_url('admin/course_form.php', ['return' => admin_relative_url('admin/courses.php')])) ?>">Thêm khóa học</a>
</div>

<?php admin_render_search('Tìm theo tên khóa học hoặc danh mục...'); ?>

<div class="bg-white rounded-2 shadow-sm table-responsive">
    <table class="table mb-0">
        <thead>
        <tr>
            <th>Tên khóa học</th>
            <th>Danh mục</th>
            <th>Nội dung</th>
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
                <td><?= (int) $course['chapter_count'] ?> chương · <?= (int) $course['lesson_count'] ?> bài</td>
                <td><?= money((float) $course['price']) ?></td>
                <td><?= $course['is_active'] ? 'Đang bán' : 'Đã ẩn' ?></td>
                <td class="text-end">
                    <a class="btn btn-sm btn-outline-secondary" href="<?= APP_URL ?>/<?= e(admin_relative_url('admin/course_content.php', ['id' => (int) $course['id'], 'return' => admin_relative_url('admin/courses.php')])) ?>">Nội dung</a>
                    <a class="btn btn-sm btn-outline-primary" href="<?= APP_URL ?>/<?= e(admin_relative_url('admin/course_form.php', ['id' => (int) $course['id'], 'return' => admin_relative_url('admin/courses.php')])) ?>">Sửa</a>
                    <form class="d-inline" method="post">
                        <input type="hidden" name="id" value="<?= (int) $course['id'] ?>">
                        <?php if ($course['is_active']): ?>
                            <button class="btn btn-sm btn-outline-danger" name="action" value="hide" type="submit">Ẩn</button>
                        <?php else: ?>
                            <button class="btn btn-sm btn-outline-success" name="action" value="show" type="submit">Mở bán</button>
                        <?php endif; ?>
                    </form>
                    <form class="d-inline" method="post" onsubmit="return confirm('Xóa khóa học này?')">
                        <input type="hidden" name="id" value="<?= (int) $course['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger" name="action" value="delete" type="submit">Xóa</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$courses): ?>
            <tr><td colspan="6" class="text-muted">Không tìm thấy khóa học phù hợp.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php admin_render_pagination($totalCourses, 'admin/courses.php'); ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
