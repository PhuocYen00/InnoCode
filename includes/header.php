<?php require_once __DIR__ . '/../core/init.php'; ?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/style.css?v=20260518-innocode-logo" rel="stylesheet">
</head>
<body>
<nav class="site-nav sticky-top">
    <div class="container nav-inner">
        <a class="brand" href="<?= url('home') ?>">
            <img class="site-logo" src="<?= APP_URL ?>/assets/images/innocode.jpg" alt="<?= e(APP_NAME) ?>" width="86" height="34" style="width:86px;height:34px;object-fit:contain;display:block;">
        </a>
        <form class="nav-search" action="<?= url('courses') ?>" method="get">
            <input name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Tìm khóa học, bài tập, hỏi đáp...">
        </form>
        <div class="nav-links">
            <a class="<?= active_nav('index.php') ?>" href="<?= url('home') ?>">Trang chủ</a>
            <a class="<?= active_nav('courses.php') ?>" href="<?= url('courses') ?>">Khóa học</a>
            <a class="<?= active_nav('cart.php') ?>" href="<?= url('cart') ?>">Giỏ hàng (<?= cart_items_count() ?>)</a>
            <?php if ($user = current_user()): ?>
                <a class="<?= active_nav('my_courses.php') ?>" href="<?= url('my_courses') ?>">Khóa học của tôi</a>
                <a class="<?= active_nav('profile.php') ?>" href="<?= url('profile') ?>">Trang cá nhân</a>
                <a class="login-link" href="<?= url('logout') ?>">Đăng xuất</a>
            <?php else: ?>
                <a class="<?= active_nav('register.php') ?>" href="<?= url('register') ?>">Đăng ký</a>
                <a class="login-link" href="<?= url('login') ?>">Đăng nhập</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main>
<?php if ($message = flash('success')): ?>
    <div class="container mt-3">
        <div class="alert alert-success"><?= e($message) ?></div>
    </div>
<?php endif; ?>
<?php if ($message = flash('error')): ?>
    <div class="container mt-3">
        <div class="alert alert-danger"><?= e($message) ?></div>
    </div>
<?php endif; ?>
