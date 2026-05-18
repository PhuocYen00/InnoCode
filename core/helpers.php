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

    return match ($name) {
        '', 'index' => 'home',
        default => $name,
    };
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
    return ($_SESSION['admin_logged_in'] ?? false) === true;
}

function require_admin(): void
{
    if (!is_admin()) {
        redirect('admin/login.php');
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
    $user = current_user();

    if (!$user || $user['email_verified_at'] === null) {
        flash('error', 'Vui lòng xác thực email trước khi thanh toán và học khóa học.');
        redirect('verify_notice.php');
    }
}

function login_user(array $user): void
{
    $_SESSION['user_id'] = (int) $user['id'];
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

function send_app_mail(string $recipient, string $subject, string $body, ?int $userId = null): void
{
    $stmt = db()->prepare('INSERT INTO email_logs (user_id, recipient, subject, body) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, $recipient, $subject, $body]);

    if (SMTP_USERNAME !== '' && SMTP_PASSWORD !== '') {
        smtp_send_mail($recipient, $subject, $body);
        return;
    }

    $from = MAIL_FROM !== '' ? MAIL_FROM : 'noreply@localhost';
    @mail($recipient, $subject, $body, 'From: ' . MAIL_FROM_NAME . ' <' . $from . '>');
}

function smtp_send_mail(string $recipient, string $subject, string $body): void
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
        'Content-Type: text/plain; charset=UTF-8',
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
        $sql .= ' AND (courses.title LIKE :keyword OR courses.description LIKE :keyword)';
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

function cart_add(string $type, int $id): void
{
    require_login();

    if ($type !== 'course') {
        return;
    }

    $stmt = db()->prepare('INSERT INTO cart_items (user_id, course_id, quantity) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE quantity = quantity + 1');
    $stmt->execute([(int) current_user()['id'], $id]);
}

function cart_remove(string $key): void
{
    require_login();
    $courseId = cart_course_id_from_key($key);
    $stmt = db()->prepare('DELETE FROM cart_items WHERE user_id = ? AND course_id = ?');
    $stmt->execute([(int) current_user()['id'], $courseId]);
}

function cart_update(array $quantities): void
{
    require_login();

    foreach ($quantities as $key => $quantity) {
        $quantity = max(0, (int) $quantity);
        $courseId = cart_course_id_from_key((string) $key);

        if ($quantity === 0) {
            cart_remove((string) $key);
        } else {
            $stmt = db()->prepare('UPDATE cart_items SET quantity = ? WHERE user_id = ? AND course_id = ?');
            $stmt->execute([$quantity, (int) current_user()['id'], $courseId]);
        }
    }
}

function cart_items_count(): int
{
    if (!is_logged_in()) {
        return 0;
    }

    $stmt = db()->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE user_id = ?');
    $stmt->execute([(int) current_user()['id']]);

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

    return array_map(static function (array $item): array {
        return [
            'key' => cart_key('course', (int) $item['course_id']),
            'type' => 'course',
            'id' => (int) $item['course_id'],
            'title' => $item['title'],
            'price' => (float) $item['price'],
            'quantity' => (int) $item['quantity'],
            'line_total' => (float) $item['price'] * (int) $item['quantity'],
            'description' => ($item['level'] ?? '') . ' · ' . ($item['duration_hours'] ?? '') . ' giờ',
        ];
    }, $stmt->fetchAll());
}

function cart_course_id_from_key(string $key): int
{
    if (str_contains($key, '_')) {
        [, $id] = explode('_', $key, 2);
        return (int) $id;
    }

    return (int) $key;
}

function clear_cart(int $userId): void
{
    $stmt = db()->prepare('DELETE FROM cart_items WHERE user_id = ?');
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
function course_sections(array $course): array
{
    $title = strtolower((string) $course['title']);

    if (str_contains($title, 'javascript')) {
        return course_javascript_sections();
    }

    if (str_contains($title, 'react')) {
        return course_react_sections();
    }

    if (str_contains($title, 'mysql')) {
        return course_mysql_sections();
    }

    return course_default_sections();
}

function course_javascript_sections(): array
{
    return [
        ['title' => '1. Nền tảng JavaScript', 'lessons' => [
            ['title' => 'Tổng quan khóa học', 'duration' => '04:18'],
            ['title' => 'Biến, kiểu dữ liệu và toán tử', 'duration' => '18:42'],
            ['title' => 'Function, scope và closure', 'duration' => '29:10'],
        ]],
        ['title' => '2. API và bất đồng bộ', 'lessons' => [
            ['title' => 'Promise và async/await', 'duration' => '31:06'],
            ['title' => 'Fetch API và JSON', 'duration' => '26:44'],
            ['title' => 'Mini project Todo App', 'duration' => '42:20'],
        ]],
    ];
}

function course_react_sections(): array
{
    return [
        ['title' => '1. React căn bản', 'lessons' => [
            ['title' => 'React giải quyết vấn đề gì?', 'duration' => '07:25'],
            ['title' => 'Component, props và JSX', 'duration' => '22:10'],
            ['title' => 'State và sự kiện', 'duration' => '27:40'],
        ]],
        ['title' => '2. Hooks và dự án', 'lessons' => [
            ['title' => 'useState và useEffect', 'duration' => '35:18'],
            ['title' => 'Gọi API trong React', 'duration' => '28:55'],
            ['title' => 'Build và deploy', 'duration' => '33:12'],
        ]],
    ];
}

function course_mysql_sections(): array
{
    return [
        ['title' => '1. Thiết kế database', 'lessons' => [
            ['title' => 'Bảng và khóa chính', 'duration' => '16:22'],
            ['title' => 'Khóa ngoại và quan hệ', 'duration' => '24:45'],
            ['title' => 'Database cho website bán hàng', 'duration' => '38:10'],
        ]],
        ['title' => '2. Truy vấn dữ liệu', 'lessons' => [
            ['title' => 'JOIN và GROUP BY', 'duration' => '34:18'],
            ['title' => 'Index cơ bản', 'duration' => '27:06'],
            ['title' => 'Transaction đặt hàng', 'duration' => '21:55'],
        ]],
    ];
}

function course_default_sections(): array
{
    return [
        ['title' => '1. Khởi động dự án', 'lessons' => [
            ['title' => 'Giới thiệu lộ trình học', 'duration' => '05:30'],
            ['title' => 'Cài đặt môi trường', 'duration' => '18:25'],
            ['title' => 'Tư duy xây dựng website', 'duration' => '20:40'],
        ]],
        ['title' => '2. Xây dựng chức năng', 'lessons' => [
            ['title' => 'Kết nối MySQL', 'duration' => '29:12'],
            ['title' => 'Danh sách và chi tiết', 'duration' => '32:05'],
            ['title' => 'Giỏ hàng và thanh toán', 'duration' => '41:30'],
        ]],
    ];
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
    $userId = $userId ?? (int) (current_user()['id'] ?? 0);

    if ($userId <= 0) {
        return false;
    }

    $stmt = db()->prepare('SELECT COUNT(*) FROM enrollments WHERE user_id = ? AND course_id = ?');
    $stmt->execute([$userId, $courseId]);

    return (int) $stmt->fetchColumn() > 0;
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
            mark_course_purchased((int) $item['course_id'], $orderId, (int) $order['user_id']);
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function order_items(int $orderId): array
{
    $stmt = db()->prepare('SELECT order_items.*, courses.title, courses.image_url, courses.level, courses.duration_hours
        FROM order_items
        JOIN courses ON courses.id = order_items.course_id
        WHERE order_items.order_id = ?');
    $stmt->execute([$orderId]);

    return $stmt->fetchAll();
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

