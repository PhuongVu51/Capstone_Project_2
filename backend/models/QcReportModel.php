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
                   b.BCH_received_date, p.PRD_unit_price,
                   (q.QCI_rotten_weight_kg * p.PRD_unit_price) AS rotten_cost
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

    // ============================================================
    // WASTE COST ATTRIBUTION (mới) — dịch KG hao hụt sang VNĐ,
    // tách riêng Natural loss (hao hụt tự nhiên) vs Abnormal loss
    // (hao hụt bất thường / có thể phòng tránh), dùng PRD_unit_price.
    // ============================================================

    // Tổng chi phí hao hụt toàn hệ thống — dùng cho 2 card + % card
    public function getWasteCostSummary() {
        $stmt = $this->pdo->query("
            SELECT 
                SUM(q.QCI_natural_loss_weight_kg * p.PRD_unit_price) AS total_natural_cost,
                SUM(q.QCI_rotten_weight_kg * p.PRD_unit_price) AS total_abnormal_cost
            FROM QC_INSPECTIONS q
            JOIN BATCHES b ON q.QCI_batch_id = b.BCH_batch_id
            JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
        ");
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        $naturalCost = (float) ($res['total_natural_cost'] ?? 0);
        $abnormalCost = (float) ($res['total_abnormal_cost'] ?? 0);
        $totalCost = $naturalCost + $abnormalCost;
        $abnormalPercent = ($totalCost > 0) ? ($abnormalCost / $totalCost) * 100 : 0;

        return [
            'naturalCost'     => $naturalCost,
            'abnormalCost'    => $abnormalCost,
            'abnormalPercent' => $abnormalPercent
        ];
    }

    // Chi phí hao hụt theo từng loại sản phẩm — dùng cho stacked bar chart
    public function getWasteCostByProduct() {
        $stmt = $this->pdo->query("
            SELECT 
                p.PRD_product_name,
                SUM(q.QCI_natural_loss_weight_kg) AS natural_kg,
                SUM(q.QCI_rotten_weight_kg) AS abnormal_kg,
                SUM(q.QCI_natural_loss_weight_kg * p.PRD_unit_price) AS natural_cost,
                SUM(q.QCI_rotten_weight_kg * p.PRD_unit_price) AS abnormal_cost
            FROM QC_INSPECTIONS q
            JOIN BATCHES b ON q.QCI_batch_id = b.BCH_batch_id
            JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
            GROUP BY p.PRD_product_id, p.PRD_product_name
            ORDER BY abnormal_cost DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Xu hướng chi phí hao hụt theo ngày nhập hàng — dùng cho line chart
    public function getWasteCostTrend($days = 14) {
        $days = (int) $days; // an toàn cho LIMIT

        $stmt = $this->pdo->query("
            SELECT 
                DATE(b.BCH_received_date) AS loss_date,
                SUM(q.QCI_natural_loss_weight_kg * p.PRD_unit_price) AS natural_cost,
                SUM(q.QCI_rotten_weight_kg * p.PRD_unit_price) AS abnormal_cost
            FROM QC_INSPECTIONS q
            JOIN BATCHES b ON q.QCI_batch_id = b.BCH_batch_id
            JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
            GROUP BY DATE(b.BCH_received_date)
            ORDER BY loss_date DESC
            LIMIT $days
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Đảo lại để biểu đồ đọc theo thứ tự thời gian tăng dần (trái -> phải)
        return array_reverse($rows);
    }
}
?>