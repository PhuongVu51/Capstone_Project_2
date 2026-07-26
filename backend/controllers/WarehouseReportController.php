<?php

require_once __DIR__ . '/../models/WarehouseReportModel.php';

class WarehouseReportController
{
    private $model;

    public function __construct()
    {
        $this->model = new WarehouseReportModel();
    }

    public function loadReportData($lang = 'vi')
    {
        $totalStock = $this->model->getTotalStock();

        $totalBatches = $this->model->getTotalBatches();

        $expiringCount = $this->model->getExpiringBatches();

        $totalOutbound = $this->model->getTotalOutbound();

        $stockData = $this->model->getStockByProduct($lang);

        $criticalBatches = $this->model->getCriticalBatches($lang);

        $chartLabels = [];
        $chartData = [];

        foreach ($stockData as $row)
        {
            $chartLabels[] = $row['PRD_product_name'];
            $chartData[] = (float)$row['stock_kg'];
        }

        return [
            'totalStock' => $totalStock,
            'totalBatches' => $totalBatches,
            'expiringCount' => $expiringCount,
            'totalOutbound' => $totalOutbound,
            'criticalBatches' => $criticalBatches,
            'chartLabels' => $chartLabels,
            'chartData' => $chartData
        ];
    }
}