<?php
// Đường dẫn: backend/connection/process_login.php
session_start();
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Chỉ nhận Username và Password từ giao diện (bỏ biến Role)
    $username_input = trim($_POST['USR_username']);
    $password_input = $_POST['USR_password_hash'];

    // 2. Tìm User trong Database (chỉ cần khớp Username và Active)
    $sql = "SELECT * FROM USERS WHERE USR_username = :username AND USR_is_active = 1 LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':username' => $username_input]);
    
    $user = $stmt->fetch();

    // 3. So khớp Mật khẩu
    if ($user && password_verify($password_input, $user['USR_password_hash'])) {
        
        // 4. Khởi tạo Session và tự động lấy Role từ Database gán vào
        $_SESSION['user_id'] = $user['USR_user_id'];
        $_SESSION['username'] = $user['USR_username'];
        $_SESSION['full_name'] = $user['USR_full_name'];
        $_SESSION['role'] = $user['USR_role']; // Tự động lấy Role

        // 5. Điều hướng theo Role
        switch ($user['USR_role']) {
            case 'Production_Manager':
                header("Location: ../../frontend/dashboard_production.php");
                break;
            case 'QC':
                header("Location: ../../frontend/qc_dashboard.php"); // Sửa theo chuẩn MVC
                break;
            case 'Warehouse_Staff':
                header("Location: ../../frontend/dashboard_warehouse.php");
                break;
            case 'Director':
                header("Location: ../../frontend/dashboard_director.php");
                break;
            default:
                header("Location: ../../frontend/login.php?error=invalid_role");
        }
        exit();
    } else {
        // Thông báo lỗi nếu sai pass hoặc user không tồn tại
        header("Location: ../../frontend/login.php?error=wrong_credentials");
        exit();
    }
} else {
    // Nếu truy cập trực tiếp file này mà không qua form POST
    header("Location: ../../frontend/login.php");
    exit();
}
?>