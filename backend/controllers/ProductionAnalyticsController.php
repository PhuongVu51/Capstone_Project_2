<?php

require_once __DIR__ . '/../models/ProductionAnalyticsModel.php';

class ProductionAnalyticsController
{
    private $model;

    public function __construct()
    {
        $this->model = new ProductionAnalyticsModel();
    }

    public function loadAnalyticsData()
    {
        $chartRows = $this->model->getProductionChart();

        $chartLabels = [];
        $chartData = [];

        foreach($chartRows as $row)
        {
            $chartLabels[] = $row['FGD_batch_id'];
            $chartData[] = (int)$row['FGD_total_cans'];
        }

        return [
            'totalOutput' => $this->model->getTotalOutput(),
            'averageYield' => $this->model->getAverageYield(),
            'productionBatches' => $this->model->getProductionBatches(),
            'quarantineCount' => $this->model->getQuarantineCount(),
            'productionLog' => $this->model->getProductionLog(),
            'chartLabels' => $chartLabels,
            'chartData' => $chartData
        ];
    }
}