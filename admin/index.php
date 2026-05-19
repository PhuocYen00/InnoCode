<?php
$pageTitle = 'Bảng điều khiển';
require_once __DIR__ . '/includes/header.php';

$stats = [
    'courses' => (int) db()->query('SELECT COUNT(*) FROM courses')->fetchColumn(),
    'orders' => (int) db()->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'paid_revenue' => (float) db()->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'paid'")->fetchColumn(),
    'students' => (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'questions_open' => (int) db()->query("SELECT COUNT(*) FROM course_questions WHERE status = 'open'")->fetchColumn(),
    'quiz_attempts' => (int) db()->query('SELECT COUNT(*) FROM quiz_attempts')->fetchColumn(),
];
$latestOrders = db()->query('SELECT * FROM orders ORDER BY created_at DESC LIMIT 5')->fetchAll();
$latestQuestions = db()->query('SELECT course_questions.*, users.name AS user_name, courses.title AS course_title
    FROM course_questions
    JOIN users ON users.id = course_questions.user_id
    JOIN courses ON courses.id = course_questions.course_id
    ORDER BY course_questions.created_at DESC LIMIT 4')->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 mb-1">Bảng điều khiển</h1>
        <p class="text-muted mb-0">Tổng quan vận hành khóa học, doanh thu và hỗ trợ học viên.</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary" href="<?= APP_URL ?>/admin/revenue.php">Xem doanh thu</a>
        <a class="btn btn-primary" href="<?= APP_URL ?>/admin/course_form.php">Thêm khóa học</a>
    </div>
</div>

<div class="row g-3 mb-4 admin-stats-row">
    <div class="col-md-4 col-xl-2"><div class="bg-white rounded-2 p-4 shadow-sm"><span class="text-muted">Khóa học</span><div class="h2"><?= $stats['courses'] ?></div></div></div>
    <div class="col-md-4 col-xl-2"><div class="bg-white rounded-2 p-4 shadow-sm"><span class="text-muted">Đơn hàng</span><div class="h2"><?= $stats['orders'] ?></div></div></div>
    <div class="col-md-4 col-xl-2"><div class="bg-white rounded-2 p-4 shadow-sm"><span class="text-muted">Học viên</span><div class="h2"><?= $stats['students'] ?></div></div></div>
    <div class="col-md-4 col-xl-2"><div class="bg-white rounded-2 p-4 shadow-sm"><span class="text-muted">Hỏi đáp mở</span><div class="h2"><?= $stats['questions_open'] ?></div></div></div>
    <div class="col-md-4 col-xl-2"><div class="bg-white rounded-2 p-4 shadow-sm"><span class="text-muted">Bài quiz</span><div class="h2"><?= $stats['quiz_attempts'] ?></div></div></div>
    <div class="col-md-4 col-xl-2"><div class="admin-stat-card bg-white rounded-2 p-4 shadow-sm"><span class="text-muted">Doanh thu</span><div class="h2 admin-revenue-value"><?= money($stats['paid_revenue']) ?></div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="bg-white rounded-2 p-4 shadow-sm h-100">
            <div class="d-flex justify-content-between mb-3">
                <h2 class="h5 mb-0">Đơn mới nhất</h2>
                <a href="<?= APP_URL ?>/admin/orders.php">Quản lý</a>
            </div>
            <?php foreach ($latestOrders as $order): ?>
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span>#<?= (int) $order['id'] ?> · <?= e($order['customer_name']) ?> · <?= e($order['status']) ?></span>
                    <strong><?= money((float) $order['total_amount']) ?></strong>
                </div>
            <?php endforeach; ?>
            <?php if (!$latestOrders): ?>
                <p class="text-muted mb-0">Chưa có đơn hàng.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="bg-white rounded-2 p-4 shadow-sm h-100">
            <div class="d-flex justify-content-between mb-3">
                <h2 class="h5 mb-0">Hỏi đáp mới</h2>
                <a href="<?= APP_URL ?>/admin/questions.php">Trả lời</a>
            </div>
            <?php foreach ($latestQuestions as $question): ?>
                <div class="border-bottom py-2">
                    <strong><?= e($question['course_title']) ?></strong>
                    <p class="mb-1"><?= e(excerpt((string) $question['question'], 100)) ?></p>
                    <small class="text-muted"><?= e($question['user_name']) ?> · <?= e($question['status']) ?></small>
                </div>
            <?php endforeach; ?>
            <?php if (!$latestQuestions): ?>
                <p class="text-muted mb-0">Chưa có câu hỏi.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

