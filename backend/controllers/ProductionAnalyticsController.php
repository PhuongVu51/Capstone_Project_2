<?php

require_once __DIR__ . '/../models/ProductionAnalyticsModel.php';

class ProductionAnalyticsController
{
    private $model;

    public function __construct()
    {
        $this->model = new ProductionAnalyticsModel();
    }

    public function loadAnalyticsData($lang = 'vi')
    {
        $chartRows = $this->model->getProductionChart();

        $chartLabels = [];
        $chartData = [];

        foreach($chartRows as $row)
        {
            $chartLabels[] = $row['FGD_batch_id'];
            $chartData[] = (int)$row['FGD_total_cans'];
        }

        $productionLog = $this->model->getProductionLog($lang);
        if ($lang === 'en' && !empty($productionLog)) {
            foreach ($productionLog as &$row) {
                $row['PRD_product_name'] = translate_product_name($row['PRD_product_name']);
            }
        }

        return [
            'totalOutput' => $this->model->getTotalOutput(),
            'averageYield' => $this->model->getAverageYield(),
            'productionBatches' => $this->model->getProductionBatches(),
            'quarantineCount' => $this->model->getQuarantineCount(),
            'productionLog' => $productionLog,
            'chartLabels' => $chartLabels,
            'chartData' => $chartData
        ];
    }
}