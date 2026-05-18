<div class="card course-card">
    <a href="<?= url('course') ?>&id=<?= (int) $course['id'] ?>" class="course-image-link">
        <img src="<?= e($course['image_url']) ?>" class="card-img-top" alt="<?= e($course['title']) ?>">
        <span class="course-level"><?= e($course['level']) ?></span>
    </a>
    <div class="card-body">
        <span class="course-category"><?= e($course['category_name'] ?? 'Lập trình') ?></span>
        <h3 class="course-card-title">
            <a href="<?= url('course') ?>&id=<?= (int) $course['id'] ?>"><?= e($course['title']) ?></a>
        </h3>
        <div class="course-rating">★★★★★ <strong>4.9</strong> (<?= number_format(((int) $course['id'] * 83) + 117) ?>)</div>
        <div class="course-card-bottom">
            <span class="price"><?= is_free_course($course) ? 'Miễn phí' : money((float) $course['price']) ?></span>
            <span><?= e($course['duration_hours']) ?>h</span>
        </div>
    </div>
</div>


