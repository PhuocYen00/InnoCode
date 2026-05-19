<?php

declare(strict_types=1);

function payment_methods(): array
{
    return [
        'bank' => 'Chuyển khoản VietQR qua PayOS',
        'vnpay' => 'VNPay qua PayOS',
        'momo' => 'MoMo qua PayOS',
        'cod' => 'Thanh toán khi nhận hàng',
    ];
}

function find_order(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }

    $stmt = db()->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$id]);
    $order = $stmt->fetch();

    return $order ?: null;
}

function find_order_by_payos_code(int $orderCode): ?array
{
    $stmt = db()->prepare('SELECT * FROM orders WHERE payos_order_code = ?');
    $stmt->execute([$orderCode]);
    $order = $stmt->fetch();

    return $order ?: null;
}

function payment_qr_url(string $method, int $orderId, float $amount): ?string
{
    $content = order_payment_code($orderId);

    return 'https://img.vietqr.io/image/' . BANK_CODE . '-' . BANK_ACCOUNT_NUMBER . '-compact2.png?amount=' . (int) $amount
        . '&addInfo=' . urlencode($content)
        . '&accountName=' . urlencode(BANK_ACCOUNT_NAME);
}

function course_video_map(): array
{
    return [
        'php' => [
            'trailer' => 'https://www.youtube.com/embed/OK_JCtrrv-c',
            'lessons' => [
                'https://www.youtube.com/embed/OK_JCtrrv-c',
                'https://www.youtube.com/embed/2eebptXfEvw',
                'https://www.youtube.com/embed/7TF00hJI78Y',
            ],
        ],
        'javascript' => [
            'trailer' => 'https://www.youtube.com/embed/W6NZfCO5SIk',
            'lessons' => [
                'https://www.youtube.com/embed/W6NZfCO5SIk',
                'https://www.youtube.com/embed/PkZNo7MFNFg',
                'https://www.youtube.com/embed/hdI2bqOjy3c',
            ],
        ],
        'react' => [
            'trailer' => 'https://www.youtube.com/embed/Ke90Tje7VS0',
            'lessons' => [
                'https://www.youtube.com/embed/Ke90Tje7VS0',
                'https://www.youtube.com/embed/SqcY0GlETPk',
                'https://www.youtube.com/embed/bMknfKXIFA8',
            ],
        ],
        'laravel' => [
            'trailer' => 'https://www.youtube.com/embed/MFh0Fd7BsjE',
            'lessons' => [
                'https://www.youtube.com/embed/MFh0Fd7BsjE',
                'https://www.youtube.com/embed/376vZ1wNYPA',
                'https://www.youtube.com/embed/ImtZ5yENzgE',
            ],
        ],
        'mysql' => [
            'trailer' => 'https://www.youtube.com/embed/7S_tz1z_5bA',
            'lessons' => [
                'https://www.youtube.com/embed/7S_tz1z_5bA',
                'https://www.youtube.com/embed/HXV3zeQKqGY',
                'https://www.youtube.com/embed/9Pzj7Aj25lw',
            ],
        ],
        'html' => [
            'trailer' => 'https://www.youtube.com/embed/G3e-cpL7ofc',
            'lessons' => [
                'https://www.youtube.com/embed/G3e-cpL7ofc',
                'https://www.youtube.com/embed/qz0aGYrrlhU',
                'https://www.youtube.com/embed/OXGznpKZ_sA',
            ],
        ],
    ];
}

