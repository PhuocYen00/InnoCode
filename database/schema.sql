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
    price DECIMAL(12, 2) NOT NULL DEFAULT 0,
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
    password_hash VARCHAR(255) NOT NULL,
    email_verified_at DATETIME NULL,
    verification_token VARCHAR(100) NULL,
    reset_token VARCHAR(100) NULL,
    reset_expires_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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

CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    customer_name VARCHAR(190) NOT NULL,
    customer_email VARCHAR(190) NOT NULL,
    customer_phone VARCHAR(50) NOT NULL,
    note TEXT NULL,
    total_amount DECIMAL(12, 2) NOT NULL DEFAULT 0,
    payment_method VARCHAR(40) NOT NULL DEFAULT 'bank',
    payment_code VARCHAR(80) NULL,
    status ENUM('pending', 'paid', 'cancelled') NOT NULL DEFAULT 'pending',
    paid_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_orders_user (user_id)
);

CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    price DECIMAL(12, 2) NOT NULL,
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

CREATE TABLE IF NOT EXISTS email_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    recipient VARCHAR(190) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO categories (name, slug) VALUES
('Backend', 'backend'),
('Frontend', 'frontend'),
('Fullstack', 'fullstack'),
('Database', 'database')
ON DUPLICATE KEY UPDATE name = VALUES(name);

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
