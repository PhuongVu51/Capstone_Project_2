<?php
// backend/api/get_weather_forecast.php

ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php'; // Ensure user is logged in
require_once __DIR__ . '/../services/WeatherForecastService.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$city = $_GET['city'] ?? 'Hanoi';

$validCities = ['Hanoi', 'Hai Phong', 'Hung Yen'];
if (!in_array($city, $validCities)) {
    $city = 'Hanoi';
}

$weatherService = new WeatherForecastService();
$weatherData = $weatherService->getAdvancedForecast($city);

echo json_encode($weatherData);
exit;
