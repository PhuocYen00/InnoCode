<?php
require_once __DIR__ . '/../../core/init.php';
require_admin();
$currentAdminPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Admin') ?></title>
    <script>
    document.documentElement.dataset.theme = localStorage.getItem('theme') || 'light';
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/style.css?v=20260519-fixes" rel="stylesheet">
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="<?= APP_URL ?>/admin/index.php">Admin <?= e(APP_NAME) ?></a>
        <nav class="admin-nav">
            <a class="<?= $currentAdminPage === 'index.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/index.php">Tổng quan</a>
            <details class="admin-nav-group">
                <summary>Đào tạo</summary>
                <a class="<?= in_array($currentAdminPage, ['courses.php', 'course_form.php', 'course_content.php'], true) ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/courses.php">Khóa học</a>
                <a class="<?= $currentAdminPage === 'categories.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/categories.php">Danh mục</a>
                <a class="<?= $currentAdminPage === 'quizzes.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/quizzes.php">Quiz</a>
                <a class="<?= $currentAdminPage === 'quiz_attempts.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/quiz_attempts.php">Bài làm</a>
            </details>
            <details class="admin-nav-group">
                <summary>Thương mại</summary>
                <a class="<?= in_array($currentAdminPage, ['orders.php', 'order_detail.php'], true) ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/orders.php">Đơn hàng</a>
                <a class="<?= $currentAdminPage === 'revenue.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/revenue.php">Thống kê doanh thu</a>
                <a class="<?= $currentAdminPage === 'coupons.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/coupons.php">Coupon</a>
                <a class="<?= $currentAdminPage === 'products.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/products.php">Sách & quà lưu niệm</a>
            </details>
            <details class="admin-nav-group">
                <summary>Cộng đồng</summary>
                <a class="<?= $currentAdminPage === 'users.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/users.php">Học viên</a>
                <a class="<?= $currentAdminPage === 'questions.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/questions.php">Hỏi đáp</a>
            </details>
        </nav>
        <div class="admin-sidebar-foot">
            <button class="theme-toggle admin-theme-toggle" type="button" aria-label="Đổi theme" data-theme-toggle>☾</button>
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
