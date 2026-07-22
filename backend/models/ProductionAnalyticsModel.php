<?php

require_once __DIR__ . '/../core/BaseModel.php';

class ProductionAnalyticsModel extends BaseModel
{
    public function getTotalOutput()
    {
        $stmt = $this->pdo->query("
            SELECT IFNULL(SUM(FGD_total_cans),0)
            FROM FINISHED_GOODS
        ");

        return (int)$stmt->fetchColumn();
    }

    public function getAverageYield()
    {
        $stmt = $this->pdo->query("
            SELECT IFNULL(AVG(FGD_actual_yield_rate),0)
            FROM FINISHED_GOODS
        ");

        return round((float)$stmt->fetchColumn(),1);
    }

    public function getProductionBatches()
    {
        $stmt = $this->pdo->query("
            SELECT COUNT(*)
            FROM FINISHED_GOODS
        ");

        return (int)$stmt->fetchColumn();
    }

    public function getQuarantineCount()
    {
        $stmt = $this->pdo->query("
            SELECT COUNT(*)
            FROM FINISHED_GOODS
            WHERE FGD_status='Quarantine'
        ");

        return (int)$stmt->fetchColumn();
    }

    public function getProductionChart()
    {
        $stmt = $this->pdo->query("
            SELECT
                FGD_batch_id,
                FGD_total_cans
            FROM FINISHED_GOODS
            ORDER BY FGD_fg_id DESC
            LIMIT 10
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductionLog()
    {
        $stmt = $this->pdo->query("
            SELECT
                f.FGD_batch_id,
                p.PRD_product_name,
                f.FGD_total_cans,
                f.FGD_actual_yield_rate,
                f.FGD_status
            FROM FINISHED_GOODS f
            JOIN BATCHES b
                ON f.FGD_batch_id=b.BCH_batch_id
            JOIN PRODUCTS p
                ON b.BCH_product_id=p.PRD_product_id
            ORDER BY f.FGD_produced_date DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}