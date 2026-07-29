<?php

require_once __DIR__ . '/../core/BaseModel.php';

class WarehouseReportModel extends BaseModel
{
    public function getTotalStock()
    {
        $stmt = $this->pdo->query("
            SELECT IFNULL(SUM(BCH_available_stock_kg),0)
            FROM BATCHES
        ");

        return (float)$stmt->fetchColumn();
    }

    public function getTotalBatches()
    {
        $stmt = $this->pdo->query("
            SELECT COUNT(*)
            FROM BATCHES
        ");

        return (int)$stmt->fetchColumn();
    }

    public function getExpiringBatches()
    {
        $stmt = $this->pdo->query("
            SELECT COUNT(*)
            FROM BATCHES
            WHERE BCH_expiry_date <= DATE_ADD(NOW(), INTERVAL 7 DAY)
            AND BCH_available_stock_kg > 0
        ");

        return (int)$stmt->fetchColumn();
    }

    public function getTotalOutbound()
    {
        $stmt = $this->pdo->query("
            SELECT IFNULL(SUM(STM_quantity_kg),0)
            FROM STOCK_MOVEMENTS
            WHERE STM_movement_type='OUT'
        ");

        return (float)$stmt->fetchColumn();
    }

    public function getStockByProduct($lang = 'vi')
    {
        $productNameCol = ($lang === 'en') ? 'COALESCE(p.PRD_product_name_en, p.PRD_product_name)' : 'p.PRD_product_name';
        $stmt = $this->pdo->query("
            SELECT
                $productNameCol AS PRD_product_name,
                SUM(b.BCH_available_stock_kg) AS stock_kg
            FROM BATCHES b
            JOIN PRODUCTS p
                ON b.BCH_product_id = p.PRD_product_id
            GROUP BY p.PRD_product_id
            ORDER BY stock_kg DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCriticalBatches($lang = 'vi')
    {
        $productNameCol = ($lang === 'en') ? 'COALESCE(p.PRD_product_name_en, p.PRD_product_name)' : 'p.PRD_product_name';
        $supplierNameCol = ($lang === 'en') ? 'COALESCE(s.SUP_supplier_name_en, s.SUP_supplier_name)' : 's.SUP_supplier_name';
        $sql = "SELECT 
                b.BCH_batch_id,
                $productNameCol AS PRD_product_name,
                $supplierNameCol AS SUP_supplier_name,
                b.BCH_available_stock_kg,
                b.BCH_expiry_date,
                b.BCH_health_status
            FROM BATCHES b
            JOIN PRODUCTS p
                ON b.BCH_product_id = p.PRD_product_id
            JOIN SUPPLIERS s
                ON b.BCH_supplier_id = s.SUP_supplier_id
            WHERE b.BCH_health_status IN ('Warning','Critical')
            ORDER BY b.BCH_expiry_date ASC
        ";
        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}