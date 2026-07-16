<?php
// Đường dẫn: backend/connection/process_login.php
session_start();
// Gọi file db_connect (nằm cùng thư mục nên gọi trực tiếp)
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role_selected = trim($_POST['USR_role']);
    $username_input = trim($_POST['USR_username']);
    $password_input = $_POST['USR_password_hash'];

    $sql = "SELECT * FROM USERS WHERE USR_username = :username AND USR_role = :role AND USR_is_active = 1 LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':username' => $username_input,
        ':role' => $role_selected
    ]);
    
    $user = $stmt->fetch();

    if ($user && password_verify($password_input, $user['USR_password_hash'])) {
        $_SESSION['user_id'] = $user['USR_user_id'];
        $_SESSION['username'] = $user['USR_username'];
        $_SESSION['full_name'] = $user['USR_full_name'];
        $_SESSION['role'] = $user['USR_role'];

        // ĐIỀU HƯỚNG THEO ROLE (Lùi 2 cấp thư mục: từ connection -> backend -> root -> frontend)
        switch ($user['USR_role']) {
            case 'Production_Manager':
                header("Location: ../../frontend/dashboard_production.php");
                break;
            case 'QC':
                header("Location: ../../frontend/dashboard_qc.php");
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
        header("Location: ../../frontend/login.php?error=wrong_credentials");
        exit();
    }
} else {
    header("Location: ../../frontend/login.php");
    exit();
}
?>