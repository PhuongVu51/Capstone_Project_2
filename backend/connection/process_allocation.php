<?php
// Đường dẫn: backend/connection/process_allocation.php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Production_Manager') {
    header("Location: ../../frontend/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $batch_id = trim($_POST['batch_id']);
    $allocate_qty = floatval($_POST['allocate_qty']);
    $user_id = $_SESSION['user_id'];
    $production_line = "Production Line Alpha-2"; // Hardcode theo UI Success

    try {
        // Bắt đầu Transaction (Đảm bảo an toàn dữ liệu, 1 bảng lỗi là rollback toàn bộ)
        $pdo->beginTransaction();

        // 1. Kiểm tra tồn kho thực tế xem có đủ để xuất không (chống submit 2 lần hoặc hack DOM)
        $stmtCheck = $pdo->prepare("SELECT BCH_available_stock_kg FROM BATCHES WHERE BCH_batch_id = :id FOR UPDATE");
        $stmtCheck->execute([':id' => $batch_id]);
        $batch = $stmtCheck->fetch();

        if (!$batch || $allocate_qty <= 0 || $allocate_qty > $batch['BCH_available_stock_kg']) {
            throw new Exception("Invalid allocation quantity or insufficient stock.");
        }

        $new_stock = $batch['BCH_available_stock_kg'] - $allocate_qty;
        
        // Chuyển trạng thái nếu kho hết sạch
        $new_stage = ($new_stock == 0) ? 'Fully_Allocated' : 'In_Production';

        // 2. Trừ tồn kho trong bảng BATCHES
        $stmtUpdate = $pdo->prepare("UPDATE BATCHES SET BCH_available_stock_kg = :new_stock, BCH_current_stage = :stage WHERE BCH_batch_id = :id");
        $stmtUpdate->execute([
            ':new_stock' => $new_stock,
            ':stage' => $new_stage,
            ':id' => $batch_id
        ]);

        // 3. Ghi lịch sử cấp phát vào bảng MATERIAL_ALLOCATIONS
        $stmtAlloc = $pdo->prepare("INSERT INTO MATERIAL_ALLOCATIONS (ALC_batch_id, ALC_user_id, ALC_allocated_quantity_kg, ALC_production_line) VALUES (:batch, :user, :qty, :line)");
        $stmtAlloc->execute([
            ':batch' => $batch_id,
            ':user' => $user_id,
            ':qty' => $allocate_qty,
            ':line' => $production_line
        ]);

        // 4. Ghi log Nhật ký kho vào bảng STOCK_MOVEMENTS
        // Tạo Reference Code duy nhất
        $ref_code = "ALLOC_" . time() . "_" . rand(100, 999); 
        $stmtMvmt = $pdo->prepare("INSERT INTO STOCK_MOVEMENTS (STM_reference_code, STM_batch_id, STM_movement_type, STM_quantity_kg, STM_user_id) VALUES (:ref, :batch, 'OUT', :qty, :user)");
        $stmtMvmt->execute([
            ':ref' => $ref_code,
            ':batch' => $batch_id,
            ':qty' => $allocate_qty,
            ':user' => $user_id
        ]);

        // Xác nhận lưu toàn bộ thay đổi (Commit)
        $pdo->commit();

        // Trả về trang Dashboard kèm thông báo Success
        header("Location: ../../frontend/dashboard_production.php?status=success&batch=" . urlencode($batch_id));
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Allocation Failed: " . $e->getMessage());
    }
}
?>