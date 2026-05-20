        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
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

document.querySelectorAll('[data-admin-nav-group]').forEach(function (group) {
    const key = 'admin-nav-group-' + group.dataset.adminNavGroup;
    const saved = localStorage.getItem(key);
    if (saved === 'open') {
        group.open = true;
    }
    if (saved === 'closed') {
        group.open = false;
    }
    if (group.querySelector('a.active') && saved === null) {
        group.open = true;
    }
    group.addEventListener('toggle', function () {
        localStorage.setItem(key, group.open ? 'open' : 'closed');
    });
});
</script>
</body>
</html>

