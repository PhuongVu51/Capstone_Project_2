<?php
// backend/api/notifications.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Ensure user is authenticated
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized', 'count' => 0, 'data' => []]);
    exit;
}

$role = $_SESSION['role'];
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'vi';
$_SESSION['lang'] = $lang;

session_write_close();

require_once __DIR__ . '/../models/NotificationModel.php';

try {
    $notificationModel = new NotificationModel($lang);
    
    // Fetch notifications
    $notifications = $notificationModel->getAlertsByRole($role);
    $count = count($notifications);
    
    echo json_encode([
        'status' => 'success',
        'lang' => $lang,
        'count' => $count,
        'data' => $notifications
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error: ' . $e->getMessage(),
        'count' => 0,
        'data' => []
    ]);
}
?>
