<?php

declare(strict_types=1);

function admin_search_term(): string
{
    return trim((string) ($_GET['q'] ?? ''));
}

function admin_page_number(): int
{
    return max(1, (int) ($_GET['p'] ?? 1));
}

function admin_per_page(): int
{
    return 5;
}

function admin_offset(): int
{
    return (admin_page_number() - 1) * admin_per_page();
}

function admin_url(string $path, array $params = []): string
{
    $query = array_merge($_GET, $params);
    foreach ($query as $key => $value) {
        if ($value === '' || $value === null) {
            unset($query[$key]);
        }
    }

    return APP_URL . '/' . ltrim($path, '/') . ($query ? '?' . http_build_query($query) : '');
}

function admin_relative_url(string $path, array $params = []): string
{
    $query = array_merge($_GET, $params);
    foreach ($query as $key => $value) {
        if ($value === '' || $value === null) {
            unset($query[$key]);
        }
    }

    return ltrim($path, '/') . ($query ? '?' . http_build_query($query) : '');
}

function admin_return_path(string $fallback = 'admin/courses.php'): string
{
    $return = (string) ($_POST['return'] ?? $_GET['return'] ?? '');

    if (preg_match('/^admin\/[a-zA-Z0-9_\/.-]+\.php(?:\?[^#]*)?$/', $return) === 1) {
        return $return;
    }

    return $fallback;
}

function admin_render_search(string $placeholder = 'Tìm kiếm...', array $hidden = []): void
{
    $q = admin_search_term();
    $clearQuery = [];
    foreach ($hidden as $key => $value) {
        if ($value !== '' && $value !== null) {
            $clearQuery[$key] = $value;
        }
    }
    $clearUrl = (strtok($_SERVER['REQUEST_URI'] ?? '', '?') ?: '') . ($clearQuery ? '?' . http_build_query($clearQuery) : '');
    ?>
    <form class="bg-white rounded-2 p-3 shadow-sm mb-3" method="get">
        <?php foreach ($hidden as $key => $value): ?>
            <?php if ($value !== '' && $value !== null): ?>
                <input type="hidden" name="<?= e((string) $key) ?>" value="<?= e((string) $value) ?>">
            <?php endif; ?>
        <?php endforeach; ?>
        <div class="input-group">
            <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="<?= e($placeholder) ?>">
            <button class="btn btn-primary" type="submit">Tìm</button>
            <?php if ($q !== ''): ?>
                <a class="btn btn-outline-secondary" href="<?= e($clearUrl) ?>">Xóa lọc</a>
            <?php endif; ?>
        </div>
    </form>
    <?php
}

function admin_render_pagination(int $total, string $path, array $extra = []): void
{
    $perPage = admin_per_page();
    $pages = max(1, (int) ceil($total / $perPage));
    $current = min(admin_page_number(), $pages);

    if ($pages <= 1) {
        return;
    }
    ?>
    <nav class="mt-3" aria-label="Phân trang">
        <ul class="pagination mb-0">
            <?php for ($page = 1; $page <= $pages; $page++): ?>
                <li class="page-item <?= $page === $current ? 'active' : '' ?>">
                    <a class="page-link" href="<?= e(admin_url($path, array_merge($extra, ['p' => $page]))) ?>"><?= $page ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php
}
