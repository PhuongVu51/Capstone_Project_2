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

        return [
            'totalInspected' => number_format($summary['totalInspected'], 1),
            'totalLoss'      => number_format($summary['totalLoss'], 1),
            'defectRate'     => number_format($summary['defectRate'], 2),
            'topReason'      => $topReason,
            'topReasonKg'    => number_format((float)$topReasonKg, 1),
            'chartLabels'    => $chartLabels,
            'chartData'      => $chartData,
            'lossBatches'    => $lossBatches
        ];
    }
}
?>