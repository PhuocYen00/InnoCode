CREATE DATABASE IF NOT EXISTS innocode CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE innocode;

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS courses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    level VARCHAR(50) NOT NULL DEFAULT 'Beginner',
    duration_hours INT UNSIGNED NOT NULL DEFAULT 1,
    image_url VARCHAR(500) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_courses_category FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(50) NULL,
    avatar_url VARCHAR(500) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    email_verified_at DATETIME NULL,
    verification_token VARCHAR(100) NULL,
    reset_token VARCHAR(100) NULL,
    reset_expires_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    customer_name VARCHAR(190) NOT NULL,
    customer_email VARCHAR(190) NOT NULL,
    customer_phone VARCHAR(50) NOT NULL,
    note TEXT NULL,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    coupon_code VARCHAR(60) NULL,
    discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    payment_method VARCHAR(40) NOT NULL DEFAULT 'bank',
    payment_provider VARCHAR(40) NOT NULL DEFAULT 'payos',
    payment_code VARCHAR(80) NULL,
    payos_order_code BIGINT UNSIGNED NULL,
    payos_payment_link_id VARCHAR(120) NULL,
    payos_checkout_url VARCHAR(500) NULL,
    status ENUM('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
    paid_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_orders_user (user_id),
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_course FOREIGN KEY (course_id) REFERENCES courses(id)
);

CREATE TABLE IF NOT EXISTS enrollments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    order_id INT UNSIGNED NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_enrollments_user_course (user_id, course_id),
    CONSTRAINT fk_enrollments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_enrollments_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS cart_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cart_user_course (user_id, course_id),
    CONSTRAINT fk_cart_items_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_items_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS course_chapters (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_course_chapters_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS course_lessons (
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
);

CREATE TABLE IF NOT EXISTS lesson_materials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lesson_id INT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    material_type ENUM('pdf','source_code','slide','link') NOT NULL DEFAULT 'pdf',
    file_url VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lesson_materials_lesson FOREIGN KEY (lesson_id) REFERENCES course_lessons(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS lesson_practices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lesson_id INT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    instruction MEDIUMTEXT NOT NULL,
    starter_code MEDIUMTEXT NULL,
    expected_output MEDIUMTEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lesson_practices_lesson FOREIGN KEY (lesson_id) REFERENCES course_lessons(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS quizzes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lesson_id INT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_quizzes_lesson FOREIGN KEY (lesson_id) REFERENCES course_lessons(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS quiz_questions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT UNSIGNED NOT NULL,
    question_type ENUM('choice','essay') NOT NULL DEFAULT 'choice',
    question TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NULL,
    option_d VARCHAR(255) NULL,
    correct_option CHAR(1) NOT NULL,
    sample_answer TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_quiz_questions_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS quiz_attempts (
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
);

CREATE TABLE IF NOT EXISTS lesson_progress (
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
);

CREATE TABLE IF NOT EXISTS course_lesson_progress (
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
);

CREATE TABLE IF NOT EXISTS lesson_submissions (
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
    status ENUM('submitted','reviewed') NOT NULL DEFAULT 'submitted',
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_lesson_submissions_course (course_id),
    INDEX idx_lesson_submissions_user (user_id),
    CONSTRAINT fk_lesson_submissions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_lesson_submissions_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS physical_products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    product_type ENUM('pdf','printed_document','souvenir') NOT NULL DEFAULT 'pdf',
    description TEXT NULL,
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    image_url VARCHAR(500) NULL,
    digital_file_url VARCHAR(500) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS product_cart_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_product_cart_user_product (user_id, product_id),
    CONSTRAINT fk_product_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_product_cart_product FOREIGN KEY (product_id) REFERENCES physical_products(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS physical_order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    product_name VARCHAR(190) NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    payment_status ENUM('paid_online','pay_later') NOT NULL DEFAULT 'paid_online',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_physical_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_physical_order_items_product FOREIGN KEY (product_id) REFERENCES physical_products(id)
);

CREATE TABLE IF NOT EXISTS shipments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    receiver_name VARCHAR(190) NOT NULL,
    receiver_phone VARCHAR(50) NOT NULL,
    address TEXT NOT NULL,
    carrier VARCHAR(120) NULL,
    tracking_code VARCHAR(120) NULL,
    status ENUM('pending','packing','shipping','delivered','cancelled') NOT NULL DEFAULT 'pending',
    shipped_at DATETIME NULL,
    delivered_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_shipments_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS gift_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    receiver_name VARCHAR(190) NOT NULL,
    receiver_phone VARCHAR(50) NOT NULL,
    address TEXT NOT NULL,
    status ENUM('pending','confirmed','shipping','delivered') NOT NULL DEFAULT 'pending',
    note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_gift_user_course (user_id, course_id),
    CONSTRAINT fk_gift_requests_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_gift_requests_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_gift_requests_product FOREIGN KEY (product_id) REFERENCES physical_products(id)
);

CREATE TABLE IF NOT EXISTS course_reviews (
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
);

CREATE TABLE IF NOT EXISTS course_questions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    lesson_id INT UNSIGNED NULL,
    lesson_index INT UNSIGNED NULL,
    question TEXT NOT NULL,
    answer TEXT NULL,
    status ENUM('open','answered','closed') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_course_questions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_course_questions_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS lesson_reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    lesson_index INT UNSIGNED NULL,
    report_type VARCHAR(80) NOT NULL DEFAULT 'content_error',
    message TEXT NOT NULL,
    status ENUM('open','reviewing','resolved') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lesson_reports_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_lesson_reports_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS favorites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_favorites_user_course (user_id, course_id),
    CONSTRAINT fk_favorites_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_favorites_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS coupons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(60) NOT NULL UNIQUE,
    discount_type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    discount_value DECIMAL(12,2) NOT NULL,
    starts_at DATETIME NULL,
    expires_at DATETIME NULL,
    usage_limit INT UNSIGNED NULL,
    used_count INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS email_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    recipient VARCHAR(190) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_email_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS app_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO categories (name, slug) VALUES
('Backend', 'backend'),
('Frontend', 'frontend'),
('Fullstack', 'fullstack'),
('Database', 'database')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO users (name, email, phone, password_hash, role, email_verified_at) VALUES
('Quản trị viên', 'admin@innocode.local', '', '$2y$10$vaCBuH3v3JbS3NYO042o5OTwqQpQfiDm88AdTyY4lXQJW84kt.TZO', 'admin', NOW())
ON DUPLICATE KEY UPDATE role = 'admin', email_verified_at = COALESCE(email_verified_at, NOW());

INSERT INTO courses (category_id, title, description, price, level, duration_hours, image_url, is_active) VALUES
((SELECT id FROM categories WHERE slug = 'backend'), 'PHP căn bản đến thực chiến', 'Nắm vững cú pháp PHP, xử lý form, session, upload file và xây dựng website động theo mô hình rõ ràng.', 799000, 'Beginner', 28, 'https://images.unsplash.com/photo-1599507593499-a3f7d7d97667?auto=format&fit=crop&w=1200&q=80', 1),
((SELECT id FROM categories WHERE slug = 'backend'), 'Laravel cho dự án thương mại', 'Xây dựng ứng dụng Laravel với routing, Eloquent, migration, authentication, queue và deploy cơ bản.', 1499000, 'Intermediate', 42, 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=1200&q=80', 1),
((SELECT id FROM categories WHERE slug = 'frontend'), 'JavaScript hiện đại', 'Học ES6+, DOM, fetch API, module, async/await và tư duy tổ chức frontend dễ bảo trì.', 899000, 'Beginner', 32, 'https://images.unsplash.com/photo-1555949963-aa79dcee981c?auto=format&fit=crop&w=1200&q=80', 1),
((SELECT id FROM categories WHERE slug = 'frontend'), 'React từ zero đến production', 'Tạo SPA với component, hook, form, API, state management và tối ưu trải nghiệm người dùng.', 1599000, 'Intermediate', 45, 'https://images.unsplash.com/photo-1633356122544-f134324a6cee?auto=format&fit=crop&w=1200&q=80', 1),
((SELECT id FROM categories WHERE slug = 'fullstack'), 'Fullstack PHP MySQL', 'Xây dựng website bán hàng hoàn chỉnh với PHP, MySQL, Bootstrap, admin dashboard và luồng đặt hàng.', 1299000, 'Intermediate', 38, 'https://images.unsplash.com/photo-1515879218367-8466d910aaa4?auto=format&fit=crop&w=1200&q=80', 1),
((SELECT id FROM categories WHERE slug = 'database'), 'MySQL cho lập trình viên', 'Thiết kế bảng, quan hệ, index, truy vấn JOIN, transaction và tối ưu truy vấn thường gặp.', 699000, 'Beginner', 24, 'https://images.unsplash.com/photo-1544383835-bda2bc66a55d?auto=format&fit=crop&w=1200&q=80', 1),
((SELECT id FROM categories WHERE slug = 'frontend'), 'HTML CSS nhập môn miễn phí', 'Làm quen cấu trúc trang web, bố cục responsive, form, button và cách dựng giao diện đầu tiên bằng HTML CSS.', 0, 'Beginner', 8, 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=1200&q=80', 1),
((SELECT id FROM categories WHERE slug = 'frontend'), 'JavaScript DOM mini project miễn phí', 'Thực hành DOM, event, form validation và xây dựng các tương tác nhỏ để chuẩn bị học frontend nâng cao.', 0, 'Beginner', 10, 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1200&q=80', 1),
((SELECT id FROM categories WHERE slug = 'database'), 'SQL căn bản miễn phí', 'Nắm SELECT, WHERE, JOIN, GROUP BY và tư duy đọc dữ liệu cho các dự án web thương mại điện tử.', 0, 'Beginner', 6, 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=1200&q=80', 1);

INSERT INTO course_chapters (course_id, title, sort_order)
SELECT courses.id, 'Chương 1 - Nền tảng và thực hành', 1
FROM courses
WHERE NOT EXISTS (
    SELECT 1 FROM course_chapters WHERE course_chapters.course_id = courses.id
);

INSERT INTO course_lessons (chapter_id, title, video_url, theory_content, duration_minutes, is_preview, unlock_order)
SELECT course_chapters.id,
    lesson_data.title,
    CASE
        WHEN LOWER(courses.title) LIKE '%javascript%' AND lesson_data.unlock_order = 1 THEN 'https://www.youtube.com/embed/W6NZfCO5SIk'
        WHEN LOWER(courses.title) LIKE '%javascript%' AND lesson_data.unlock_order = 2 THEN 'https://www.youtube.com/embed/PkZNo7MFNFg'
        WHEN LOWER(courses.title) LIKE '%javascript%' THEN 'https://www.youtube.com/embed/hdI2bqOjy3c'
        WHEN LOWER(courses.title) LIKE '%react%' AND lesson_data.unlock_order = 1 THEN 'https://www.youtube.com/embed/Ke90Tje7VS0'
        WHEN LOWER(courses.title) LIKE '%react%' AND lesson_data.unlock_order = 2 THEN 'https://www.youtube.com/embed/SqcY0GlETPk'
        WHEN LOWER(courses.title) LIKE '%react%' THEN 'https://www.youtube.com/embed/bMknfKXIFA8'
        WHEN (LOWER(courses.title) LIKE '%mysql%' OR LOWER(courses.title) LIKE '%sql%' OR LOWER(courses.title) LIKE '%database%') AND lesson_data.unlock_order = 1 THEN 'https://www.youtube.com/embed/7S_tz1z_5bA'
        WHEN (LOWER(courses.title) LIKE '%mysql%' OR LOWER(courses.title) LIKE '%sql%' OR LOWER(courses.title) LIKE '%database%') AND lesson_data.unlock_order = 2 THEN 'https://www.youtube.com/embed/HXV3zeQKqGY'
        WHEN LOWER(courses.title) LIKE '%mysql%' OR LOWER(courses.title) LIKE '%sql%' OR LOWER(courses.title) LIKE '%database%' THEN 'https://www.youtube.com/embed/9Pzj7Aj25lw'
        WHEN LOWER(courses.title) LIKE '%laravel%' AND lesson_data.unlock_order = 1 THEN 'https://www.youtube.com/embed/MFh0Fd7BsjE'
        WHEN LOWER(courses.title) LIKE '%laravel%' AND lesson_data.unlock_order = 2 THEN 'https://www.youtube.com/embed/376vZ1wNYPA'
        WHEN LOWER(courses.title) LIKE '%laravel%' THEN 'https://www.youtube.com/embed/ImtZ5yENzgE'
        WHEN LOWER(courses.title) LIKE '%html%' AND lesson_data.unlock_order = 1 THEN 'https://www.youtube.com/embed/G3e-cpL7ofc'
        WHEN LOWER(courses.title) LIKE '%html%' AND lesson_data.unlock_order = 2 THEN 'https://www.youtube.com/embed/qz0aGYrrlhU'
        WHEN LOWER(courses.title) LIKE '%html%' THEN 'https://www.youtube.com/embed/OXGznpKZ_sA'
        WHEN lesson_data.unlock_order = 1 THEN 'https://www.youtube.com/embed/OK_JCtrrv-c'
        WHEN lesson_data.unlock_order = 2 THEN 'https://www.youtube.com/embed/2eebptXfEvw'
        ELSE 'https://www.youtube.com/embed/7TF00hJI78Y'
    END,
    lesson_data.theory,
    lesson_data.duration_minutes,
    lesson_data.is_preview,
    lesson_data.unlock_order
FROM course_chapters
JOIN courses ON courses.id = course_chapters.course_id
JOIN (
    SELECT 1 AS unlock_order, 'Tổng quan và môi trường học' AS title, 'Bài học giới thiệu mục tiêu khóa học, công cụ cần chuẩn bị và cách tổ chức thư mục khi thực hành.' AS theory, 18 AS duration_minutes, 1 AS is_preview
    UNION ALL
    SELECT 2, 'Kiến thức cốt lõi', 'Bài học tập trung vào khái niệm cốt lõi, cú pháp thường dùng và lỗi phổ biến khi triển khai.', 28, 0
    UNION ALL
    SELECT 3, 'Bài tập ứng dụng', 'Bài học hướng dẫn áp dụng kiến thức vào một chức năng nhỏ, có luồng xử lý và dữ liệu đầu ra cụ thể.', 35, 0
) AS lesson_data
WHERE NOT EXISTS (
    SELECT 1 FROM course_lessons WHERE course_lessons.chapter_id = course_chapters.id
);

INSERT INTO lesson_materials (lesson_id, title, material_type, file_url)
SELECT course_lessons.id, 'PDF bài học', 'pdf', 'php-intro.pdf'
FROM course_lessons
WHERE NOT EXISTS (
    SELECT 1 FROM lesson_materials WHERE lesson_materials.lesson_id = course_lessons.id
);

INSERT INTO lesson_materials (lesson_id, title, material_type, file_url)
SELECT course_lessons.id, 'Source code mẫu', 'source_code', 'source-mau.php'
FROM course_lessons
WHERE NOT EXISTS (
    SELECT 1 FROM lesson_materials WHERE lesson_materials.lesson_id = course_lessons.id AND lesson_materials.material_type = 'source_code'
);

INSERT INTO lesson_practices (lesson_id, title, instruction, starter_code, expected_output)
SELECT course_lessons.id,
    CONCAT('Bài thực hành - ', course_lessons.title),
    'Hoàn thành một bài tập nhỏ dựa trên nội dung vừa học, nộp source code để admin/giảng viên kiểm tra và phản hồi.',
    '',
    'Chức năng chạy được, có ghi chú cách kiểm tra và không báo lỗi cú pháp.'
FROM course_lessons
WHERE NOT EXISTS (
    SELECT 1 FROM lesson_practices WHERE lesson_practices.lesson_id = course_lessons.id
);

INSERT INTO quizzes (lesson_id, title)
SELECT course_lessons.id, CONCAT('Quiz - ', course_lessons.title)
FROM course_lessons
WHERE NOT EXISTS (
    SELECT 1 FROM quizzes WHERE quizzes.lesson_id = course_lessons.id
);

INSERT INTO quiz_questions (quiz_id, question_type, question, option_a, option_b, option_c, option_d, correct_option, sample_answer)
SELECT quizzes.id, 'choice', 'Một bài học hoàn chỉnh nên có những thành phần nào?', 'Lý thuyết, tài liệu, quiz và bài thực hành', 'Chỉ có video', 'Chỉ có ảnh minh họa', 'Không cần kiểm tra', 'a', ''
FROM quizzes
WHERE NOT EXISTS (
    SELECT 1 FROM quiz_questions WHERE quiz_questions.quiz_id = quizzes.id
);

INSERT INTO quiz_questions (quiz_id, question_type, question, option_a, option_b, option_c, option_d, correct_option, sample_answer)
SELECT quizzes.id, 'essay', 'Mô tả ngắn cách áp dụng nội dung bài học này vào một chức năng thực tế.', 'Câu trả lời cần nêu được mục tiêu, bước xử lý chính và kết quả mong muốn.', '-', '', '', 'a', 'Gợi ý: liên hệ với một màn hình hoặc luồng nghiệp vụ trong website.'
FROM quizzes
WHERE NOT EXISTS (
    SELECT 1 FROM quiz_questions WHERE quiz_questions.quiz_id = quizzes.id AND quiz_questions.question_type = 'essay'
);

INSERT INTO app_settings (setting_key, setting_value) VALUES ('demo_course_content_seeded', '1')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

INSERT IGNORE INTO physical_products (id, name, product_type, description, price, stock, image_url, digital_file_url, is_active) VALUES
(1, 'Sổ tay InnoCode', 'souvenir', 'Sổ ghi chú học lập trình in logo InnoCode.', 0, 100, 'https://images.unsplash.com/photo-1517842645767-c639042777db?auto=format&fit=crop&w=900&q=80', NULL, 1),
(2, 'Áo thun InnoCode', 'souvenir', 'Áo thun cotton dành cho học viên InnoCode.', 0, 50, 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=900&q=80', NULL, 1),
(3, 'Tài liệu PHP & MySQL', 'printed_document', 'Tài liệu học tập PHP & MySQL dạng file tải về.', 129000, 999, 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?auto=format&fit=crop&w=900&q=80', 'php-intro.pdf', 1),
(4, 'Slide tóm tắt lập trình web', 'pdf', 'Bộ slide ôn tập kiến thức nền tảng lập trình web.', 49000, 999, 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=900&q=80', 'slide-tom-tat.txt', 1);

INSERT IGNORE INTO coupons (code, discount_type, discount_value, usage_limit, is_active) VALUES
('INNO10', 'percent', 10, 200, 1),
('WELCOME50', 'fixed', 50000, 200, 1);
