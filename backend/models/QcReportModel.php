<?php
// Đường dẫn: backend/models/QcReportModel.php
require_once __DIR__ . '/../core/BaseModel.php';

class QcReportModel extends BaseModel {

    // Lấy tổng quan thống kê hao hụt
    public function getLossSummary() {
        $stmt = $this->pdo->query("
            SELECT 
                SUM(QCI_usable_weight_kg + QCI_rotten_weight_kg + QCI_natural_loss_weight_kg) AS total_inspected,
                SUM(QCI_rotten_weight_kg) AS total_rotten,
                SUM(QCI_natural_loss_weight_kg) AS total_natural
            FROM QC_INSPECTIONS
        ");
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $totalInspected = (float)($res['total_inspected'] ?? 0);
        $totalRotten = (float)($res['total_rotten'] ?? 0);
        $totalNatural = (float)($res['total_natural'] ?? 0);
        $totalLoss = $totalRotten + $totalNatural;

        $defectRate = ($totalInspected > 0) ? ($totalLoss / $totalInspected) * 100 : 0;

        return [
            'totalInspected' => $totalInspected,
            'totalLoss' => $totalLoss,
            'defectRate' => $defectRate
        ];
    }

    // Phân bổ nguyên nhân loại bỏ để vẽ Biểu đồ Tròn
    public function getReasonBreakdown() {
        $stmt = $this->pdo->query("
            SELECT QCI_rejection_reason AS reason, COUNT(*) AS count, SUM(QCI_rotten_weight_kg) AS total_kg
            FROM QC_INSPECTIONS
            WHERE QCI_rejection_reason IS NOT NULL AND QCI_rejection_reason != 'None' AND QCI_rejection_reason != ''
            GROUP BY QCI_rejection_reason
            ORDER BY total_kg DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Danh sách các lô hàng có phế phẩm cao (Lớn hơn 0)
    public function getHighLossBatches($limit = 15) {
        $limit = (int) $limit; // Ép kiểu thành số nguyên an toàn tuyệt đối
        
        $stmt = $this->pdo->query("
            SELECT q.QCI_batch_id, p.PRD_product_name, s.SUP_supplier_name,
                   q.QCI_rotten_weight_kg, q.QCI_rejection_reason, q.QCI_actual_yield_pct,
                   b.BCH_received_date
            FROM QC_INSPECTIONS q
            JOIN BATCHES b ON q.QCI_batch_id = b.BCH_batch_id
            JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
            JOIN SUPPLIERS s ON b.BCH_supplier_id = s.SUP_supplier_id
            WHERE q.QCI_rotten_weight_kg > 0
            ORDER BY q.QCI_rotten_weight_kg DESC
            LIMIT $limit
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>