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
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="<?= APP_URL ?>/admin/courses.php">Khóa học</a>
            <a class="nav-link" href="<?= APP_URL ?>/admin/orders.php">Đơn hàng</a>
            <a class="nav-link" href="<?= APP_URL ?>/admin/users.php">Học viên</a>
            <a class="nav-link" href="<?= APP_URL ?>/admin/coupons.php">Coupon</a>
            <a class="nav-link" href="<?= APP_URL ?>/admin/questions.php">Hỏi đáp</a>
            <a class="nav-link" href="<?= APP_URL ?>/admin/quizzes.php">Quiz</a>
            <a class="nav-link" href="<?= APP_URL ?>/admin/quiz_attempts.php">Bài làm</a>
            <a class="nav-link" href="<?= url('home') ?>">Xem website</a>
            <a class="nav-link" href="<?= APP_URL ?>/admin/logout.php">Đăng xuất</a>
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
