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

$mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
    'pdf' => 'application/pdf',
    'txt', 'php', 'js', 'ts', 'py', 'java', 'c', 'cpp', 'cs', 'html', 'css', 'sql' => 'text/plain; charset=utf-8',
    'zip' => 'application/zip',
    default => 'application/octet-stream',
};

header('Content-Type: ' . $mime);
$downloadName = str_replace('"', '', basename((string) $submission['original_name']));
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
