<?php
require_once dirname(__DIR__) . '/core/init.php';
$pageTitle = 'Khóa học - ' . APP_NAME;
require_once dirname(__DIR__) . '/includes/header.php';

$categoryId = isset($_GET['category']) ? (int) $_GET['category'] : null;
$keyword = trim($_GET['q'] ?? '');
$categories = all_categories();
$courses = courses_by_filter($categoryId, $keyword ?: null);
?>

<section class="container py-5">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Tất cả khóa học</h1>
            <p class="text-muted mb-0">Tìm khóa học phù hợp với mục tiêu lập trình của bạn.</p>
        </div>
        <form class="d-flex gap-2 flex-wrap" method="get">
            <input class="form-control" name="q" value="<?= e($keyword) ?>" placeholder="Tìm theo tên khóa học">
            <select class="form-select" name="category">
                <option value="">Tất cả danh mục</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>>
                        <?= e($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary" type="submit">Lọc</button>
        </form>
    </div>

    <?php if (!$courses): ?>
        <div class="alert alert-info">Chưa có khóa học phù hợp.</div>
    <?php endif; ?>

    <div class="row g-4">
        <?php foreach ($courses as $course): ?>
            <div class="col-md-6 col-lg-4">
                <?php include dirname(__DIR__) . '/includes/course-card.php'; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>


