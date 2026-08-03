<?php
// Đường dẫn: backend/models/QcReportModel.php
require_once __DIR__ . '/../core/BaseModel.php';

class QcReportModel extends BaseModel {
    private $reasonFilter = '';

    public function setReasonFilter($reason) {
        $this->reasonFilter = $reason;
    }

    private function getReasonCondition() {
        if (empty($this->reasonFilter)) return "";
        return " AND q.QCI_rejection_reason = " . $this->pdo->quote($this->reasonFilter);
    }

    // Lấy tổng quan thống kê hao hụt
    public function getLossSummary() {
        $reasonCond = $this->getReasonCondition();
        $stmt = $this->pdo->query("
            SELECT 
                SUM(COALESCE(q.QCI_usable_weight_kg, 0) + COALESCE(q.QCI_rotten_weight_kg, 0) + COALESCE(q.QCI_natural_loss_weight_kg, 0)) AS total_inspected,
                SUM(COALESCE(q.QCI_rotten_weight_kg, 0)) AS total_rotten,
                SUM(COALESCE(q.QCI_natural_loss_weight_kg, 0)) AS total_natural,
                SUM((COALESCE(q.QCI_rotten_weight_kg, 0) + COALESCE(q.QCI_natural_loss_weight_kg, 0)) * p.PRD_unit_price) AS total_loss_cost,
                SUM(q.QCI_rotten_weight_kg * p.PRD_unit_price) AS total_abnormal_loss_cost,
                SUM(q.QCI_natural_loss_weight_kg * p.PRD_unit_price) AS total_natural_loss_cost
            FROM QC_INSPECTIONS q
            JOIN BATCHES b ON q.QCI_batch_id = b.BCH_batch_id
            JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
            WHERE 1=1 $reasonCond
        ");
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $totalInspected = (float)($res['total_inspected'] ?? 0);
        $totalRotten = (float)($res['total_rotten'] ?? 0);
        $totalNatural = (float)($res['total_natural'] ?? 0);
        $totalLoss = $totalRotten + $totalNatural;

        $defectRate = ($totalInspected > 0) ? ($totalLoss / $totalInspected) * 100 : 0;
        
        $totalLossCost = (float)($res['total_loss_cost'] ?? 0);
        $totalAbnormalCost = (float)($res['total_abnormal_loss_cost'] ?? 0);
        $totalNaturalCost = (float)($res['total_natural_loss_cost'] ?? 0);
        $abnormalCostPct = ($totalLossCost > 0) ? ($totalAbnormalCost / $totalLossCost) * 100 : 0;

        return [
            'totalInspected' => $totalInspected,
            'totalLoss' => $totalLoss,
            'defectRate' => $defectRate,
            'totalLossCost' => $totalLossCost,
            'totalAbnormalCost' => $totalAbnormalCost,
            'totalNaturalCost' => $totalNaturalCost,
            'abnormalCostPct' => $abnormalCostPct
        ];
    }

    // Phân bổ nguyên nhân loại bỏ để vẽ Biểu đồ Tròn
    public function getReasonBreakdown($lang = 'vi') {
        $reasonCol = ($lang === 'en') ? 'COALESCE(QCI_rejection_reason_en, QCI_rejection_reason)' : 'QCI_rejection_reason';
        $reasonCond = $this->getReasonCondition();
        $stmt = $this->pdo->query("
            SELECT $reasonCol AS reason, COUNT(*) AS count, SUM(QCI_rotten_weight_kg) AS total_kg
            FROM QC_INSPECTIONS q
            WHERE QCI_rejection_reason IS NOT NULL AND QCI_rejection_reason != 'None' AND QCI_rejection_reason != '' $reasonCond
            GROUP BY reason
            ORDER BY total_kg DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Danh sách các lô hàng có phế phẩm cao (Lớn hơn 0)
    public function getHighLossBatches($lang = 'vi', $limit = 15) {
        $limit = (int) $limit;
        $reasonCol = ($lang === 'en') ? 'COALESCE(q.QCI_rejection_reason_en, q.QCI_rejection_reason)' : 'q.QCI_rejection_reason';
        $reasonCond = $this->getReasonCondition();
        
        $stmt = $this->pdo->prepare("
            SELECT q.QCI_batch_id, 
                   " . (($lang === 'en') ? 'COALESCE(p.PRD_product_name_en, p.PRD_product_name)' : 'p.PRD_product_name') . " AS PRD_product_name, 
                   " . (($lang === 'en') ? 'COALESCE(s.SUP_supplier_name_en, s.SUP_supplier_name)' : 's.SUP_supplier_name') . " AS SUP_supplier_name,
                   q.QCI_rotten_weight_kg, $reasonCol AS QCI_rejection_reason, q.QCI_actual_yield_pct,
                   b.BCH_received_date, (q.QCI_rotten_weight_kg * p.PRD_unit_price) AS cost_impact
            FROM QC_INSPECTIONS q
            JOIN BATCHES b ON q.QCI_batch_id = b.BCH_batch_id
            JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
            JOIN SUPPLIERS s ON b.BCH_supplier_id = s.SUP_supplier_id
            WHERE q.QCI_rotten_weight_kg > 0 $reasonCond
            ORDER BY cost_impact DESC
            LIMIT :limit
        ");
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy dữ liệu cho Supplier Scorecard
    public function getSupplierScorecard($lang = 'vi') {
        $supplierNameCol = ($lang === 'en') ? 'COALESCE(s.SUP_supplier_name_en, s.SUP_supplier_name)' : 's.SUP_supplier_name';
        $reasonCond = $this->getReasonCondition();
        $sql = "
            SELECT 
                s.SUP_supplier_id,
                $supplierNameCol AS SUP_supplier_name,
                COALESCE(SUM(b.BCH_initial_volume_kg), 0) AS total_supplied,
                COALESCE(SUM(q.QCI_rotten_weight_kg + q.QCI_natural_loss_weight_kg), 0) AS total_rejected,
                COALESCE(SUM((q.QCI_rotten_weight_kg + q.QCI_natural_loss_weight_kg) * p.PRD_unit_price), 0) AS total_waste_cost,
                
                -- Current 30 days
                COALESCE(SUM(CASE WHEN b.BCH_received_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN b.BCH_initial_volume_kg ELSE 0 END), 0) AS current_supplied,
                COALESCE(SUM(CASE WHEN b.BCH_received_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN (q.QCI_rotten_weight_kg + q.QCI_natural_loss_weight_kg) ELSE 0 END), 0) AS current_rejected,

                -- Previous 30 days
                COALESCE(SUM(CASE WHEN b.BCH_received_date >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND b.BCH_received_date < DATE_SUB(NOW(), INTERVAL 30 DAY) THEN b.BCH_initial_volume_kg ELSE 0 END), 0) AS prev_supplied,
                COALESCE(SUM(CASE WHEN b.BCH_received_date >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND b.BCH_received_date < DATE_SUB(NOW(), INTERVAL 30 DAY) THEN (q.QCI_rotten_weight_kg + q.QCI_natural_loss_weight_kg) ELSE 0 END), 0) AS prev_rejected
            FROM SUPPLIERS s
            LEFT JOIN BATCHES b ON s.SUP_supplier_id = b.BCH_supplier_id
            LEFT JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
            LEFT JOIN QC_INSPECTIONS q ON b.BCH_batch_id = q.QCI_batch_id
            WHERE 1=1 $reasonCond
            GROUP BY s.SUP_supplier_id
            ORDER BY total_waste_cost DESC
        ";
        $stmt = $this->pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $processed = [];
        foreach ($results as $row) {
            $totalSupplied = (float)$row['total_supplied'];
            $totalRejected = (float)$row['total_rejected'];
            $wasteCost = (float)$row['total_waste_cost'];
            
            $currentSupplied = (float)$row['current_supplied'];
            $currentRejected = (float)$row['current_rejected'];
            $prevSupplied = (float)$row['prev_supplied'];
            $prevRejected = (float)$row['prev_rejected'];

            if ($totalSupplied == 0) {
                $wastePct = null;
                $badgeText = ($lang === 'en') ? 'Insufficient Data' : 'Chưa đủ dữ liệu';
                $badgeColor = 'bg-gray-500';
                $badgeIconColor = 'text-gray-500';
            } else {
                $wastePct = ($totalRejected / $totalSupplied) * 100;
                if ($wastePct < 5) {
                    $badgeText = 'Good';
                    $badgeColor = 'bg-green-500/10 text-green-500 border border-green-500/20';
                    $badgeIconColor = 'text-green-500 bg-green-500';
                } elseif ($wastePct <= 15) {
                    $badgeText = ($lang === 'en') ? 'Monitor' : 'Cần theo dõi';
                    $badgeColor = 'bg-yellow-500/10 text-yellow-500 border border-yellow-500/20';
                    $badgeIconColor = 'text-yellow-500 bg-yellow-500';
                } else {
                    $badgeText = ($lang === 'en') ? 'Warning' : 'Cảnh báo';
                    $badgeColor = 'bg-red-500/10 text-red-500 border border-red-500/20';
                    $badgeIconColor = 'text-red-500 bg-red-500';
                }
            }

            $currentPct = ($currentSupplied > 0) ? ($currentRejected / $currentSupplied) * 100 : 0;
            $prevPct = ($prevSupplied > 0) ? ($prevRejected / $prevSupplied) * 100 : 0;
            $trendValue = $currentPct - $prevPct;

            $processed[] = [
                'supplier_name' => $row['SUP_supplier_name'],
                'total_supplied' => $totalSupplied,
                'waste_pct' => $wastePct,
                'waste_cost' => $wasteCost,
                'trend_value' => $trendValue,
                'badge_text' => $badgeText,
                'badge_color' => $badgeColor,
                'badge_icon_color' => $badgeIconColor
            ];
        }

        // Sắp xếp lại theo tỷ lệ hao hụt (giảm dần) cho biểu đồ
        usort($processed, function($a, $b) {
            if ($a['waste_pct'] === null && $b['waste_pct'] === null) return 0;
            if ($a['waste_pct'] === null) return 1;
            if ($b['waste_pct'] === null) return -1;
            return $b['waste_pct'] <=> $a['waste_pct'];
        });

        return $processed;
    }

    // Biểu đồ cột: So sánh chi phí hao hụt giữa các sản phẩm
    public function getWasteCostByProduct($lang = 'vi') {
        $productNameCol = ($lang === 'en') ? 'COALESCE(p.PRD_product_name_en, p.PRD_product_name)' : 'p.PRD_product_name';
        $reasonCond = $this->getReasonCondition();
        $stmt = $this->pdo->query("
            SELECT 
                $productNameCol AS product_name, 
                COALESCE(SUM(q.QCI_rotten_weight_kg * p.PRD_unit_price), 0) AS abnormal_cost,
                COALESCE(SUM(q.QCI_natural_loss_weight_kg * p.PRD_unit_price), 0) AS natural_cost
            FROM QC_INSPECTIONS q
            JOIN BATCHES b ON q.QCI_batch_id = b.BCH_batch_id
            JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
            WHERE 1=1 $reasonCond
            GROUP BY product_name
            ORDER BY abnormal_cost DESC
        ");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as &$r) {
            $r['abnormal_cost'] = (float)$r['abnormal_cost'];
            $r['natural_cost'] = (float)$r['natural_cost'];
        }
        return $results;
    }

    // Biểu đồ xu hướng: Chi phí hao hụt theo ngày (30 ngày gần nhất)
    public function getWasteCostTrend($days = 30) {
        $days = (int)$days;
        $reasonCond = $this->getReasonCondition();
        $stmt = $this->pdo->query("
            SELECT 
                DATE(b.BCH_received_date) AS report_date,
                COALESCE(SUM(q.QCI_rotten_weight_kg * p.PRD_unit_price), 0) AS daily_abnormal_cost,
                COALESCE(SUM(q.QCI_natural_loss_weight_kg * p.PRD_unit_price), 0) AS daily_natural_cost
            FROM QC_INSPECTIONS q
            JOIN BATCHES b ON q.QCI_batch_id = b.BCH_batch_id
            JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
            WHERE 1=1 $reasonCond
            GROUP BY report_date
            ORDER BY report_date DESC
            LIMIT $days
        ");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Reverse array to show oldest to newest left to right
        $results = array_reverse($results);
        
        foreach ($results as &$r) {
            $r['daily_abnormal_cost'] = (float)$r['daily_abnormal_cost'];
            $r['daily_natural_cost'] = (float)$r['daily_natural_cost'];
        }
        return $results;
    }

    public function getAvailableReasons($lang = 'vi') {
        $reasonCol = ($lang === 'en') ? 'COALESCE(QCI_rejection_reason_en, QCI_rejection_reason)' : 'QCI_rejection_reason';
        $stmt = $this->pdo->query("
            SELECT DISTINCT $reasonCol AS reason
            FROM QC_INSPECTIONS
            WHERE QCI_rejection_reason IS NOT NULL AND QCI_rejection_reason != '' AND QCI_rejection_reason != 'None'
            ORDER BY reason ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
?>