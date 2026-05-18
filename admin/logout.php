<?php
require_once __DIR__ . '/../core/init.php';

unset($_SESSION['admin_logged_in']);
redirect('admin/login.php');


