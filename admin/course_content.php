<?php
$pageTitle = 'Nội dung khóa học';
require_once __DIR__ . '/includes/header.php';

$courseId = (int) ($_GET['id'] ?? $_POST['course_id'] ?? 0);
$course = find_course($courseId, false);
$returnPath = admin_return_path();

if (!$course) {
    flash('error', 'Không tìm thấy khóa học.');
    redirect('admin/courses.php');
}

function admin_material_file_name(): ?string
{
    if (!isset($_FILES['material_file']) || ($_FILES['material_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES['material_file']['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload file không thành công.');
    }

    $original = basename((string) $_FILES['material_file']['name']);
    $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $allowed = ['pdf', 'txt', 'zip', 'php', 'js', 'py', 'html', 'css', 'ppt', 'pptx'];

    if (!in_array($extension, $allowed, true)) {
        throw new RuntimeException('Định dạng file chưa được hỗ trợ.');
    }

    $targetName = date('YmdHis') . '-' . preg_replace('/[^a-zA-Z0-9._-]/', '-', $original);
    $targetDir = dirname(__DIR__) . '/storage/materials';

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    if (!move_uploaded_file($_FILES['material_file']['tmp_name'], $targetDir . '/' . $targetName)) {
        throw new RuntimeException('Không thể lưu file upload.');
    }

    return $targetName;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'add_chapter') {
            $stmt = db()->prepare('INSERT INTO course_chapters (course_id, title, sort_order) VALUES (?, ?, ?)');
            $stmt->execute([$courseId, trim((string) $_POST['title']), (int) $_POST['sort_order']]);
            flash('success', 'Đã thêm chương học.');
        }

        if ($action === 'update_chapter') {
            $stmt = db()->prepare('UPDATE course_chapters SET title = ?, sort_order = ? WHERE id = ? AND course_id = ?');
            $stmt->execute([trim((string) $_POST['title']), (int) $_POST['sort_order'], (int) $_POST['chapter_id'], $courseId]);
            flash('success', 'Đã cập nhật chương học.');
        }

        if ($action === 'delete_chapter') {
            $stmt = db()->prepare('DELETE FROM course_chapters WHERE id = ? AND course_id = ?');
            $stmt->execute([(int) $_POST['chapter_id'], $courseId]);
            flash('success', 'Đã xóa chương và các bài học bên trong.');
        }

        if ($action === 'add_lesson') {
            $stmt = db()->prepare('INSERT INTO course_lessons (chapter_id, title, video_url, theory_content, duration_minutes, is_preview, unlock_order) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                (int) $_POST['chapter_id'],
                trim((string) $_POST['title']),
                trim((string) $_POST['video_url']),
                trim((string) $_POST['theory_content']),
                (int) $_POST['duration_minutes'],
                isset($_POST['is_preview']) ? 1 : 0,
                (int) $_POST['unlock_order'],
            ]);
            flash('success', 'Đã thêm bài học.');
        }

        if ($action === 'update_lesson') {
            $stmt = db()->prepare('UPDATE course_lessons
                JOIN course_chapters ON course_chapters.id = course_lessons.chapter_id
                SET course_lessons.title = ?, course_lessons.video_url = ?, course_lessons.theory_content = ?, course_lessons.duration_minutes = ?, course_lessons.is_preview = ?, course_lessons.unlock_order = ?
                WHERE course_lessons.id = ? AND course_chapters.course_id = ?');
            $stmt->execute([
                trim((string) $_POST['title']),
                trim((string) $_POST['video_url']),
                trim((string) $_POST['theory_content']),
                (int) $_POST['duration_minutes'],
                isset($_POST['is_preview']) ? 1 : 0,
                (int) $_POST['unlock_order'],
                (int) $_POST['lesson_id'],
                $courseId,
            ]);
            flash('success', 'Đã cập nhật bài học.');
        }

        if ($action === 'delete_lesson') {
            $stmt = db()->prepare('DELETE course_lessons FROM course_lessons
                JOIN course_chapters ON course_chapters.id = course_lessons.chapter_id
                WHERE course_lessons.id = ? AND course_chapters.course_id = ?');
            $stmt->execute([(int) $_POST['lesson_id'], $courseId]);
            flash('success', 'Đã xóa bài học.');
        }

        if ($action === 'add_material' || $action === 'update_material') {
            $uploaded = admin_material_file_name();
            $fileUrl = $uploaded ?: trim((string) ($_POST['file_url'] ?? ''));
            $type = in_array($_POST['material_type'] ?? 'pdf', ['pdf', 'source_code', 'slide', 'link'], true) ? $_POST['material_type'] : 'pdf';

            if ($fileUrl === '') {
                throw new RuntimeException('Vui lòng upload file hoặc nhập tên file/URL.');
            }

            if ($action === 'add_material') {
                $stmt = db()->prepare('INSERT INTO lesson_materials (lesson_id, title, material_type, file_url) VALUES (?, ?, ?, ?)');
                $stmt->execute([(int) $_POST['lesson_id'], trim((string) $_POST['title']), $type, $fileUrl]);
                flash('success', 'Đã thêm tài liệu.');
            } else {
                $stmt = db()->prepare('UPDATE lesson_materials
                    JOIN course_lessons ON course_lessons.id = lesson_materials.lesson_id
                    JOIN course_chapters ON course_chapters.id = course_lessons.chapter_id
                    SET lesson_materials.title = ?, lesson_materials.material_type = ?, lesson_materials.file_url = ?
                    WHERE lesson_materials.id = ? AND course_chapters.course_id = ?');
                $stmt->execute([trim((string) $_POST['title']), $type, $fileUrl, (int) $_POST['material_id'], $courseId]);
                flash('success', 'Đã cập nhật tài liệu.');
            }
        }

        if ($action === 'delete_material') {
            $stmt = db()->prepare('DELETE lesson_materials FROM lesson_materials
                JOIN course_lessons ON course_lessons.id = lesson_materials.lesson_id
                JOIN course_chapters ON course_chapters.id = course_lessons.chapter_id
                WHERE lesson_materials.id = ? AND course_chapters.course_id = ?');
            $stmt->execute([(int) $_POST['material_id'], $courseId]);
            flash('success', 'Đã xóa tài liệu.');
        }
    } catch (Throwable $exception) {
        flash('error', $exception->getMessage());
    }

    redirect('admin/course_content.php?id=' . $courseId . '&return=' . urlencode($returnPath));
}

$chapterStmt = db()->prepare('SELECT * FROM course_chapters WHERE course_id = ? ORDER BY sort_order, id');
$chapterStmt->execute([$courseId]);
$chapters = $chapterStmt->fetchAll();
$lessonStmt = db()->prepare('SELECT * FROM course_lessons WHERE chapter_id = ? ORDER BY unlock_order, id');
$materialStmt = db()->prepare('SELECT * FROM lesson_materials WHERE lesson_id = ? ORDER BY id');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 mb-0">Soạn nội dung khóa học</h1>
        <p class="text-muted mb-0"><?= e($course['title']) ?></p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= APP_URL ?>/<?= e($returnPath) ?>">Quay lại</a>
</div>

<div class="alert alert-info">
    Quy trình: 1. Tạo chương học → 2. Thêm bài học vào chương → 3. Upload PDF/source/slide cho từng bài. Mỗi chương, bài học và tài liệu đều có nút sửa/xóa ngay bên dưới.
</div>

<form class="bg-white rounded-2 p-4 shadow-sm mb-4" method="post">
    <input type="hidden" name="course_id" value="<?= $courseId ?>">
    <input type="hidden" name="action" value="add_chapter">
    <div class="row g-3 align-items-end">
        <div class="col-md-7">
            <label class="form-label">Tên chương mới</label>
            <input class="form-control" name="title" placeholder="Ví dụ: Chương 1 - Nền tảng PHP" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Thứ tự</label>
            <input class="form-control" type="number" name="sort_order" value="<?= count($chapters) + 1 ?>">
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary w-100" type="submit">Thêm chương</button>
        </div>
    </div>
</form>

<?php if (!$chapters): ?>
    <div class="bg-white rounded-2 p-4 shadow-sm">
        <p class="text-muted mb-0">Chưa có chương nào. Hãy tạo chương đầu tiên để bắt đầu thêm bài học.</p>
    </div>
<?php endif; ?>

<?php foreach ($chapters as $chapter): ?>
    <?php
    $lessonStmt->execute([(int) $chapter['id']]);
    $lessons = $lessonStmt->fetchAll();
    ?>
    <section class="bg-white rounded-2 p-4 shadow-sm mb-4">
        <form class="row g-3 align-items-end border-bottom pb-3 mb-3" method="post">
            <input type="hidden" name="course_id" value="<?= $courseId ?>">
            <input type="hidden" name="chapter_id" value="<?= (int) $chapter['id'] ?>">
            <div class="col-md-7">
                <label class="form-label">Tên chương</label>
                <input class="form-control" name="title" value="<?= e($chapter['title']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Thứ tự</label>
                <input class="form-control" type="number" name="sort_order" value="<?= (int) $chapter['sort_order'] ?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-outline-primary flex-fill" name="action" value="update_chapter" type="submit">Lưu</button>
                <button class="btn btn-outline-danger flex-fill" name="action" value="delete_chapter" type="submit" onclick="return confirm('Xóa chương này và toàn bộ bài học bên trong?')">Xóa</button>
            </div>
        </form>

        <details class="mb-4">
            <summary class="fw-semibold text-primary">+ Thêm bài học vào chương này</summary>
            <form class="row g-3 mt-2" method="post">
                <input type="hidden" name="course_id" value="<?= $courseId ?>">
                <input type="hidden" name="action" value="add_lesson">
                <input type="hidden" name="chapter_id" value="<?= (int) $chapter['id'] ?>">
                <div class="col-md-6">
                    <label class="form-label">Tên bài học</label>
                    <input class="form-control" name="title" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Video embed URL</label>
                    <input class="form-control" name="video_url" placeholder="https://www.youtube.com/embed/...">
                </div>
                <div class="col-12">
                    <label class="form-label">Lý thuyết cơ bản</label>
                    <textarea class="form-control" name="theory_content" rows="3"></textarea>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Phút</label>
                    <input class="form-control" type="number" name="duration_minutes" value="20">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Thứ tự mở</label>
                    <input class="form-control" type="number" name="unlock_order" value="<?= count($lessons) + 1 ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_preview" id="preview_<?= (int) $chapter['id'] ?>">
                        <label class="form-check-label" for="preview_<?= (int) $chapter['id'] ?>">Cho học thử</label>
                    </div>
                </div>
                <div class="col-md-5 d-flex align-items-end">
                    <button class="btn btn-primary" type="submit">Thêm bài học</button>
                </div>
            </form>
        </details>

        <?php foreach ($lessons as $lesson): ?>
            <?php
            $materialStmt->execute([(int) $lesson['id']]);
            $materials = $materialStmt->fetchAll();
            ?>
            <div class="border rounded-2 p-3 mb-3">
                <form class="row g-3" method="post">
                    <input type="hidden" name="course_id" value="<?= $courseId ?>">
                    <input type="hidden" name="lesson_id" value="<?= (int) $lesson['id'] ?>">
                    <div class="col-md-6">
                        <label class="form-label">Tên bài</label>
                        <input class="form-control" name="title" value="<?= e($lesson['title']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Video embed URL</label>
                        <input class="form-control" name="video_url" value="<?= e($lesson['video_url']) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Lý thuyết</label>
                        <textarea class="form-control" name="theory_content" rows="3"><?= e($lesson['theory_content']) ?></textarea>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Phút</label>
                        <input class="form-control" type="number" name="duration_minutes" value="<?= (int) $lesson['duration_minutes'] ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Thứ tự mở</label>
                        <input class="form-control" type="number" name="unlock_order" value="<?= (int) $lesson['unlock_order'] ?>">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_preview" id="lesson_preview_<?= (int) $lesson['id'] ?>" <?= (int) $lesson['is_preview'] === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="lesson_preview_<?= (int) $lesson['id'] ?>">Cho học thử</label>
                        </div>
                    </div>
                    <div class="col-md-5 d-flex align-items-end justify-content-end gap-2">
                        <button class="btn btn-outline-primary" name="action" value="update_lesson" type="submit">Lưu bài</button>
                        <button class="btn btn-outline-danger" name="action" value="delete_lesson" type="submit" onclick="return confirm('Xóa bài học này?')">Xóa bài</button>
                    </div>
                </form>

                <div class="mt-3">
                    <h3 class="h6">Tài liệu của bài học</h3>
                    <?php foreach ($materials as $material): ?>
                        <form class="row g-2 align-items-end mb-2" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="course_id" value="<?= $courseId ?>">
                            <input type="hidden" name="material_id" value="<?= (int) $material['id'] ?>">
                            <div class="col-md-3">
                                <input class="form-control" name="title" value="<?= e($material['title']) ?>">
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="material_type">
                                    <?php foreach (['pdf' => 'PDF', 'source_code' => 'Source', 'slide' => 'Slide', 'link' => 'Link'] as $value => $label): ?>
                                        <option value="<?= e($value) ?>" <?= $material['material_type'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input class="form-control" name="file_url" value="<?= e($material['file_url']) ?>">
                            </div>
                            <div class="col-md-2">
                                <input class="form-control" type="file" name="material_file">
                            </div>
                            <div class="col-md-2 d-flex gap-1">
                                <button class="btn btn-sm btn-outline-primary" name="action" value="update_material" type="submit">Lưu</button>
                                <button class="btn btn-sm btn-outline-danger" name="action" value="delete_material" type="submit">Xóa</button>
                            </div>
                        </form>
                    <?php endforeach; ?>

                    <form class="row g-2 align-items-end" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="course_id" value="<?= $courseId ?>">
                        <input type="hidden" name="lesson_id" value="<?= (int) $lesson['id'] ?>">
                        <input type="hidden" name="action" value="add_material">
                        <div class="col-md-3">
                            <label class="form-label">Tên tài liệu</label>
                            <input class="form-control" name="title" placeholder="PDF bài học" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Loại</label>
                            <select class="form-select" name="material_type">
                                <option value="pdf">PDF</option>
                                <option value="source_code">Source</option>
                                <option value="slide">Slide</option>
                                <option value="link">Link</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">URL/tên file</label>
                            <input class="form-control" name="file_url" placeholder="php-intro.pdf">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Upload file</label>
                            <input class="form-control" type="file" name="material_file">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-success w-100" type="submit">Thêm file</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </section>
<?php endforeach; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
