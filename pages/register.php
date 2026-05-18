<?php
require_once dirname(__DIR__) . '/core/init.php';

if (is_logged_in()) {
    redirect('my_courses.php');
}

$pageTitle = 'Đăng ký - ' . APP_NAME;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['password_confirmation'] ?? '');

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6 || $password !== $confirm) {
        flash('error', 'Vui lòng nhập đúng thông tin, mật khẩu từ 6 ký tự và xác nhận mật khẩu khớp.');
        redirect('register.php');
    }

    if (find_user_by_email($email)) {
        flash('error', 'Email này đã được đăng ký.');
        redirect('register.php');
    }

    $token = random_token();
    $stmt = db()->prepare('INSERT INTO users (name, email, phone, password_hash, verification_token) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT), $token]);

    $user = find_user_by_email($email);
    send_verification_email($user);
    login_user($user);

    flash('success', 'Đăng ký thành công. Vui lòng mở email xác thực trước khi thanh toán.');
    redirect('verify_notice.php');
}

require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="container py-5 auth-page">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <form class="bg-white rounded-2 p-4 shadow-sm" method="post">
                <h1 class="h3 mb-3">Đăng ký học viên</h1>
                <div class="mb-3">
                    <label class="form-label">Họ tên</label>
                    <input class="form-control" name="name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input class="form-control" type="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Số điện thoại</label>
                    <input class="form-control" name="phone">
                </div>
                <div class="mb-3">
                    <label class="form-label">Mật khẩu</label>
                    <input class="form-control" type="password" name="password" minlength="6" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nhập lại mật khẩu</label>
                    <input class="form-control" type="password" name="password_confirmation" minlength="6" required>
                </div>
                <button class="btn btn-primary w-100" type="submit">Tạo tài khoản</button>
                <p class="text-muted small mt-3 mb-0">Đã có tài khoản? <a href="<?= url('login') ?>">Đăng nhập</a></p>
            </form>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>


