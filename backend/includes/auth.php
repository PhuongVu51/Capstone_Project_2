<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_role($allowed_roles, $redirect_path = 'login.php') {
    // 1. Kiểm tra xem người dùng đã đăng nhập chưa
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header("Location: " . $redirect_path);
        exit();
    }

    // 2. Lấy role hiện tại của user đang đăng nhập
    $current_role = $_SESSION['role'];

    // 3. Kiểm tra: Role hiện tại có nằm trong mảng được cấp phép không?
    if (!in_array($current_role, $allowed_roles)) {
        // Nếu không có quyền, đá sang trang 403
        $forbidden_path = str_replace('login.php', '403.php', $redirect_path);
        header("Location: " . $forbidden_path);
        exit();
    }
}
?>