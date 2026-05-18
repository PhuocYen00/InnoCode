<?php
require_once dirname(__DIR__) . '/core/init.php';
require_login();

$user = current_user();
$pageTitle = 'Xác thực email - ' . APP_NAME;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = random_token();
    $stmt = db()->prepare('UPDATE users SET verification_token = ? WHERE id = ?');
    $stmt->execute([$token, (int) $user['id']]);
    $user = current_user();
    send_verification_email($user);
    flash('success', 'Đã gửi lại email xác thực.');
    redirect('verify_notice.php');
}

require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="container py-5">
    <div class="bg-white rounded-2 p-4 shadow-sm">
        <h1 class="h3">Xác thực email</h1>
        <?php if ($user['email_verified_at']): ?>
            <p>Email của bạn đã được xác thực.</p>
            <a class="btn btn-primary" href="<?= url('my_courses') ?>">Vào khóa học của tôi</a>
        <?php else: ?>
            <p class="text-muted">Hệ thống đã gửi link xác thực đến <strong><?= e($user['email']) ?></strong>. Vui lòng kiểm tra hộp thư đến hoặc thư rác.</p>
            <form method="post">
                <button class="btn btn-primary" type="submit">Gửi lại email xác thực</button>
            </form>
        <?php endif; ?>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>


