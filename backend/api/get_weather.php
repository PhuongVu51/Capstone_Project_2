<?php
// backend/api/get_weather.php

// Ensure no PHP errors are printed directly which would break JSON response
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php'; // Ensure user is logged in
require_once __DIR__ . '/../services/WeatherService.php';

// Only allow specific roles or just logged-in users? The dashboard already checks roles, 
// but it's good practice to ensure they are logged in.
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

$weatherService = new WeatherService();
$weatherData = $weatherService->getWeatherAlert($city);

echo json_encode($weatherData);
exit;
