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
    <link href="<?= APP_URL ?>/assets/css/style.css?v=20260519-admin-sidebar" rel="stylesheet">
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="<?= APP_URL ?>/admin/index.php">Admin <?= e(APP_NAME) ?></a>
        <nav class="admin-nav">
            <a href="<?= APP_URL ?>/admin/index.php">Tổng quan</a>
            <span>Đào tạo</span>
            <a href="<?= APP_URL ?>/admin/courses.php">Khóa học</a>
            <a href="<?= APP_URL ?>/admin/categories.php">Danh mục</a>
            <a href="<?= APP_URL ?>/admin/quizzes.php">Quiz</a>
            <a href="<?= APP_URL ?>/admin/quiz_attempts.php">Bài làm</a>
            <span>Thương mại</span>
            <a href="<?= APP_URL ?>/admin/orders.php">Đơn hàng</a>
            <a href="<?= APP_URL ?>/admin/revenue.php">Thống kê doanh thu</a>
            <a href="<?= APP_URL ?>/admin/coupons.php">Coupon</a>
            <a href="<?= APP_URL ?>/admin/products.php">Sách & quà lưu niệm</a>
            <span>Cộng đồng</span>
            <a href="<?= APP_URL ?>/admin/users.php">Học viên</a>
            <a href="<?= APP_URL ?>/admin/questions.php">Hỏi đáp</a>
        </nav>
        <div class="admin-sidebar-foot">
            <a href="<?= url('home') ?>">Xem website</a>
            <a href="<?= APP_URL ?>/admin/logout.php">Đăng xuất</a>
        </div>
    </aside>
    <main class="admin-main">
        <div class="admin-content">
            <?php if ($message = flash('success')): ?>
                <div class="alert alert-success"><?= e($message) ?></div>
            <?php endif; ?>
            <?php if ($message = flash('error')): ?>
                <div class="alert alert-danger"><?= e($message) ?></div>
            <?php endif; ?>

