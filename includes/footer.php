</main>
<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <img class="footer-logo" src="<?= APP_URL ?>/assets/images/innocode.jpg" alt="<?= e(APP_NAME) ?>">
            <p>InnoCode cung cấp khóa học lập trình web thực chiến, bài tập, quiz, compiler và học liệu đi kèm cho học viên tự học nghiêm túc.</p>
        </div>
        <div>
            <h3>Học tập</h3>
            <a href="<?= url('courses') ?>">Khóa học</a>
            <a href="<?= url('compiler') ?>">Trình biên dịch</a>
            <a href="<?= url('my_courses') ?>">Khóa học của tôi</a>
        </div>
        <div>
            <h3>InnoCode</h3>
            <a href="<?= url('about') ?>">Giới thiệu</a>
            <a href="<?= url('cart') ?>">Giỏ hàng</a>
            <a href="<?= APP_URL ?>/admin/index.php">Admin</a>
        </div>
        <div>
            <h3>Liên hệ</h3>
            <p>Email: phuocyen.281004@gmail.com</p>
            <p>Vietcombank: 3392604697</p>
            <div class="footer-social">
                <span>Facebook</span>
                <span>YouTube</span>
                <span>GitHub</span>
            </div>
        </div>
    </div>
    <div class="container footer-bottom">InnoCode - Website khóa học lập trình web thực chiến.</div>
</footer>
<div class="cart-toast" id="cart-toast" role="status" aria-live="polite">Đã thêm vào giỏ hàng.</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('submit', async function (event) {
    const form = event.target.closest('.js-add-cart');
    if (!form) {
        return;
    }

    event.preventDefault();
    const button = form.querySelector('button[type="submit"]');
    const oldText = button ? button.textContent : '';
    if (button) {
        button.disabled = true;
        button.textContent = 'Đang thêm...';
    }

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
        const data = await response.json();
        if (data.redirect) {
            window.location.href = data.redirect;
            return;
        }
        if (data.ok) {
            const count = document.getElementById('cart-count');
            if (count) {
                count.textContent = data.cart_count;
            }
            const toast = document.getElementById('cart-toast');
            if (toast) {
                toast.textContent = data.message || 'Đã thêm vào giỏ hàng.';
                toast.classList.add('show');
                setTimeout(() => toast.classList.remove('show'), 2200);
            }
        }
    } catch (error) {
        form.submit();
    } finally {
        if (button) {
            button.disabled = false;
            button.textContent = oldText;
        }
    }
});
</script>
</body>
</html>
