<?php
require_once dirname(__DIR__) . '/core/init.php';

$token = $_GET['token'] ?? '';

if ($token === '') {
    flash('error', 'Link xác thực không hợp lệ.');
    redirect('login.php');
}

$stmt = db()->prepare('SELECT * FROM users WHERE verification_token = ?');
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    flash('error', 'Link xác thực đã hết hạn hoặc không tồn tại.');
    redirect('login.php');
}

$stmt = db()->prepare('UPDATE users SET email_verified_at = NOW(), verification_token = NULL WHERE id = ?');
$stmt->execute([(int) $user['id']]);
login_user($user);
flash('success', 'Email đã được xác thực. Bạn có thể thanh toán và học khóa học.');
redirect('my_courses.php');


