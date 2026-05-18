<?php
require_once dirname(__DIR__) . '/core/init.php';
require_login();
$pageTitle = 'Học khóa học - ' . APP_NAME;
require_once dirname(__DIR__) . '/includes/header.php';

$course = find_course((int) ($_GET['id'] ?? 0));

if (!$course) {
    flash('error', 'Không tìm thấy khóa học.');
    redirect('courses.php');
}

if (!has_purchased_course((int) $course['id'])) {
    flash('error', 'Vui lòng đăng ký khóa học trước khi xem video bài giảng.');
    redirect('course.php?id=' . (int) $course['id']);
}

$sections = course_sections($course);
$flatLessons = [];
foreach ($sections as $sectionIndex => $section) {
    foreach ($section['lessons'] as $lesson) {
        $flatLessons[] = [
            'section' => $section['title'],
            'title' => $lesson['title'],
            'duration' => $lesson['duration'],
            'section_index' => $sectionIndex,
        ];
    }
}

$lessonIndex = max(0, min((int) ($_GET['lesson'] ?? 0), count($flatLessons) - 1));
$currentLesson = $flatLessons[$lessonIndex];
?>

<section class="learning-shell">
    <div class="learning-video">
        <div class="video-frame learning-frame">
            <iframe src="<?= e(lesson_embed_url($course, $lessonIndex)) ?>" title="<?= e($currentLesson['title']) ?>" allowfullscreen></iframe>
        </div>
        <div class="learning-info">
            <span class="badge badge-soft"><?= e($currentLesson['section']) ?></span>
            <h1><?= e($currentLesson['title']) ?></h1>
            <p><?= e($course['title']) ?> · <?= e($currentLesson['duration']) ?></p>
        </div>
    </div>

    <aside class="learning-sidebar">
        <div class="learning-sidebar-head">
            <h2><?= e($course['title']) ?></h2>
            <a href="<?= url('course') ?>&id=<?= (int) $course['id'] ?>">Chi tiết</a>
        </div>
        <?php $counter = 0; ?>
        <?php foreach ($sections as $section): ?>
            <div class="learn-section">
                <h3><?= e($section['title']) ?></h3>
                <?php foreach ($section['lessons'] as $lesson): ?>
                    <a class="learn-lesson <?= $counter === $lessonIndex ? 'active' : '' ?>" href="<?= url('learn') ?>&id=<?= (int) $course['id'] ?>&lesson=<?= $counter ?>">
                        <span><?= e($lesson['title']) ?></span>
                        <small><?= e($lesson['duration']) ?></small>
                    </a>
                    <?php $counter++; ?>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </aside>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>


