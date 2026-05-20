<?php
$pageTitle = 'Thống kê doanh thu';
require_once __DIR__ . '/includes/header.php';

$from = trim((string) ($_GET['from'] ?? ''));
$to = trim((string) ($_GET['to'] ?? ''));
$month = trim((string) ($_GET['month'] ?? ''));
$year = trim((string) ($_GET['year'] ?? ''));

$currentYear = (int) date('Y');
if ($month !== '' && preg_match('/^(\d{4})-(\d{2})$/', $month, $matches)) {
    $year = $matches[1];
    $month = (string) (int) $matches[2];
}

$monthNumber = filter_var($month, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 12]]);
$yearNumber = filter_var($year, FILTER_VALIDATE_INT, ['options' => ['min_range' => 2000, 'max_range' => 2100]]);
if ($monthNumber !== false) {
    $yearNumber = $yearNumber !== false ? $yearNumber : $currentYear;
    $from = sprintf('%04d-%02d-01', $yearNumber, $monthNumber);
    $to = date('Y-m-t', strtotime($from));
    $month = (string) $monthNumber;
    $year = (string) $yearNumber;
} elseif ($yearNumber !== false) {
    $year = (string) $yearNumber;
    $from = $year . '-01-01';
    $to = $year . '-12-31';
}

$dateExpr = 'DATE(COALESCE(paid_at, created_at))';
$whereParts = ["status = 'paid'"];
$params = [];
if ($from !== '') {
    $whereParts[] = $dateExpr . ' >= ?';
    $params[] = $from;
}
if ($to !== '') {
    $whereParts[] = $dateExpr . ' <= ?';
    $params[] = $to;
}
$whereSql = ' WHERE ' . implode(' AND ', $whereParts);

$summaryStmt = db()->prepare("SELECT COALESCE(SUM(total_amount), 0) AS revenue, COUNT(*) AS orders, COALESCE(AVG(total_amount), 0) AS average_order
    FROM orders" . $whereSql);
$summaryStmt->execute($params);
$summary = $summaryStmt->fetch();

$dailyStmt = db()->prepare("SELECT " . $dateExpr . " AS label, SUM(total_amount) AS total
    FROM orders" . $whereSql . "
    GROUP BY " . $dateExpr . "
    ORDER BY label DESC LIMIT 31");
$dailyStmt->execute($params);
$daily = $dailyStmt->fetchAll();

$monthlyStmt = db()->prepare("SELECT DATE_FORMAT(COALESCE(paid_at, created_at), '%Y-%m') AS label, SUM(total_amount) AS total
    FROM orders" . $whereSql . "
    GROUP BY DATE_FORMAT(COALESCE(paid_at, created_at), '%Y-%m')
    ORDER BY label DESC LIMIT 12");
$monthlyStmt->execute($params);
$monthly = $monthlyStmt->fetchAll();

$yearlyStmt = db()->prepare("SELECT YEAR(COALESCE(paid_at, created_at)) AS label, SUM(total_amount) AS total
    FROM orders" . $whereSql . "
    GROUP BY YEAR(COALESCE(paid_at, created_at))
    ORDER BY label DESC LIMIT 5");
$yearlyStmt->execute($params);
$yearly = $yearlyStmt->fetchAll();

$filterLabel = $from || $to ? trim(($from ?: '...') . ' - ' . ($to ?: '...')) : 'Tất cả thời gian';

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

<form class="bg-white rounded-2 p-4 shadow-sm mb-4" method="get">
    <div class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Từ ngày</label>
            <input class="form-control" type="date" name="from" value="<?= e($from) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Đến ngày</label>
            <input class="form-control" type="date" name="to" value="<?= e($to) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Theo tháng</label>
            <input class="form-control" type="number" min="1" max="12" name="month" value="<?= e($month) ?>" placeholder="1-12">
        </div>
        <div class="col-md-2">
            <label class="form-label">Theo năm</label>
            <input class="form-control" type="number" min="2000" max="2100" name="year" value="<?= e($year) ?>" placeholder="2026">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-primary flex-fill" type="submit">Lọc</button>
            <a class="btn btn-outline-secondary" href="<?= APP_URL ?>/admin/revenue.php">Xóa</a>
        </div>
    </div>
</form>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="bg-white rounded-2 p-4 shadow-sm"><span class="text-muted">Khoảng lọc</span><div class="h6 mb-0"><?= e($filterLabel) ?></div></div></div>
    <div class="col-md-3"><div class="bg-white rounded-2 p-4 shadow-sm"><span class="text-muted">Doanh thu</span><div class="h4 mb-0"><?= money((float) $summary['revenue']) ?></div></div></div>
    <div class="col-md-3"><div class="bg-white rounded-2 p-4 shadow-sm"><span class="text-muted">Đơn paid</span><div class="h4 mb-0"><?= (int) $summary['orders'] ?></div></div></div>
    <div class="col-md-3"><div class="bg-white rounded-2 p-4 shadow-sm"><span class="text-muted">Trung bình/đơn</span><div class="h4 mb-0"><?= money((float) $summary['average_order']) ?></div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="bg-white rounded-2 p-4 shadow-sm">
            <h2 class="h5">Doanh thu theo ngày</h2>
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
