</main>
<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <img class="footer-logo" src="<?= APP_URL ?>/assets/images/innocode.jpg" alt="<?= e(APP_NAME) ?>">
            <p>InnoCode cung cấp khóa học lập trình thực chiến, bài tập, quiz, compiler và học liệu đi kèm cho học viên tự học nghiêm túc.</p>
        </div>
        <div>
            <h3>InnoCode</h3>
            <a href="<?= url('about') ?>">Giới thiệu</a>
            <a href="<?= url('courses') ?>">Khóa học</a>
        </div>
        <div>
            <h3>Học tập</h3>
            <a href="<?= url('my_courses') ?>">Khóa học của tôi</a>
            <a href="<?= url('compiler') ?>">Trình biên dịch</a>
        </div>
        <div>
            <h3>Liên hệ</h3>
            <p>Email: InnoCode@gmail.com</p>
            <div class="footer-social">
                <span>Facebook</span>
                <span>YouTube</span>
                <span>GitHub</span>
            </div>
        </div>
    </div>
    <div class="container footer-bottom">InnoCode - Website khóa học lập trình thực chiến.</div>
</footer>
<div class="cart-toast" id="cart-toast" role="status" aria-live="polite">Đã thêm vào giỏ hàng.</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
async function saveLessonNote(noteForm, submitButton = null) {
    const button = submitButton || noteForm.querySelector('button[type="submit"]');
    const oldText = button ? button.textContent : '';
    let status = noteForm.querySelector('.note-save-status');
    if (!status) {
        status = document.createElement('span');
        status.className = 'note-save-status';
        noteForm.querySelector('.lesson-action-row')?.appendChild(status);
    }
    if (button) {
        button.disabled = true;
        button.textContent = 'Đang lưu...';
    }

    try {
        const formData = new FormData(noteForm);
        formData.set('ajax', '1');
        const response = await fetch(noteForm.action, {
            method: 'POST',
            body: formData,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
        const data = await response.json();
        if (!response.ok || !data.ok) {
            throw new Error(data.message || 'Không lưu được ghi chú.');
        }
        status.textContent = data.message || 'Đã lưu ghi chú.';
        status.classList.remove('text-danger');
        status.classList.add('text-success');
        return true;
    } catch (error) {
        status.textContent = 'Không lưu được ghi chú. Đang tải lại để lưu theo cách thường...';
        status.classList.remove('text-success');
        status.classList.add('text-danger');
        return false;
    } finally {
        if (button) {
            button.disabled = false;
            button.textContent = oldText;
        }
    }
}

document.addEventListener('click', async function (event) {
    const downloadLink = event.target.closest('[data-note-download]');
    if (!downloadLink) {
        return;
    }

    const noteForm = downloadLink.closest('.js-note-form');
    if (!noteForm) {
        return;
    }

    event.preventDefault();
    const saved = await saveLessonNote(noteForm);
    if (saved) {
        window.location.href = downloadLink.href;
    } else {
        noteForm.submit();
    }
});

document.addEventListener('submit', async function (event) {
    const noteForm = event.target.closest('.js-note-form');
    if (noteForm) {
        event.preventDefault();
        const button = event.submitter || noteForm.querySelector('button[type="submit"]');
        const saved = await saveLessonNote(noteForm, button);
        if (!saved) {
            noteForm.submit();
        }
        return;
    }

    const form = event.target.closest('.js-add-cart');
    if (!form) {
        return;
    }

    event.preventDefault();
    const button = form.querySelector('button[type="submit"]');
    const oldText = button ? button.textContent : '';
    if (button) {
        button.disabled = true;
        button.textContent = '\u0110ang th\u00eam...';
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
                toast.textContent = data.message || '\u0110\u00e3 th\u00eam v\u00e0o gi\u1ecf h\u00e0ng.';
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


document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
    const sync = function () {
        button.textContent = document.documentElement.dataset.theme === 'dark' ? '☀' : '☾';
    };

    sync();
    button.addEventListener('click', function () {
        const nextTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
        document.documentElement.dataset.theme = nextTheme;
        localStorage.setItem('theme', nextTheme);
        sync();
    });
});
</script>
</body>
</html>
