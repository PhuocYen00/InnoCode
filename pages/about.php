<?php
require_once dirname(__DIR__) . '/core/init.php';

$pageTitle = 'Giới thiệu - ' . APP_NAME;
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="about-hero">
    <div class="container about-grid">
        <div>
            <span class="hero-kicker">Về InnoCode</span>
            <h1>Nền tảng học lập trình web theo dự án thực tế</h1>
            <p>InnoCode giúp học viên học từ nền tảng đến triển khai sản phẩm: xem bài giảng, làm quiz, ghi chú, chạy code trực tiếp, tải học liệu và đặt câu hỏi trong từng bài học.</p>
            <div class="about-actions">
                <a class="btn btn-primary" href="<?= url('courses') ?>">Khám phá khóa học</a>
                <a class="btn btn-outline-primary" href="<?= url('compiler') ?>">Thử compiler</a>
            </div>
        </div>
        <div class="about-card">
            <strong>Hệ thống hiện có</strong>
            <ul>
                <li>Khóa học chia theo chương, bài học và tiến độ mở khóa.</li>
                <li>Quiz trắc nghiệm, tự luận và lưu lịch sử làm bài.</li>
                <li>Compiler hỗ trợ PHP, Python, JavaScript, C, C++ và Java.</li>
                <li>Giỏ hàng, coupon, thanh toán PayOS và quản lý đơn hàng.</li>
            </ul>
        </div>
    </div>
</section>

<section class="container home-section">
    <div class="section-title">
        <h2>Cách InnoCode tổ chức việc học</h2>
        <span>Học, luyện, hỏi, hoàn thiện</span>
    </div>
    <div class="value-strip-grid">
        <div><strong>Học theo lộ trình</strong><span>Nội dung được chia thành chương, bài học, học liệu và bài kiểm tra rõ ràng.</span></div>
        <div><strong>Luyện code thật</strong><span>Học viên chạy thử code ngay trong bài học, xem output và sửa lỗi nhanh hơn.</span></div>
        <div><strong>Ghi chú cá nhân</strong><span>Mỗi bài học có ghi chú riêng, tải được thành file để ôn tập.</span></div>
        <div><strong>Hỏi đáp theo bài</strong><span>Câu hỏi gắn trực tiếp với bài học để admin hoặc giảng viên phản hồi đúng ngữ cảnh.</span></div>
    </div>
</section>

<section class="container home-section">
    <div class="about-feature-grid">
        <article>
            <h2>Dành cho học viên</h2>
            <p>Học viên có thể tìm khóa học, đăng ký tài khoản, xác thực email, mua khóa học, học video, làm quiz, lưu tiến độ, tải tài liệu và theo dõi các khóa đã sở hữu.</p>
        </article>
        <article>
            <h2>Dành cho quản trị</h2>
            <p>Admin quản lý danh mục, khóa học, chương bài, học liệu, quiz, học viên, đơn hàng, coupon, sản phẩm vật lý và thống kê doanh thu theo ngày, tháng, năm.</p>
        </article>
        <article>
            <h2>Định hướng phát triển</h2>
            <p>InnoCode hướng đến trải nghiệm học gọn, rõ và thực dụng: ít lý thuyết rời rạc, nhiều bài thực hành, nhiều phản hồi và dữ liệu học tập có thể theo dõi được.</p>
        </article>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
