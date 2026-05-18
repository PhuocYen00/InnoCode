<?php
require_once dirname(__DIR__) . '/core/init.php';
$pageTitle = 'Chi tiết khóa học - ' . APP_NAME;
require_once dirname(__DIR__) . '/includes/header.php';

$course = find_course((int) ($_GET['id'] ?? 0));

if (!$course) {
    http_response_code(404);
    flash('error', 'Không tìm thấy khóa học.');
    redirect('courses.php');
}

$currentCourse = $course;
$sections = course_sections($course);
$lessonsCount = course_lessons_count($sections);
$relatedCourses = array_filter(latest_courses(6), static fn (array $item): bool => (int) $item['id'] !== (int) $course['id']);
?>

<section class="course-detail-hero">
    <div class="container">
        <div class="small text-primary fw-semibold mb-2">InnoCode / <?= e($course['category_name']) ?></div>
        <h1 class="course-title"><?= e($course['title']) ?></h1>
        <p class="course-subtitle"><?= e($course['description']) ?></p>
        <div class="course-meta">
            <span>★ 4.9 (225 đánh giá)</span>
            <span><?= number_format($lessonsCount * 1587, 0, ',', '.') ?> học viên</span>
            <span><?= count($sections) ?> chương</span>
            <span><?= $lessonsCount ?> bài học</span>
        </div>
    </div>
</section>

<section class="container course-detail-layout">
    <aside class="course-trailer">
        <div class="video-frame">
            <iframe src="<?= e(trailer_embed_url($course)) ?>" title="Trailer <?= e($course['title']) ?>" allowfullscreen></iframe>
        </div>
        <div class="trailer-note">
            <strong>Video giới thiệu khóa học</strong>
            <p>Preview lộ trình, sản phẩm cuối khóa và những gì bạn sẽ nhận được sau khi đăng ký.</p>
        </div>
    </aside>

    <article class="course-main-content">
        <section class="content-block">
            <h2>Bạn sẽ học được gì?</h2>
            <div class="outcome-grid">
                <?php foreach (course_outcomes($course) as $outcome): ?>
                    <div class="outcome-item">
                        <span>✓</span>
                        <p><?= e($outcome) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="content-block">
            <div class="section-heading">
                <div>
                    <h2>Nội dung khóa học</h2>
                    <p><?= count($sections) ?> chương · <?= $lessonsCount ?> bài học · <?= e($course['duration_hours']) ?> giờ học</p>
                </div>
                <a href="<?= url('learn') ?>&id=<?= (int) $course['id'] ?>">Xem sau khi mua</a>
            </div>

            <div class="curriculum">
                <?php foreach ($sections as $sectionIndex => $section): ?>
                    <details <?= $sectionIndex === 0 ? 'open' : '' ?>>
                        <summary>
                            <span><?= e($section['title']) ?></span>
                            <small><?= count($section['lessons']) ?> bài học</small>
                        </summary>
                        <?php foreach ($section['lessons'] as $lessonIndex => $lesson): ?>
                            <div class="lesson-row">
                                <span><?= $sectionIndex === 0 && $lessonIndex === 0 ? '▶' : '●' ?> <?= e($lesson['title']) ?></span>
                                <small><?= e($lesson['duration']) ?></small>
                            </div>
                        <?php endforeach; ?>
                    </details>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="content-block">
            <h2>Yêu cầu</h2>
            <ul class="requirement-list">
                <?php foreach (course_requirements($course) as $requirement): ?>
                    <li><?= e($requirement) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="content-block">
            <h2>Khóa học liên quan</h2>
            <div class="row g-4">
                <?php foreach (array_slice($relatedCourses, 0, 3) as $relatedCourse): ?>
                    <div class="col-md-4">
                        <?php $course = $relatedCourse; ?>
                        <?php include dirname(__DIR__) . '/includes/course-card.php'; ?>
                    </div>
                <?php endforeach; ?>
                <?php $course = $currentCourse; ?>
            </div>
        </section>
    </article>

    <aside class="course-buy-card">
        <div class="video-thumb">
            <img src="<?= e($course['image_url']) ?>" alt="<?= e($course['title']) ?>">
            <span>▶</span>
        </div>
        <div class="buy-body">
            <span class="small text-muted">Chi phí khóa học</span>
            <div class="buy-price"><?= is_free_course($course) ? 'Miễn phí' : money((float) $course['price']) ?></div>
            <?php if (has_purchased_course((int) $course['id'])): ?>
                <a class="btn btn-success w-100" href="<?= url('learn') ?>&id=<?= (int) $course['id'] ?>">Vào học ngay</a>
            <?php else: ?>
                <form method="post" action="<?= url('purchase_course') ?>">
                    <input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>">
                    <button class="btn btn-primary w-100" type="submit">Đăng ký học</button>
                </form>
            <?php endif; ?>
            <ul class="buy-features">
                <li><?= $lessonsCount ?> bài học chia theo chương</li>
                <li>Học mọi lúc trên máy tính</li>
                <li>Source code và tài liệu đi kèm</li>
                <li>Cập nhật nội dung miễn phí</li>
            </ul>
        </div>
    </aside>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>


