<?php
require_once __DIR__ . '/../models/QcDashboardModel.php';

class QcDashboardController {
    private $model;

    public function __construct() {
        $this->model = new QcDashboardModel();
    }

    public function loadDashboard() {
        // Lấy Data từ Model
        $stats = $this->model->getQualityStats();
        $pendingCount = $this->model->getPendingBatchesCount();
        $recentActivities = $this->model->getRecentActivities();
        $rawTrends = $this->model->getTrendData();

        // Xử lý Logic an toàn (bảo vệ khi Database rỗng)
        $passRate = ($stats && $stats['pass_rate'] !== null) ? number_format($stats['pass_rate'], 1) : '0.0';
        $defectRatio = ($stats && $stats['defect_ratio'] !== null) ? number_format($stats['defect_ratio'], 1) : '0.0';

        $trendLabels = [];
        $trendPass = [];
        $trendFail = [];

        if (!empty($rawTrends)) {
            $rawTrends = array_reverse($rawTrends);
            foreach ($rawTrends as $r) {
                $usable = floatval($r['sum_usable']);
                $fail = floatval($r['sum_fail']);
                $total = $usable + $fail;

                $trendLabels[] = date('M d', strtotime($r['shift_date']));
                $trendPass[] = $total > 0 ? round(($usable / $total) * 100, 1) : 0;
                $trendFail[] = $total > 0 ? round(($fail / $total) * 100, 1) : 0;
            }
        }

        // Trả về một mảng chứa dữ liệu đã làm sạch
        return [
            'passRate' => $passRate,
            'defectRatio' => $defectRatio,
            'pendingCount' => $pendingCount ?: 0,
            'recentActivities' => $recentActivities ?: [],
            'trendLabels' => $trendLabels,
            'trendPass' => $trendPass,
            'trendFail' => $trendFail
        ];
    }

    public function handleListQueue() {
        $queue = $this->model->getPendingQueue();
        
        $activeQueueCount = count($queue);
        $highPriorityCount = 0;
        foreach ($queue as $item) {
            if (strtolower($item['BCH_priority']) === 'high') {
                $highPriorityCount++;
            }
        }

        $kpis = $this->model->getInspectionKPIs();

        return [
            'queue' => $queue,
            'activeCount' => $activeQueueCount,
            'highCount' => $highPriorityCount,
            'processedCount' => $kpis['processedCount'],
            'avgLeadTimeMins' => $kpis['avgLeadTimeMins']
        ];
    }
}
?>