<?php
// backend/services/WeatherService.php

require_once __DIR__ . '/../core/env_loader.php';

class WeatherService {
    private $apiKey;
    private $baseUrl = 'https://api.openweathermap.org/data/2.5/weather';

    public function __construct() {
        $this->apiKey = getenv('WEATHER_API_KEY');
    }

    public function getWeatherAlert($city) {
        if (!$this->apiKey) {
            return ['error' => 'API key is missing'];
        }

        // Map city names to query parameters if needed
        $cityMap = [
            'Hanoi' => 'Hanoi,VN',
            'Hai Phong' => 'Haiphong,VN',
            'Hung Yen' => 'Hung Yen,VN'
        ];

        $queryCity = $cityMap[$city] ?? 'Hanoi,VN';
        
        $url = $this->baseUrl . "?q=" . urlencode($queryCity) . "&appid=" . $this->apiKey . "&units=metric";

        // Using cURL for better handling
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 seconds timeout
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return ['error' => 'Failed to fetch weather data. HTTP Code: ' . $httpCode];
        }

        $data = json_decode($response, true);
        if (!$data) {
            return ['error' => 'Failed to parse weather data'];
        }

        $temp = $data['main']['temp'] ?? 0;
        $weatherId = $data['weather'][0]['id'] ?? 800; // 800 is clear sky
        $weatherMain = $data['weather'][0]['main'] ?? 'Clear';
        $weatherDesc = $data['weather'][0]['description'] ?? 'Clear sky';
        $icon = $data['weather'][0]['icon'] ?? '01d';

        // Determine alert level
        // OpenWeatherMap condition codes: https://openweathermap.org/weather-conditions
        // 2xx: Thunderstorm, 3xx: Drizzle, 5xx: Rain, 6xx: Snow
        $alertLevel = 'Normal';
        $alertMessage = __('weather_optimal');
        $alertColor = 'green';

        if ($weatherId >= 200 && $weatherId < 600) { // Thunderstorm, Drizzle, Rain
            $alertLevel = 'Warning';
            $alertMessage = __('weather_heavy_rain');
            $alertColor = 'red';
        } else if ($temp >= 38) {
            $alertLevel = 'Warning';
            $alertMessage = __('weather_extreme_heat');
            $alertColor = 'orange';
        }

        return [
            'city' => $city,
            'temp' => $temp,
            'condition' => $weatherMain,
            'description' => ucfirst($weatherDesc),
            'icon' => $icon,
            'alert_level' => $alertLevel,
            'alert_message' => $alertMessage,
            'alert_color' => $alertColor
        ];
    }
}
