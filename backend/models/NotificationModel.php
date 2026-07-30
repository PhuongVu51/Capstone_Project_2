<?php
// backend/models/NotificationModel.php

require_once __DIR__ . '/../core/BaseModel.php';

class NotificationModel extends BaseModel {
    
    // 1. Warehouse Alerts: Low stock, Out of stock
    private function getWarehouseAlerts() {
        $alerts = [];
        $lang = $_SESSION['lang'] ?? 'vi';
        
        $sql = "SELECT b.BCH_batch_id, p.PRD_product_name, b.BCH_available_stock_kg
                FROM BATCHES b
                JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
                WHERE b.BCH_available_stock_kg < 100
                ORDER BY b.BCH_available_stock_kg ASC
                LIMIT 5";
        $stmt = $this->pdo->query($sql);
        $lowStockBatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($lowStockBatches as $batch) {
            if ($batch['BCH_available_stock_kg'] <= 0) {
                $alerts[] = [
                    'id' => 'oos_' . $batch['BCH_batch_id'],
                    'type' => 'out_of_stock',
                    'icon' => '<svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>',
                    'title' => ($lang === 'en') ? 'Out of Stock' : 'Hết hàng tồn kho',
                    'message' => "Batch " . htmlspecialchars($batch['BCH_batch_id']) . " (" . htmlspecialchars($batch['PRD_product_name']) . ")",
                    'time_desc' => ($lang === 'en') ? 'Action required' : 'Cần xử lý',
                    'link' => 'inventory.php'
                ];
            } else {
                $alerts[] = [
                    'id' => 'low_' . $batch['BCH_batch_id'],
                    'type' => 'low_stock',
                    'icon' => '<svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>',
                    'title' => ($lang === 'en') ? 'Low Stock Alert' : 'Cảnh báo tồn kho thấp',
                    'message' => "Batch " . htmlspecialchars($batch['BCH_batch_id']) . " has only " . (float)$batch['BCH_available_stock_kg'] . " kg left.",
                    'time_desc' => ($lang === 'en') ? 'Check inventory' : 'Kiểm tra kho',
                    'link' => 'inventory.php'
                ];
            }
        }

        // Thông báo khi QC inspect thành công cho bên Warehouse
        $sqlQC = "SELECT b.BCH_batch_id, p.PRD_product_name 
                  FROM BATCHES b
                  JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
                  JOIN QC_INSPECTIONS q ON b.BCH_batch_id = q.QCI_batch_id
                  WHERE b.BCH_current_stage = 'QC_Passed'
                  ORDER BY q.QCI_inspection_id DESC
                  LIMIT 3"; // Limit to top 3 recent to avoid clutter
        $stmtQC = $this->pdo->query($sqlQC);
        $qcPassedBatches = $stmtQC->fetchAll(PDO::FETCH_ASSOC);

        foreach ($qcPassedBatches as $batch) {
            $alerts[] = [
                'id' => 'qcpass_' . $batch['BCH_batch_id'],
                'type' => 'qc_passed',
                'icon' => '<svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                'title' => ($lang === 'en') ? 'QC Passed' : 'QC Thành công',
                'message' => "Lô " . htmlspecialchars($batch['BCH_batch_id']) . " (" . htmlspecialchars($batch['PRD_product_name']) . ") đã qua kiểm định.",
                'time_desc' => ($lang === 'en') ? 'Ready for stock' : 'Đã sẵn sàng',
                'link' => 'inventory.php'
            ];
        }

        return $alerts;
    }

    // 2. Production Alerts: FEFO (Expiring within 7 days)
    private function getProductionAlerts() {
        $alerts = [];
        $lang = $_SESSION['lang'] ?? 'vi';
        
        $sql = "SELECT b.BCH_batch_id, p.PRD_product_name, b.BCH_expiry_date, DATEDIFF(b.BCH_expiry_date, CURDATE()) as days_left
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
            $alerts[] = [
                'id' => 'fefo_' . $batch['BCH_batch_id'],
                'type' => 'fefo',
                'icon' => '<svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                'title' => ($lang === 'en') ? 'FEFO Warning' : 'Cảnh báo FEFO',
                'message' => "Batch " . htmlspecialchars($batch['BCH_batch_id']) . " expires in " . $daysLeft . " days.",
                'time_desc' => ($lang === 'en') ? 'Expiring soon' : 'Sắp hết hạn',
                'link' => 'production_FEFO.php'
            ];
        }
        return $alerts;
    }

    // 3. QC Alerts: Batches in Quarantine
    private function getQCAlerts() {
        $alerts = [];
        $lang = $_SESSION['lang'] ?? 'vi';
        
        $sql = "SELECT b.BCH_batch_id, p.PRD_product_name, DATEDIFF(CURDATE(), b.BCH_received_date) as days_waiting
                FROM BATCHES b
                JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
                WHERE b.BCH_current_stage = 'Pending_QC'
                ORDER BY b.BCH_received_date ASC
                LIMIT 5";
        $stmt = $this->pdo->query($sql);
        $pendingBatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($pendingBatches as $batch) {
            $daysWaiting = (int)$batch['days_waiting'];
            $alerts[] = [
                'id' => 'qc_' . $batch['BCH_batch_id'],
                'type' => 'qc',
                'icon' => '<svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                'title' => ($lang === 'en') ? 'New Batch for QC' : 'Hàng mới cần QC check',
                'message' => "Lô " . htmlspecialchars($batch['BCH_batch_id']) . " đang chờ kiểm định (" . $daysWaiting . " ngày).",
                'time_desc' => ($lang === 'en') ? 'Action required' : 'Cần xử lý ngay',
                'link' => 'qc_inspections.php'
            ];
        }
        return $alerts;
    }

    public function getAlertsByRole($role) {
        $alerts = [];
        
        if ($role === 'QC') {
            $alerts = array_merge($alerts, $this->getQCAlerts());
        } elseif ($role === 'Production_Manager') {
            $alerts = array_merge($alerts, $this->getProductionAlerts());
        } elseif ($role === 'Warehouse_Staff') {
            $alerts = array_merge($alerts, $this->getWarehouseAlerts());
        } elseif ($role === 'Director') {
            // Director sees everything
            $alerts = array_merge($alerts, $this->getWarehouseAlerts(), $this->getProductionAlerts(), $this->getQCAlerts());
        } else {
            // Default to warehouse just in case
            $alerts = array_merge($alerts, $this->getWarehouseAlerts());
        }
        
        return $alerts;
    }
}
?>
