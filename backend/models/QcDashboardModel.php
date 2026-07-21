<?php
require_once __DIR__ . '/../core/BaseModel.php';

class QcDashboardModel extends BaseModel {
    
    public function getQualityStats() {
        $stmt =$this->pdo->query("
            SELECT 
                (SUM(QCI_usable_weight_kg) / SUM(QCI_usable_weight_kg + QCI_rotten_weight_kg + QCI_natural_loss_weight_kg)) * 100 as pass_rate,
                (SUM(QCI_rotten_weight_kg) / SUM(QCI_usable_weight_kg + QCI_rotten_weight_kg + QCI_natural_loss_weight_kg)) * 100 as defect_ratio
            FROM QC_INSPECTIONS 
            WHERE (QCI_usable_weight_kg + QCI_rotten_weight_kg + QCI_natural_loss_weight_kg) > 0
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getPendingBatchesCount() {
        $stmt =$this->pdo->query("SELECT COUNT(*) FROM BATCHES WHERE BCH_current_stage = 'Pending_QC'");
        return $stmt->fetchColumn();
    }

    public function getRecentActivities($limit = 5) {
        $limit = (int) $limit;
        
        $stmt = $this->pdo->query("
            SELECT QCI_inspection_id, QCI_usable_weight_kg, QCI_destination, QCI_batch_id 
            FROM QC_INSPECTIONS 
            ORDER BY QCI_inspection_id DESC 
            LIMIT $limit
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTrendData($limit = 7) {
        $limit = (int) $limit;
        
        $stmt = $this->pdo->query("
            SELECT s.SHF_shift_date AS shift_date,
                   SUM(q.QCI_usable_weight_kg) AS sum_usable,
                   SUM(q.QCI_rotten_weight_kg + q.QCI_natural_loss_weight_kg) AS sum_fail
            FROM QC_INSPECTIONS q
            JOIN BATCHES b ON q.QCI_batch_id = b.BCH_batch_id
            JOIN SHIFTS s ON b.BCH_shift_id = s.SHF_shift_id
            GROUP BY s.SHF_shift_date
            ORDER BY s.SHF_shift_date DESC
            LIMIT $limit
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>