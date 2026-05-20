<?php
$pageTitle = 'Khóa học';
require_once __DIR__ . '/includes/header.php';

$id = (int) ($_GET['id'] ?? 0);
$course = $id ? find_course($id, false) : null;
$categories = all_categories();
$returnPath = admin_return_path();
$formPath = admin_relative_url('admin/course_form.php', [
    'id' => $id ?: null,
    'return' => $returnPath,
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imageUrl = trim((string) ($_POST['image_url'] ?? ''));

    if (!empty($_FILES['image_file']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $extension = strtolower(pathinfo((string) $_FILES['image_file']['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowed, true)) {
            flash('error', 'Ảnh bìa chỉ hỗ trợ JPG, PNG, WEBP hoặc GIF.');
            redirect($formPath);
        }

        $uploadDir = dirname(__DIR__) . '/storage/uploads/courses';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = 'course_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $target = $uploadDir . '/' . $fileName;

        if (!move_uploaded_file($_FILES['image_file']['tmp_name'], $target)) {
            flash('error', 'Không thể tải ảnh bìa lên. Vui lòng thử lại.');
            redirect($formPath);
        }

        $imageUrl = APP_URL . '/storage/uploads/courses/' . $fileName;
    }

    $data = [
        'category_id' => (int) ($_POST['category_id'] ?? 0),
        'title' => trim((string) ($_POST['title'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
        'price' => (float) ($_POST['price'] ?? 0),
        'level' => trim((string) ($_POST['level'] ?? '')),
        'duration_hours' => (int) ($_POST['duration_hours'] ?? 0),
        'image_url' => $imageUrl,
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];

    if ($data['title'] === '' || $data['description'] === '' || !$data['category_id']) {
        flash('error', 'Vui lòng nhập đầy đủ thông tin bắt buộc.');
        redirect($formPath);
    }

    if ($data['image_url'] === '') {
        $data['image_url'] = 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?auto=format&fit=crop&w=1200&q=80';
    }

    if ($id) {
        $stmt = db()->prepare('UPDATE courses SET category_id = :category_id, title = :title, description = :description, price = :price, level = :level, duration_hours = :duration_hours, image_url = :image_url, is_active = :is_active WHERE id = :id');
        $data['id'] = $id;
        $stmt->execute($data);
        flash('success', 'Đã cập nhật khóa học.');
    } else {
        $stmt = db()->prepare('INSERT INTO courses (category_id, title, description, price, level, duration_hours, image_url, is_active) VALUES (:category_id, :title, :description, :price, :level, :duration_hours, :image_url, :is_active)');
        $stmt->execute($data);
        $id = (int) db()->lastInsertId();
        flash('success', 'Đã thêm khóa học. Bạn có thể thêm chương và bài học ngay.');
        redirect('admin/course_content.php?id=' . $id . '&return=' . urlencode($returnPath));
    }

    redirect($returnPath);
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0"><?= $course ? 'Sửa khóa học' : 'Thêm khóa học' ?></h1>
    <a class="btn btn-outline-secondary" href="<?= APP_URL ?>/<?= e($returnPath) ?>">Quay lại</a>
</div>

<form class="bg-white rounded-2 p-4 shadow-sm" method="post" enctype="multipart/form-data">
    <input type="hidden" name="return" value="<?= e($returnPath) ?>">
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label">Tên khóa học</label>
            <input class="form-control" name="title" value="<?= e($course['title'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Danh mục</label>
            <select class="form-select" name="category_id" required>
                <option value="">Chọn danh mục</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>" <?= (int) ($course['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>>
                        <?= e($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12">
            <label class="form-label">Mô tả</label>
            <textarea class="form-control" name="description" rows="4" required><?= e($course['description'] ?? '') ?></textarea>
        </div>
        <div class="col-md-4">
            <label class="form-label">Giá</label>
            <input class="form-control" type="number" min="0" name="price" value="<?= e($course['price'] ?? 0) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Trình độ</label>
            <input class="form-control" name="level" value="<?= e($course['level'] ?? 'Beginner') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Thời lượng giờ</label>
            <input class="form-control" type="number" min="1" name="duration_hours" value="<?= e($course['duration_hours'] ?? 20) ?>">
        </div>
        <div class="col-md-7">
            <label class="form-label">Ảnh bìa URL</label>
            <input class="form-control" name="image_url" value="<?= e($course['image_url'] ?? '') ?>" placeholder="Dán URL ảnh hoặc tải file bên cạnh">
        </div>
        <div class="col-md-5">
            <label class="form-label">Chọn tệp ảnh từ máy</label>
            <input class="form-control" type="file" name="image_file" accept="image/*">
        </div>
        <?php if (!empty($course['image_url'])): ?>
            <div class="col-12">
                <img class="admin-course-preview" src="<?= e($course['image_url']) ?>" alt="<?= e($course['title']) ?>">
            </div>
        <?php endif; ?>
        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= (int) ($course['is_active'] ?? 1) === 1 ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">Đang bán</label>
            </div>
        </div>
    </div>
    <button class="btn btn-primary mt-4" type="submit">Lưu khóa học</button>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
