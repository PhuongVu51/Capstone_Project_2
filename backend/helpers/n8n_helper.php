<?php
// backend/helpers/n8n_helper.php

if (!defined('N8N_WEBHOOK_BASE_URL')) {
    define('N8N_WEBHOOK_BASE_URL', getenv('N8N_WEBHOOK_BASE_URL') ?: 'http://localhost:5678/webhook');
}

/**
 * Trigger an asynchronous HTTP POST webhook to n8n workflow engine.
 * 
 * @param string $eventType Webhook endpoint slug (e.g. 'qc-alert', 'stock-alert', 'material-request-alert')
 * @param array $payload Additional event context payload
 * @return string|bool Returns cURL response or false on error
 */
function triggerN8nWebhook($eventType, array $payload = []) {
    $url = rtrim(N8N_WEBHOOK_BASE_URL, '/') . '/' . ltrim($eventType, '/');

    $eventData = array_merge([
        'event' => $eventType,
        'timestamp' => date('Y-m-d H:i:s'),
        'environment' => getenv('APP_ENV') ?: 'production',
        'source' => 'Capstone_Project_2_Backend'
    ], $payload);

    $jsonPayload = json_encode($eventData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $jsonPayload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 3, // 3s non-blocking timeout
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonPayload),
            'X-Source-App: Capstone_Project_2'
        ]
    ]);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("[n8n Webhook Error] Event: {$eventType} | Error: {$error}");
        return false;
    }

    return $result;
}
