<?php
require_once dirname(__DIR__) . '/core/init.php';

if (is_logged_in()) {
    redirect(is_admin() ? 'admin/index.php' : 'my_courses.php');
}

$pageTitle = 'Đăng nhập - ' . APP_NAME;
$next = $_GET['next'] ?? 'my_courses.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $next = $_POST['next'] ?? 'my_courses.php';
    $user = find_user_by_email($email);

    if ($user && password_verify($password, (string) $user['password_hash'])) {
        login_user($user);
        if (($user['role'] ?? 'user') === 'admin') {
            redirect('admin/index.php');
        }
        redirect($next ?: 'my_courses.php');
    }

    flash('error', 'Email hoặc mật khẩu không đúng.');
    redirect('login.php');
}

require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="container py-5 auth-page">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <form class="bg-white rounded-2 p-4 shadow-sm" method="post">
                <h1 class="h3 mb-3">Đăng nhập</h1>
                <input type="hidden" name="next" value="<?= e($next) ?>">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input class="form-control" type="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mật khẩu</label>
                    <input class="form-control" type="password" name="password" required>
                </div>
                <button class="btn btn-primary w-100" type="submit">Đăng nhập</button>
                <div class="d-flex justify-content-between mt-3 small">
                    <a href="<?= url('register') ?>">Tạo tài khoản</a>
                    <a href="<?= url('forgot_password') ?>">Quên mật khẩu?</a>
                </div>
            </form>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>


