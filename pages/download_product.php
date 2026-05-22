<?php
require_once dirname(__DIR__) . '/core/init.php';
require_login();

$itemId = (int) ($_GET['item_id'] ?? 0);
$stmt = db()->prepare("SELECT physical_order_items.*, physical_products.digital_file_url, physical_products.name, orders.user_id, orders.status
    FROM physical_order_items
    JOIN orders ON orders.id = physical_order_items.order_id
    JOIN physical_products ON physical_products.id = physical_order_items.product_id
    WHERE physical_order_items.id = ? AND physical_products.product_type <> 'souvenir'
    LIMIT 1");
$stmt->execute([$itemId]);
$item = $stmt->fetch();

if (!$item || (int) $item['user_id'] !== (int) current_user()['id'] || $item['status'] !== 'paid') {
    http_response_code(403);
    exit('Bạn chưa có quyền tải tài liệu này.');
}

$fileName = (string) ($item['digital_file_url'] ?: '');
if ($fileName === '') {
    http_response_code(404);
    exit('Sản phẩm này chưa có file tải về.');
}

if (str_starts_with($fileName, 'http://') || str_starts_with($fileName, 'https://')) {
    header('Location: ' . $fileName);
    exit;
}

$path = dirname(__DIR__) . '/storage/materials/' . basename($fileName);
if (!is_file($path)) {
    http_response_code(404);
    exit('File tài liệu chưa tồn tại.');
}

$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$textExtensions = ['txt', 'php', 'js', 'py', 'html', 'css'];

if ($extension === 'pdf') {
    $mime = 'application/pdf';
} elseif (in_array($extension, $textExtensions, true)) {
    $mime = 'text/plain; charset=utf-8';
} else {
    $mime = 'application/octet-stream';
}

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($path) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
