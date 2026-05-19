<?php
require_once dirname(__DIR__) . '/core/init.php';
$pageTitle = 'Trang chủ - ' . APP_NAME;
require_once dirname(__DIR__) . '/includes/header.php';

$courses = latest_courses(8);
$popularCourses = array_slice($courses, 0, 4);
$learningPaths = [
    ['title' => 'Frontend Web', 'text' => 'HTML, CSS, JavaScript, React và tư duy xây dựng giao diện thực tế.', 'meta' => '4 dự án mẫu', 'tone' => 'blue'],
    ['title' => 'Backend PHP/MySQL', 'text' => 'PHP, MySQL, session, upload file, thanh toán và quản trị dữ liệu.', 'meta' => '5 module thực chiến', 'tone' => 'green'],
    ['title' => 'Full-stack Mastery', 'text' => 'Kết hợp frontend, backend, database, deploy và quy trình sản phẩm hoàn chỉnh.', 'meta' => '1 website hoàn chỉnh', 'tone' => 'orange'],
];
$products = array_slice(all_physical_products(), 0, 4);
?>

<section class="home-hero">
    <div class="container">
        <div class="hero-banner">
            <div>
                <span class="hero-kicker">Học lập trình để đi làm</span>
                <h1>Khóa học lập trình web thực chiến</h1>
                <p>Học PHP, MySQL, JavaScript, React và Laravel qua dự án thật. Có bài tập, quiz, compiler, tài liệu và lộ trình học theo chương.</p>
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

<section class="container home-value-wrap">
    <div class="value-strip-grid">
        <div><strong>Chấm code tự động</strong><span>Compiler nhiều ngôn ngữ</span></div>
        <div><strong>Học liệu đa dạng</strong><span>PDF, slide, source code</span></div>
        <div><strong>Mở khóa trọn đời</strong><span>Học lại bất cứ lúc nào</span></div>
        <div><strong>Hỏi đáp Mentor</strong><span>Gửi câu hỏi theo bài học</span></div>
    </div>
</section>

<section class="container home-section">
    <div class="section-title">
        <h2>Lộ trình học tập gợi ý</h2>
        <span>Chọn hướng học phù hợp với mục tiêu của bạn</span>
    </div>
    <div class="path-grid">
        <?php foreach ($learningPaths as $path): ?>
            <article class="path-card path-<?= e($path['tone']) ?>">
                <span><?= e($path['meta']) ?></span>
                <h3><?= e($path['title']) ?></h3>
                <p><?= e($path['text']) ?></p>
                <a class="btn btn-outline-primary btn-sm" href="<?= url('courses') ?>">Xem lộ trình</a>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="container home-section">
    <div class="section-title">
        <h2>Khóa học phổ biến</h2>
        <a href="<?= url('courses') ?>">Xem tất cả</a>
    </div>
    <div class="course-grid course-grid-4">
        <?php foreach ($popularCourses as $course): ?>
            <?php include dirname(__DIR__) . '/includes/course-card.php'; ?>
        <?php endforeach; ?>
    </div>
</section>

<section class="container home-section">
    <div class="compiler-preview">
        <div>
            <span class="hero-kicker">Live compiler</span>
            <h2>Chạy thử PHP, Python, JavaScript, C, C++ và Java</h2>
            <p>Học viên có thể thử code ngay trong bài học, xem output thật và sửa lỗi theo phản hồi của trình biên dịch.</p>
            <a class="btn btn-primary" href="<?= url('compiler') ?>">Mở trình biên dịch</a>
        </div>
        <pre><code>&lt;?php
$name = "InnoCode";
echo "Hello " . $name;</code></pre>
    </div>
</section>

<section class="container home-section">
    <div class="section-title">
        <h2>Sách, giáo trình & quà lưu niệm</h2>
        <span>Tài liệu giấy, PDF và vật phẩm học viên</span>
    </div>
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <article class="product-card">
                <img src="<?= e($product['image_url'] ?: 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=900&q=80') ?>" alt="<?= e($product['name']) ?>">
                <div>
                    <span><?= e($product['product_type']) ?></span>
                    <h3><?= e($product['name']) ?></h3>
                    <p><?= e(excerpt((string) $product['description'], 80)) ?></p>
                    <strong><?= money((float) $product['price']) ?></strong>
                    <form method="post" action="<?= url('cart') ?>" class="mt-3 js-add-cart">
                        <input type="hidden" name="action" value="add_product">
                        <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                        <button class="btn btn-primary btn-sm w-100" type="submit">Thêm vào giỏ hàng</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="container home-section">
    <div class="section-title">
        <h2>Video nổi bật</h2>
        <a href="<?= url('courses') ?>">Xem tất cả</a>
    </div>
    <div class="video-grid">
        <?php foreach (array_slice($courses, 0, 4) as $course): ?>
            <a class="video-card" href="<?= url('course') ?>&id=<?= (int) $course['id'] ?>">
                <img src="<?= e($course['image_url']) ?>" alt="<?= e($course['title']) ?>">
                <span>▶</span>
                <strong><?= e($course['title']) ?></strong>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
