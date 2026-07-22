<?php
// Đường dẫn: backend/controllers/QcInspectionController.php
require_once __DIR__ . '/../models/QcInspectionModel.php';

class QcInspectionController {
    private $model;

    public function __construct() {
        $this->model = new QcInspectionModel();
    }

    // Xử lý nạp danh sách hàng chờ kiểm định
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
        $lastSyncTime = time();

        return [
            'queue' => $queue,
            'activeCount' => $activeQueueCount,
            'highCount' => $highPriorityCount,
            'processedCount' => $kpis['processedCount'],
            'totalBatches' => $kpis['totalBatches'],
            'avgLeadTimeMins' => $kpis['avgLeadTimeMins'],
            'leadTimePct' => $kpis['leadTimePct'], 
            'isDecrease' => $kpis['isDecrease'],
            'lastSyncTime' => $lastSyncTime
        ];
    }

    // Xử lý chuẩn bị form thực thi ca kiểm định
    public function handlePerformScreen($batch_id) {
        $batch = $this->model->getBatchForInspection($batch_id);
        if (!$batch) {
            header("Location: qc_inspections.php?error=batch_not_found");
            exit();
        }
        return $batch;
    }

    // Tiếp nhận POST dữ liệu kiểm định gửi lên
    public function postInspection() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $batch_id = trim($_POST['batch_id'] ?? '');
            $rejected_qty = floatval($_POST['rejected_qty'] ?? 0);
            $reason = $_POST['rejection_reason'] ?? 'None';
            $comments = trim($_POST['inspector_comments'] ?? '');
            $user_id = $_SESSION['user_id'] ?? 4; 

            $success = $this->model->submitInspection($batch_id, $rejected_qty, $reason, $comments, $user_id);
            if ($success) {
                header("Location: ../../frontend/qc_inspection_success.php?batch_id=" . urlencode($batch_id) . "&rejected=" . urlencode($rejected_qty));
                exit();
            }
        }
    }
}

// Router lắng nghe tác vụ POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'submit') {
    session_start();
    $router = new QcInspectionController();
    $router->postInspection();
}
?>