<?php

declare(strict_types=1);

function ensure_schema(): void
{
    static $done = false;

    if ($done) {
        return;
    }

    $done = true;
    $pdo = db();

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(190) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        phone VARCHAR(50) NULL,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
        email_verified_at DATETIME NULL,
        verification_token VARCHAR(100) NULL,
        reset_token VARCHAR(100) NULL,
        reset_expires_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    ensure_column('users', 'avatar_url', 'VARCHAR(500) NULL AFTER phone');
    ensure_column('users', 'role', "ENUM('user', 'admin') NOT NULL DEFAULT 'user' AFTER password_hash");

    $pdo->exec("CREATE TABLE IF NOT EXISTS cart_items (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        course_id INT UNSIGNED NOT NULL,
        quantity INT UNSIGNED NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_cart_user_course (user_id, course_id),
        CONSTRAINT fk_cart_items_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_cart_items_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS enrollments (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        course_id INT UNSIGNED NOT NULL,
        order_id INT UNSIGNED NULL,
        enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_enrollments_user_course (user_id, course_id),
        CONSTRAINT fk_enrollments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_enrollments_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS email_logs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NULL,
        recipient VARCHAR(190) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        body TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_email_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS app_settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value VARCHAR(255) NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS course_chapters (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        course_id INT UNSIGNED NOT NULL,
        title VARCHAR(190) NOT NULL,
        sort_order INT UNSIGNED NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_course_chapters_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS course_lessons (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        chapter_id INT UNSIGNED NOT NULL,
        title VARCHAR(190) NOT NULL,
        video_url VARCHAR(500) NULL,
        theory_content MEDIUMTEXT NULL,
        duration_minutes INT UNSIGNED NOT NULL DEFAULT 0,
        is_preview TINYINT(1) NOT NULL DEFAULT 0,
        unlock_order INT UNSIGNED NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_course_lessons_chapter FOREIGN KEY (chapter_id) REFERENCES course_chapters(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS lesson_materials (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        lesson_id INT UNSIGNED NOT NULL,
        title VARCHAR(190) NOT NULL,
        material_type ENUM('pdf', 'source_code', 'slide', 'link') NOT NULL DEFAULT 'pdf',
        file_url VARCHAR(500) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_lesson_materials_lesson FOREIGN KEY (lesson_id) REFERENCES course_lessons(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS lesson_practices (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        lesson_id INT UNSIGNED NOT NULL,
        title VARCHAR(190) NOT NULL,
        instruction MEDIUMTEXT NOT NULL,
        starter_code MEDIUMTEXT NULL,
        expected_output MEDIUMTEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_lesson_practices_lesson FOREIGN KEY (lesson_id) REFERENCES course_lessons(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS quizzes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        lesson_id INT UNSIGNED NOT NULL,
        title VARCHAR(190) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_quizzes_lesson FOREIGN KEY (lesson_id) REFERENCES course_lessons(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS quiz_questions (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        quiz_id INT UNSIGNED NOT NULL,
        question TEXT NOT NULL,
        option_a VARCHAR(255) NOT NULL,
        option_b VARCHAR(255) NOT NULL,
        option_c VARCHAR(255) NULL,
        option_d VARCHAR(255) NULL,
        correct_option CHAR(1) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_quiz_questions_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS lesson_progress (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        lesson_id INT UNSIGNED NOT NULL,
        is_completed TINYINT(1) NOT NULL DEFAULT 0,
        note TEXT NULL,
        completed_at DATETIME NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_lesson_progress_user_lesson (user_id, lesson_id),
        CONSTRAINT fk_lesson_progress_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_lesson_progress_lesson FOREIGN KEY (lesson_id) REFERENCES course_lessons(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS course_reviews (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        course_id INT UNSIGNED NOT NULL,
        rating TINYINT UNSIGNED NOT NULL,
        comment TEXT NULL,
        is_visible TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_course_reviews_user_course (user_id, course_id),
        CONSTRAINT fk_course_reviews_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_course_reviews_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS course_questions (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        course_id INT UNSIGNED NOT NULL,
        lesson_id INT UNSIGNED NULL,
        question TEXT NOT NULL,
        answer TEXT NULL,
        status ENUM('open', 'answered', 'closed') NOT NULL DEFAULT 'open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_course_questions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_course_questions_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS favorites (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        course_id INT UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_favorites_user_course (user_id, course_id),
        CONSTRAINT fk_favorites_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_favorites_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS coupons (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(60) NOT NULL UNIQUE,
        discount_type ENUM('percent', 'fixed') NOT NULL DEFAULT 'percent',
        discount_value DECIMAL(12,2) NOT NULL,
        starts_at DATETIME NULL,
        expires_at DATETIME NULL,
        usage_limit INT UNSIGNED NULL,
        used_count INT UNSIGNED NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS physical_products (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(190) NOT NULL,
        product_type ENUM('pdf', 'printed_document', 'souvenir') NOT NULL DEFAULT 'pdf',
        description TEXT NULL,
        price DECIMAL(12,2) NOT NULL DEFAULT 0,
        stock INT UNSIGNED NOT NULL DEFAULT 0,
        image_url VARCHAR(500) NULL,
        digital_file_url VARCHAR(500) NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    ensure_column('physical_products', 'digital_file_url', 'VARCHAR(500) NULL AFTER image_url');

    $pdo->exec("CREATE TABLE IF NOT EXISTS product_cart_items (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        product_id INT UNSIGNED NOT NULL,
        quantity INT UNSIGNED NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_product_cart_user_product (user_id, product_id),
        CONSTRAINT fk_product_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_product_cart_product FOREIGN KEY (product_id) REFERENCES physical_products(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS shipments (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        order_id INT UNSIGNED NOT NULL,
        receiver_name VARCHAR(190) NOT NULL,
        receiver_phone VARCHAR(50) NOT NULL,
        address TEXT NOT NULL,
        carrier VARCHAR(120) NULL,
        tracking_code VARCHAR(120) NULL,
        status ENUM('pending', 'packing', 'shipping', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
        shipped_at DATETIME NULL,
        delivered_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_shipments_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS physical_order_items (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        order_id INT UNSIGNED NOT NULL,
        product_id INT UNSIGNED NOT NULL,
        product_name VARCHAR(190) NOT NULL,
        price DECIMAL(12,2) NOT NULL,
        quantity INT UNSIGNED NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_physical_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        CONSTRAINT fk_physical_order_items_product FOREIGN KEY (product_id) REFERENCES physical_products(id)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS gift_requests (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        course_id INT UNSIGNED NOT NULL,
        product_id INT UNSIGNED NOT NULL,
        receiver_name VARCHAR(190) NOT NULL,
        receiver_phone VARCHAR(50) NOT NULL,
        address TEXT NOT NULL,
        status ENUM('pending', 'confirmed', 'shipping', 'delivered') NOT NULL DEFAULT 'pending',
        note TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_gift_user_course (user_id, course_id),
        CONSTRAINT fk_gift_requests_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_gift_requests_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        CONSTRAINT fk_gift_requests_product FOREIGN KEY (product_id) REFERENCES physical_products(id)
    )");

    ensure_column('orders', 'user_id', 'INT UNSIGNED NULL AFTER id');
    ensure_column('orders', 'payment_method', "VARCHAR(40) NOT NULL DEFAULT 'bank' AFTER total_amount");
    ensure_column('orders', 'payment_code', 'VARCHAR(80) NULL AFTER payment_method');
    ensure_column('orders', 'coupon_code', 'VARCHAR(60) NULL AFTER total_amount');
    ensure_column('orders', 'discount_amount', 'DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER coupon_code');
    ensure_column('orders', 'payment_provider', "VARCHAR(40) NOT NULL DEFAULT 'payos' AFTER payment_method");
    ensure_column('orders', 'payos_order_code', 'BIGINT UNSIGNED NULL AFTER payment_code');
    ensure_column('orders', 'payos_payment_link_id', 'VARCHAR(120) NULL AFTER payos_order_code');
    ensure_column('orders', 'payos_checkout_url', 'VARCHAR(500) NULL AFTER payos_payment_link_id');
    ensure_column('orders', 'paid_at', 'DATETIME NULL AFTER status');
    ensure_column('orders', 'updated_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    ensure_index('orders', 'idx_orders_user', 'user_id');
    ensure_column('physical_order_items', 'payment_status', "ENUM('paid_online', 'pay_later') NOT NULL DEFAULT 'pay_later' AFTER quantity");
    ensure_column('course_questions', 'lesson_index', 'INT UNSIGNED NULL AFTER lesson_id');

    $pdo->exec("CREATE TABLE IF NOT EXISTS course_lesson_progress (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        course_id INT UNSIGNED NOT NULL,
        lesson_index INT UNSIGNED NOT NULL,
        is_completed TINYINT(1) NOT NULL DEFAULT 0,
        note TEXT NULL,
        completed_at DATETIME NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_course_lesson_progress (user_id, course_id, lesson_index),
        CONSTRAINT fk_course_lesson_progress_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_course_lesson_progress_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS lesson_reports (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        course_id INT UNSIGNED NOT NULL,
        lesson_index INT UNSIGNED NULL,
        report_type VARCHAR(80) NOT NULL DEFAULT 'content_error',
        message TEXT NOT NULL,
        status ENUM('open', 'reviewing', 'resolved') NOT NULL DEFAULT 'open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_lesson_reports_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_lesson_reports_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS lesson_submissions (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        course_id INT UNSIGNED NOT NULL,
        lesson_index INT UNSIGNED NOT NULL,
        lesson_title VARCHAR(190) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        stored_name VARCHAR(255) NOT NULL,
        file_size INT UNSIGNED NOT NULL DEFAULT 0,
        note TEXT NULL,
        feedback TEXT NULL,
        status ENUM('submitted', 'reviewed') NOT NULL DEFAULT 'submitted',
        reviewed_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_lesson_submissions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_lesson_submissions_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    )");
    ensure_index('lesson_submissions', 'idx_lesson_submissions_course', 'course_id');
    ensure_index('lesson_submissions', 'idx_lesson_submissions_user', 'user_id');

    $pdo->exec("CREATE TABLE IF NOT EXISTS quiz_attempts (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        course_id INT UNSIGNED NOT NULL,
        lesson_index INT UNSIGNED NOT NULL,
        score INT UNSIGNED NOT NULL DEFAULT 0,
        total INT UNSIGNED NOT NULL DEFAULT 0,
        essay_answer TEXT NULL,
        feedback TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_quiz_attempts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_quiz_attempts_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    )");

    ensure_column('quiz_questions', 'question_type', "ENUM('choice', 'essay') NOT NULL DEFAULT 'choice' AFTER quiz_id");
    ensure_column('quiz_questions', 'sample_answer', 'TEXT NULL AFTER correct_option');

    try {
        $pdo->exec("INSERT IGNORE INTO coupons (code, discount_type, discount_value, usage_limit, is_active)
            VALUES ('INNO10', 'percent', 10, 200, 1), ('WELCOME50', 'fixed', 50000, 200, 1)");

        $adminHash = password_hash(ADMIN_PASSWORD, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password_hash, role, email_verified_at)
            VALUES ('Quản trị viên', 'admin@innocode.local', '', ?, 'admin', NOW())
            ON DUPLICATE KEY UPDATE role = 'admin', email_verified_at = COALESCE(email_verified_at, NOW())");
        $stmt->execute([$adminHash]);

        $pdo->exec("INSERT IGNORE INTO physical_products (id, name, product_type, description, price, stock, image_url, digital_file_url, is_active)
            VALUES
            (1, 'Sổ tay InnoCode', 'souvenir', 'Sổ ghi chú học lập trình in logo InnoCode.', 0, 100, 'https://images.unsplash.com/photo-1517842645767-c639042777db?auto=format&fit=crop&w=900&q=80', NULL, 1),
            (2, 'Áo thun InnoCode', 'souvenir', 'Áo thun cotton dành cho học viên InnoCode.', 0, 50, 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=900&q=80', NULL, 1),
            (3, 'Tài liệu PHP & MySQL', 'printed_document', 'Tài liệu học tập PHP & MySQL dạng file tải về.', 129000, 999, 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?auto=format&fit=crop&w=900&q=80', 'php-intro.pdf', 1),
            (4, 'Slide tóm tắt lập trình web', 'pdf', 'Bộ slide ôn tập kiến thức nền tảng lập trình web.', 49000, 999, 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=900&q=80', 'slide-tom-tat.txt', 1)");

        seed_free_demo_courses($pdo);
        seed_course_content_demo_data($pdo);
        seed_revenue_demo_data($pdo);
    } catch (PDOException $exception) {
        if (!in_array((string) $exception->getCode(), ['40001', 'HY000'], true)) {
            throw $exception;
        }
    }
}

function seed_course_content_demo_data(PDO $pdo): void
{
    $stmt = $pdo->prepare('SELECT setting_value FROM app_settings WHERE setting_key = ?');
    $stmt->execute(['demo_course_content_seeded']);

    if ($stmt->fetchColumn()) {
        return;
    }

    $chapterCount = (int) $pdo->query('SELECT COUNT(*) FROM course_chapters')->fetchColumn();
    if ($chapterCount > 0) {
        $stmt = $pdo->prepare('INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        $stmt->execute(['demo_course_content_seeded', '1']);
        return;
    }

    $courses = $pdo->query('SELECT id, title FROM courses ORDER BY id')->fetchAll();
    if (!$courses) {
        return;
    }

    $chapterExistsStmt = $pdo->prepare('SELECT COUNT(*) FROM course_chapters WHERE course_id = ?');
    $chapterStmt = $pdo->prepare('INSERT INTO course_chapters (course_id, title, sort_order) VALUES (?, ?, ?)');
    $lessonStmt = $pdo->prepare('INSERT INTO course_lessons (chapter_id, title, video_url, theory_content, duration_minutes, is_preview, unlock_order) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $materialStmt = $pdo->prepare('INSERT INTO lesson_materials (lesson_id, title, material_type, file_url) VALUES (?, ?, ?, ?)');
    $practiceStmt = $pdo->prepare('INSERT INTO lesson_practices (lesson_id, title, instruction, starter_code, expected_output) VALUES (?, ?, ?, ?, ?)');
    $quizStmt = $pdo->prepare('INSERT INTO quizzes (lesson_id, title) VALUES (?, ?)');
    $questionStmt = $pdo->prepare('INSERT INTO quiz_questions (quiz_id, question_type, question, option_a, option_b, option_c, option_d, correct_option, sample_answer) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');

    foreach ($courses as $course) {
        $courseId = (int) $course['id'];
        $chapterExistsStmt->execute([$courseId]);
        if ((int) $chapterExistsStmt->fetchColumn() > 0) {
            continue;
        }

        $chapterStmt->execute([$courseId, 'Chương 1 - Nền tảng và thực hành', 1]);
        $chapterId = (int) $pdo->lastInsertId();
        $lessonTemplates = course_content_seed_lessons((string) $course['title']);

        foreach ($lessonTemplates as $index => $lesson) {
            $videos = course_video_map()[course_video_key($course)]['lessons'];
            $lessonStmt->execute([
                $chapterId,
                $lesson['title'],
                $videos[$index % count($videos)],
                $lesson['theory'],
                $lesson['duration'],
                $index === 0 ? 1 : 0,
                $index + 1,
            ]);
            $lessonId = (int) $pdo->lastInsertId();

            $materialStmt->execute([$lessonId, 'PDF bài học', 'pdf', 'php-intro.pdf']);
            $materialStmt->execute([$lessonId, 'Source code mẫu', 'source_code', 'source-mau.php']);
            $practiceStmt->execute([$lessonId, 'Bài thực hành - ' . $lesson['title'], $lesson['practice'], $lesson['starter_code'], $lesson['expected_output']]);

            $quizStmt->execute([$lessonId, 'Quiz - ' . $lesson['title']]);
            $quizId = (int) $pdo->lastInsertId();
            $questionStmt->execute([$quizId, 'choice', $lesson['quiz_question'], $lesson['option_a'], $lesson['option_b'], $lesson['option_c'], $lesson['option_d'], 'a', '']);
            $questionStmt->execute([$quizId, 'essay', 'Mô tả ngắn cách áp dụng nội dung bài học này vào một chức năng thực tế.', 'Câu trả lời cần nêu được mục tiêu, bước xử lý chính và kết quả mong muốn.', '-', '', '', 'a', 'Gợi ý: liên hệ với một màn hình hoặc luồng nghiệp vụ trong website.']);
        }
    }

    $stmt = $pdo->prepare('INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    $stmt->execute(['demo_course_content_seeded', '1']);
}

function course_content_seed_lessons(string $courseTitle): array
{
    $lowerTitle = strtolower($courseTitle);
    $topic = str_contains($lowerTitle, 'javascript') ? 'JavaScript'
        : (str_contains($lowerTitle, 'react') ? 'React'
        : (str_contains($lowerTitle, 'mysql') || str_contains($lowerTitle, 'sql') ? 'SQL'
        : (str_contains($lowerTitle, 'laravel') ? 'Laravel' : 'PHP')));

    return [
        [
            'title' => 'Tổng quan và môi trường học',
            'theory' => 'Bài học giới thiệu mục tiêu khóa học, công cụ cần chuẩn bị và cách tổ chức thư mục khi thực hành.',
            'duration' => 18,
            'practice' => 'Tạo cấu trúc thư mục thực hành cho chủ đề ' . $topic . ', thêm file README mô tả mục tiêu và nộp lại file nén hoặc source code.',
            'starter_code' => "README.md\nsrc/\npublic/",
            'expected_output' => 'Có cấu trúc thư mục rõ ràng và README mô tả đúng mục tiêu.',
            'quiz_question' => 'Mục tiêu chính của bước chuẩn bị môi trường là gì?',
            'option_a' => 'Đảm bảo người học có công cụ và cấu trúc thực hành thống nhất',
            'option_b' => 'Tăng giá khóa học',
            'option_c' => 'Ẩn nội dung khỏi admin',
            'option_d' => 'Bỏ qua phần thực hành',
        ],
        [
            'title' => 'Kiến thức cốt lõi',
            'theory' => 'Bài học tập trung vào khái niệm cốt lõi, cú pháp thường dùng và lỗi phổ biến khi triển khai.',
            'duration' => 28,
            'practice' => 'Viết một ví dụ nhỏ sử dụng kiến thức cốt lõi của ' . $topic . '. Code cần có input, xử lý và output rõ ràng.',
            'starter_code' => $topic === 'SQL' ? "SELECT * FROM users WHERE id = 1;" : "<?php\nfunction demo() {\n    return 'InnoCode';\n}\n",
            'expected_output' => 'Chạy được ví dụ và giải thích được từng bước xử lý.',
            'quiz_question' => 'Khi học kiến thức cốt lõi, điều nào quan trọng nhất?',
            'option_a' => 'Hiểu mục đích sử dụng và tự viết được ví dụ',
            'option_b' => 'Chỉ xem video',
            'option_c' => 'Không cần kiểm thử kết quả',
            'option_d' => 'Sao chép code mà không đọc',
        ],
        [
            'title' => 'Bài tập ứng dụng',
            'theory' => 'Bài học hướng dẫn áp dụng kiến thức vào một chức năng nhỏ, có luồng xử lý và dữ liệu đầu ra cụ thể.',
            'duration' => 35,
            'practice' => 'Xây dựng một chức năng nhỏ theo chủ đề khóa học, nộp source code và ghi chú cách chạy.',
            'starter_code' => '',
            'expected_output' => 'Chức năng chạy được, có ghi chú cách kiểm tra và không báo lỗi cú pháp.',
            'quiz_question' => 'Một bài tập ứng dụng tốt cần có gì?',
            'option_a' => 'Yêu cầu rõ, code chạy được và có kết quả kiểm tra',
            'option_b' => 'Chỉ có giao diện',
            'option_c' => 'Không cần nộp bài',
            'option_d' => 'Không cần dữ liệu mẫu',
        ],
    ];
}

function seed_free_demo_courses(PDO $pdo): void
{
    $pdo->exec("INSERT INTO categories (name, slug) VALUES
        ('Backend', 'backend'),
        ('Frontend', 'frontend'),
        ('Fullstack', 'fullstack'),
        ('Database', 'database')
        ON DUPLICATE KEY UPDATE name = VALUES(name)");

    $freeCourses = [
        [
            'frontend',
            'HTML CSS nhập môn miễn phí',
            'Làm quen cấu trúc trang web, bố cục responsive, form, button và cách dựng giao diện đầu tiên bằng HTML CSS.',
            'Beginner',
            8,
            'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=1200&q=80',
        ],
        [
            'frontend',
            'JavaScript DOM mini project miễn phí',
            'Thực hành DOM, event, form validation và xây dựng các tương tác nhỏ để chuẩn bị học frontend nâng cao.',
            'Beginner',
            10,
            'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1200&q=80',
        ],
        [
            'database',
            'SQL căn bản miễn phí',
            'Nắm SELECT, WHERE, JOIN, GROUP BY và tư duy đọc dữ liệu cho các dự án web thương mại điện tử.',
            'Beginner',
            6,
            'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=1200&q=80',
        ],
    ];

    $categoryStmt = $pdo->prepare('SELECT id FROM categories WHERE slug = ?');
    $existsStmt = $pdo->prepare('SELECT id FROM courses WHERE title = ? LIMIT 1');
    $insertStmt = $pdo->prepare('INSERT INTO courses (category_id, title, description, price, level, duration_hours, image_url, is_active) VALUES (?, ?, ?, 0, ?, ?, ?, 1)');

    foreach ($freeCourses as [$slug, $title, $description, $level, $durationHours, $imageUrl]) {
        $existsStmt->execute([$title]);
        if ($existsStmt->fetchColumn()) {
            continue;
        }

        $categoryStmt->execute([$slug]);
        $categoryId = (int) $categoryStmt->fetchColumn();
        if ($categoryId <= 0) {
            continue;
        }

        $insertStmt->execute([$categoryId, $title, $description, $level, $durationHours, $imageUrl]);
    }
}

function seed_revenue_demo_data(PDO $pdo): void
{
    $courseIds = $pdo->query('SELECT id, price FROM courses WHERE is_active = 1 ORDER BY id LIMIT 6')->fetchAll();
    if (!$courseIds) {
        return;
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute(['demo.student@innocode.local']);
    $userId = (int) $stmt->fetchColumn();

    if ($userId <= 0) {
        $stmt = $pdo->prepare('INSERT INTO users (name, email, phone, password_hash, email_verified_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute(['Học viên doanh thu mẫu', 'demo.student@innocode.local', '0392604697', password_hash('InnoCode@123', PASSWORD_DEFAULT)]);
        $userId = (int) $pdo->lastInsertId();
    }

    $seedOrders = [
        ['2025-01-12 09:20:00', 0, 0, 'bank'],
        ['2025-03-18 14:05:00', 1, 50000, 'vnpay'],
        ['2025-06-25 19:40:00', 2, 0, 'momo'],
        ['2025-09-09 10:15:00', 3, 100000, 'bank'],
        ['2025-12-20 16:30:00', 4, 0, 'vnpay'],
        ['2026-01-15 08:45:00', 5, 50000, 'momo'],
        ['2026-02-22 13:10:00', 1, 0, 'bank'],
        ['2026-03-11 18:25:00', 2, 50000, 'vnpay'],
        ['2026-04-07 11:55:00', 3, 0, 'momo'],
        ['2026-05-18 17:02:21', 0, 50000, 'bank'],
    ];

    foreach ($seedOrders as $index => [$createdAt, $courseOffset, $discount, $method]) {
        $course = $courseIds[$courseOffset % count($courseIds)];
        $courseId = (int) $course['id'];
        $price = (float) $course['price'];
        $total = max(0, $price - (float) $discount);
        $paymentCode = 'IC-SEED-' . str_replace(['-', ' ', ':'], '', $createdAt);

        $stmt = $pdo->prepare('SELECT id FROM orders WHERE payment_code = ?');
        $stmt->execute([$paymentCode]);
        if ($stmt->fetchColumn()) {
            continue;
        }

        $stmt = $pdo->prepare("INSERT INTO orders (user_id, customer_name, customer_email, customer_phone, note, total_amount, coupon_code, discount_amount, payment_method, payment_provider, payment_code, status, paid_at, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid', ?, ?, ?)");
        $stmt->execute([
            $userId,
            'Học viên doanh thu mẫu',
            'demo.student@innocode.local',
            '0392604697',
            'Đơn dữ liệu mẫu phục vụ thống kê doanh thu theo tháng/năm.',
            $total,
            $discount > 0 ? 'WELCOME50' : null,
            $discount,
            $method,
            'payos',
            $paymentCode,
            $createdAt,
            $createdAt,
            $createdAt,
        ]);
        $orderId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare('INSERT INTO order_items (order_id, course_id, price, quantity, created_at) VALUES (?, ?, ?, 1, ?)');
        $stmt->execute([$orderId, $courseId, $price, $createdAt]);

        $stmt = $pdo->prepare('INSERT IGNORE INTO enrollments (user_id, course_id, order_id, enrolled_at) VALUES (?, ?, ?, ?)');
        $stmt->execute([$userId, $courseId, $orderId, $createdAt]);
    }
}

function ensure_column(string $table, string $column, string $definition): void
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$table, $column]);

    if ((int) $stmt->fetchColumn() === 0) {
        db()->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }
}

function ensure_index(string $table, string $index, string $column): void
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?");
    $stmt->execute([$table, $index]);

    if ((int) $stmt->fetchColumn() === 0) {
        db()->exec("ALTER TABLE {$table} ADD INDEX {$index} ({$column})");
    }
}

