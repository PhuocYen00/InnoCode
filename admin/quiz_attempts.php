<?php
$pageTitle = 'Kết quả quiz';
require_once __DIR__ . '/includes/header.php';

$attempts = db()->query('SELECT quiz_attempts.*, users.name AS user_name, courses.title AS course_title
    FROM quiz_attempts
    JOIN users ON users.id = quiz_attempts.user_id
    JOIN courses ON courses.id = quiz_attempts.course_id
    ORDER BY quiz_attempts.created_at DESC')->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0">Kết quả quiz</h1>
    <div class="d-flex gap-2 align-items-center">
        <span class="badge bg-primary"><?= count($attempts) ?> lượt nộp</span>
        <a class="btn btn-outline-primary" href="<?= APP_URL ?>/admin/quizzes.php">Quản lý câu hỏi</a>
    </div>
</div>

<div class="bg-white rounded-2 p-4 shadow-sm table-responsive">
    <table class="table">
        <thead>
        <tr>
            <th>Học viên</th>
            <th>Khóa học</th>
            <th>Bài</th>
            <th>Điểm</th>
            <th>Tự luận</th>
            <th>Thời gian</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($attempts as $attempt): ?>
            <tr>
                <td><?= e($attempt['user_name']) ?></td>
                <td><?= e($attempt['course_title']) ?></td>
                <td><?= (int) $attempt['lesson_index'] + 1 ?></td>
                <td><?= (int) $attempt['score'] ?>/<?= (int) $attempt['total'] ?></td>
                <td><?= e(excerpt((string) $attempt['essay_answer'], 80)) ?></td>
                <td><?= e($attempt['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (!$attempts): ?>
        <p class="text-muted mb-0">Chưa có lượt làm quiz.</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
