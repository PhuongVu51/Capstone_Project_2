<?php
// backend/services/WeatherForecastService.php

require_once __DIR__ . '/../core/env_loader.php';

class WeatherForecastService {
    private $apiKey;
    private $baseUrl = 'https://api.openweathermap.org/data/2.5';
    
    private $cityCoords = [
        'Hanoi' => ['lat' => 21.0285, 'lon' => 105.8542],
        'Hai Phong' => ['lat' => 20.8449, 'lon' => 106.6881],
        'Hung Yen' => ['lat' => 20.6464, 'lon' => 106.0511]
    ];

    public function __construct() {
        $this->apiKey = getenv('WEATHER_API_KEY');
    }

    public function getAdvancedForecast($city) {
        if (!$this->apiKey) {
            return ['error' => 'API key is missing'];
        }

        $cityMap = [
            'Hanoi' => 'Hanoi,VN',
            'Hai Phong' => 'Haiphong,VN',
            'Hung Yen' => 'Hung Yen,VN'
        ];

        $queryCity = $cityMap[$city] ?? 'Hanoi,VN';
        
        // 1. Fetch 5-day / 3-hour forecast
        $forecastUrl = $this->baseUrl . "/forecast?q=" . urlencode($queryCity) . "&appid=" . $this->apiKey . "&units=metric";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $forecastUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix for local dev SSL issues
        $forecastResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$forecastResponse) {
            return ['error' => 'Failed to fetch forecast data.'];
        }

        $forecastData = json_decode($forecastResponse, true);
        if (!$forecastData || !isset($forecastData['list'])) {
            return ['error' => 'Failed to parse forecast data'];
        }

        // 2. Fetch current weather for the main metrics (Wind, Humidity, Visibility, Pressure)
        $currentUrl = $this->baseUrl . "/weather?q=" . urlencode($queryCity) . "&appid=" . $this->apiKey . "&units=metric";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $currentUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $currentResponse = curl_exec($ch);
        curl_close($ch);
        $currentData = json_decode($currentResponse, true);

        // 3. Fetch UV Index from Open-Meteo (Free, no API key needed)
        $uvIndex = 'N/A';
        if (isset($this->cityCoords[$city])) {
            $lat = $this->cityCoords[$city]['lat'];
            $lon = $this->cityCoords[$city]['lon'];
            $omUrl = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&current=uv_index";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $omUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $omResponse = curl_exec($ch);
            curl_close($ch);
            if ($omResponse) {
                $omData = json_decode($omResponse, true);
                if (isset($omData['current']['uv_index'])) {
                    $uvIndex = round($omData['current']['uv_index'], 1);
                }
            }
        }

        return $this->processData($currentData, $forecastData, $uvIndex, $city);
    }

    private function processData($currentData, $forecastData, $uvIndex, $city) {
        // Current metrics
        $temp = round($currentData['main']['temp'] ?? 0);
        $feelsLike = round($currentData['main']['feels_like'] ?? 0);
        $weatherMain = $currentData['weather'][0]['main'] ?? 'Clear';
        $weatherDesc = ucfirst($currentData['weather'][0]['description'] ?? 'Clear sky');
        
        $windSpeed = $currentData['wind']['speed'] ?? 0; // m/s
        $windDeg = $currentData['wind']['deg'] ?? 0;
        $windDir = $this->getWindDirection($windDeg);
        
        $humidity = $currentData['main']['humidity'] ?? 0;
        $visibility = ($currentData['visibility'] ?? 10000) / 1000; // to km
        $pressure = $currentData['main']['pressure'] ?? 0;
        
        // Calculate Dew Point approx
        $dewPoint = $temp - ((100 - $humidity) / 5);
        $dewPoint = round($dewPoint);

        $weatherId = $currentData['weather'][0]['id'] ?? 800;
        $icon = $currentData['weather'][0]['icon'] ?? '01d';
        
        $alertLevel = 'Normal';
        $alertMessage = function_exists('__') ? __('weather_optimal') : 'Conditions are optimal.';
        $alertColor = 'green';

        if (($weatherId >= 200 && $weatherId < 300) || in_array($weatherId, [502, 503, 504, 522, 531])) { // Thunderstorm or Heavy Rain
            $alertLevel = 'Warning';
            $alertMessage = function_exists('__') ? __('weather_heavy_rain') : 'Heavy rain/storm detected!';
            $alertColor = 'red';
        } else if ($temp >= 38) {
            $alertLevel = 'Warning';
            $alertMessage = function_exists('__') ? __('weather_high_temp') : 'High temperature warning!';
            $alertColor = 'orange';
        }

        $current = [
            'temp' => $temp,
            'feels_like' => $feelsLike,
            'condition' => $weatherMain,
            'description' => $weatherDesc,
            'icon' => $icon,
            'wind' => $windSpeed . ' m/s ' . $windDir,
            'humidity' => $humidity . '%',
            'visibility' => $visibility . ' km',
            'pressure' => $pressure . ' hPa',
            'uv_index' => $uvIndex . ' UV',
            'dew_point' => $dewPoint . '°C',
            'alert_level' => $alertLevel,
            'alert_message' => $alertMessage,
            'alert_color' => $alertColor
        ];

        // Group forecast by day
        $daily = [];
        $timezone = new DateTimeZone('Asia/Ho_Chi_Minh');
        
        $sunriseTs = $forecastData['city']['sunrise'] ?? 0;
        $sunsetTs = $forecastData['city']['sunset'] ?? 0;
        
        $sunriseStr = $sunriseTs > 0 ? (new DateTime('@' . $sunriseTs))->setTimezone($timezone)->format('H:i') : '--:--';
        $sunsetStr = $sunsetTs > 0 ? (new DateTime('@' . $sunsetTs))->setTimezone($timezone)->format('H:i') : '--:--';

        $hourly_by_date = [];
        
        $todayStr = (new DateTime('now', $timezone))->format('Y-m-d');
        
        foreach ($forecastData['list'] as $item) {
            $dt = $item['dt'];
            $dateObj = new DateTime('@' . $dt);
            $dateObj->setTimezone($timezone);
            $dateStr = $dateObj->format('Y-m-d');
            $hourStr = $dateObj->format('H:i');
            
            // Build hourly array by date
            if (!isset($hourly_by_date[$dateStr])) {
                $hourly_by_date[$dateStr] = [];
            }
            $hourly_by_date[$dateStr][] = [
                'time' => $hourStr,
                'temp' => round($item['main']['temp']),
                'feels_like' => round($item['main']['feels_like'] ?? $item['main']['temp']),
                'icon' => $item['weather'][0]['icon'],
                'pop' => isset($item['pop']) ? round($item['pop'] * 100) : 0
            ];
            
            // Build daily array
            if (!isset($daily[$dateStr])) {
                $daily[$dateStr] = [
                    'dateStr' => $dateStr,
                    'day_name' => ($dateStr === $todayStr) ? 'Today' : $dateObj->format('D'),
                    'temp_min' => $item['main']['temp_min'],
                    'temp_max' => $item['main']['temp_max'],
                    'icon' => $item['weather'][0]['icon']
                ];
            } else {
                if ($item['main']['temp_min'] < $daily[$dateStr]['temp_min']) {
                    $daily[$dateStr]['temp_min'] = $item['main']['temp_min'];
                }
                if ($item['main']['temp_max'] > $daily[$dateStr]['temp_max']) {
                    $daily[$dateStr]['temp_max'] = $item['main']['temp_max'];
                }
                // Use midday icon if possible
                if ($dateObj->format('H') == '13' || $dateObj->format('H') == '14') {
                    $daily[$dateStr]['icon'] = $item['weather'][0]['icon'];
                }
            }
        }

        if (!isset($daily[$todayStr])) {
            $todayEntry = [
                'dateStr' => $todayStr,
                'day_name' => 'Today',
                'temp_min' => $temp - 2,
                'temp_max' => $temp + 2,
                'icon' => $icon
            ];
            $daily = [$todayStr => $todayEntry] + $daily;
        }

        if (!isset($hourly_by_date[$todayStr]) || empty($hourly_by_date[$todayStr])) {
            $nowTime = (new DateTime('now', $timezone))->format('H:i');
            $hourly_by_date[$todayStr] = [
                [
                    'time' => $nowTime,
                    'temp' => $temp,
                    'feels_like' => $feelsLike,
                    'icon' => $icon,
                    'pop' => 0
                ]
            ];
        }

        foreach ($daily as &$day) {
            $day['temp_min'] = round($day['temp_min']);
            $day['temp_max'] = round($day['temp_max']);
        }

        return [
            'city' => $city,
            'current' => $current,
            'daily' => array_values($daily),
            'hourly_by_date' => $hourly_by_date,
            'sunrise' => $sunriseStr,
            'sunset' => $sunsetStr
        ];
    }

    private function getWindDirection($degree) {
        $val = intval(($degree / 22.5) + .5);
        $arr = ["N","NNE","NE","ENE","E","ESE", "SE", "SSE","S","SSW","SW","WSW","W","WNW","NW","NNW"];
        return $arr[($val % 16)];
    }
}
