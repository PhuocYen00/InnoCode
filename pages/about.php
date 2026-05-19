<?php
require_once dirname(__DIR__) . '/core/init.php';

$pageTitle = 'Giới thiệu - ' . APP_NAME;
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="about-hero">
    <div class="container about-grid">
        <div>
            <span class="hero-kicker">Về InnoCode</span>
            <h1>Website học lập trình web thực chiến</h1>
            <p>InnoCode được xây dựng cho học viên muốn học PHP, MySQL, JavaScript và quy trình làm website thật: xem bài giảng, làm quiz, luyện code, tải tài liệu và hỏi đáp cùng mentor.</p>
            <a class="btn btn-primary" href="<?= url('courses') ?>">Khám phá khóa học</a>
        </div>
        <div class="about-card">
            <strong>Trọng tâm hệ thống</strong>
            <ul>
                <li>Khóa học theo chương, bài học và tiến độ học.</li>
                <li>Giỏ hàng, coupon, PayOS/VietQR và biên lai.</li>
                <li>Compiler chạy thật cho các runtime có trên máy.</li>
                <li>Admin quản lý khóa học, quiz, đơn hàng và doanh thu.</li>
            </ul>
        </div>
    </div>
</section>

<section class="container home-section">
    <div class="value-strip-grid">
        <div><strong>Học theo dự án</strong><span>Từ kiến thức nền đến website hoàn chỉnh</span></div>
        <div><strong>Luyện tập liên tục</strong><span>Quiz, bài nộp, ghi chú và compiler</span></div>
        <div><strong>Thanh toán rõ ràng</strong><span>Khóa học online, sách/quà có giao hàng</span></div>
        <div><strong>Quản trị đầy đủ</strong><span>Theo dõi học viên, đơn hàng và doanh thu</span></div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
