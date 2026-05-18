<?php
require_once dirname(__DIR__) . '/core/init.php';
$pageTitle = 'Trang chủ - ' . APP_NAME;
require_once dirname(__DIR__) . '/includes/header.php';

$allCourses = latest_courses(12);
$recommended = array_slice($allCourses, 0, 4);
$proCourses = array_slice($allCourses, 0, 8);
$freeCourses = array_slice($allCourses, 2, 8);
$otherCourses = array_reverse(array_slice($allCourses, 0, 8));
?>

<section class="home-hero">
    <div class="container">
        <div class="hero-banner">
            <div>
                <span class="hero-kicker">Học lập trình để đi làm</span>
                <h1>Khóa học lập trình web thực chiến</h1>
                <p>Học PHP, MySQL, JavaScript, React và Laravel qua dự án thật. Xem trailer trước, đăng ký xong là vào học theo từng chương.</p>
                <a class="btn btn-light text-primary fw-semibold" href="<?= url('courses') ?>">Khám phá khóa học</a>
            </div>
            <div class="hero-code">
                <span>&lt;?php</span>
                <strong>build()</strong>
                <small>HTML · CSS · JS · PHP · MySQL</small>
            </div>
        </div>
    </div>
</section>

<section class="container home-section">
    <div class="section-title">
        <h2>Dành cho bạn</h2>
        <a href="<?= url('courses') ?>">Xem tất cả</a>
    </div>
    <div class="course-grid course-grid-4">
        <?php foreach ($recommended as $course): ?>
            <?php include dirname(__DIR__) . '/includes/course-card.php'; ?>
        <?php endforeach; ?>
    </div>
</section>

<section class="container home-section">
    <div class="section-title">
        <h2>Khóa học Pro</h2>
        <span>Được nhiều học viên chọn</span>
    </div>
    <div class="course-grid course-grid-4">
        <?php foreach ($proCourses as $course): ?>
            <?php include dirname(__DIR__) . '/includes/course-card.php'; ?>
        <?php endforeach; ?>
    </div>
</section>

<section class="container home-section">
    <div class="section-title">
        <h2>Khóa học Free</h2>
        <span>Bắt đầu ngay hôm nay</span>
    </div>
    <div class="course-grid course-grid-4">
        <?php foreach ($freeCourses as $course): ?>
            <?php include dirname(__DIR__) . '/includes/course-card.php'; ?>
        <?php endforeach; ?>
    </div>
</section>

<section class="container home-section">
    <div class="section-title">
        <h2>Khóa học khác</h2>
        <a href="<?= url('courses') ?>">Tải thêm</a>
    </div>
    <div class="course-grid course-grid-4">
        <?php foreach ($otherCourses as $course): ?>
            <?php include dirname(__DIR__) . '/includes/course-card.php'; ?>
        <?php endforeach; ?>
    </div>
</section>

<section class="container home-section">
    <div class="section-title">
        <h2>Video nổi bật</h2>
        <a href="<?= url('courses') ?>">Xem tất cả</a>
    </div>
    <div class="video-grid">
        <?php foreach (array_slice($allCourses, 0, 4) as $course): ?>
            <a class="video-card" href="<?= url('course') ?>&id=<?= (int) $course['id'] ?>">
                <img src="<?= e($course['image_url']) ?>" alt="<?= e($course['title']) ?>">
                <span>▶</span>
                <strong><?= e($course['title']) ?></strong>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>

