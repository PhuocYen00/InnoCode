<?php
require_once __DIR__ . '/../core/init.php';

redirect('login.php?next=' . urlencode('/admin/index.php'));

