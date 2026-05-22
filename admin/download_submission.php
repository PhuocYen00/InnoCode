<?php
require_once dirname(__DIR__) . '/core/init.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM lesson_submissions WHERE id = ?');
$stmt->execute([$id]);
$submission = $stmt->fetch();

if (!$submission) {
    http_response_code(404);
    exit('Không tìm thấy bài nộp.');
}

$path = dirname(__DIR__) . '/storage/submissions/' . basename((string) $submission['stored_name']);
if (!is_file($path)) {
    http_response_code(404);
    exit('File bài nộp không còn tồn tại.');
}

$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$textExtensions = ['txt', 'php', 'js', 'ts', 'py', 'java', 'c', 'cpp', 'cs', 'html', 'css', 'sql'];

if ($extension === 'pdf') {
    $mime = 'application/pdf';
} elseif (in_array($extension, $textExtensions, true)) {
    $mime = 'text/plain; charset=utf-8';
} elseif ($extension === 'zip') {
    $mime = 'application/zip';
} else {
    $mime = 'application/octet-stream';
}

header('Content-Type: ' . $mime);
$downloadName = str_replace('"', '', basename((string) $submission['original_name']));
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
