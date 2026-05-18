<?php
require_once dirname(__DIR__) . '/core/init.php';
$pageTitle = 'Đặt lại mật khẩu - ' . APP_NAME;
$token = $_GET['token'] ?? ($_POST['token'] ?? '');

$stmt = db()->prepare('SELECT * FROM users WHERE reset_token = ? AND reset_expires_at > NOW()');
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    flash('error', 'Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.');
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['password_confirmation'] ?? '');

    if (strlen($password) < 6 || $password !== $confirm) {
        flash('error', 'Mật khẩu từ 6 ký tự và xác nhận phải khớp.');
        redirect('reset_password.php?token=' . urlencode($token));
    }

    $stmt = db()->prepare('UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires_at = NULL WHERE id = ?');
    $stmt->execute([password_hash($password, PASSWORD_DEFAULT), (int) $user['id']]);
    flash('success', 'Đã đặt lại mật khẩu. Vui lòng đăng nhập.');
    redirect('login.php');
}

require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="container py-5 auth-page">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <form class="bg-white rounded-2 p-4 shadow-sm" method="post">
                <h1 class="h3 mb-3">Đặt lại mật khẩu</h1>
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <div class="mb-3">
                    <label class="form-label">Mật khẩu mới</label>
                    <input class="form-control" type="password" name="password" minlength="6" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nhập lại mật khẩu</label>
                    <input class="form-control" type="password" name="password_confirmation" minlength="6" required>
                </div>
                <button class="btn btn-primary w-100" type="submit">Lưu mật khẩu mới</button>
            </form>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>


