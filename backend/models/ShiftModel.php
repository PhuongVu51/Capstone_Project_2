<?php
// Đường dẫn: backend/models/ShiftModel.php
require_once __DIR__ . '/../core/BaseModel.php';

class ShiftModel extends BaseModel
{
    /**
     * Tự động lấy hoặc tạo ca làm việc mới dựa trên THỜI GIAN THỰC
     */
    public function getRealTimeShift()
    {
        // 1. Thiết lập múi giờ Việt Nam
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $hour = (int)date('H');
        $currentDate = date('Y-m-d');
        
        // 2. Phân loại ca theo khung giờ công nghiệp
        if ($hour >= 6 && $hour < 14) {
            $shiftType = 'Morning';
        } elseif ($hour >= 14 && $hour < 22) {
            $shiftType = 'Afternoon';
        } else {
            $shiftType = 'Overtime';
            // Nếu qua 12h đêm (0h-5h sáng), tính là ca Overtime của ngày hôm trước
            if ($hour < 6) {
                $currentDate = date('Y-m-d', strtotime('-1 day'));
            }
        }

        // 3. Truy vấn tìm ca hiện tại
        $stmt = $this->pdo->prepare("SELECT * FROM SHIFTS WHERE SHF_shift_date = ? AND SHF_shift_type = ? LIMIT 1");
        $stmt->execute([$currentDate, $shiftType]);
        $shift = $stmt->fetch(PDO::FETCH_ASSOC);

        // 4. AUTO-CREATE: Nếu ca chưa tồn tại (chưa ai tạo), tự động Insert vào CSDL
        if (!$shift) {
            $insertStmt = $this->pdo->prepare("
                INSERT INTO SHIFTS (SHF_shift_date, SHF_shift_type, SHF_worker_count, SHF_status) 
                VALUES (?, ?, 0, 'Open')
            ");
            $insertStmt->execute([$currentDate, $shiftType]);
            
            // Lấy lại ca vừa tạo
            $stmt->execute([$currentDate, $shiftType]);
            $shift = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // 5. Trả dữ liệu về nếu ca còn đang Open
        if ($shift && $shift['SHF_status'] === 'Open') {
            return $shift;
        }

        return null; // Nếu ca đã bị đóng (Closed), trả về null
    }

    /**
     * Giữ lại hàm cũ phòng trường hợp các trang khác vẫn đang gọi tên hàm này
     */
    public function getCurrentOpenShift()
    {
        return $this->getRealTimeShift();
    }

    /**
     * Lấy danh sách lịch sử luân chuyển kho trong một ca cụ thể
     */
    public function getMovementsForShift($shiftId)
    {
        $stmt = $this->pdo->prepare("
            SELECT m.STM_timestamp, m.STM_reference_code, m.STM_batch_id, 
                   p.PRD_product_name, p.PRD_product_name_en, 
                   m.STM_movement_type, m.STM_quantity_kg, 
                   u.USR_full_name
            FROM STOCK_MOVEMENTS m
            LEFT JOIN BATCHES b ON m.STM_batch_id = b.BCH_batch_id
            LEFT JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
            LEFT JOIN USERS u ON m.STM_user_id = u.USR_user_id
            WHERE m.STM_shift_id = ?
            ORDER BY m.STM_timestamp DESC
        ");
        $stmt->execute([$shiftId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy các số liệu thống kê tổng hợp để hiển thị ở màn hình Review
     */
    public function getShiftSummary($shiftId)
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN STM_movement_type = 'IN' THEN STM_quantity_kg ELSE 0 END), 0) AS total_in_kg,
                COALESCE(SUM(CASE WHEN STM_movement_type = 'OUT' THEN STM_quantity_kg ELSE 0 END), 0) AS total_out_kg,
                COUNT(DISTINCT STM_batch_id) AS batch_count,
                COUNT(STM_movement_id) AS movement_count,
                0 AS incident_count
            FROM STOCK_MOVEMENTS
            WHERE STM_shift_id = ?
        ");
        $stmt->execute([$shiftId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$res) {
            return [
                'total_in_kg' => 0,
                'total_out_kg' => 0,
                'batch_count' => 0,
                'movement_count' => 0,
                'incident_count' => 0
            ];
        }
        return $res;
    }

    /**
     * Thực hiện khóa ca làm việc
     */
    public function closeShift($shiftId, $userId)
    {
        $stmt = $this->pdo->prepare("
            UPDATE SHIFTS 
            SET SHF_status = 'Closed', 
                SHF_closed_by = ?, 
                SHF_closed_at = NOW() 
            WHERE SHF_shift_id = ?
        ");
        $stmt->execute([$userId, $shiftId]);
    }
}
?>