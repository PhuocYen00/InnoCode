<?php

declare(strict_types=1);

function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money(int|float $amount): string
{
    return number_format((float) $amount, 0, ',', '.') . 'đ';
}

function url(string $page = 'home', array $params = []): string
{
    $page = page_from_path($page);
    $query = array_merge(['page' => $page], $params);

    return APP_URL . '/index.php?' . http_build_query($query);
}

function page_from_path(string $path): string
{
    $path = trim($path, '/');
    $pathOnly = strtok($path, '?') ?: $path;
    $name = basename($pathOnly, '.php');

    if ($name === '' || $name === 'index') {
        return 'home';
    }

    return $name;
}

function redirect(string $path): never
{
    $pathOnly = strtok($path, '?') ?: $path;

    if (!str_starts_with($path, 'admin/') && str_ends_with($pathOnly, '.php')) {
        $query = parse_url($path, PHP_URL_QUERY);
        $params = [];

        if (is_string($query)) {
            parse_str($query, $params);
        }

        header('Location: ' . url(page_from_path($path), $params));
        exit;
    }

    header('Location: ' . APP_URL . '/' . ltrim($path, '/'));
    exit;
}

function flash(?string $key = null, ?string $message = null): ?string
{
    if ($key !== null && $message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    $message = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);

    return $message;
}

function active_nav(string $page): string
{
    return page_from_path($page) === (string) ($_GET['page'] ?? 'home') ? 'active' : '';
}

function is_admin(): bool
{
    $user = current_user();
    return $user !== null && ($user['role'] ?? 'user') === 'admin';
}

function require_admin(): void
{
    if (!is_admin()) {
        redirect('login.php?next=' . urlencode('/admin/index.php'));
    }
}

function current_user(): ?array
{
    $userId = (int) ($_SESSION['user_id'] ?? 0);

    if ($userId <= 0) {
        return null;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('error', 'Vui lòng đăng nhập để sử dụng chức năng này.');
        redirect('login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? url('courses')));
    }
}

function require_verified_email(): void
{
    if (is_admin()) {
        return;
    }

    $user = current_user();

    if (!$user || $user['email_verified_at'] === null) {
        flash('error', 'Vui lòng xác thực email trước khi thanh toán và học khóa học.');
        redirect('verify_notice.php');
    }
}

function login_user(array $user): void
{
    $_SESSION['user_id'] = (int) $user['id'];
    unset($_SESSION['admin_logged_in']);
}

function logout_user(): void
{
    unset($_SESSION['user_id']);
}

function find_user_by_email(string $email): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function random_token(): string
{
    return bin2hex(random_bytes(32));
}

function send_app_mail(string $recipient, string $subject, string $body, ?int $userId = null, bool $isHtml = false): void
{
    $stmt = db()->prepare('INSERT INTO email_logs (user_id, recipient, subject, body) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, $recipient, $subject, $body]);

    if (SMTP_USERNAME !== '' && SMTP_PASSWORD !== '') {
        smtp_send_mail($recipient, $subject, $body, $isHtml);
        return;
    }

    $from = MAIL_FROM !== '' ? MAIL_FROM : 'noreply@localhost';
    $headers = 'From: ' . MAIL_FROM_NAME . ' <' . $from . ">\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= 'Content-Type: ' . ($isHtml ? 'text/html' : 'text/plain') . '; charset=UTF-8';
    @mail($recipient, $subject, $body, $headers);
}

function smtp_send_mail(string $recipient, string $subject, string $body, bool $isHtml = false): void
{
    $socket = fsockopen(SMTP_HOST, SMTP_PORT, $errno, $error, 20);

    if (!$socket) {
        throw new RuntimeException('Không thể kết nối SMTP: ' . $error);
    }

    smtp_expect($socket, 220);
    smtp_command($socket, 'EHLO localhost', 250);

    if (SMTP_ENCRYPTION === 'tls') {
        smtp_command($socket, 'STARTTLS', 220);

        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            throw new RuntimeException('Không thể bật TLS cho SMTP.');
        }

        smtp_command($socket, 'EHLO localhost', 250);
    }

    smtp_command($socket, 'AUTH LOGIN', 334);
    smtp_command($socket, base64_encode(SMTP_USERNAME), 334);
    smtp_command($socket, base64_encode(SMTP_PASSWORD), 235);

    $from = MAIL_FROM !== '' ? MAIL_FROM : SMTP_USERNAME;
    smtp_command($socket, 'MAIL FROM:<' . $from . '>', 250);
    smtp_command($socket, 'RCPT TO:<' . $recipient . '>', [250, 251]);
    smtp_command($socket, 'DATA', 354);

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: ' . ($isHtml ? 'text/html' : 'text/plain') . '; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'From: ' . MAIL_FROM_NAME . ' <' . $from . '>',
        'To: <' . $recipient . '>',
        'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
    ];

    $message = implode("\r\n", $headers) . "\r\n\r\n" . str_replace(["\r\n", "\r"], "\n", $body);
    $message = str_replace("\n.", "\n..", $message);

    smtp_command($socket, $message . "\r\n.", 250);
    smtp_command($socket, 'QUIT', 221);
    fclose($socket);
}

function smtp_command($socket, string $command, int|array $expected): string
{
    fwrite($socket, $command . "\r\n");
    return smtp_expect($socket, $expected);
}

function smtp_expect($socket, int|array $expected): string
{
    $expectedCodes = is_array($expected) ? $expected : [$expected];
    $response = '';

    while ($line = fgets($socket, 515)) {
        $response .= $line;

        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }

    $code = (int) substr($response, 0, 3);

    if (!in_array($code, $expectedCodes, true)) {
        throw new RuntimeException('SMTP trả về lỗi: ' . trim($response));
    }

    return $response;
}

function send_verification_email(array $user): void
{
    $link = url('verify_email', ['token' => (string) $user['verification_token']]);
    $body = "Chào {$user['name']},\n\nBấm link để xác thực email tài khoản InnoCode:\n{$link}";
    send_app_mail((string) $user['email'], 'Xác thực tài khoản ' . APP_NAME, $body, (int) $user['id']);
}

function all_categories(): array
{
    return db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
}

function all_physical_products(bool $activeOnly = true): array
{
    $sql = "SELECT * FROM physical_products WHERE product_type <> 'souvenir'";

    if ($activeOnly) {
        $sql .= ' AND is_active = 1';
    }

    $sql .= ' ORDER BY created_at DESC, id DESC';

    return db()->query($sql)->fetchAll();
}

function latest_courses(?int $limit = null): array
{
    $sql = 'SELECT courses.*, categories.name AS category_name
            FROM courses
            JOIN categories ON categories.id = courses.category_id
            WHERE courses.is_active = 1
            ORDER BY courses.created_at DESC';

    if ($limit !== null) {
        $sql .= ' LIMIT ' . max(1, $limit);
    }

    return db()->query($sql)->fetchAll();
}

function courses_by_filter(?int $categoryId, ?string $keyword): array
{
    $sql = 'SELECT courses.*, categories.name AS category_name
            FROM courses
            JOIN categories ON categories.id = courses.category_id
            WHERE courses.is_active = 1';
    $params = [];

    if ($categoryId) {
        $sql .= ' AND courses.category_id = :category_id';
        $params['category_id'] = $categoryId;
    }

    if ($keyword) {
        $sql .= ' AND (courses.title LIKE :keyword OR courses.description LIKE :keyword OR courses.level LIKE :keyword OR categories.name LIKE :keyword)';
        $params['keyword'] = '%' . $keyword . '%';
    }

    $sql .= ' ORDER BY courses.created_at DESC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function find_course(int $id, bool $onlyActive = true): ?array
{
    $sql = 'SELECT courses.*, categories.name AS category_name
            FROM courses
            JOIN categories ON categories.id = courses.category_id
            WHERE courses.id = :id';

    if ($onlyActive) {
        $sql .= ' AND courses.is_active = 1';
    }

    $stmt = db()->prepare($sql);
    $stmt->execute(['id' => $id]);
    $course = $stmt->fetch();

    return $course ?: null;
}

function is_free_course(array $course): bool
{
    return (float) $course['price'] <= 0;
}

function excerpt(string $value, int $limit = 112): string
{
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($value, 0, $limit, '...', 'UTF-8');
    }

    return strlen($value) > $limit ? substr($value, 0, $limit - 3) . '...' : $value;
}

function cart_key(string $type, int $id): string
{
    return $type . '_' . $id;
}

function cart_key_parts(string $key): array
{
    if (str_contains($key, '_')) {
        [$type, $id] = explode('_', $key, 2);
        return [$type === 'product' ? 'product' : 'course', (int) $id];
    }

    return ['course', (int) $key];
}

function cart_add(string $type, int $id): void
{
    require_login();

    if ($type === 'course') {
        $stmt = db()->prepare('INSERT INTO cart_items (user_id, course_id, quantity) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE quantity = 1');
        $stmt->execute([(int) current_user()['id'], $id]);
        return;
    }

    if ($type === 'product') {
        $stmt = db()->prepare('INSERT INTO product_cart_items (user_id, product_id, quantity) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE quantity = quantity + 1');
        $stmt->execute([(int) current_user()['id'], $id]);
    }
}

function cart_remove(string $key): void
{
    require_login();
    [$type, $id] = cart_key_parts($key);
    $stmt = $type === 'product'
        ? db()->prepare('DELETE FROM product_cart_items WHERE user_id = ? AND product_id = ?')
        : db()->prepare('DELETE FROM cart_items WHERE user_id = ? AND course_id = ?');
    $stmt->execute([(int) current_user()['id'], $id]);
}

function cart_update(array $quantities): void
{
    require_login();

    foreach ($quantities as $key => $quantity) {
        [$type, $id] = cart_key_parts((string) $key);
        $quantity = $type === 'course' ? min(1, max(0, (int) $quantity)) : max(0, (int) $quantity);

        if ($quantity === 0) {
            cart_remove((string) $key);
        } elseif ($type === 'product') {
            $stmt = db()->prepare('UPDATE product_cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?');
            $stmt->execute([$quantity, (int) current_user()['id'], $id]);
        } else {
            $stmt = db()->prepare('UPDATE cart_items SET quantity = ? WHERE user_id = ? AND course_id = ?');
            $stmt->execute([$quantity, (int) current_user()['id'], $id]);
        }
    }
}

function cart_items_count(): int
{
    if (!is_logged_in()) {
        return 0;
    }

    $stmt = db()->prepare('SELECT
        (SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE user_id = ?)
        + (SELECT COALESCE(SUM(quantity), 0) FROM product_cart_items WHERE user_id = ?)');
    $stmt->execute([(int) current_user()['id'], (int) current_user()['id']]);

    return (int) $stmt->fetchColumn();
}

function cart_items(): array
{
    if (!is_logged_in()) {
        return [];
    }

    $stmt = db()->prepare('SELECT cart_items.course_id, cart_items.quantity, courses.*
        FROM cart_items
        JOIN courses ON courses.id = cart_items.course_id
        WHERE cart_items.user_id = ? AND courses.is_active = 1
        ORDER BY cart_items.updated_at DESC');
    $stmt->execute([(int) current_user()['id']]);

    $courseItems = [];
    foreach ($stmt->fetchAll() as $item) {
        $courseItems[] = [
            'key' => cart_key('course', (int) $item['course_id']),
            'type' => 'course',
            'id' => (int) $item['course_id'],
            'title' => $item['title'],
            'price' => (float) $item['price'],
            'quantity' => 1,
            'line_total' => (float) $item['price'],
            'description' => ($item['level'] ?? '') . ' · ' . ($item['duration_hours'] ?? '') . ' giờ',
            'is_quantity_editable' => false,
        ];
    }

    $stmt = db()->prepare('SELECT product_cart_items.product_id, product_cart_items.quantity, physical_products.*
        FROM product_cart_items
        JOIN physical_products ON physical_products.id = product_cart_items.product_id
        WHERE product_cart_items.user_id = ? AND physical_products.is_active = 1
        ORDER BY product_cart_items.updated_at DESC');
    $stmt->execute([(int) current_user()['id']]);

    $productItems = [];
    foreach ($stmt->fetchAll() as $item) {
        $productItems[] = [
            'key' => cart_key('product', (int) $item['product_id']),
            'type' => 'product',
            'id' => (int) $item['product_id'],
            'title' => $item['name'],
            'price' => (float) $item['price'],
            'quantity' => (int) $item['quantity'],
            'line_total' => (float) $item['price'] * (int) $item['quantity'],
            'description' => product_type_label((string) $item['product_type']) . ' · Tồn kho: ' . (int) $item['stock'],
            'is_quantity_editable' => true,
        ];
    }

    return array_merge($courseItems, $productItems);
}

function cart_course_id_from_key(string $key): int
{
    [, $id] = cart_key_parts($key);
    return $id;
}

function clear_cart(int $userId): void
{
    $stmt = db()->prepare('DELETE FROM cart_items WHERE user_id = ?');
    $stmt->execute([$userId]);
    $stmt = db()->prepare('DELETE FROM product_cart_items WHERE user_id = ?');
    $stmt->execute([$userId]);
}

function cart_courses(): array
{
    return cart_items();
}

function cart_total(array $items): float
{
    return array_sum(array_column($items, 'line_total'));
}

function cart_has_physical_items(array $items): bool
{
    return false;
}

function product_type_label(string $type): string
{
    return [
        'pdf' => 'Sách điện tử',
        'printed_document' => 'Tài liệu học tập',
        'souvenir' => 'Quà lưu niệm',
    ][$type] ?? $type;
}
function short_text(string $value, int $limit): string
{
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $limit, 'UTF-8');
    }

    return substr($value, 0, $limit);
}

function course_sections(array $course): array
{
    return course_db_sections((int) $course['id']);
}

function course_db_sections(int $courseId): array
{
    $stmt = db()->prepare('SELECT * FROM course_chapters WHERE course_id = ? ORDER BY sort_order, id');
    $stmt->execute([$courseId]);
    $chapters = $stmt->fetchAll();

    if (!$chapters) {
        return [];
    }

    $sections = [];
    $lessonStmt = db()->prepare('SELECT * FROM course_lessons WHERE chapter_id = ? ORDER BY unlock_order, id');

    foreach ($chapters as $chapter) {
        $lessonStmt->execute([(int) $chapter['id']]);
        $lessons = [];

        foreach ($lessonStmt->fetchAll() as $lesson) {
            $lessons[] = [
                'id' => (int) $lesson['id'],
                'title' => $lesson['title'],
                'duration' => ((int) $lesson['duration_minutes']) . ' phút',
                'video_url' => $lesson['video_url'],
                'theory_content' => $lesson['theory_content'],
                'is_preview' => (int) $lesson['is_preview'] === 1,
            ];
        }

        $sections[] = [
            'id' => (int) $chapter['id'],
            'title' => $chapter['title'],
            'lessons' => $lessons,
        ];
    }

    return $sections;
}

function course_flat_lessons(array $course): array
{
    $flat = [];

    foreach (course_sections($course) as $sectionIndex => $section) {
        foreach ($section['lessons'] as $lesson) {
            $lesson['section'] = $section['title'];
            $lesson['section_index'] = $sectionIndex;
            $flat[] = $lesson;
        }
    }

    return $flat;
}

function course_lessons_count(array $sections): int
{
    $total = 0;

    foreach ($sections as $section) {
        $total += count($section['lessons']);
    }

    return $total;
}

function course_outcomes(array $course): array
{
    return [
        'Nắm được kiến thức cốt lõi và biết áp dụng vào dự án thực tế.',
        'Có source code mẫu để luyện tập sau từng chương học.',
        'Biết cách phân tích yêu cầu và chia nhỏ chức năng.',
        'Hoàn thiện sản phẩm có thể đưa vào portfolio.',
        'Tự tin đọc lỗi, debug và cải thiện code.',
        'Hiểu cách kết nối giao diện, dữ liệu và luồng người dùng.',
    ];
}

function course_requirements(array $course): array
{
    return [
        'Có máy tính cài sẵn trình duyệt và trình soạn code.',
        'Nên biết HTML/CSS căn bản.',
        'Dành thời gian thực hành lại sau mỗi video.',
        'Không cần kinh nghiệm nâng cao.',
    ];
}

function course_video_key(array $course): string
{
    $title = strtolower((string) $course['title']);

    if (str_contains($title, 'sql') || str_contains($title, 'database')) {
        return 'mysql';
    }

    foreach (array_keys(course_video_map()) as $key) {
        if (str_contains($title, $key)) {
            return $key;
        }
    }

    return 'php';
}

function trailer_embed_url(array $course): string
{
    $key = course_video_key($course);
    return course_video_map()[$key]['trailer'];
}

function lesson_embed_url(array $course, int $lessonIndex): string
{
    $flatLessons = course_flat_lessons($course);
    $lesson = $flatLessons[$lessonIndex] ?? null;

    if (!empty($lesson['video_url'])) {
        return (string) $lesson['video_url'];
    }

    $key = course_video_key($course);
    $videos = course_video_map()[$key]['lessons'];

    return $videos[$lessonIndex % count($videos)];
}

function mark_course_purchased(int $courseId, ?int $orderId = null, ?int $userId = null): void
{
    $userId = $userId ?? (int) (current_user()['id'] ?? 0);

    if ($userId <= 0) {
        return;
    }

    $stmt = db()->prepare('INSERT INTO enrollments (user_id, course_id, order_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE order_id = COALESCE(VALUES(order_id), order_id)');
    $stmt->execute([$userId, $courseId, $orderId]);
}

function has_purchased_course(int $courseId, ?int $userId = null): bool
{
    if (is_admin()) {
        return true;
    }

    $userId = $userId ?? (int) (current_user()['id'] ?? 0);

    if ($userId <= 0) {
        return false;
    }

    $stmt = db()->prepare('SELECT COUNT(*) FROM enrollments WHERE user_id = ? AND course_id = ?');
    $stmt->execute([$userId, $courseId]);

    return (int) $stmt->fetchColumn() > 0;
}

function course_is_completed(int $courseId, ?int $userId = null): bool
{
    $course = find_course($courseId, false);

    if (!$course) {
        return false;
    }

    $lessonsCount = count(course_flat_lessons($course));
    if ($lessonsCount === 0) {
        return false;
    }

    $userId = $userId ?? (int) (current_user()['id'] ?? 0);
    if ($userId <= 0) {
        return false;
    }

    $stmt = db()->prepare('SELECT COUNT(*) FROM course_lesson_progress WHERE user_id = ? AND course_id = ? AND is_completed = 1');
    $stmt->execute([$userId, $courseId]);

    return (int) $stmt->fetchColumn() >= $lessonsCount;
}

function souvenir_products(bool $activeOnly = true): array
{
    $sql = "SELECT * FROM physical_products WHERE product_type = 'souvenir'";

    if ($activeOnly) {
        $sql .= ' AND is_active = 1';
    }

    $sql .= ' ORDER BY created_at DESC, id DESC';

    return db()->query($sql)->fetchAll();
}

function gift_request_for(int $userId, int $courseId): ?array
{
    $stmt = db()->prepare('SELECT gift_requests.*, physical_products.name AS product_name
        FROM gift_requests
        JOIN physical_products ON physical_products.id = gift_requests.product_id
        WHERE gift_requests.user_id = ? AND gift_requests.course_id = ?
        LIMIT 1');
    $stmt->execute([$userId, $courseId]);
    $request = $stmt->fetch();

    return $request ?: null;
}

function gift_status_label(string $status): string
{
    return [
        'pending' => 'Đang chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'shipping' => 'Đã giao cho vận chuyển',
        'delivered' => 'Đã vận chuyển',
    ][$status] ?? $status;
}

function user_materials(int $userId): array
{
    $stmt = db()->prepare("SELECT physical_order_items.*, physical_products.description, physical_products.image_url, physical_products.digital_file_url, orders.paid_at
        FROM physical_order_items
        JOIN orders ON orders.id = physical_order_items.order_id
        JOIN physical_products ON physical_products.id = physical_order_items.product_id
        WHERE orders.user_id = ? AND orders.status = 'paid' AND physical_products.product_type <> 'souvenir'
        ORDER BY orders.paid_at DESC, physical_order_items.id DESC");
    $stmt->execute([$userId]);

    return $stmt->fetchAll();
}

function complete_order(int $orderId): void
{
    $order = find_order($orderId);

    if (!$order || $order['status'] === 'paid') {
        return;
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'paid', paid_at = NOW() WHERE id = ?");
        $stmt->execute([$orderId]);

        $items = order_items($orderId);
        foreach ($items as $item) {
            if (($item['type'] ?? 'course') === 'course') {
                mark_course_purchased((int) $item['course_id'], $orderId, (int) $order['user_id']);
            }
        }

        if (!empty($order['coupon_code'])) {
            $stmt = $pdo->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE code = ?');
            $stmt->execute([$order['coupon_code']]);
        }

        $pdo->commit();

        try {
            send_order_paid_email($orderId);
        } catch (Throwable $mailException) {
            flash('error', 'Đơn hàng đã được mở khóa nhưng email chưa gửi được: ' . $mailException->getMessage());
        }
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function cancel_order(int $orderId): void
{
    if ($orderId <= 0) {
        return;
    }

    $stmt = db()->prepare("UPDATE orders
        SET status = 'cancelled', payos_checkout_url = NULL
        WHERE id = ? AND status <> 'paid'");
    $stmt->execute([$orderId]);
}

function order_items(int $orderId): array
{
    $stmt = db()->prepare('SELECT order_items.*, courses.title, courses.image_url, courses.level, courses.duration_hours
        FROM order_items
        JOIN courses ON courses.id = order_items.course_id
        WHERE order_items.order_id = ?');
    $stmt->execute([$orderId]);

    $courseItems = [];
    foreach ($stmt->fetchAll() as $item) {
        $item['type'] = 'course';
        $courseItems[] = $item;
    }

    $stmt = db()->prepare('SELECT physical_order_items.*, product_name AS title, NULL AS image_url, NULL AS level, NULL AS duration_hours
        FROM physical_order_items
        WHERE order_id = ?');
    $stmt->execute([$orderId]);

    $productItems = [];
    foreach ($stmt->fetchAll() as $item) {
        $item['type'] = 'product';
        $productItems[] = $item;
    }

    return array_merge($courseItems, $productItems);
}

function user_orders(int $userId): array
{
    $stmt = db()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$userId]);

    return $stmt->fetchAll();
}

function user_courses(int $userId): array
{
    $stmt = db()->prepare('SELECT courses.*, enrollments.enrolled_at
        FROM enrollments
        JOIN courses ON courses.id = enrollments.course_id
        WHERE enrollments.user_id = ?
        ORDER BY enrollments.enrolled_at DESC');
    $stmt->execute([$userId]);

    return $stmt->fetchAll();
}

function order_payment_code(int $orderId): string
{
    return 'IC' . $orderId;
}

function coupon_by_code(string $code): ?array
{
    $stmt = db()->prepare('SELECT * FROM coupons WHERE code = ? AND is_active = 1');
    $stmt->execute([strtoupper(trim($code))]);
    $coupon = $stmt->fetch();

    return $coupon ?: null;
}

function coupon_discount(array $coupon, float $subtotal): float
{
    if ($coupon['starts_at'] && strtotime((string) $coupon['starts_at']) > time()) {
        return 0;
    }

    if ($coupon['expires_at'] && strtotime((string) $coupon['expires_at']) < time()) {
        return 0;
    }

    if ($coupon['usage_limit'] !== null && (int) $coupon['used_count'] >= (int) $coupon['usage_limit']) {
        return 0;
    }

    $discount = $coupon['discount_type'] === 'fixed'
        ? (float) $coupon['discount_value']
        : $subtotal * ((float) $coupon['discount_value'] / 100);

    return min($subtotal, max(0, $discount));
}

function cart_coupon(): ?array
{
    $code = $_SESSION['coupon_code'] ?? null;

    return is_string($code) ? coupon_by_code($code) : null;
}

function cart_discount(float $subtotal): float
{
    $coupon = cart_coupon();

    return $coupon ? coupon_discount($coupon, $subtotal) : 0;
}

function payos_order_code(int $orderId): int
{
    return 100000 + $orderId;
}

function payos_signature(array $data): string
{
    ksort($data);
    $parts = [];

    foreach ($data as $key => $value) {
        if (is_array($value) || $value === null) {
            continue;
        }

        $parts[] = $key . '=' . $value;
    }

    return hash_hmac('sha256', implode('&', $parts), PAYOS_CHECKSUM_KEY);
}

function payos_create_payment_link(array $order, array $items): ?string
{
    if (PAYOS_CLIENT_ID === '' || PAYOS_API_KEY === '' || PAYOS_CHECKSUM_KEY === '' || !function_exists('curl_init')) {
        $_SESSION['payos_last_error'] = 'PayOS chưa được cấu hình hoặc PHP chưa bật curl.';
        return null;
    }

    $orderId = (int) $order['id'];
    $orderCode = payos_order_code($orderId);
    $amount = (int) max(0, round((float) $order['total_amount']));
    $description = order_payment_code($orderId);
    $returnUrl = url('payment_success', ['id' => $orderId]);
    $cancelUrl = url('payment_success', ['id' => $orderId, 'status' => 'CANCELLED']);

    $signature = payos_signature([
        'amount' => $amount,
        'cancelUrl' => $cancelUrl,
        'description' => $description,
        'orderCode' => $orderCode,
        'returnUrl' => $returnUrl,
    ]);

    $payosItems = [];
    foreach ($items as $item) {
        $payosItems[] = [
            'name' => short_text((string) $item['title'], 90),
            'quantity' => (int) $item['quantity'],
            'price' => (int) round((float) $item['price']),
        ];
    }

    $payload = [
        'orderCode' => $orderCode,
        'amount' => $amount,
        'description' => $description,
        'returnUrl' => $returnUrl,
        'cancelUrl' => $cancelUrl,
        'items' => $payosItems,
        'signature' => $signature,
    ];

    $curl = curl_init(PAYOS_API_URL);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-client-id: ' . PAYOS_CLIENT_ID,
            'x-api-key: ' . PAYOS_API_KEY,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 20,
    ]);

    $response = curl_exec($curl);
    $error = curl_error($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    $result = is_string($response) ? json_decode($response, true) : null;
    $data = is_array($result) ? ($result['data'] ?? $result) : null;

    if (!is_array($data)) {
        $_SESSION['payos_last_error'] = $error !== ''
            ? 'Không kết nối được PayOS: ' . $error
            : 'PayOS trả về dữ liệu không hợp lệ.';
        return null;
    }

    if ($status >= 400 || (($result['code'] ?? '00') !== '00' && empty($data['checkoutUrl']))) {
        $_SESSION['payos_last_error'] = (string) ($result['desc'] ?? $result['message'] ?? 'PayOS từ chối yêu cầu thanh toán.');
        return null;
    }

    $checkoutUrl = $data['checkoutUrl'] ?? null;
    $paymentLinkId = $data['paymentLinkId'] ?? null;

    $stmt = db()->prepare('UPDATE orders SET payment_provider = ?, payos_order_code = ?, payos_payment_link_id = ?, payos_checkout_url = ? WHERE id = ?');
    $stmt->execute(['payos', $orderCode, $paymentLinkId, $checkoutUrl, $orderId]);
    unset($_SESSION['payos_last_error']);

    return is_string($checkoutUrl) ? $checkoutUrl : null;
}

function payos_verify_webhook(array $data, string $signature): bool
{
    return hash_equals(payos_signature($data), $signature);
}

function send_order_paid_email(int $orderId): void
{
    $order = find_order($orderId);

    if (!$order) {
        return;
    }

    $receipt = url('receipt', ['id' => $orderId]);
    $learn = url('my_courses');
    $body = '<p>Chào ' . e($order['customer_name']) . ',</p>'
        . '<p>Đơn hàng #' . $orderId . ' đã thanh toán thành công.</p>'
        . '<p><a href="' . e($learn) . '">Vào khóa học của tôi</a></p>'
        . '<p><a href="' . e($receipt) . '">Xem biên lai thanh toán</a></p>'
        . '<p>Cảm ơn bạn đã học cùng ' . e(APP_NAME) . '.</p>';

    send_app_mail((string) $order['customer_email'], 'Hóa đơn và hướng dẫn vào học - ' . APP_NAME, $body, (int) $order['user_id'], true);
}

function is_favorite_course(int $courseId, ?int $userId = null): bool
{
    $userId = $userId ?? (int) (current_user()['id'] ?? 0);

    if ($userId <= 0) {
        return false;
    }

    $stmt = db()->prepare('SELECT COUNT(*) FROM favorites WHERE user_id = ? AND course_id = ?');
    $stmt->execute([$userId, $courseId]);

    return (int) $stmt->fetchColumn() > 0;
}

function course_reviews(int $courseId): array
{
    $stmt = db()->prepare('SELECT course_reviews.*, users.name FROM course_reviews JOIN users ON users.id = course_reviews.user_id WHERE course_reviews.course_id = ? AND course_reviews.is_visible = 1 ORDER BY course_reviews.created_at DESC');
    $stmt->execute([$courseId]);

    return $stmt->fetchAll();
}

function course_average_rating(int $courseId): float
{
    $stmt = db()->prepare('SELECT COALESCE(AVG(rating), 0) FROM course_reviews WHERE course_id = ? AND is_visible = 1');
    $stmt->execute([$courseId]);

    return round((float) $stmt->fetchColumn(), 1);
}

function lesson_progress(int $courseId, int $lessonIndex): ?array
{
    $userId = (int) (current_user()['id'] ?? 0);

    if ($userId <= 0) {
        return null;
    }

    $stmt = db()->prepare('SELECT * FROM course_lesson_progress WHERE user_id = ? AND course_id = ? AND lesson_index = ?');
    $stmt->execute([$userId, $courseId, $lessonIndex]);
    $progress = $stmt->fetch();

    return $progress ?: null;
}

function is_lesson_unlocked(int $courseId, int $lessonIndex): bool
{
    if (is_admin()) {
        return true;
    }

    if ($lessonIndex <= 0) {
        return true;
    }

    $previous = lesson_progress($courseId, $lessonIndex - 1);

    return $previous !== null && (int) $previous['is_completed'] === 1;
}

function lesson_questions(int $courseId, ?int $lessonIndex = null): array
{
    $sql = 'SELECT course_questions.*, users.name
        FROM course_questions
        JOIN users ON users.id = course_questions.user_id
        WHERE course_questions.course_id = ?';
    $params = [$courseId];

    if ($lessonIndex !== null) {
        $sql .= ' AND (course_questions.lesson_index = ? OR course_questions.lesson_index IS NULL)';
        $params[] = $lessonIndex;
    }

    $sql .= ' ORDER BY course_questions.created_at DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function lesson_materials_for(int $courseId, int $lessonIndex): array
{
    $course = find_course($courseId, false);
    $lesson = $course ? (course_flat_lessons($course)[$lessonIndex] ?? null) : null;
    $lessonId = (int) ($lesson['id'] ?? 0);

    if ($lessonId > 0) {
        $stmt = db()->prepare('SELECT material_type AS type, title, file_url AS filename FROM lesson_materials WHERE lesson_id = ? ORDER BY id');
        $stmt->execute([$lessonId]);
        $materials = $stmt->fetchAll();

        return $materials;
    }

    return [];
}

function lesson_practice_for(int $courseId, int $lessonIndex): ?array
{
    $course = find_course($courseId, false);
    $lesson = $course ? (course_flat_lessons($course)[$lessonIndex] ?? null) : null;
    $lessonId = (int) ($lesson['id'] ?? 0);

    if ($lessonId > 0) {
        $stmt = db()->prepare('SELECT * FROM lesson_practices WHERE lesson_id = ? ORDER BY id LIMIT 1');
        $stmt->execute([$lessonId]);
        $practice = $stmt->fetch();

        return $practice ?: null;
    }

    return null;
}

function quiz_questions_for(int $courseId, int $lessonIndex): array
{
    $course = find_course($courseId, false);
    $lesson = $course ? (course_flat_lessons($course)[$lessonIndex] ?? null) : null;
    $lessonId = (int) ($lesson['id'] ?? 0);

    if ($lessonId > 0) {
        $stmt = db()->prepare('SELECT quiz_questions.*
            FROM quizzes
            JOIN quiz_questions ON quiz_questions.quiz_id = quizzes.id
            WHERE quizzes.lesson_id = ?
            ORDER BY quiz_questions.id');
        $stmt->execute([$lessonId]);
        $rows = $stmt->fetchAll();

        if ($rows) {
            $questions = [];

            foreach ($rows as $row) {
                if (($row['question_type'] ?? 'choice') === 'essay') {
                    $questions[] = [
                        'type' => 'essay',
                        'question' => $row['question'],
                        'correct_answer' => $row['option_a'] ?? '',
                        'hint' => $row['sample_answer'] ?? '',
                        'sample_answer' => $row['sample_answer'] ?? '',
                    ];
                    continue;
                }

                $questions[] = [
                    'type' => 'choice',
                    'question' => $row['question'],
                    'options' => [
                        'a' => $row['option_a'],
                        'b' => $row['option_b'],
                        'c' => $row['option_c'],
                        'd' => $row['option_d'],
                    ],
                    'answer' => strtolower((string) $row['correct_option']),
                ];
            }

            return $questions;
        }
    }

    return [];
}

function compiler_languages(): array
{
    return [
        'html' => ['label' => 'HTML/CSS/JS', 'file' => 'index.html', 'preview' => true],
        'php' => ['label' => 'PHP', 'file' => 'main.php', 'command' => ['php', '{file}'], 'piston_language' => 'php'],
        'python' => ['label' => 'Python', 'file' => 'main.py', 'command' => ['python', '{file}'], 'piston_language' => 'python'],
        'javascript' => ['label' => 'JavaScript', 'file' => 'main.js', 'command' => ['node', '{file}'], 'piston_language' => 'javascript'],
        'c' => ['label' => 'C', 'file' => 'main.c', 'compile' => ['gcc', '{file}', '-o', '{exe}'], 'command' => ['{exe}'], 'piston_language' => 'c'],
        'cpp' => ['label' => 'C++', 'file' => 'main.cpp', 'compile' => ['g++', '{file}', '-o', '{exe}'], 'command' => ['{exe}'], 'piston_language' => 'c++'],
        'java' => ['label' => 'Java', 'file' => 'Main.java', 'compile' => ['javac', '{file}'], 'command' => ['java', '-cp', '{dir}', 'Main'], 'piston_language' => 'java'],
    ];
}

function compiler_command_available(string $command): bool
{
    if (str_starts_with($command, '{')) {
        return true;
    }

    static $cache = [];
    if (array_key_exists($command, $cache)) {
        return $cache[$command];
    }

    $checker = PHP_OS_FAMILY === 'Windows' ? ['where', $command] : ['command', '-v', $command];
    $process = @proc_open($checker, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

    if (!is_resource($process)) {
        return $cache[$command] = false;
    }

    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    $text = trim((string) $output . (string) $error);

    if ($command === 'python' && stripos($text, 'Microsoft\\WindowsApps\\python.exe') !== false) {
        return $cache[$command] = false;
    }

    return $cache[$command] = ($exitCode === 0 && $text !== '');
}

function compiler_runtime_available(array $runtime): bool
{
    $commands = [];
    if (!empty($runtime['compile'])) {
        $commands[] = $runtime['compile'][0];
    }
    if (!empty($runtime['command']) && !str_starts_with((string) $runtime['command'][0], '{')) {
        $commands[] = $runtime['command'][0];
    }

    foreach (array_unique($commands) as $command) {
        if (!compiler_command_available((string) $command)) {
            return false;
        }
    }

    return true;
}

function compiler_available_languages(): array
{
    $languages = compiler_languages();

    foreach ($languages as $key => $language) {
        if (!empty($language['preview']) || compiler_runtime_available($language)) {
            continue;
        }

        if (!str_contains((string) PISTON_API_BASE, 'emkc.org')) {
            continue;
        }

        unset($languages[$key]);
    }

    return $languages;
}

function compiler_sample_code(string $language): string
{
    if ($language === 'html') {
        return "<!DOCTYPE html>\n<html lang=\"vi\">\n<head>\n    <meta charset=\"UTF-8\">\n    <title>Demo HTML</title>\n    <style>\n        body { font-family: Arial, sans-serif; text-align: center; margin-top: 50px; }\n        #dong-ho { color: #2563eb; font-size: 48px; }\n        button { margin: 4px; padding: 10px 18px; }\n    </style>\n</head>\n<body>\n    <h1>Đồng hồ đếm giây</h1>\n    <h2 id=\"dong-ho\">0</h2>\n    <button onclick=\"batDau()\">Bắt đầu</button>\n    <button onclick=\"dungLai()\">Dừng lại</button>\n    <button onclick=\"lamMoi()\">Làm mới</button>\n\n    <script>\n        let giay = 0;\n        let timer = null;\n\n        function capNhat() {\n            giay++;\n            document.getElementById('dong-ho').textContent = giay;\n        }\n\n        function batDau() {\n            if (!timer) timer = setInterval(capNhat, 1000);\n        }\n\n        function dungLai() {\n            clearInterval(timer);\n            timer = null;\n        }\n\n        function lamMoi() {\n            dungLai();\n            giay = 0;\n            document.getElementById('dong-ho').textContent = giay;\n        }\n    </script>\n</body>\n</html>\n";
    }

    if ($language === 'python') {
        return "print('Hello InnoCode')\n";
    }

    if ($language === 'javascript') {
        return "console.log('Hello InnoCode');\n";
    }

    if ($language === 'c') {
        return "#include <stdio.h>\n\nint main(void) {\n    printf(\"Hello InnoCode\");\n    return 0;\n}\n";
    }

    if ($language === 'cpp') {
        return "#include <iostream>\n\nint main() {\n    std::cout << \"Hello InnoCode\";\n    return 0;\n}\n";
    }

    if ($language === 'java') {
        return "public class Main {\n    public static void main(String[] args) {\n        System.out.print(\"Hello InnoCode\");\n    }\n}\n";
    }

    return "<?php\necho \"Hello InnoCode\";\n";
}

function run_code(string $language, string $code): array
{
    return run_code_multi($language, $code);
}

function run_code_multi(string $language, string $code, string $stdin = ''): array
{
    $languages = compiler_languages();
    $availableLanguages = compiler_available_languages();

    if (!isset($languages[$language]) || !isset($availableLanguages[$language])) {
        return ['ok' => false, 'output' => 'Ngôn ngữ này hiện chưa chạy được trên máy chủ. Hãy cài runtime tương ứng hoặc cấu hình PISTON_API_BASE tới Piston server riêng.'];
    }

    $stdin = compiler_normalize_stdin($stdin);

    if (!empty($languages[$language]['preview'])) {
        return ['ok' => true, 'output' => $code, 'preview' => true];
    }

    $precheck = compiler_precheck_code($language, $code);
    if ($precheck !== null) {
        return ['ok' => false, 'output' => $precheck];
    }

    $pistonResult = run_code_piston($language, $code, $stdin);
    if ($pistonResult['ok'] || !compiler_runtime_available($languages[$language])) {
        return compiler_format_result($language, $pistonResult);
    }

    $runtime = $languages[$language];

    $baseDir = dirname(__DIR__) . '/storage/compiler';
    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0777, true);
    }

    $dir = $baseDir . '/' . uniqid('run_', true);
    mkdir($dir, 0777, true);
    $file = $dir . '/' . $runtime['file'];
    $exe = $dir . '/program' . (PHP_OS_FAMILY === 'Windows' ? '.exe' : '');
    file_put_contents($file, $code);

    $cleanup = static function (string $path) use (&$cleanup): void {
        if (!is_dir($path)) {
            return;
        }

        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $item) {
            $child = $path . DIRECTORY_SEPARATOR . $item;
            is_dir($child) ? $cleanup($child) : @unlink($child);
        }

        @rmdir($path);
    };

    $buildCommand = static function (array $command) use ($file, $exe, $dir): array {
        $result = [];

        foreach ($command as $part) {
            if ($part === '{file}') {
                $result[] = $file;
            } elseif ($part === '{exe}') {
                $result[] = $exe;
            } elseif ($part === '{dir}') {
                $result[] = $dir;
            } else {
                $result[] = $part;
            }
        }

        return $result;
    };

    $execute = static function (array $command, string $cwd, string $input = ''): array {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = @proc_open($command, $descriptors, $pipes, $cwd);

        if (!is_resource($process)) {
            return ['ok' => false, 'output' => 'Không khởi động được trình biên dịch. Kiểm tra runtime đã được cài trong PATH chưa.'];
        }

        if ($input !== '') {
            fwrite($pipes[0], $input);
        }
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return [
            'ok' => $exitCode === 0,
            'output' => trim((string) $output . ($error ? "\n" . $error : '')),
        ];
    };

    if (!empty($runtime['compile'])) {
        $compiled = $execute($buildCommand($runtime['compile']), $dir);

        if (!$compiled['ok']) {
            $cleanup($dir);
            return [
                'ok' => false,
                'output' => $compiled['output'] !== ''
                    ? $compiled['output']
                    : 'Biên dịch thất bại. Hãy kiểm tra cú pháp hoặc cài compiler tương ứng.',
            ];
        }
    }

    $result = $execute($buildCommand($runtime['command']), $dir, $stdin);
    $cleanup($dir);

    return compiler_format_result($language, [
        'ok' => $result['ok'],
        'output' => $result['output'] !== '' ? $result['output'] : '(Chương trình không in ra kết quả.)',
    ]);
}

function compiler_format_result(string $language, array $result): array
{
    $output = (string) ($result['output'] ?? '');

    if ($language === 'python' && str_contains($output, "ModuleNotFoundError: No module named 'requests'")) {
        $result['output'] = "Python trên compiler hiện không cài thư viện requests.\n\nCách xử lý:\n- Dùng thư viện có sẵn như urllib.request cho ví dụ cơ bản.\n- Hoặc chạy code này trên máy cá nhân sau khi cài: pip install requests.\n- Lưu ý: compiler web này phù hợp với bài console cơ bản, không phù hợp để mở trình duyệt hoặc gọi API ngoài.";
        return $result;
    }

    if ($language === 'python' && str_contains($output, 'Python was not found')) {
        $result['output'] = "Máy chủ chưa cài Python thật trong PATH.\n\nWindows đang trỏ lệnh python tới Microsoft Store alias nên PHP không chạy được file .py.\n\nCách xử lý:\n1. Cài Python từ python.org.\n2. Khi cài, chọn Add python.exe to PATH.\n3. Tắt alias Microsoft Store tại Settings > Apps > Advanced app settings > App execution aliases.\n4. Khởi động lại Laragon/Apache rồi thử lại.";
        return $result;
    }

    if (str_contains($output, 'EOFError: EOF when reading a line')) {
        $result['output'] = trim($output) . "\n\nGợi ý: chương trình đang gọi input() nhiều hơn số dòng bạn nhập ở ô Input stdin. Hãy nhập mỗi giá trị trên một dòng, ví dụ:\n8\n5\n3";
        return $result;
    }

    if ($language === 'php' && preg_match('/<([a-z][a-z0-9]*)\b[^>]*>/i', $output)) {
        $result['preview'] = true;
    }

    return $result;
}

function compiler_precheck_code(string $language, string $code): ?string
{
    if ($language !== 'python') {
        return null;
    }

    if (preg_match('/^\s*import\s+webbrowser\b|^\s*from\s+webbrowser\s+import\b/m', $code)) {
        return "Python trên compiler web không thể mở trình duyệt bằng webbrowser.open().\n\nHãy dùng compiler này cho bài console, tính toán, input/output cơ bản. Nếu muốn hiển thị giao diện, chọn HTML/CSS/JS.";
    }

    if (preg_match('/^\s*import\s+requests\b|^\s*from\s+requests\s+import\b/m', $code)) {
        return "Python trên compiler hiện không cài thư viện requests.\n\nBạn có thể đổi sang urllib.request cho ví dụ cơ bản, hoặc chạy trên máy cá nhân sau khi cài: pip install requests.";
    }

    return null;
}

function compiler_normalize_stdin(string $stdin): string
{
    if ($stdin === '') {
        return '';
    }

    return str_ends_with($stdin, "\n") ? $stdin : $stdin . "\n";
}

function run_code_piston(string $language, string $code, string $stdin = ''): array
{
    $languages = compiler_languages();

    if (!isset($languages[$language])) {
        return ['ok' => false, 'output' => 'Ngôn ngữ chưa được hỗ trợ.'];
    }

    $runtime = $languages[$language];
    $pistonLanguage = (string) ($runtime['piston_language'] ?? $language);
    $response = piston_api_request('/execute', [
        'language' => $pistonLanguage,
        'version' => piston_runtime_version($pistonLanguage),
        'files' => [[
            'name' => (string) $runtime['file'],
            'content' => $code,
        ]],
        'stdin' => $stdin,
        'args' => [],
        'compile_timeout' => 10000,
        'run_timeout' => 5000,
    ]);

    if (!$response['ok']) {
        return ['ok' => false, 'output' => $response['error']];
    }

    $data = $response['data'];
    $compile = is_array($data['compile'] ?? null) ? $data['compile'] : null;
    $run = is_array($data['run'] ?? null) ? $data['run'] : null;
    $chunks = [];

    if ($compile && trim((string) ($compile['output'] ?? '')) !== '') {
        $chunks[] = trim((string) $compile['output']);
    }

    if ($run && trim((string) ($run['output'] ?? '')) !== '') {
        $chunks[] = trim((string) $run['output']);
    }

    $exitCode = (int) ($run['code'] ?? $compile['code'] ?? 0);
    $signal = (string) ($run['signal'] ?? $compile['signal'] ?? '');
    $output = trim(implode("\n", $chunks));

    if ($signal !== '') {
        $output .= ($output !== '' ? "\n" : '') . 'Process stopped by signal: ' . $signal;
    }

    return [
        'ok' => $exitCode === 0 && $signal === '',
        'output' => $output !== '' ? $output : '(Chương trình không in ra kết quả.)',
    ];
}

function piston_runtime_version(string $language): string
{
    static $versions = null;

    if ($versions === null) {
        $versions = [];
        $response = piston_api_request('/runtimes');

        if ($response['ok'] && is_array($response['data'])) {
            foreach ($response['data'] as $runtime) {
                if (!is_array($runtime) || empty($runtime['version'])) {
                    continue;
                }

                $names = [(string) ($runtime['language'] ?? '')];
                foreach (($runtime['aliases'] ?? []) as $alias) {
                    $names[] = (string) $alias;
                }

                foreach ($names as $name) {
                    if ($name !== '' && !isset($versions[$name])) {
                        $versions[$name] = (string) $runtime['version'];
                    }
                }
            }
        }
    }

    return $versions[$language] ?? '*';
}

function piston_api_request(string $path, ?array $payload = null): array
{
    $url = rtrim(PISTON_API_BASE, '/') . '/' . ltrim($path, '/');
    $body = $payload === null ? null : json_encode($payload, JSON_UNESCAPED_UNICODE);
    $headers = ['Content-Type: application/json', 'Accept: application/json'];

    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $payload === null ? 'GET' : 'POST',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
        ]);

        $raw = curl_exec($curl);
        $error = curl_error($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => $payload === null ? 'GET' : 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $body ?? '',
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);
        $raw = @file_get_contents($url, false, $context);
        $error = '';
        $status = 0;

        foreach (($http_response_header ?? []) as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $matches)) {
                $status = (int) $matches[1];
                break;
            }
        }
    }

    if (!is_string($raw) || $raw === '') {
        return [
            'ok' => false,
            'error' => 'Không kết nối được Piston API. Vui lòng kiểm tra mạng hoặc PISTON_API_BASE.',
        ];
    }

    $data = json_decode($raw, true);

    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'Piston API trả về dữ liệu không hợp lệ.'];
    }

    if ($status >= 400) {
        $message = (string) ($data['message'] ?? 'Piston API trả về lỗi HTTP ' . $status . '.');
        if (stripos($message, 'whitelist') !== false) {
            $message = 'Piston API public hiện yêu cầu whitelist. Hãy cấu hình PISTON_API_BASE trỏ tới Piston server riêng, ví dụ http://localhost:2000/api/v2.';
        }

        return ['ok' => false, 'error' => $message];
    }

    if ($error !== '') {
        return ['ok' => false, 'error' => 'Lỗi Piston API: ' . $error];
    }

    return ['ok' => true, 'data' => $data];
}

function vnpay_payment_url(array $order): ?string
{
    if (VNPAY_TMN_CODE === '' || VNPAY_HASH_SECRET === '') {
        return null;
    }

    $params = [
        'vnp_Version' => '2.1.0',
        'vnp_Command' => 'pay',
        'vnp_TmnCode' => VNPAY_TMN_CODE,
        'vnp_Amount' => (int) ((float) $order['total_amount'] * 100),
        'vnp_CurrCode' => 'VND',
        'vnp_TxnRef' => (string) $order['id'],
        'vnp_OrderInfo' => 'Thanh toan don hang ' . order_payment_code((int) $order['id']),
        'vnp_OrderType' => 'billpayment',
        'vnp_Locale' => 'vn',
        'vnp_ReturnUrl' => url('payment_return_vnpay'),
        'vnp_IpAddr' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        'vnp_CreateDate' => date('YmdHis'),
    ];

    ksort($params);
    $hashData = [];
    $query = [];

    foreach ($params as $key => $value) {
        $hashData[] = $key . '=' . urlencode((string) $value);
        $query[] = urlencode($key) . '=' . urlencode((string) $value);
    }

    $secureHash = hash_hmac('sha512', implode('&', $hashData), VNPAY_HASH_SECRET);

    return VNPAY_URL . '?' . implode('&', $query) . '&vnp_SecureHash=' . $secureHash;
}

function momo_payment_url(array $order): ?string
{
    if (MOMO_PARTNER_CODE === '' || MOMO_ACCESS_KEY === '' || MOMO_SECRET_KEY === '') {
        return null;
    }

    if (!function_exists('curl_init')) {
        return null;
    }

    $requestId = time() . '_' . $order['id'];
    $orderId = (string) $order['id'];
    $amount = (string) (int) $order['total_amount'];
    $orderInfo = 'Thanh toán đơn hàng ' . order_payment_code((int) $order['id']);
    $redirectUrl = url('payment_return_momo');
    $ipnUrl = url('payment_return_momo');
    $extraData = '';
    $requestType = 'captureWallet';

    $raw = 'accessKey=' . MOMO_ACCESS_KEY
        . '&amount=' . $amount
        . '&extraData=' . $extraData
        . '&ipnUrl=' . $ipnUrl
        . '&orderId=' . $orderId
        . '&orderInfo=' . $orderInfo
        . '&partnerCode=' . MOMO_PARTNER_CODE
        . '&redirectUrl=' . $redirectUrl
        . '&requestId=' . $requestId
        . '&requestType=' . $requestType;

    $payload = [
        'partnerCode' => MOMO_PARTNER_CODE,
        'accessKey' => MOMO_ACCESS_KEY,
        'requestId' => $requestId,
        'amount' => $amount,
        'orderId' => $orderId,
        'orderInfo' => $orderInfo,
        'redirectUrl' => $redirectUrl,
        'ipnUrl' => $ipnUrl,
        'extraData' => $extraData,
        'requestType' => $requestType,
        'signature' => hash_hmac('sha256', $raw, MOMO_SECRET_KEY),
        'lang' => 'vi',
    ];

    $curl = curl_init(MOMO_ENDPOINT);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 12,
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    $data = is_string($response) ? json_decode($response, true) : null;

    return is_array($data) ? ($data['payUrl'] ?? null) : null;
}


