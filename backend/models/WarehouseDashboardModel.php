<?php
// backend/models/WarehouseDashboardModel.php

require_once __DIR__ . '/../core/BaseModel.php';

class WarehouseDashboardModel extends BaseModel {

    public function getTotalStockKg() {
        $stmt = $this->pdo->query("SELECT SUM(BCH_initial_volume_kg) as total_kg FROM BATCHES");
        return $stmt->fetchColumn() ?? 0;
    }

    public function getIncomingTodayCount() {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM BATCHES WHERE DATE(BCH_received_date) = CURDATE()");
        return $stmt->fetchColumn() ?? 0;
    }

    public function getPendingValidationCount() {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM BATCHES WHERE BCH_current_stage = 'Pending_QC'");
        return $stmt->fetchColumn() ?? 0;
    }

    public function getWarehouseCapacity() {
        $stmt = $this->pdo->query("SELECT SUM(STZ_current_load_kg) as cur_load, SUM(STZ_max_capacity_kg) as max_cap FROM STORAGE_ZONES");
        return $stmt->fetch();
    }

    public function getRecentMovements($lang = 'vi', $limit = 5) {
        $productNameCol = ($lang === 'en') ? 'COALESCE(p.PRD_product_name_en, p.PRD_product_name)' : 'p.PRD_product_name';
        $sql = "SELECT s.STM_reference_code, s.STM_quantity_kg, s.STM_movement_type, s.STM_timestamp, b.BCH_batch_id, $productNameCol AS PRD_product_name
                FROM STOCK_MOVEMENTS s
                JOIN BATCHES b ON s.STM_batch_id = b.BCH_batch_id
                LEFT JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
                ORDER BY s.STM_timestamp DESC LIMIT " . (int)$limit;
        return $this->pdo->query($sql)->fetchAll();
    }

    public function getNodeStatus($zoneId = 1) {
        $stmt = $this->pdo->prepare("SELECT STZ_current_temp_c, STZ_current_humidity_pct FROM STORAGE_ZONES WHERE STZ_zone_id = :zone_id");
        $stmt->execute([':zone_id' => $zoneId]);
        return $stmt->fetch();
    }
}
?>
