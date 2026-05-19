<?php
require_once dirname(__DIR__) . '/core/init.php';

$compilerResult = $_SESSION['compiler_result'] ?? null;
$compilerLanguage = (string) ($_SESSION['compiler_language'] ?? 'php');
$languages = compiler_available_languages();
if (!isset($languages[$compilerLanguage])) {
    $compilerLanguage = array_key_first($languages) ?: 'php';
}
$sampleCodes = [];
foreach (array_keys($languages) as $languageKey) {
    $sampleCodes[$languageKey] = compiler_sample_code($languageKey);
}
$compilerCode = (string) ($_SESSION['compiler_code'] ?? compiler_sample_code($compilerLanguage));
$compilerStdin = (string) ($_SESSION['compiler_stdin'] ?? '');
$isPreviewResult = is_array($compilerResult) && !empty($compilerResult['preview']);
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
            </div>
            <form method="post" action="<?= url('compiler_run') ?>" class="compiler-run-form">
                <select class="form-select" name="language" id="compiler-language">
                    <?php foreach ($languages as $key => $language): ?>
                        <option value="<?= e($key) ?>" <?= $key === $compilerLanguage ? 'selected' : '' ?>><?= e($language['label']) ?></option>
                    <?php endforeach; ?>
                </select>
        </div>
        <div class="compiler-panel-grid">
            <div class="compiler-editor">
                <textarea name="code" id="compiler-code" spellcheck="false"><?= e($compilerCode) ?></textarea>
            </div>
            <div class="compiler-result-panel">
                <div class="result-head" id="compiler-result-head"><?= $isPreviewResult ? 'Preview' : 'Output' ?></div>
                <?php if ($isPreviewResult): ?>
                    <iframe class="compiler-preview-frame" sandbox="allow-scripts allow-forms" srcdoc="<?= e($compilerResult['output']) ?>" title="Preview"></iframe>
                <?php else: ?>
                    <pre id="compiler-output"><?= e(is_array($compilerResult) ? $compilerResult['output'] : 'Kết quả chạy code sẽ hiển thị tại đây.') ?></pre>
                <?php endif; ?>
            </div>
        </div>
        <div class="compiler-input-panel">
            <label class="form-label fw-semibold">Input stdin</label>
            <textarea class="form-control" name="stdin" rows="4" placeholder="Nhập dữ liệu input cho chương trình nếu cần"><?= e($compilerStdin) ?></textarea>
        </div>
        <div class="compiler-actions">
            <button class="btn btn-primary btn-lg" type="submit">Run</button>
            <a class="btn btn-outline-primary" href="<?= url('courses') ?>">Học qua bài giảng</a>
            <span class="small text-muted">Với chương trình có input, nhập mỗi giá trị trên một dòng.</span>
            </form>
        </div>
    </div>
</section>

<script>
const compilerSamples = <?= json_encode($sampleCodes, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const compilerLanguage = document.getElementById('compiler-language');
const compilerCode = document.getElementById('compiler-code');
const compilerStdin = document.querySelector('textarea[name="stdin"]');
const compilerResultHead = document.getElementById('compiler-result-head');
const compilerResultPanel = document.querySelector('.compiler-result-panel');

compilerLanguage?.addEventListener('change', function () {
    compilerCode.value = compilerSamples[this.value] || '';
    if (compilerStdin) {
        compilerStdin.value = '';
    }
    compilerResultHead.textContent = 'Output';
    compilerResultPanel.querySelector('iframe')?.remove();

    let output = document.getElementById('compiler-output');
    if (!output) {
        output = document.createElement('pre');
        output.id = 'compiler-output';
        compilerResultPanel.appendChild(output);
    }
    output.textContent = 'Kết quả chạy code sẽ hiển thị tại đây.';
});
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
