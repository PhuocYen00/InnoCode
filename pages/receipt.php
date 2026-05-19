<?php
require_once dirname(__DIR__) . '/core/init.php';
require_login();

$orderId = (int) ($_GET['id'] ?? 0);
$order = find_order($orderId);

if (!$order || (int) $order['user_id'] !== (int) current_user()['id']) {
    flash('error', 'Không tìm thấy biên lai.');
    redirect('my_courses.php');
}

if ($order['status'] !== 'paid') {
    flash('error', 'Chỉ đơn hàng đã thanh toán mới có biên lai.');
    redirect('payment_success.php?id=' . $orderId);
}

$items = order_items($orderId);
$pageTitle = 'Biên lai #' . $orderId . ' - ' . APP_NAME;
$paymentName = payment_methods()[$order['payment_method']] ?? $order['payment_method'];
$subtotal = array_sum(array_map(static fn (array $item): float => (float) $item['price'] * (int) $item['quantity'], $items));
$discount = (float) ($order['discount_amount'] ?? 0);
$total = (float) $order['total_amount'];
$paidSubtotal = $total + $discount;
$deferredTotal = array_sum(array_map(static fn (array $item): float => (($item['payment_status'] ?? '') === 'pay_later') ? (float) $item['price'] * (int) $item['quantity'] : 0, $items));
$couponCode = (string) ($order['coupon_code'] ?? '');
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background: #f3f4f6;
            color: #111827;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 32px;
        }

        .receipt {
            background: #fff;
            border: 1px solid #e5e7eb;
            margin: 0 auto;
            max-width: 760px;
            padding: 34px 38px;
        }

        .receipt-head {
            align-items: center;
            border-bottom: 2px solid #111827;
            display: flex;
            justify-content: space-between;
            margin-bottom: 28px;
            padding-bottom: 18px;
        }

        .brand {
            align-items: center;
            display: flex;
            gap: 12px;
        }

        .brand .receipt-logo {
            height: 68px;
            object-fit: contain;
            width: 150px;
        }

        .brand strong {
            display: block;
            font-size: 22px;
        }

        .brand small,
        .muted {
            color: #6b7280;
        }

        h1 {
            font-size: 26px;
            margin: 0 0 6px;
            text-transform: uppercase;
        }

        .receipt-title {
            text-align: right;
        }

        .info-grid {
            display: grid;
            gap: 24px;
            grid-template-columns: 1fr 1fr;
            margin-bottom: 28px;
        }

        .info-box h2 {
            font-size: 14px;
            margin: 0 0 8px;
            text-transform: uppercase;
        }

        .info-box p {
            line-height: 1.55;
            margin: 0;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border-bottom: 1px solid #e5e7eb;
            padding: 12px 8px;
            text-align: left;
        }

        th {
            background: #f9fafb;
            font-size: 13px;
            text-transform: uppercase;
        }

        .text-center {
            text-align: center;
        }

        .text-end {
            text-align: right;
        }

        .summary {
            margin-left: auto;
            margin-top: 22px;
            max-width: 330px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
        }

        .summary-row.discount {
            color: #16a34a;
        }

        .summary-row.total {
            border-top: 1px solid #e5e7eb;
            font-size: 22px;
            font-weight: 800;
            margin-top: 8px;
            padding-top: 12px;
        }

        .summary-row.total span:last-child {
            color: #ef4444;
        }

        .note {
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 13px;
            margin-top: 28px;
            padding-top: 14px;
        }

        .actions {
            margin: 0 auto 14px;
            max-width: 760px;
            text-align: right;
        }

        .actions button {
            background: #2563eb;
            border: 0;
            border-radius: 6px;
            color: #fff;
            cursor: pointer;
            font-weight: 700;
            padding: 10px 16px;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .actions {
                display: none;
            }

            .receipt {
                border: 0;
                max-width: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
<div class="actions">
    <button onclick="window.print()">In / Lưu PDF</button>
</div>

<main class="receipt">
    <header class="receipt-head">
        <div class="brand">
            <img class="receipt-logo" src="<?= APP_URL ?>/assets/images/innocode.jpg" alt="<?= e(APP_NAME) ?>">
            <div>
                <strong><?= e(APP_NAME) ?></strong>
                <small>Website khóa học lập trình</small>
            </div>
        </div>
        <div class="receipt-title">
            <h1>Biên Lai Thanh Toán</h1>
            <div class="muted">Mã đơn: #<?= $orderId ?> · <?= e(strtoupper((string) $order['status'])) ?></div>
        </div>
    </header>

    <section class="info-grid">
        <div class="info-box">
            <h2>Khách hàng</h2>
            <p>
                <?= e($order['customer_name']) ?><br>
                <?= e($order['customer_email']) ?><br>
                <?= e($order['customer_phone']) ?>
            </p>
        </div>
        <div class="info-box">
            <h2>Thông tin thanh toán</h2>
            <p>
                Phương thức: <?= e($paymentName) ?><br>
                Nội dung: <?= e($order['payment_code']) ?><br>
                Thời gian: <?= e($order['paid_at'] ?: $order['created_at']) ?>
            </p>
        </div>
    </section>

    <table>
        <thead>
        <tr>
            <th>Nội dung</th>
            <th class="text-center">Số lượng</th>
            <th class="text-end">Đơn giá</th>
            <th class="text-end">Thành tiền</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= e($item['title']) ?></td>
                <td class="text-center"><?= (int) $item['quantity'] ?></td>
                <td class="text-end"><?= money((float) $item['price']) ?></td>
                <td class="text-end"><?= money((float) $item['price'] * (int) $item['quantity']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="summary">
        <div class="summary-row">
            <span>Tạm tính đã thanh toán</span>
            <strong><?= money($paidSubtotal) ?></strong>
        </div>
        <?php if ($deferredTotal > 0): ?>
            <div class="summary-row">
                <span>Sách/quà tính sau</span>
                <strong><?= money($deferredTotal) ?></strong>
            </div>
        <?php endif; ?>
        <?php if ($discount > 0): ?>
            <div class="summary-row discount">
                <span>Giảm giá<?= $couponCode !== '' ? ' (' . e($couponCode) . ')' : '' ?></span>
                <strong>-<?= money($discount) ?></strong>
            </div>
        <?php endif; ?>
        <div class="summary-row total">
            <span>Tổng đã thanh toán</span>
            <span><?= money($total) ?></span>
        </div>
    </div>

    <p class="note">Biên lai được tạo tự động bởi hệ thống <?= e(APP_NAME) ?>. Vui lòng lưu lại để đối chiếu khi cần.</p>
</main>
</body>
</html>
