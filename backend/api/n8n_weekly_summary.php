<?php
// backend/api/n8n_weekly_summary.php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../connection/db_connect.php';

try {
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection failed");
    }

    // 1. Weekly QC & Yield Summary (Last 7 days or overall fallback)
    $sqlQC = "
        SELECT 
            COUNT(q.QCI_inspection_id) as total_inspections,
            COALESCE(SUM(q.QCI_usable_weight_kg), 0) as total_usable_kg,
            COALESCE(SUM(q.QCI_rotten_weight_kg), 0) as total_rotten_kg,
            COALESCE(SUM(q.QCI_natural_loss_weight_kg), 0) as total_natural_loss_kg,
            COALESCE(AVG(q.QCI_actual_yield_pct), 0) as avg_yield_pct,
            SUM(CASE WHEN q.QCI_destination = 'Production' THEN 1 ELSE 0 END) as passed_count,
            SUM(CASE WHEN q.QCI_destination = 'Rejected' THEN 1 ELSE 0 END) as rejected_count
        FROM QC_INSPECTIONS q
    ";
    $stmtQC = $pdo->query($sqlQC);
    $qcSummary = $stmtQC->fetch(PDO::FETCH_ASSOC);

    // 2. Finished Goods Production (Last 7 days or total)
    $sqlFG = "
        SELECT 
            COALESCE(SUM(FGD_total_cans), 0) as total_cans_produced,
            COUNT(DISTINCT FGD_batch_id) as active_batches_packaged
        FROM FINISHED_GOODS
    ";
    $stmtFG = $pdo->query($sqlFG);
    $fgSummary = $stmtFG->fetch(PDO::FETCH_ASSOC);

    // 3. Low stock batches needing attention (< 100 kg)
    $sqlLowStock = "
        SELECT 
            b.BCH_batch_id, 
            p.PRD_product_name, 
            b.BCH_available_stock_kg, 
            b.BCH_health_status
        FROM BATCHES b
        JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
        WHERE b.BCH_available_stock_kg < 100
        ORDER BY b.BCH_available_stock_kg ASC
        LIMIT 5
    ";
    $stmtLowStock = $pdo->query($sqlLowStock);
    $lowStockBatches = $stmtLowStock->fetchAll(PDO::FETCH_ASSOC);

    // 4. Pending Material Requests
    $sqlMatReq = "
        SELECT COUNT(*) as pending_requests
        FROM MATERIAL_REQUESTS
        WHERE REQ_status = 'Pending'
    ";
    $stmtMatReq = $pdo->query($sqlMatReq);
    $pendingMatRequests = (int)$stmtMatReq->fetchColumn();

    $responsePayload = [
        'status' => 'success',
        'report_period' => [
            'generated_at' => date('Y-m-d H:i:s'),
            'type' => 'Weekly_Production_Yield_Summary'
        ],
        'qc_metrics' => [
            'total_inspections' => (int)$qcSummary['total_inspections'],
            'passed_count' => (int)$qcSummary['passed_count'],
            'rejected_count' => (int)$qcSummary['rejected_count'],
            'pass_rate_pct' => round((float)$qcSummary['avg_yield_pct'], 2),
            'total_usable_kg' => round((float)$qcSummary['total_usable_kg'], 2),
            'total_rotten_kg' => round((float)$qcSummary['total_rotten_kg'], 2),
            'total_natural_loss_kg' => round((float)$qcSummary['total_natural_loss_kg'], 2)
        ],
        'production_metrics' => [
            'total_cans_produced' => (int)$fgSummary['total_cans_produced'],
            'active_batches_packaged' => (int)$fgSummary['active_batches_packaged'],
            'pending_material_requests' => $pendingMatRequests
        ],
        'low_stock_alerts' => array_map(function($b) {
            return [
                'batch_id' => $b['BCH_batch_id'],
                'product_name' => $b['PRD_product_name'],
                'available_stock_kg' => (float)$b['BCH_available_stock_kg'],
                'health_status' => $b['BCH_health_status']
            ];
        }, $lowStockBatches)
    ];

    echo json_encode($responsePayload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
