<?php
$pageTitle = 'Quản lý học viên';
require_once __DIR__ . '/includes/header.php';

$q = admin_search_term();
$params = [];
$where = '';
if ($q !== '') {
    $where = ' WHERE users.name LIKE ? OR users.email LIKE ? OR users.phone LIKE ?';
    $like = '%' . $q . '%';
    $params = [$like, $like, $like];
}

$countStmt = db()->prepare('SELECT COUNT(*) FROM users' . $where);
$countStmt->execute($params);
$totalUsers = (int) $countStmt->fetchColumn();

$stmt = db()->prepare('SELECT users.*, COUNT(DISTINCT enrollments.course_id) AS course_count, COUNT(DISTINCT orders.id) AS order_count
    FROM users
    LEFT JOIN enrollments ON enrollments.user_id = users.id
    LEFT JOIN orders ON orders.user_id = users.id
    ' . $where . '
    GROUP BY users.id
    ORDER BY users.created_at DESC
    LIMIT ' . admin_per_page() . ' OFFSET ' . admin_offset());
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0">Quản lý học viên</h1>
</div>

<?php admin_render_search('Tìm theo tên, email hoặc số điện thoại...'); ?>

<div class="bg-white rounded-2 shadow-sm table-responsive">
    <table class="table mb-0">
        <thead>
        <tr>
            <th>Học viên</th>
            <th>Email</th>
            <th>Xác thực</th>
            <th>Khóa học</th>
            <th>Đơn hàng</th>
            <th>Ngày tạo</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= e($user['name']) ?><br><small class="text-muted"><?= e($user['phone']) ?></small></td>
                <td><?= e($user['email']) ?></td>
                <td><?= $user['email_verified_at'] ? 'Đã xác thực' : 'Chưa xác thực' ?></td>
                <td><?= (int) $user['course_count'] ?></td>
                <td><?= (int) $user['order_count'] ?></td>
                <td><?= e($user['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$users): ?>
            <tr><td colspan="6" class="text-muted">Chưa có học viên.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php admin_render_pagination($totalUsers, 'admin/users.php'); ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
