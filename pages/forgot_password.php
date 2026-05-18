<?php
require_once dirname(__DIR__) . '/core/init.php';
$pageTitle = 'Quên mật khẩu - ' . APP_NAME;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $user = find_user_by_email($email);

    if ($user) {
        $token = random_token();
        $stmt = db()->prepare('UPDATE users SET reset_token = ?, reset_expires_at = DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE id = ?');
        $stmt->execute([$token, (int) $user['id']]);
        $link = url('reset_password', ['token' => $token]);
        send_app_mail($email, 'Đặt lại mật khẩu ' . APP_NAME, "Bấm link để đặt lại mật khẩu:\n{$link}", (int) $user['id']);
    }

    flash('success', 'Nếu email tồn tại, hệ thống đã gửi link đặt lại mật khẩu.');
    redirect('login.php');
}

require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="container py-5 auth-page">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <form class="bg-white rounded-2 p-4 shadow-sm" method="post">
                <h1 class="h3 mb-3">Quên mật khẩu</h1>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input class="form-control" type="email" name="email" required>
                </div>
                <button class="btn btn-primary w-100" type="submit">Gửi link đặt lại</button>
            </form>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>


