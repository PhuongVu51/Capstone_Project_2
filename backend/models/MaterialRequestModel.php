<?php
// Đường dẫn: backend/models/MaterialRequestModel.php
require_once __DIR__ . '/../core/BaseModel.php';

class MaterialRequestModel extends BaseModel {

    // 1. Dành cho PM: Gửi yêu cầu mới
    public function createRequest($materialId, $quantity, $neededDate, $priority, $notes, $userId) {
        $stmt = $this->pdo->prepare("
            INSERT INTO MATERIAL_REQUESTS (REQ_material_id, REQ_quantity, REQ_needed_date, REQ_priority, REQ_notes, REQ_requested_by, REQ_status) 
            VALUES (?, ?, ?, ?, ?, ?, 'Pending')
        ");
        return $stmt->execute([$materialId, $quantity, $neededDate, $priority, $notes, $userId]);
    }

    // 2. Dành cho Warehouse: Lấy tất cả yêu cầu (Pending lên đầu)
    public function getAllRequests() {
        $stmt = $this->pdo->query("
            SELECT r.*, u.USR_full_name as requester_name
            FROM MATERIAL_REQUESTS r
            LEFT JOIN USERS u ON r.REQ_requested_by = u.USR_user_id
            ORDER BY 
                CASE r.REQ_status WHEN 'Pending' THEN 1 ELSE 2 END, 
                r.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. Dành cho Kho/Director: Xử lý duyệt yêu cầu
    public function updateRequestStatus($requestId, $status) {
        $stmt = $this->pdo->prepare("UPDATE MATERIAL_REQUESTS SET REQ_status = ? WHERE REQ_id = ?");
        return $stmt->execute([$status, $requestId]);
    }
}
?>