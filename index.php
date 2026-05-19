<?php
require_once __DIR__ . '/core/init.php';

$routes = [
    'home' => 'home.php',
    'courses' => 'courses.php',
    'course' => 'course.php',
    'learn' => 'learn.php',
    'cart' => 'cart.php',
    'checkout' => 'checkout.php',
    'payment_success' => 'payment_success.php',
    'payment_return_vnpay' => 'payment_return_vnpay.php',
    'payment_return_momo' => 'payment_return_momo.php',
    'payos_webhook' => 'payos_webhook.php',
    'purchase_course' => 'purchase_course.php',
    'toggle_favorite' => 'toggle_favorite.php',
    'review_course' => 'review_course.php',
    'lesson_progress' => 'lesson_progress.php',
    'lesson_question' => 'lesson_question.php',
    'lesson_report' => 'lesson_report.php',
    'download_material' => 'download_material.php',
    'download_note' => 'download_note.php',
    'quiz' => 'quiz.php',
    'compiler_run' => 'compiler_run.php',
    'receipt' => 'receipt.php',
    'login' => 'login.php',
    'logout' => 'logout.php',
    'register' => 'register.php',
    'forgot_password' => 'forgot_password.php',
    'reset_password' => 'reset_password.php',
    'verify_email' => 'verify_email.php',
    'verify_notice' => 'verify_notice.php',
    'my_courses' => 'my_courses.php',
    'profile' => 'profile.php',
];

$page = preg_replace('/[^a-z0-9_]/', '', (string) ($_GET['page'] ?? 'home'));

if (!isset($routes[$page])) {
    http_response_code(404);
    $page = 'home';
    flash('error', 'Trang bạn yêu cầu không tồn tại.');
}

require __DIR__ . '/pages/' . $routes[$page];
