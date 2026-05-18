# InnoCode

Website bán khóa học lập trình bằng PHP + MySQL, chạy trực tiếp trên Laragon.

## Chạy website

```text
http://localhost/Courses/index.php
```

Các trang public dùng router:

```text
http://localhost/Courses/index.php?page=courses
http://localhost/Courses/index.php?page=course&id=1
```

## Cấu trúc thư mục

```text
admin/        Trang quản trị
assets/       CSS, hình ảnh, logo
core/         Cấu hình, kết nối database, schema, helper, dữ liệu nền
database/     File SQL để import lại khi cần
includes/     Header, footer, course card
pages/        Các trang public: khóa học, giỏ hàng, thanh toán, học tập, tài khoản
storage/      Session và dữ liệu runtime local
index.php     Router chính của website
```

## Chức năng hiện có

- Đăng ký, đăng nhập, đăng xuất học viên.
- Xác thực email qua Gmail SMTP, quên mật khẩu, đặt lại mật khẩu.
- Giỏ hàng theo tài khoản, khách chưa đăng nhập không thêm được giỏ.
- Thanh toán VietQR, VNPay/MoMo khi có merchant, xác nhận thanh toán.
- Tự động mở khóa học sau khi đơn paid.
- Trang Khóa học của tôi, trang học video, biên lai in/lưu PDF.
- Admin quản lý khóa học, đơn hàng, học viên.

## Nền chức năng đã chuẩn bị trong database

- Chương học, bài học, video, lý thuyết.
- Tài liệu đính kèm: PDF, source code, slide, link.
- Mini practice, bài thực hành.
- Quiz/trắc nghiệm.
- Ghi chú, đánh dấu bài học hoàn thành, tiến độ học.
- Đánh giá khóa học, hỏi đáp, phản hồi.
- Yêu thích khóa học.
- Mã giảm giá.
- Sản phẩm tài liệu PDF/tài liệu giấy/quà lưu niệm.
- Vận chuyển, mã vận đơn, trạng thái giao hàng.

## Cấu hình Gmail

Mở `core/config.php`:

```php
const SMTP_USERNAME = 'email-cua-ban@gmail.com';
const SMTP_PASSWORD = 'app-password-16-ky-tu';
const MAIL_FROM = 'email-cua-ban@gmail.com';
```

`SMTP_PASSWORD` là App password của Google, không phải mật khẩu Gmail thường.

## Tài khoản admin

```text
URL: http://localhost/Courses/admin/login.php
User: admin
Pass: admin123
```
