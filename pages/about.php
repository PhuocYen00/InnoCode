<?php
require_once dirname(__DIR__) . '/core/init.php';

$pageTitle = 'Giới thiệu - ' . APP_NAME;
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="about-hero">
    <div class="container">
        <span class="hero-kicker">Về InnoCode</span>
        <h1 class="course-title">Khóa học lập trình thực chiến</h1>
        <p class="course-subtitle">InnoCode là website học lập trình dành cho người muốn học qua ví dụ, bài tập và dự án nhỏ. Nội dung tập trung vào những kiến thức có thể áp dụng ngay khi xây dựng website.</p>
        <div class="about-actions">
            <a class="btn btn-primary" href="<?= url('courses') ?>">Xem khóa học</a>
            <a class="btn btn-outline-primary" href="<?= url('compiler') ?>">Thử viết code</a>
        </div>
    </div>
</section>

<section class="container home-section">
    <div class="about-feature-grid">
        <article>
            <h2>Học dễ theo dõi</h2>
            <p>Khóa học được chia theo chương và bài học rõ ràng. Học viên có thể xem video, tải tài liệu và theo dõi tiến độ học của mình.</p>
        </article>
        <article>
            <h2>Thực hành ngay</h2>
            <p>Mỗi bài học có bài tập, quiz và khu vực chạy thử code để học viên kiểm tra lại kiến thức vừa học.</p>
        </article>
        <article>
            <h2>Quản lý đơn giản</h2>
            <p>Website hỗ trợ đăng ký tài khoản, mua khóa học, lưu khóa học đã sở hữu và quản lý thông tin cá nhân.</p>
        </article>
    </div>
</section>

<section class="container home-section">
    <div class="content-block">
        <h2>Mục tiêu của InnoCode</h2>
        <p class="text-muted mb-0">Giúp người học nắm chắc nền tảng lập trình web, luyện tập thường xuyên và từng bước xây dựng sản phẩm thực tế. InnoCode ưu tiên cách học gọn, rõ, dễ hiểu và có thể thực hành ngay.</p>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
