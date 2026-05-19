<?php
$pageTitle = 'Thống kê doanh thu';
require_once __DIR__ . '/includes/header.php';

$summary = [
    'today' => (float) db()->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'paid' AND DATE(paid_at) = CURDATE()")->fetchColumn(),
    'month' => (float) db()->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'paid' AND YEAR(paid_at) = YEAR(CURDATE()) AND MONTH(paid_at) = MONTH(CURDATE())")->fetchColumn(),
    'year' => (float) db()->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'paid' AND YEAR(paid_at) = YEAR(CURDATE())")->fetchColumn(),
    'orders' => (int) db()->query("SELECT COUNT(*) FROM orders WHERE status = 'paid'")->fetchColumn(),
];

$daily = db()->query("SELECT DATE(COALESCE(paid_at, created_at)) AS label, SUM(total_amount) AS total
    FROM orders WHERE status = 'paid'
    GROUP BY DATE(COALESCE(paid_at, created_at))
    ORDER BY label DESC LIMIT 14")->fetchAll();
$monthly = db()->query("SELECT DATE_FORMAT(COALESCE(paid_at, created_at), '%Y-%m') AS label, SUM(total_amount) AS total
    FROM orders WHERE status = 'paid'
    GROUP BY DATE_FORMAT(COALESCE(paid_at, created_at), '%Y-%m')
    ORDER BY label DESC LIMIT 12")->fetchAll();
$yearly = db()->query("SELECT YEAR(COALESCE(paid_at, created_at)) AS label, SUM(total_amount) AS total
    FROM orders WHERE status = 'paid'
    GROUP BY YEAR(COALESCE(paid_at, created_at))
    ORDER BY label DESC LIMIT 5")->fetchAll();

$daily = array_reverse($daily);
$monthly = array_reverse($monthly);
$yearly = array_reverse($yearly);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 mb-1">Thống kê doanh thu</h1>
        <p class="text-muted mb-0">Theo dõi doanh thu đã thanh toán theo ngày, tháng và năm.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= APP_URL ?>/admin/orders.php">Xem đơn hàng</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="bg-white rounded-2 p-4 shadow-sm"><span class="text-muted">Hôm nay</span><div class="h4 mb-0"><?= money($summary['today']) ?></div></div></div>
    <div class="col-md-3"><div class="bg-white rounded-2 p-4 shadow-sm"><span class="text-muted">Tháng này</span><div class="h4 mb-0"><?= money($summary['month']) ?></div></div></div>
    <div class="col-md-3"><div class="bg-white rounded-2 p-4 shadow-sm"><span class="text-muted">Năm nay</span><div class="h4 mb-0"><?= money($summary['year']) ?></div></div></div>
    <div class="col-md-3"><div class="bg-white rounded-2 p-4 shadow-sm"><span class="text-muted">Đơn paid</span><div class="h4 mb-0"><?= $summary['orders'] ?></div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="bg-white rounded-2 p-4 shadow-sm">
            <h2 class="h5">Doanh thu 14 ngày gần nhất</h2>
            <canvas id="dailyRevenueChart" height="120"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="bg-white rounded-2 p-4 shadow-sm">
            <h2 class="h5">Theo năm</h2>
            <canvas id="yearRevenueChart" height="240"></canvas>
        </div>
    </div>
    <div class="col-12">
        <div class="bg-white rounded-2 p-4 shadow-sm">
            <h2 class="h5">Theo tháng</h2>
            <canvas id="monthRevenueChart" height="90"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const moneyTick = value => new Intl.NumberFormat('vi-VN').format(value) + 'đ';
new Chart(document.getElementById('dailyRevenueChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($daily, 'label')) ?>,
        datasets: [{ label: 'Doanh thu', data: <?= json_encode(array_map('floatval', array_column($daily, 'total'))) ?>, backgroundColor: '#2563eb' }]
    },
    options: { scales: { y: { ticks: { callback: moneyTick } } } }
});
new Chart(document.getElementById('monthRevenueChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($monthly, 'label')) ?>,
        datasets: [{ label: 'Doanh thu', data: <?= json_encode(array_map('floatval', array_column($monthly, 'total'))) ?>, borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,.12)', fill: true, tension: .3 }]
    },
    options: { scales: { y: { ticks: { callback: moneyTick } } } }
});
new Chart(document.getElementById('yearRevenueChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($yearly, 'label')) ?>,
        datasets: [{ data: <?= json_encode(array_map('floatval', array_column($yearly, 'total'))) ?>, backgroundColor: ['#2563eb', '#16a34a', '#f97316', '#ef4444', '#8b5cf6'] }]
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

