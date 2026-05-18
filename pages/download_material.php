<?php
require_once dirname(__DIR__) . '/core/init.php';
require_login();

$courseId = (int) ($_GET['course_id'] ?? 0);
$lessonIndex = max(0, (int) ($_GET['lesson'] ?? 0));
$type = (string) ($_GET['type'] ?? '');

if (!find_course($courseId) || !has_purchased_course($courseId)) {
    http_response_code(403);
    exit('Bạn chưa có quyền tải tài liệu này.');
}

$material = null;
foreach (lesson_materials_for($courseId, $lessonIndex) as $item) {
    if ($item['type'] === $type) {
        $material = $item;
        break;
    }
}

if (!$material) {
    http_response_code(404);
    exit('Không tìm thấy tài liệu.');
}

$fileName = (string) $material['filename'];
$path = str_starts_with($fileName, 'http://') || str_starts_with($fileName, 'https://')
    ? ''
    : dirname(__DIR__) . '/storage/materials/' . basename($fileName);

if ($path === '') {
    header('Location: ' . $fileName);
    exit;
}

if (!is_file($path)) {
    http_response_code(404);
    exit('File tài liệu chưa tồn tại.');
}

$mime = match (pathinfo($path, PATHINFO_EXTENSION)) {
    'pdf' => 'application/pdf',
    'php', 'txt' => 'text/plain; charset=utf-8',
    default => 'application/octet-stream',
};

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($path) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
