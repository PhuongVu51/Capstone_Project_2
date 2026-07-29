<?php
// backend/controllers/InventoryController.php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/StockModel.php';

class InventoryController {
    private $stockModel;

    public function __construct() {
        $this->stockModel = new StockModel();
    }

    public function getInventoryData() {
        $userRole = $_SESSION['role'] ?? 'Warehouse_Staff';
        $lang = $_SESSION['lang'] ?? 'vi';
        
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = 10;
        
        $messages = [];
        if (isset($_GET['success'])) {
            if ($_GET['success'] === 'delete_ok') $messages[] = 'Batch deleted successfully.';
        }
        if (isset($_GET['error'])) {
            if ($_GET['error'] === 'delete_failed') $messages[] = 'Failed to delete batch.';
            if ($_GET['error'] === 'missing_batch_id') $messages[] = 'Batch ID is required.';
        }

        $totalRecords = $this->stockModel->getInventoryCount($search, $statusFilter);
        $totalPages = max(1, (int) ceil($totalRecords / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $inventoryItems = $this->stockModel->getInventoryList($search, $statusFilter, $offset, $perPage);

        $selectedBatch = null;
        if (isset($_GET['view_id']) && trim($_GET['view_id']) !== '') {
            $viewId = trim($_GET['view_id']);
            $selectedBatch = $this->stockModel->getBatchDetails($viewId, $lang);

            if (!$selectedBatch) {
                $selectedBatch = [
                    'BCH_batch_id' => $viewId,
                    'PRD_product_name' => 'Batch Item (' . $viewId . ')',
                    'STZ_zone_name' => 'Main Storage Zone',
                    'BCH_available_stock_kg' => 0.00,
                    'BCH_initial_volume_kg' => 0.00,
                    'BCH_current_stage' => 'Active Stock',
                    'BCH_received_date' => date('Y-m-d'),
                    'BCH_expiry_date' => date('Y-m-d', strtotime('+30 days')),
                    'SUP_supplier_name' => 'Standard Supplier'
                ];
            }
        }

        return [
            'userRole' => $userRole,
            'lang' => $lang,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'page' => $page,
            'perPage' => $perPage,
            'offset' => $offset,
            'messages' => $messages,
            'totalRecords' => $totalRecords,
            'totalPages' => $totalPages,
            'inventoryItems' => $inventoryItems,
            'selectedBatch' => $selectedBatch
        ];
    }
}
?>
