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
        email_verified_at DATETIME NULL,
        verification_token VARCHAR(100) NULL,
        reset_token VARCHAR(100) NULL,
        reset_expires_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

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
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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

    $pdo->exec("INSERT IGNORE INTO coupons (code, discount_type, discount_value, usage_limit, is_active)
        VALUES ('INNO10', 'percent', 10, 200, 1), ('WELCOME50', 'fixed', 50000, 200, 1)");

    $pdo->exec("INSERT IGNORE INTO physical_products (id, name, product_type, description, price, stock, image_url, is_active)
        VALUES
        (1, 'Sổ tay InnoCode', 'souvenir', 'Sổ ghi chú học lập trình in logo InnoCode.', 59000, 100, 'https://images.unsplash.com/photo-1517842645767-c639042777db?auto=format&fit=crop&w=900&q=80', 1),
        (2, 'Áo thun InnoCode', 'souvenir', 'Áo thun cotton dành cho học viên InnoCode.', 179000, 50, 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=900&q=80', 1),
        (3, 'Tài liệu PHP bản in', 'printed_document', 'Tài liệu giấy PHP & MySQL có vận chuyển.', 129000, 80, 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?auto=format&fit=crop&w=900&q=80', 1)");
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

