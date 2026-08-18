<?php
// Đường dẫn: backend/controllers/MaterialRequestController.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/MaterialRequestModel.php';

// Cấp quyền cho Warehouse, Director và PM (PM chỉ được xem)
require_role(['Warehouse_Staff', 'Director', 'Production_Manager'], '../../frontend/login.php');

class MaterialRequestController {
    private $reqModel;

    public function __construct() {
        $this->reqModel = new MaterialRequestModel();
    }

    // Trả data ra cho giao diện hiển thị
    public function getRequestsData() {
        $requests = $this->reqModel->getAllRequests();
        return ['requests' => $requests];
    }

    // Xử lý khi Form Approve/Reject gửi lên
    public function handleAction() {
        // Ngăn PM tự ý duyệt/từ chối qua API
        if ($_SESSION['role'] === 'Production_Manager') {
            header("Location: ../../frontend/403.php");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'], $_POST['request_id'])) {
            $requestId = (int)$_POST['request_id'];
            $action = $_POST['action_type']; // Nhận 'Approve' hoặc 'Reject'

            if ($action === 'Approve') {
                $this->reqModel->updateRequestStatus($requestId, 'Approved');
                // Tính năng nâng cao (Tùy chọn): Ở đây có thể gọi code tự động xuất kho bảng BATCHES nếu muốn. 
                // Hiện tại ta chỉ đổi status Request cho chuẩn luồng form.
                $_SESSION['success_msg'] = "Request #$requestId has been Approved.";
            } elseif ($action === 'Reject') {
                $this->reqModel->updateRequestStatus($requestId, 'Rejected');
                $_SESSION['error_msg'] = "Request #$requestId has been Rejected.";
            }
            
            header("Location: ../../frontend/manage_requests.php");
            exit;
        }
    }
}

// Lắng nghe tín hiệu POST từ giao diện
if (isset($_GET['action']) && $_GET['action'] === 'process') {
    $controller = new MaterialRequestController();
    $controller->handleAction();
}
?>
