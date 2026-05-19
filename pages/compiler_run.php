<?php
require_once dirname(__DIR__) . '/core/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('compiler.php');
}

$courseId = (int) ($_POST['course_id'] ?? 0);
$lessonIndex = max(0, (int) ($_POST['lesson_index'] ?? 0));
$language = (string) ($_POST['language'] ?? 'php');
$code = (string) ($_POST['code'] ?? '');

if ($courseId <= 0) {
    $_SESSION['compiler_result'] = run_code_multi($language, $code);
    $_SESSION['compiler_code'] = $code;
    $_SESSION['compiler_language'] = $language;
    redirect('compiler.php');
}

if (!find_course($courseId) || !has_purchased_course($courseId)) {
    flash('error', 'Bạn chưa có quyền chạy code trong khóa học này.');
    redirect('my_courses.php');
}

$_SESSION['compiler_result'] = run_code_multi($language, $code);
$_SESSION['compiler_code'] = $code;
$_SESSION['compiler_language'] = $language;

redirect('learn.php?id=' . $courseId . '&lesson=' . $lessonIndex . '#compiler');
