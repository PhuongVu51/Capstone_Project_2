<?php
// Đường dẫn: backend/controllers/QcReportController.php
require_once __DIR__ . '/../models/QcReportModel.php';

class QcReportController {
    private $model;

    public function __construct() {
        $this->model = new QcReportModel();
    }

    public function loadReportData($lang = 'vi') {
        $reasonFilter = $_GET['reason'] ?? '';
        $this->model->setReasonFilter($reasonFilter);

        $summary = $this->model->getLossSummary();
        $breakdown = $this->model->getReasonBreakdown($lang);
        $lossBatches = $this->model->getHighLossBatches($lang);
        $supplierScorecard = $this->model->getSupplierScorecard($lang);
        
        $availableReasons = $this->model->getAvailableReasons($lang);
        
        $costByProduct = $this->model->getWasteCostByProduct($lang);
        $costTrend = $this->model->getWasteCostTrend(30);

        // Xử lý logic dữ liệu cho biểu đồ tròn (Doughnut Chart)
        $chartLabels = [];
        $chartData = [];
        $topReason = 'N/A';
        $topReasonKg = 0;

        if (!empty($breakdown)) {
            $topReason = ($lang === 'en') ? $this->translateReason($breakdown[0]['reason']) : $breakdown[0]['reason'];
            $topReasonKg = $breakdown[0]['total_kg'];

            foreach ($breakdown as $item) {
                $reasonText = ($lang === 'en') ? $this->translateReason($item['reason']) : $item['reason'];
                $chartLabels[] = $reasonText;
                $chartData[] = round($item['total_kg'], 1);
            }
        }

        if ($lang === 'en') {
            if (!empty($lossBatches)) {
                foreach ($lossBatches as &$b) {
                    $b['QCI_rejection_reason'] = $this->translateReason($b['QCI_rejection_reason']);
                    $b['PRD_product_name'] = translate_product_name($b['PRD_product_name']);
                    $b['SUP_supplier_name'] = translate_supplier_name($b['SUP_supplier_name']);
                }
            }
            if (!empty($supplierScorecard)) {
                foreach ($supplierScorecard as &$s) {
                    if (isset($s['SUP_supplier_name'])) {
                        $s['SUP_supplier_name'] = translate_supplier_name($s['SUP_supplier_name']);
                    }
                }
            }
            if (!empty($costByProduct)) {
                foreach ($costByProduct as &$p) {
                    if (isset($p['PRD_product_name'])) {
                        $p['PRD_product_name'] = translate_product_name($p['PRD_product_name']);
                    }
                }
            }
        }

        return [
            'totalInspected' => number_format($summary['totalInspected'], 1),
            'totalLoss'      => number_format($summary['totalLoss'], 1),
            'defectRate'     => number_format($summary['defectRate'], 2),
            'totalLossCost'  => number_format($summary['totalLossCost']),
            'totalAbnormalCost' => number_format($summary['totalAbnormalCost']),
            'totalNaturalCost'  => number_format($summary['totalNaturalCost']),
            'abnormalCostPct'   => number_format($summary['abnormalCostPct'], 1),
            'topReason'      => $topReason,
            'topReasonKg'    => number_format((float)$topReasonKg, 1),
            'chartLabels'    => $chartLabels,
            'chartData'      => $chartData,
            'lossBatches'    => $lossBatches,
            'supplierScorecard' => $supplierScorecard,
            'availableReasons' => $availableReasons,
            'costByProduct'  => $costByProduct,
            'costTrend'      => $costTrend
        ];
    }

    private function translateReason($str) {
        if (!$str || $str === 'N/A' || $str === 'None') return 'None';
        
        $r = strtolower($str);
        if (str_contains($r, 'han') || str_contains($r, 'gỉ') || str_contains($r, 'gi') || str_contains($r, 'corrosion')) {
            if (str_contains($r, 'ngoai') || str_contains($r, 'ngoài')) return 'External Rusty Cans (Internal Unverified)';
            if (str_contains($r, 'hong') || str_contains($r, 'hỏng')) return 'Rust & Damaged Cans';
            return 'Rust / Corrosion';
        }
        if (str_contains($r, '10') || str_contains($r, 'mop') || str_contains($r, 'móp')) {
            if (str_contains($r, '10')) return 'Dented Cans (10 Units)';
            if (str_contains($r, 'meo') || str_contains($r, 'méo')) return 'Dented & Deformed Cans';
            return 'Dented Cans';
        }
        if (str_contains($r, 'meo') || str_contains($r, 'méo')) return 'Deformed Cans';
        if (str_contains($r, 'cut') || str_contains($r, 'cắt')) return 'Miscut Specification Batch';
        if (str_contains($r, 'tibit')) return 'Tibit Defect';
        if (str_contains($r, '27') || str_contains($r, 'thung') || str_contains($r, 'thùng')) return '27 Cartons + 7 Cans (Severe Rust)';
        if (str_contains($r, 'hong yen') || str_contains($r, 'hồng yên')) return 'Hong Yen Returned Batch';
        if (str_contains($r, 'tra') || str_contains($r, 'kh')) return 'Customer Return (Canceled Label)';
        if (str_contains($r, 'den') || str_contains($r, 'đen')) return 'Black Labeled Batch (VP)';
        if (str_contains($r, 'trang') || str_contains($r, 'trắng')) return 'White Labeled Batch (VP)';
        if (str_contains($r, 'damaged')) return 'Damaged Goods Seed Record';
        
        return $this->removeAccents($str);
    }

    private function removeAccents($str) {
        if (!$str) return '';
        $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", "a", $str);
        $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", "e", $str);
        $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", "i", $str);
        $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", "o", $str);
        $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", "u", $str);
        $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", "y", $str);
        $str = preg_replace("/(đ)/", "d", $str);
        $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", "A", $str);
        $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", "E", $str);
        $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", "I", $str);
        $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", "O", $str);
        $str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", "U", $str);
        $str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", "Y", $str);
        $str = preg_replace("/(Đ)/", "D", $str);
        return $str;
    }
}
?>