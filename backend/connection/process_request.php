<?php
// Đường dẫn: backend/connection/process_request.php
session_start();
require_once '../includes/auth.php'; // Đường dẫn có thể cần chỉnh lại tùy cấu trúc thư mục
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Lấy dữ liệu từ form
    $material_id = $_POST['material_id'] ?? '';
    $quantity    = $_POST['quantity'] ?? 0;
    $needed_date = $_POST['needed_date'] ?? '';
    $priority    = $_POST['priority'] ?? 'Normal';
    $notes       = $_POST['notes'] ?? '';
    $requested_by = $_SESSION['user_id'] ?? 1; // Lấy ID người đang đăng nhập

    if (strtotime($needed_date) < strtotime(date('Y-m-d'))) {
        die("Lỗi: Ngày cần vật tư (Needed By Date) không được nằm trong quá khứ.");
    }

    try {
        // 2. Viết câu lệnh INSERT (LƯU Ý: Thay đổi tên bảng và tên cột cho khớp với CSDL thật của bạn)
        $sql = "INSERT INTO MATERIAL_REQUESTS (REQ_material_id, REQ_quantity, REQ_needed_date, REQ_priority, REQ_notes, REQ_status, REQ_requested_by) 
                VALUES (:material_id, :quantity, :needed_date, :priority, :notes, 'Pending', :requested_by)";
        
        $stmt = $pdo->prepare($sql);
        
        // 3. Truyền tham số an toàn (Chống SQL Injection)
        $stmt->execute([
            ':material_id'  => $material_id,
            ':quantity'     => $quantity,
            ':needed_date'  => $needed_date,
            ':priority'     => $priority,
            ':notes'        => $notes,
            ':requested_by' => $requested_by
        ]);

        $reqId = $pdo->lastInsertId();
        require_once __DIR__ . '/../helpers/n8n_helper.php';
        triggerN8nWebhook('material-request-alert', [
            'request_id' => $reqId,
            'action' => 'created',
            'material_id' => $material_id,
            'quantity_kg' => (float)$quantity,
            'needed_date' => $needed_date,
            'priority' => $priority,
            'notes' => $notes,
            'requested_by_user_id' => $requested_by,
            'status' => 'Pending'
        ]);

        // 4. Chuyển hướng về trang Inventory và báo thành công
        $_SESSION['success_msg'] = "Material request submitted successfully!";
        header("Location: ../../frontend/inventory.php");
        exit();

    } catch (PDOException $e) {
        die("Lỗi lưu yêu cầu vật tư: " . $e->getMessage());
    }
} else {
    // Nếu truy cập trực tiếp bằng URL (GET) thì đẩy về trang chủ
    header("Location: ../../frontend/dashboard_production.php");
    exit();
}
?>