<?php
// Đường dẫn: backend/controllers/QcReportController.php
require_once __DIR__ . '/../models/QcReportModel.php';

class QcReportController {
    private $model;

    public function __construct() {
        $this->model = new QcReportModel();
    }

    public function loadReportData() {
        $summary = $this->model->getLossSummary();
        $breakdown = $this->model->getReasonBreakdown();
        $lossBatches = $this->model->getHighLossBatches();

        // Xử lý logic dữ liệu cho biểu đồ tròn (Doughnut Chart)
        $chartLabels = [];
        $chartData = [];
        $topReason = 'N/A';
        $topReasonKg = 0;

        if (!empty($breakdown)) {
            $topReason = $breakdown[0]['reason'];
            $topReasonKg = $breakdown[0]['total_kg'];

            foreach ($breakdown as $item) {
                $chartLabels[] = $item['reason'];
                $chartData[] = round($item['total_kg'], 1);
            }
        }

        // ============================================================
        // WASTE COST ATTRIBUTION (mới)
        // ============================================================
        $costSummary   = $this->model->getWasteCostSummary();
        $costByProduct = $this->model->getWasteCostByProduct();
        $costTrend     = $this->model->getWasteCostTrend();

        // Dữ liệu cho stacked bar chart (theo sản phẩm)
        $costChartLabels   = [];
        $costChartNatural  = [];
        $costChartAbnormal = [];
        foreach ($costByProduct as $row) {
            $costChartLabels[]   = $row['PRD_product_name'];
            $costChartNatural[]  = round((float) $row['natural_cost'], 0);
            $costChartAbnormal[] = round((float) $row['abnormal_cost'], 0);
        }

        // Dữ liệu cho line chart xu hướng (theo ngày nhập hàng)
        $trendLabels   = [];
        $trendNatural  = [];
        $trendAbnormal = [];
        foreach ($costTrend as $row) {
            $trendLabels[]   = date('d/m', strtotime($row['loss_date']));
            $trendNatural[]  = round((float) $row['natural_cost'], 0);
            $trendAbnormal[] = round((float) $row['abnormal_cost'], 0);
        }

        return [
            'totalInspected' => number_format($summary['totalInspected'], 1),
            'totalLoss'      => number_format($summary['totalLoss'], 1),
            'defectRate'     => number_format($summary['defectRate'], 2),
            'topReason'      => $topReason,
            'topReasonKg'    => number_format((float)$topReasonKg, 1),
            'chartLabels'    => $chartLabels,
            'chartData'      => $chartData,
            'lossBatches'    => $lossBatches,

            // --- Waste Cost Attribution (mới) ---
            'naturalCostTotal'    => number_format($costSummary['naturalCost'], 0, ',', '.'),
            'abnormalCostTotal'   => number_format($costSummary['abnormalCost'], 0, ',', '.'),
            'abnormalCostPercent' => number_format($costSummary['abnormalPercent'], 1),
            'costChartLabels'     => $costChartLabels,
            'costChartNatural'    => $costChartNatural,
            'costChartAbnormal'   => $costChartAbnormal,
            'trendLabels'         => $trendLabels,
            'trendNatural'        => $trendNatural,
            'trendAbnormal'       => $trendAbnormal,
        ];
    }
}
?>