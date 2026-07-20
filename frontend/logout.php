<?php
session_start();

// Xóa tất cả dữ liệu session hiện có
$_SESSION = array();

// Hủy session cookie nếu có
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();

// Chuyển về trang đăng nhập
header('Location: login.php');
exit();
