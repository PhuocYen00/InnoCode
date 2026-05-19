<?php
require_once __DIR__ . '/../../core/init.php';
require_admin();
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Admin') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg bg-dark navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="<?= APP_URL ?>/admin/index.php">Admin <?= e(APP_NAME) ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Mở menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/index.php">Tổng quan</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Đào tạo</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= APP_URL ?>/admin/courses.php">Khóa học</a></li>
                        <li><a class="dropdown-item" href="<?= APP_URL ?>/admin/categories.php">Danh mục</a></li>
                        <li><a class="dropdown-item" href="<?= APP_URL ?>/admin/quizzes.php">Quiz</a></li>
                        <li><a class="dropdown-item" href="<?= APP_URL ?>/admin/quiz_attempts.php">Bài làm</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Thương mại</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= APP_URL ?>/admin/orders.php">Đơn hàng</a></li>
                        <li><a class="dropdown-item" href="<?= APP_URL ?>/admin/revenue.php">Thống kê doanh thu</a></li>
                        <li><a class="dropdown-item" href="<?= APP_URL ?>/admin/coupons.php">Coupon</a></li>
                        <li><a class="dropdown-item" href="<?= APP_URL ?>/admin/products.php">Sách & quà lưu niệm</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Cộng đồng</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= APP_URL ?>/admin/users.php">Học viên</a></li>
                        <li><a class="dropdown-item" href="<?= APP_URL ?>/admin/questions.php">Hỏi đáp</a></li>
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link" href="<?= url('home') ?>">Xem website</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/logout.php">Đăng xuất</a></li>
            </ul>
        </div>
    </div>
</nav>
<main class="container py-4 admin-shell">
<?php if ($message = flash('success')): ?>
    <div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>
<?php if ($message = flash('error')): ?>
    <div class="alert alert-danger"><?= e($message) ?></div>
<?php endif; ?>

