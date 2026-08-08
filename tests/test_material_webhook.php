<?php
require_once __DIR__ . '/../backend/helpers/n8n_helper.php';

echo "[TEST] Triggering material-request-alert webhook...\n";
$res = triggerN8nWebhook('material-request-alert', [
    'request_id' => 8888,
    'action' => 'created',
    'material_id' => 'Cá Ngừ Vây Vàng (PRD_TUNA_RAW)',
    'quantity_kg' => 1500.5,
    'needed_date' => '2026-08-12',
    'priority' => 'Urgent (Khẩn cấp)',
    'notes' => 'Yêu cầu cấp phát khẩn cho Ca Sáng phân xưởng 1',
    'requested_by_user_id' => 2,
    'status' => 'Pending'
]);

echo "[RESULT] Response from n8n: " . var_export($res, true) . "\n";
