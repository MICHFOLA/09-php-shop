<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_is_valid()) {
    set_flash('error', 'Your session expired. Please use the sign-out button again.');
    redirect(admin_is_authenticated() ? 'index.php' : 'login.php');
}

unset($_SESSION['admin_authenticated']);
session_regenerate_id(true);
set_flash('success', 'You have been signed out.');
redirect('login.php');
