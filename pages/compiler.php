<?php
require_once dirname(__DIR__) . '/core/init.php';

$compilerResult = $_SESSION['compiler_result'] ?? null;
$compilerLanguage = (string) ($_SESSION['compiler_language'] ?? 'php');
$languages = compiler_available_languages();
if (!isset($languages[$compilerLanguage])) {
    $compilerLanguage = array_key_first($languages) ?: 'php';
}
$compilerCode = (string) ($_SESSION['compiler_code'] ?? compiler_sample_code($compilerLanguage));
$compilerStdin = (string) ($_SESSION['compiler_stdin'] ?? '');
unset($_SESSION['compiler_result'], $_SESSION['compiler_stdin']);

$pageTitle = 'Trình biên dịch code - ' . APP_NAME;
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="container py-5">
    <div class="compiler-workspace">
        <div class="compiler-toolbar">
            <div>
                <span class="hero-kicker">Online compiler</span>
                <h1>Chạy thử code nhiều ngôn ngữ</h1>
                <p>Viết code bên trái, bấm chạy và xem kết quả thật ở khung output bên phải.</p>
            </div>
            <form method="post" action="<?= url('compiler_run') ?>" class="compiler-run-form">
                <select class="form-select" name="language" onchange="this.form.submit()">
                    <?php foreach ($languages as $key => $language): ?>
                        <option value="<?= e($key) ?>" <?= $key === $compilerLanguage ? 'selected' : '' ?>><?= e($language['label']) ?></option>
                    <?php endforeach; ?>
                </select>
        </div>
        <div class="compiler-panel-grid">
            <div class="compiler-editor">
                <textarea name="code" spellcheck="false"><?= e($compilerCode) ?></textarea>
            </div>
            <div class="compiler-result-panel">
                <div class="result-head">Output</div>
                <pre><?= e(is_array($compilerResult) ? $compilerResult['output'] : 'Kết quả chạy code sẽ hiển thị tại đây.') ?></pre>
            </div>
        </div>
        <div class="compiler-input-panel">
            <label class="form-label fw-semibold">Input stdin</label>
            <textarea class="form-control" name="stdin" rows="4" placeholder="Nhập dữ liệu input cho chương trình nếu cần"><?= e($compilerStdin) ?></textarea>
        </div>
        <div class="compiler-actions">
            <button class="btn btn-primary btn-lg" type="submit">Run</button>
            <a class="btn btn-outline-primary" href="<?= url('courses') ?>">Học qua bài giảng</a>
            <?php if (!isset(compiler_languages()['java']) || !compiler_runtime_available(compiler_languages()['java'])): ?>
                <span class="small text-muted">Java sẽ hiển thị sau khi máy cài JDK và có java/javac trong PATH.</span>
            <?php endif; ?>
            </form>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
