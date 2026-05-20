<?php
$pageTitle = 'Bài thực hành';
require_once __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $feedback = trim((string) ($_POST['feedback'] ?? ''));
    $status = $feedback !== '' ? 'reviewed' : 'submitted';

    $stmt = db()->prepare('UPDATE lesson_submissions SET feedback = ?, status = ?, reviewed_at = ? WHERE id = ?');
    $stmt->execute([
        $feedback !== '' ? $feedback : null,
        $status,
        $status === 'reviewed' ? date('Y-m-d H:i:s') : null,
        $id,
    ]);

    flash('success', 'Đã lưu nhận xét bài thực hành.');
    redirect('admin/practice_submissions.php');
}

$status = (string) ($_GET['status'] ?? '');
$q = admin_search_term();
$params = [];
$whereParts = [];

if ($status !== '') {
    $whereParts[] = 'lesson_submissions.status = ?';
    $params[] = $status;
}
if ($q !== '') {
    $whereParts[] = '(users.name LIKE ? OR users.email LIKE ? OR courses.title LIKE ? OR lesson_submissions.lesson_title LIKE ? OR lesson_submissions.original_name LIKE ? OR lesson_submissions.note LIKE ? OR lesson_submissions.feedback LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like, $like, $like, $like);
}

$whereSql = $whereParts ? ' WHERE ' . implode(' AND ', $whereParts) : '';

$countStmt = db()->prepare('SELECT COUNT(*)
    FROM lesson_submissions
    JOIN users ON users.id = lesson_submissions.user_id
    JOIN courses ON courses.id = lesson_submissions.course_id' . $whereSql);
$countStmt->execute($params);
$totalSubmissions = (int) $countStmt->fetchColumn();

$sql = 'SELECT lesson_submissions.*, users.name AS user_name, users.email, courses.title AS course_title
    FROM lesson_submissions
    JOIN users ON users.id = lesson_submissions.user_id
    JOIN courses ON courses.id = lesson_submissions.course_id' . $whereSql . '
    ORDER BY lesson_submissions.created_at DESC, lesson_submissions.id DESC
    LIMIT ' . admin_per_page() . ' OFFSET ' . admin_offset();
$stmt = db()->prepare($sql);
$stmt->execute($params);
$submissions = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0">Bài thực hành</h1>
    <div class="btn-group">
        <a class="btn btn-outline-primary <?= $status === '' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/practice_submissions.php">Tất cả</a>
        <a class="btn btn-outline-primary <?= $status === 'submitted' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/practice_submissions.php?status=submitted">Chờ nhận xét</a>
        <a class="btn btn-outline-primary <?= $status === 'reviewed' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/practice_submissions.php?status=reviewed">Đã nhận xét</a>
    </div>
</div>

<?php admin_render_search('Tìm theo học viên, email, khóa học, bài học hoặc tên file...', ['status' => $status]); ?>

<div class="bg-white rounded-2 p-4 shadow-sm">
    <?php if (!$submissions): ?>
        <p class="text-muted mb-0">Chưa có bài thực hành nào được nộp.</p>
    <?php endif; ?>

    <?php foreach ($submissions as $submission): ?>
        <details class="border-bottom py-3">
            <summary class="d-flex justify-content-between gap-3 flex-wrap align-items-start" style="cursor:pointer">
                <span>
                    <strong><?= e($submission['course_title']) ?> · Bài <?= (int) $submission['lesson_index'] + 1 ?></strong><br>
                    <span class="text-muted">
                        <?= e($submission['lesson_title']) ?> · <?= e($submission['user_name']) ?> · <?= e($submission['created_at']) ?>
                    </span>
                </span>
                <span class="badge <?= $submission['status'] === 'reviewed' ? 'bg-success' : 'bg-warning text-dark' ?>">
                    <?= $submission['status'] === 'reviewed' ? 'Đã nhận xét' : 'Chờ nhận xét' ?>
                </span>
            </summary>

            <form class="pt-3" method="post">
                <input type="hidden" name="id" value="<?= (int) $submission['id'] ?>">
                <p class="text-muted mb-2"><?= e($submission['user_name']) ?> · <?= e($submission['email']) ?></p>

                <div class="mb-3">
                    <a class="btn btn-sm btn-outline-primary" href="<?= APP_URL ?>/admin/download_submission.php?id=<?= (int) $submission['id'] ?>">
                        Tải file <?= e($submission['original_name']) ?>
                    </a>
                    <small class="text-muted ms-2"><?= number_format((int) $submission['file_size'] / 1024, 1, ',', '.') ?> KB</small>
                </div>

                <?php if (!empty($submission['note'])): ?>
                    <p class="bg-light rounded-2 p-3"><strong>Ghi chú học viên:</strong><br><?= nl2br(e($submission['note'])) ?></p>
                <?php endif; ?>

                <label class="form-label">Nhận xét cho học viên</label>
                <textarea class="form-control" name="feedback" rows="4"><?= e($submission['feedback']) ?></textarea>
                <button class="btn btn-primary mt-2" type="submit">Lưu nhận xét</button>
            </form>
        </details>
    <?php endforeach; ?>
</div>

<?php admin_render_pagination($totalSubmissions, 'admin/practice_submissions.php', ['status' => $status]); ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
