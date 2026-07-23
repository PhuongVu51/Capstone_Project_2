<?php
// backend/controllers/StockController.php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/StockModel.php';

// Chỉ cho phép Warehouse_Staff truy cập
require_role(['Warehouse_Staff']);

class StockController {
    private $stockModel;

    public function __construct() {
        $this->stockModel = new StockModel();
    }

    // Xử lý yêu cầu nhập kho mới
    public function handleStockIn() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Nhận và kiểm tra dữ liệu đầu vào (Validation)
            $batchId = trim($_POST['batch_id'] ?? '');
            $productId = $_POST['product_id'] ?? 0;
            $supplierId = $_POST['supplier_id'] ?? 0;
            $shiftId = $_POST['shift_id'] ?? 0;
            $zoneId = $_POST['zone_id'] ?? 0;
            $receivedDate = $_POST['received_date'] ?? date('Y-m-d H:i:s');
            $expiryDate = $_POST['expiry_date'] ?? '';
            $initialVolume = $_POST['initial_volume'] ?? 0.0;
            
            // Lấy ID người dùng hiện tại
            $userId = $_SESSION['user_id'];

            // Nếu người dùng không nhập Batch ID, tự động sinh mã
            if (empty($batchId)) {
                $batchId = 'BATCH_' . date('Ymd_His');
            }

            if (empty($productId) || empty($initialVolume) || empty($zoneId)) {
                // Trở lại trang với thông báo lỗi
                header("Location: ../../frontend/log_batch.php?error=missing_fields");
                exit();
            }

            // Gọi Model để insert và cập nhật tồn kho an toàn
            $success = $this->stockModel->stockIn($batchId, $productId, $supplierId, $shiftId, $zoneId, $receivedDate, $expiryDate, $initialVolume, $userId);

            if ($success) {
                header("Location: ../../frontend/dashboard_warehouse.php?success=stock_in_ok");
                exit();
            } else {
                header("Location: ../../frontend/log_batch.php?error=db_error");
                exit();
            }
        }
    }
    // Xử lý yêu cầu xuất kho
    public function handleStockOut() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $batchId = trim($_POST['batch_id'] ?? '');
            $outVolume = $_POST['out_volume'] ?? 0.0;
            $userId = $_SESSION['user_id'];

            if (empty($batchId) || empty($outVolume) || $outVolume <= 0) {
                header("Location: ../../frontend/dashboard_warehouse.php?error=invalid_out_data");
                exit();
            }

            $success = $this->stockModel->stockOut($batchId, $outVolume, $userId);

            if ($success) {
                header("Location: ../../frontend/dashboard_warehouse.php?success=stock_out_ok");
                exit();
            } else {
                header("Location: ../../frontend/dashboard_warehouse.php?error=stock_out_failed");
                exit();
            }
        }
    }

    // Xử lý yêu cầu sửa/cập nhật thông tin lô hàng
    public function handleFetchSuppliers() {
        $productId = $_GET['product_id'] ?? $_POST['product_id'] ?? 0;
        $productId = intval($productId);
        if ($productId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'product_id_required']);
            exit();
        }

        $suppliers = $this->stockModel->getSuppliersByProduct($productId);
        header('Content-Type: application/json');
        echo json_encode($suppliers);
        exit();
    }

    public function handleUpdateBatch() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $batchId = trim($_POST['batch_id'] ?? '');
            $newZoneId = $_POST['new_zone_id'] ?? 0;
            $newExpiryDate = $_POST['new_expiry_date'] ?? '';
            $userId = $_SESSION['user_id'];

            if (empty($batchId) || empty($newZoneId) || empty($newExpiryDate)) {
                header("Location: ../../frontend/dashboard_warehouse.php?error=invalid_update_data");
                exit();
            }

            $success = $this->stockModel->updateBatch($batchId, $newZoneId, $newExpiryDate, $userId);

            if ($success) {
                header("Location: ../../frontend/dashboard_warehouse.php?success=update_ok");
                exit();
            } else {
                header("Location: ../../frontend/dashboard_warehouse.php?error=update_failed");
                exit();
            }
        }
    }

    public function handleDeleteBatch() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $batchId = trim($_POST['batch_id'] ?? '');
            if (empty($batchId)) {
                header("Location: ../../frontend/inventory.php?error=missing_batch_id");
                exit();
            }
            $success = $this->stockModel->deleteBatch($batchId);
            if ($success) {
                header("Location: ../../frontend/inventory.php?success=delete_ok");
                exit();
            } else {
                header("Location: ../../frontend/inventory.php?error=delete_failed");
                exit();
            }
        }
    }
}

// Xử lý định tuyến cơ bản (Router)
if (isset($_GET['action'])) {
    $controller = new StockController();
    if ($_GET['action'] === 'stock_in') {
        $controller->handleStockIn();
    } elseif ($_GET['action'] === 'stock_out') {
        $controller->handleStockOut();
    } elseif ($_GET['action'] === 'fetch_suppliers') {
        $controller->handleFetchSuppliers();
    } elseif ($_GET['action'] === 'update') {
        $controller->handleUpdateBatch();
    } elseif ($_GET['action'] === 'delete_batch') {
        $controller->handleDeleteBatch();
    }
}
?>
