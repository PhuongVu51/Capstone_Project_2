<?php
// Đường dẫn: backend/connection/fix_pass.php
require_once 'db_connect.php';

try {
    // 1. Dùng PHP để tạo mã Hash thuật toán Bcrypt CHUẨN cho pass "123456"
    $valid_hash = password_hash('123456', PASSWORD_BCRYPT);

    // 2. Update lại toàn bộ mật khẩu trong DB bằng mã Hash xịn này
    $sql = "UPDATE USERS SET USR_password_hash = :hash";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':hash' => $valid_hash]);

    echo "<div style='font-family: sans-serif; text-align: center; margin-top: 50px;'>";
    echo "<h1 style='color: #10b981;'>Sửa lỗi thành công! (200 OK)</h1>";
    echo "<p>Đã cập nhật mã Hash chuẩn cho toàn bộ 9 tài khoản.</p>";
    echo "<a href='../../frontend/login.php' style='padding: 10px 20px; background: #0f1722; color: white; text-decoration: none; border-radius: 5px;'>Quay lại trang Đăng nhập</a>";
    echo "</div>";

} catch (PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}
?>