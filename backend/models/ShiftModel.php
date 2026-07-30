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

        // 3. Tìm ca đang MỞ (Open) trùng ngày và khung giờ hiện tại
        $stmt = $this->pdo->prepare("SELECT * FROM SHIFTS WHERE SHF_shift_date = ? AND SHF_shift_type = ? AND SHF_status = 'Open' ORDER BY SHF_shift_id DESC LIMIT 1");
        $stmt->execute([$currentDate, $shiftType]);
        $shift = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($shift) {
            return $shift;
        }

        // 4. Nếu không có ca Open đúng khung giờ, tìm bất kỳ ca Open nào khác trong CSDL
        $fallbackStmt = $this->pdo->query("SELECT * FROM SHIFTS WHERE SHF_status = 'Open' ORDER BY SHF_shift_date DESC, SHF_shift_id DESC LIMIT 1");
        $fallbackShift = $fallbackStmt->fetch(PDO::FETCH_ASSOC);

        if ($fallbackShift) {
            return $fallbackShift;
        }

        // 5. AUTO-CREATE: Nếu tất cả các ca đều đã đóng (hoặc chưa từng tạo), tự động tạo Ca MỚI đang Open cho thời gian thực
        $insertStmt = $this->pdo->prepare("
            INSERT INTO SHIFTS (SHF_shift_date, SHF_shift_type, SHF_worker_count, SHF_status) 
            VALUES (?, ?, 0, 'Open')
        ");
        $insertStmt->execute([$currentDate, $shiftType]);
        
        $newShiftId = $this->pdo->lastInsertId();
        $stmt = $this->pdo->prepare("SELECT * FROM SHIFTS WHERE SHF_shift_id = ?");
        $stmt->execute([$newShiftId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
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

    /**
     * Lấy danh sách các ca làm việc đã đóng (Closed) kèm thông tin chi tiết người đóng và số liệu thống kê
     */
    public function getClosedShiftHistory($dateFilter = null)
    {
        $sql = "
            SELECT s.*, u.USR_full_name AS closed_by_name
            FROM SHIFTS s
            LEFT JOIN USERS u ON s.SHF_closed_by = u.USR_user_id
            WHERE s.SHF_status = 'Closed'
        ";
        $params = [];

        if ($dateFilter) {
            $sql .= " AND s.SHF_shift_date = ? ";
            $params[] = $dateFilter;
        }

        $sql .= " ORDER BY s.SHF_shift_date DESC, s.SHF_closed_at DESC, s.SHF_shift_id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($shifts as &$shift) {
            $shift['summary'] = $this->getShiftSummary($shift['SHF_shift_id']);
            $shift['movements'] = $this->getMovementsForShift($shift['SHF_shift_id']);
        }

        return $shifts;
    }

    /**
     * Lấy thông tin chi tiết một ca theo ID
     */
    public function getShiftById($shiftId)
    {
        $stmt = $this->pdo->prepare("
            SELECT s.*, u.USR_full_name AS closed_by_name
            FROM SHIFTS s
            LEFT JOIN USERS u ON s.SHF_closed_by = u.USR_user_id
            WHERE s.SHF_shift_id = ?
        ");
        $stmt->execute([(int) $shiftId]);
        $shift = $stmt->fetch(PDO::FETCH_ASSOC);
        return $shift ?: null;
    }
}
?>