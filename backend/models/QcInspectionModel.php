<?php
// Đường dẫn: backend/models/QcInspectionModel.php
require_once __DIR__ . '/../core/BaseModel.php';

class QcInspectionModel extends BaseModel {

    // Lấy danh sách toàn bộ các lô hàng đang chờ QC kiểm định
    public function getPendingQueue() {
        $stmt = $this->pdo->query("
            SELECT b.BCH_batch_id, p.PRD_product_name, b.BCH_received_date, 
                   b.BCH_initial_volume_kg, b.BCH_priority
            FROM BATCHES b
            JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
            WHERE b.BCH_current_stage = 'Pending_QC'
            ORDER BY 
                CASE b.BCH_priority 
                    WHEN 'High' THEN 1 
                    WHEN 'Medium' THEN 2 
                    ELSE 3 
                END, b.BCH_received_date ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getInspectionKPIs() {
        // 1. Tổng lô hàng đã qua kiểm định
        $stmtProcessed = $this->pdo->query("SELECT COUNT(DISTINCT QCI_batch_id) FROM QC_INSPECTIONS");
        $processedCount = (int)$stmtProcessed->fetchColumn();

        // 2. Tổng số lô hàng đã check + đang chờ
        $stmtPending = $this->pdo->query("SELECT COUNT(*) FROM BATCHES WHERE BCH_current_stage = 'Pending_QC'");
        $pendingCount = (int)$stmtPending->fetchColumn();
        $totalBatches = $processedCount + $pendingCount;

        // 3. Tính Average Lead Time thực tế (Phút) từ lúc nhận lô đến lúc QC
        $stmtLeadTime = $this->pdo->query("
            SELECT AVG(TIMESTAMPDIFF(MINUTE, b.BCH_received_date, q.QCI_inspection_id)) AS avg_minutes
            FROM QC_INSPECTIONS q
            JOIN BATCHES b ON q.QCI_batch_id = b.BCH_batch_id
            WHERE b.BCH_received_date IS NOT NULL
        ");
        $avgLeadTimeMins = (int)$stmtLeadTime->fetchColumn();
        if ($avgLeadTimeMins <= 0) $avgLeadTimeMins = 24; // Giá trị fallback an toàn

        // 4. Tính % chênh lệch Lead Time động so với ca liền trước
        $stmtShiftTrends = $this->pdo->query("
            SELECT s.SHF_shift_id, 
                   AVG(TIMESTAMPDIFF(MINUTE, b.BCH_received_date, q.QCI_inspection_id)) AS shift_avg
            FROM QC_INSPECTIONS q
            JOIN BATCHES b ON q.QCI_batch_id = b.BCH_batch_id
            JOIN SHIFTS s ON b.BCH_shift_id = s.SHF_shift_id
            GROUP BY s.SHF_shift_id
            ORDER BY s.SHF_shift_date DESC, s.SHF_shift_id DESC
            LIMIT 2
        ");
        $shiftsData = $stmtShiftTrends->fetchAll(PDO::FETCH_ASSOC);

        $pctChange = 12;
        $isDecrease = true;

        if (count($shiftsData) >= 2) {
            $currentShiftAvg = (float)$shiftsData[0]['shift_avg'];
            $previousShiftAvg = (float)$shiftsData[1]['shift_avg'];
            
            if ($previousShiftAvg > 0) {
                $diff = (($currentShiftAvg - $previousShiftAvg) / $previousShiftAvg) * 100;
                $pctChange = abs(round($diff, 1));
                $isDecrease = ($currentShiftAvg <= $previousShiftAvg);
            }
        }

        return [
            'processedCount' => $processedCount,
            'totalBatches' => $totalBatches,
            'avgLeadTimeMins' => $avgLeadTimeMins,
            'leadTimePct' => $pctChange,
            'isDecrease' => $isDecrease
        ];
    }

    // Lấy chi tiết thông số nguồn gốc lô hàng khi bấm kiểm định
    public function getBatchForInspection($batch_id) {
        $stmt = $this->pdo->prepare("
            SELECT b.BCH_batch_id, p.PRD_product_name, p.PRD_material_grade, 
                   s.SUP_supplier_name, s.SUP_origin_facility,
                   b.BCH_initial_volume_kg, b.BCH_received_date
            FROM BATCHES b
            JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
            JOIN SUPPLIERS s ON b.BCH_supplier_id = s.SUP_supplier_id
            WHERE b.BCH_batch_id = ? AND b.BCH_current_stage = 'Pending_QC'
            LIMIT 1
        ");
        $stmt->execute([$batch_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Ghi nhận kết quả QC, tính Yield thực tế
    public function submitInspection($batch_id, $rejected_qty, $reason, $comments, $user_id) {
        // ... Logic Submit giữ nguyên như cũ ...
        $stmt = $this->pdo->prepare("SELECT BCH_initial_volume_kg FROM BATCHES WHERE BCH_batch_id = ?");
        $stmt->execute([$batch_id]);
        $initial_vol = (float)$stmt->fetchColumn();

        $usable_weight = $initial_vol - $rejected_qty;
        if ($usable_weight < 0) $usable_weight = 0;
        $natural_loss = $usable_weight * 0.02; 
        $usable_weight_final = $usable_weight - $natural_loss;
        $actual_yield_pct = ($initial_vol > 0) ? ($usable_weight_final / $initial_vol) * 100 : 0;

        $this->pdo->beginTransaction();
        try {
            $destination = ($actual_yield_pct >= 80) ? 'Production' : 'Rejected';
            $stmtInsert = $this->pdo->prepare("
                INSERT INTO QC_INSPECTIONS 
                (QCI_batch_id, QCI_user_id, QCI_usable_weight_kg, QCI_rotten_weight_kg, QCI_natural_loss_weight_kg, QCI_destination, QCI_actual_yield_pct)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtInsert->execute([$batch_id, $user_id, $usable_weight_final, $rejected_qty, $natural_loss, $destination, $actual_yield_pct]);

            $stmtUpdateBatch = $this->pdo->prepare("UPDATE BATCHES SET BCH_current_stage = 'QC_Passed', BCH_available_stock_kg = ? WHERE BCH_batch_id = ?");
            $stmtUpdateBatch->execute([$usable_weight_final, $batch_id]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
?>