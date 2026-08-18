<?php
// backend/models/NotificationModel.php

require_once __DIR__ . '/../core/BaseModel.php';
require_once __DIR__ . '/../includes/i18n.php';

class NotificationModel extends BaseModel {
    private $lang;

    public function __construct($lang = null) {
        parent::__construct();
        $this->lang = $lang ?? $_SESSION['lang'] ?? 'vi';
    }

    public function setLang($lang) {
        $this->lang = $lang;
    }
    
    // 1. Warehouse Alerts: Low stock, Out of stock, QC Results
    private function getWarehouseAlerts() {
        $alerts = [];
        $lang = $this->lang;
        $productNameCol = ($lang === 'en') ? 'COALESCE(p.PRD_product_name_en, p.PRD_product_name)' : 'p.PRD_product_name';
        
        // QC inspect passed or rejected
        $sqlQC = "SELECT b.BCH_batch_id, $productNameCol AS PRD_product_name, b.BCH_current_stage, b.BCH_received_date 
                  FROM BATCHES b
                  JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
                  WHERE b.BCH_current_stage IN ('QC_Passed', 'Rejected')
                  ORDER BY b.BCH_received_date DESC
                  LIMIT 4";
        $stmtQC = $this->pdo->query($sqlQC);
        $qcFinishedBatches = $stmtQC->fetchAll(PDO::FETCH_ASSOC);

        foreach ($qcFinishedBatches as $batch) {
            $pName = t_product($batch['PRD_product_name']);
            if ($batch['BCH_current_stage'] === 'QC_Passed') {
                $alerts[] = [
                    'id' => 'qcpass_' . $batch['BCH_batch_id'],
                    'type' => 'qc_passed',
                    'icon' => '<svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                    'title' => ($lang === 'en') ? 'QC Passed' : 'QC Đạt chuẩn',
                    'message' => ($lang === 'en')
                        ? "Batch " . htmlspecialchars($batch['BCH_batch_id']) . " (" . htmlspecialchars($pName) . ") has passed inspection."
                        : "Lô " . htmlspecialchars($batch['BCH_batch_id']) . " (" . htmlspecialchars($pName) . ") đã qua kiểm định.",
                    'time_desc' => ($lang === 'en') ? 'Ready for stock' : 'Đã sẵn sàng',
                    'timestamp' => $batch['BCH_received_date'],
                    'link' => 'inventory.php'
                ];
            } else {
                $alerts[] = [
                    'id' => 'qcfail_' . $batch['BCH_batch_id'],
                    'type' => 'qc_failed',
                    'icon' => '<svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                    'title' => ($lang === 'en') ? 'QC Failed' : 'QC Không đạt',
                    'message' => ($lang === 'en')
                        ? "Batch " . htmlspecialchars($batch['BCH_batch_id']) . " (" . htmlspecialchars($pName) . ") failed inspection standard."
                        : "Lô " . htmlspecialchars($batch['BCH_batch_id']) . " (" . htmlspecialchars($pName) . ") không đạt chuẩn kiểm định.",
                    'time_desc' => ($lang === 'en') ? 'Rejected' : 'Bị từ chối',
                    'timestamp' => $batch['BCH_received_date'],
                    'link' => 'qc_reports.php'
                ];
            }
        }

        // Low stock and Out of stock
        $sql = "SELECT b.BCH_batch_id, $productNameCol AS PRD_product_name, b.BCH_available_stock_kg, b.BCH_received_date,
                       (SELECT MAX(STM_timestamp) FROM STOCK_MOVEMENTS WHERE STM_batch_id = b.BCH_batch_id) as last_movement
                FROM BATCHES b
                JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
                WHERE b.BCH_available_stock_kg < 100
                ORDER BY b.BCH_available_stock_kg ASC
                LIMIT 5";
        $stmt = $this->pdo->query($sql);
        $lowStockBatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($lowStockBatches as $batch) {
            $pName = t_product($batch['PRD_product_name']);
            if ($batch['BCH_available_stock_kg'] <= 0) {
                $alerts[] = [
                    'id' => 'oos_' . $batch['BCH_batch_id'],
                    'type' => 'out_of_stock',
                    'icon' => '<svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>',
                    'title' => ($lang === 'en') ? 'Out of Stock' : 'Hết hàng tồn kho',
                    'message' => ($lang === 'en')
                        ? "Batch " . htmlspecialchars($batch['BCH_batch_id']) . " (" . htmlspecialchars($pName) . ") is completely out of stock."
                        : "Lô " . htmlspecialchars($batch['BCH_batch_id']) . " (" . htmlspecialchars($pName) . ") đã hết hàng tồn kho.",
                    'time_desc' => ($lang === 'en') ? 'Action required' : 'Cần xử lý',
                    'timestamp' => $batch['last_movement'] ?? $batch['BCH_received_date'],
                    'link' => 'inventory.php'
                ];
            } else {
                $alerts[] = [
                    'id' => 'low_' . $batch['BCH_batch_id'],
                    'type' => 'low_stock',
                    'icon' => '<svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>',
                    'title' => ($lang === 'en') ? 'Low Stock Alert' : 'Cảnh báo tồn kho thấp',
                    'message' => ($lang === 'en')
                        ? "Batch " . htmlspecialchars($batch['BCH_batch_id']) . " (" . htmlspecialchars($pName) . ") has only " . number_format((float)$batch['BCH_available_stock_kg'], 2) . " kg left."
                        : "Lô " . htmlspecialchars($batch['BCH_batch_id']) . " (" . htmlspecialchars($pName) . ") chỉ còn " . number_format((float)$batch['BCH_available_stock_kg'], 2) . " kg.",
                    'time_desc' => ($lang === 'en') ? 'Check inventory' : 'Kiểm tra kho',
                    'timestamp' => $batch['last_movement'] ?? $batch['BCH_received_date'],
                    'link' => 'inventory.php'
                ];
            }
        }

        return $alerts;
    }

    // 2. Production Alerts: FEFO (Expiring within 7 days)
    private function getProductionAlerts() {
        $alerts = [];
        $lang = $this->lang;
        $productNameCol = ($lang === 'en') ? 'COALESCE(p.PRD_product_name_en, p.PRD_product_name)' : 'p.PRD_product_name';
        
        $sql = "SELECT b.BCH_batch_id, $productNameCol AS PRD_product_name, b.BCH_received_date, b.BCH_expiry_date, DATEDIFF(b.BCH_expiry_date, CURDATE()) as days_left
                FROM BATCHES b
                JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
                WHERE b.BCH_expiry_date IS NOT NULL 
                  AND b.BCH_available_stock_kg > 0
                  AND DATEDIFF(b.BCH_expiry_date, CURDATE()) <= 7
                ORDER BY b.BCH_expiry_date ASC
                LIMIT 5";
        $stmt = $this->pdo->query($sql);
        $expiringBatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($expiringBatches as $batch) {
            $daysLeft = (int)$batch['days_left'];
            $pName = t_product($batch['PRD_product_name']);
            $alerts[] = [
                'id' => 'fefo_' . $batch['BCH_batch_id'],
                'type' => 'fefo',
                'icon' => '<svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                'title' => ($lang === 'en') ? 'FEFO Warning' : 'Cảnh báo FEFO',
                'message' => ($lang === 'en')
                    ? "Batch " . htmlspecialchars($batch['BCH_batch_id']) . " (" . htmlspecialchars($pName) . ") expires in " . $daysLeft . " days."
                    : "Lô " . htmlspecialchars($batch['BCH_batch_id']) . " (" . htmlspecialchars($pName) . ") sẽ hết hạn trong " . $daysLeft . " ngày tới.",
                'time_desc' => ($lang === 'en') ? 'Expiring soon' : 'Sắp hết hạn',
                'timestamp' => $batch['BCH_received_date'],
                'link' => 'production_FEFO.php'
            ];
        }
        return $alerts;
    }

    // 3. QC Alerts: Batches in Quarantine
    private function getQCAlerts() {
        $alerts = [];
        $lang = $this->lang;
        $productNameCol = ($lang === 'en') ? 'COALESCE(p.PRD_product_name_en, p.PRD_product_name)' : 'p.PRD_product_name';
        
        $sql = "SELECT b.BCH_batch_id, $productNameCol AS PRD_product_name, b.BCH_received_date, DATEDIFF(CURDATE(), b.BCH_received_date) as days_waiting
                FROM BATCHES b
                JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
                WHERE b.BCH_current_stage = 'Pending_QC'
                ORDER BY b.BCH_received_date ASC
                LIMIT 5";
        $stmt = $this->pdo->query($sql);
        $pendingBatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($pendingBatches as $batch) {
            $daysWaiting = (int)$batch['days_waiting'];
            $pName = t_product($batch['PRD_product_name']);
            $alerts[] = [
                'id' => 'qc_' . $batch['BCH_batch_id'],
                'type' => 'qc',
                'icon' => '<svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                'title' => ($lang === 'en') ? 'New Batch for QC' : 'Lô hàng mới chờ QC',
                'message' => ($lang === 'en')
                    ? "Batch " . htmlspecialchars($batch['BCH_batch_id']) . " (" . htmlspecialchars($pName) . ") is pending inspection (" . $daysWaiting . " days)."
                    : "Lô " . htmlspecialchars($batch['BCH_batch_id']) . " (" . htmlspecialchars($pName) . ") đang chờ kiểm định (" . $daysWaiting . " ngày).",
                'time_desc' => ($lang === 'en') ? 'Action required' : 'Cần xử lý',
                'timestamp' => $batch['BCH_received_date'],
                'link' => 'qc_inspections.php'
            ];
        }
        return $alerts;
    }

    // 4. Material Request Alerts (For PM & Warehouse)
    private function getMaterialRequestAlerts() {
        $alerts = [];
        $lang = $this->lang;
        $productNameCol = ($lang === 'en') ? 'COALESCE(p.PRD_product_name_en, p.PRD_product_name)' : 'p.PRD_product_name';
        
        $sql = "SELECT r.REQ_id, r.REQ_material_id, r.REQ_quantity, r.REQ_status, r.created_at,
                       $productNameCol AS PRD_product_name
                FROM MATERIAL_REQUESTS r
                LEFT JOIN PRODUCTS p ON r.REQ_material_id = p.PRD_product_id
                ORDER BY r.created_at DESC
                LIMIT 4";
        $stmt = $this->pdo->query($sql);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($requests as $req) {
            $pName = !empty($req['PRD_product_name']) ? t_product($req['PRD_product_name']) : ('Material #' . $req['REQ_material_id']);
            $statusRaw = $req['REQ_status'];
            
            if ($lang === 'en') {
                $statusText = ($statusRaw === 'Pending') ? 'Pending' : (($statusRaw === 'Approved') ? 'Approved' : 'Rejected');
                $title = 'Material Request';
                $message = "Request " . number_format((float)$req['REQ_quantity'], 2) . " kg of " . htmlspecialchars($pName) . " (" . $statusText . ").";
                $timeDesc = 'Recent request';
            } else {
                $statusText = ($statusRaw === 'Pending') ? 'Chờ duyệt' : (($statusRaw === 'Approved') ? 'Đã duyệt' : 'Bị từ chối');
                $title = 'Yêu cầu Nguyên vật liệu';
                $message = "Yêu cầu " . number_format((float)$req['REQ_quantity'], 2) . " kg " . htmlspecialchars($pName) . " (" . $statusText . ").";
                $timeDesc = 'Yêu cầu gần đây';
            }
            
            $alerts[] = [
                'id' => 'matreq_' . $req['REQ_id'],
                'type' => 'material_request',
                'icon' => '<svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>',
                'title' => $title,
                'message' => $message,
                'time_desc' => $timeDesc,
                'timestamp' => $req['created_at'],
                'link' => 'manage_requests.php' // Link to where PM manages requests
            ];
        }
        return $alerts;
    }

    public function getAlertsByRole($role) {
        $alerts = [];
        
        if ($role === 'QC') {
            $alerts = array_merge($alerts, $this->getQCAlerts());
        } elseif ($role === 'Production_Manager') {
            // PM receives ALL notifications (QC, Warehouse, Production, Material Requests)
            $alerts = array_merge(
                $alerts, 
                $this->getProductionAlerts(),
                $this->getQCAlerts(),
                $this->getWarehouseAlerts(),
                $this->getMaterialRequestAlerts()
            );
        } elseif ($role === 'Warehouse_Staff') {
            $alerts = array_merge($alerts, $this->getWarehouseAlerts(), $this->getMaterialRequestAlerts());
        } elseif ($role === 'Director') {
            // Director sees everything
            $alerts = array_merge($alerts, $this->getWarehouseAlerts(), $this->getProductionAlerts(), $this->getQCAlerts(), $this->getMaterialRequestAlerts());
        } else {
            // Default to warehouse just in case
            $alerts = array_merge($alerts, $this->getWarehouseAlerts());
        }
        
        // Sắp xếp lại toàn bộ mảng alerts dựa trên trường timestamp (mới nhất đẩy lên đầu)
        usort($alerts, function($a, $b) {
            $timeA = strtotime($a['timestamp'] ?? '1970-01-01');
            $timeB = strtotime($b['timestamp'] ?? '1970-01-01');
            return $timeB <=> $timeA;
        });
        
        return $alerts;
    }
}
?>
