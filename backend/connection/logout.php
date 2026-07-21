<?php
// Đường dẫn: backend/connection/logout.php
session_start();

// 1. Xóa tất cả các biến session (Clear Data)
$_SESSION = array();

// 2. Hủy session cookie trên trình duyệt của người dùng
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// 3. Phá hủy toàn bộ session trên server
session_destroy();

// 4. Chuyển hướng về trang đăng nhập
header("Location: ../../frontend/login.php");
exit();
?>
