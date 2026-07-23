<?php
// Đường dẫn: backend/connection/process_finished_goods.php
session_start();
require_once '../includes/auth.php';
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Lấy dữ liệu từ form
    $batch_id       = $_POST['batch_id'] ?? '';
    $product_id     = $_POST['product_id'] ?? ''; 
    $mfg_date       = $_POST['mfg_date'] ?? date('Y-m-d');
    $yield_quantity = $_POST['yield_quantity'] ?? 0;
    $exp_date       = $_POST['exp_date'] ?? '';
    $qc_status      = $_POST['qc_status'] ?? 'Pending_QC';

    // Đổi 'Pending' từ form thành 'Pending_QC' để biểu diễn đúng logic
    if ($qc_status === 'Pending') {
        $qc_status = 'Pending_QC';
    }

    // 2. Xử lý các cột bị thiếu trên form nhưng Database yêu cầu NOT NULL
    // Bạn cần đảm bảo trong bảng SUPPLIERS và SHIFTS có ID = 1, nếu không sẽ bị lỗi Foreign Key.
    $supplier_id = 1; // Mặc định gán cho 1 nhà cung cấp nội bộ / xưởng nhà
    $shift_id    = 1; // Mặc định gán cho ca làm việc số 1
    $zone_id     = 1; // Khu vực lưu trữ tạm thời chờ QC
    
    // Ép kiểu ngày giờ cho chuẩn DATETIME của MySQL
    $received_date = $mfg_date . ' 00:00:00';
    $expiry_datetime = $exp_date . ' 23:59:59';

    try {
        // 3. Viết câu lệnh INSERT vào bảng BATCHES theo chuẩn schema
        $sql = "INSERT INTO BATCHES (
                    BCH_batch_id, 
                    BCH_product_id, 
                    BCH_supplier_id, 
                    BCH_shift_id, 
                    BCH_zone_id, 
                    BCH_received_date, 
                    BCH_expiry_date, 
                    BCH_initial_volume_kg, 
                    BCH_available_stock_kg, 
                    BCH_current_stage
                ) VALUES (
                    :batch_id, 
                    :product_id, 
                    :supplier_id, 
                    :shift_id, 
                    :zone_id, 
                    :received_date, 
                    :expiry_date, 
                    :initial_volume, 
                    :available_stock, 
                    :current_stage
                )";
        
        $stmt = $pdo->prepare($sql);
        
        // 4. Thực thi truyền tham số
        $stmt->execute([
            ':batch_id'         => $batch_id,
            ':product_id'       => $product_id,
            ':supplier_id'      => $supplier_id,
            ':shift_id'         => $shift_id,
            ':zone_id'          => $zone_id,
            ':received_date'    => $received_date,
            ':expiry_date'      => $expiry_datetime,
            ':initial_volume'   => $yield_quantity,
            ':available_stock'  => $yield_quantity,
            ':current_stage'    => $qc_status
        ]);

        // 5. Thành công -> Chuyển hướng
        $_SESSION['success_msg'] = "Finished goods batch $batch_id logged successfully!";
        header("Location: ../../frontend/inventory.php");
        exit();

    } catch (PDOException $e) {
        die("Lỗi lưu thành phẩm: " . $e->getMessage());
    }
} else {
    header("Location: ../../frontend/dashboard_production.php");
    exit();
}
?>