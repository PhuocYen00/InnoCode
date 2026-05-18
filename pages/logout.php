<?php
require_once dirname(__DIR__) . '/core/init.php';

logout_user();
flash('success', 'Bạn đã đăng xuất.');
redirect('index.php');


