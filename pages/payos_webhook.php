<?php
require_once dirname(__DIR__) . '/core/init.php';

header('Content-Type: application/json; charset=utf-8');

$payload = json_decode(file_get_contents('php://input') ?: '', true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$data = $payload['data'] ?? [];
$signature = (string) ($payload['signature'] ?? '');

if (!is_array($data) || $signature === '' || !payos_verify_webhook($data, $signature)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid signature']);
    exit;
}

$orderCode = (int) ($data['orderCode'] ?? 0);
$statusCode = (string) ($data['code'] ?? $payload['code'] ?? '');
$desc = strtolower((string) ($data['desc'] ?? $payload['desc'] ?? ''));
$isPaid = $statusCode === '00' || str_contains($desc, 'success') || str_contains($desc, 'thành công');
$order = $orderCode > 0 ? find_order_by_payos_code($orderCode) : null;

if ($isPaid && $order) {
    complete_order((int) $order['id']);
}

echo json_encode(['success' => true]);
