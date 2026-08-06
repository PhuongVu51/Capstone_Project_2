<?php
// Đường dẫn: frontend/dashboard_production.php
require_once '../backend/includes/auth.php';
require_role(['Production_Manager', 'Director'], 'login.php');
require_once '../backend/connection/db_connect.php';

try {
    // Lấy thông tin thống kê sơ bộ cho dashboard[cite: 10]
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM BATCHES WHERE BCH_expiry_date <= DATE_ADD(NOW(), INTERVAL 48 HOUR) AND BCH_available_stock_kg > 0");
    $expiringCount = $stmt->fetch()['count'] ?? 0;

    $lang = $_SESSION['lang'] ?? 'en';
    $productNameCol = ($lang === 'en') ? 'COALESCE(p.PRD_product_name_en, p.PRD_product_name)' : 'p.PRD_product_name';

    // Lấy danh sách lô hàng hiện tại[cite: 10]
    $batchesStmt = $pdo->query("
        SELECT b.BCH_batch_id, $productNameCol AS PRD_product_name, b.BCH_available_stock_kg, b.BCH_expiry_date 
        FROM BATCHES b 
        JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id 
        ORDER BY b.BCH_expiry_date ASC 
        LIMIT 10
    ");
    $batches = $batchesStmt->fetchAll();
} catch (PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Dashboard | ProSync Industrial</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --bg-dark: #0a1118; --bg-card: #0f1722; --accent-green: #10b981; --border-color: #1f2937; }
        body { background-color: var(--bg-dark); font-family: 'Inter', sans-serif; color: #d1d5db; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg-dark); }
        ::-webkit-scrollbar-thumb { background: #1f2937; border-radius: 4px; }
    </style>
</head>
<body class="min-h-screen flex overflow-x-hidden">

    <!-- SIDEBAR -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="flex-1 flex flex-col min-w-0 md:ml-64">
        <header class="h-16 border-b border-[#1f2937] bg-[#0a1118] flex items-center justify-between px-8 sticky top-0 z-10">
            <h1 class="text-xl font-bold text-white"><?= __('dashboard_overview') ?></h1>
            <div class="flex items-center gap-4">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-semibold text-white"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Production Manager') ?></p>
                    <p class="text-[10px] text-[#10b981]">Production Manager</p>
                </div>
            </div>
        </header>

        <div class="p-8 overflow-y-auto">
            
            <!-- ADVANCED WEATHER WIDGET (Hidden by default) -->
            <div id="advanced-weather-container" style="display: none;" class="mb-8 rounded-2xl overflow-hidden p-6 border border-[#1f2937] shadow-lg bg-[#06121a]">
                
                <div class="flex flex-col gap-6">
                    <!-- HEADER -->
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-4">
                            <h2 class="text-2xl font-bold text-white"><?= __('weather_forecast') ?></h2>
                            <div class="flex items-center gap-2">
                                <div class="flex bg-white/10 rounded-full p-1 backdrop-blur-md">
                                    <button id="btn-c" class="px-3 py-1 bg-white text-black rounded-full text-xs font-bold shadow cursor-pointer">°C</button>
                                    <button id="btn-f" class="px-3 py-1 text-white hover:bg-white/10 rounded-full text-xs font-bold transition cursor-pointer">°F</button>
                                </div>
                                <button onclick="toggleAdvancedWeather()" class="px-3 py-1 text-white bg-white/5 hover:bg-white/20 rounded-full text-xs font-bold transition cursor-pointer backdrop-blur-md">
                                    <?= __('collapse') ?>
                                </button>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-2 text-white bg-[#0f1722] px-4 py-2 rounded-full border border-[#1f2937]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.243-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                <select id="adv-weather-city" class="bg-transparent text-sm font-medium outline-none cursor-pointer appearance-none">
                                    <option value="Hanoi" class="text-black">Hanoi, VN</option>
                                    <option value="Hai Phong" class="text-black">Hai Phong, VN</option>
                                    <option value="Hung Yen" class="text-black">Hung Yen, VN</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- DAILY FORECAST BAR -->
                    <div id="daily-container" class="flex gap-3 overflow-x-auto pb-2" style="scrollbar-width: none;">
                        <!-- Rendered by JS -->
                    </div>

                    <!-- MAIN PANELS: 2 Frames -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 w-full">
                        <!-- Frame 1: Current Weather & Alert -->
                        <div id="cur-bg" class="lg:col-span-5 bg-cover bg-center border border-[#1f2937] rounded-2xl p-6 h-[300px] flex flex-col justify-between shadow-lg transition-all duration-700 relative overflow-hidden w-full min-w-0" style="background-image: url('https://images.unsplash.com/photo-1601297183314-046603a1d137?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80');">
                            <div class="absolute inset-0 bg-black/40 backdrop-blur-[2px]"></div>
                            
                            <div class="relative z-10 flex justify-between items-start">
                                <div id="weather-alert-box" class="flex px-3 py-1.5 rounded bg-green-500/10 border border-green-500/20 backdrop-blur-md">
                                    <p id="weather-alert-msg" class="text-xs font-medium text-green-400">Loading...</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-gray-200 text-sm font-medium" id="cur-date">--</p>
                                    <p class="text-gray-300 text-xs font-medium mt-0.5" id="cur-time">--</p>
                                </div>
                            </div>
                            <div class="relative z-10 flex justify-between items-end">
                                <h1 class="text-6xl font-bold text-white tracking-tighter drop-shadow-md" id="cur-temp">--°</h1>
                                <div class="text-right">
                                    <p class="text-white font-medium text-lg drop-shadow-md" id="cur-cond">--</p>
                                    <p class="text-gray-200 text-sm drop-shadow-md" id="cur-feels">Feels like --</p>
                                </div>
                            </div>
                        </div>

                        <!-- Frame 2: Hourly Forecast -->
                        <div class="lg:col-span-7 bg-[#1b212f] rounded-2xl border border-[#2b3548] flex flex-col h-[300px] overflow-hidden relative font-sans shadow-lg w-full min-w-0">
                            
                            <!-- Top Bar -->
                            <div class="flex justify-between items-center px-4 py-3 border-b border-[#2b3548]">
                                <h3 class="text-sm font-bold text-white"><?= __('hourly_forecast') ?></h3>
                                <div class="flex items-center gap-2">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" id="feels-like-toggle" class="sr-only peer" checked>
                                        <div class="w-8 h-4 bg-gray-600 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-[#3b82f6]"></div>
                                    </label>
                                    <span class="text-[11px] text-gray-400 font-medium"><?= __('feels_like') ?></span>
                                </div>
                            </div>

                            <!-- Main Container -->
                            <div class="flex-1 relative w-full overflow-hidden flex" id="hourly-scroll-container">
                                
                                <!-- Scrollable Chart Area -->
                                <div class="flex-1 overflow-hidden relative" id="hourly-scroll-area">
                                    <div class="w-full h-full relative" id="chart-inner-wrapper">
                                        
                                        <!-- Rain columns & Sunrise/Sunset (Rendered by JS) -->
                                        <div id="rain-columns" class="absolute top-12 left-0 w-full h-[170px] pointer-events-none z-0"></div>
                                        <div id="sun-events" class="absolute top-12 left-0 w-full h-[170px] pointer-events-none z-10"></div>
                                        
                                        <!-- Chart.js Canvas -->
                                        <canvas id="hourly-chart" class="absolute top-12 left-0 w-full h-[170px] z-20"></canvas>

                                        <!-- Top Labels Overlay -->
                                        <div id="top-labels" class="absolute top-0 left-0 w-full h-12 flex items-end pb-1 z-30">
                                            <!-- Rendered by JS -->
                                        </div>
                                        
                                        <!-- Vertical dashed line for tooltip -->
                                        <div id="tooltip-line" class="absolute top-12 bottom-9 w-[2px] border-l border-dashed border-gray-400 opacity-0 pointer-events-none z-40 transform -translate-x-1/2"></div>
                                        
                                        <!-- Tooltip (Custom) -->
                                        <div id="custom-tooltip" class="absolute bg-white/90 backdrop-blur rounded-xl shadow-lg flex flex-col items-center p-2 opacity-0 pointer-events-none transition-opacity duration-200 z-50 transform -translate-x-1/2" style="top: 10px; width: 60px;">
                                            <span class="text-[10px] text-gray-500 font-medium" id="tooltip-time">14:00</span>
                                            <img id="tooltip-icon" src="" class="w-8 h-8 -my-1" />
                                            <span class="text-lg font-bold text-gray-800 leading-none mt-1" id="tooltip-temp">32°</span>
                                            <div class="flex items-center gap-1 text-[9px] font-bold text-gray-600 mt-1">
                                                <svg class="w-2.5 h-2.5 fill-current" viewBox="0 0 16 16"><path d="M8 16c-3.3 0-6-2.7-6-6 0-3.5 6-10 6-10s6 6.5 6 10c0 3.3-2.7 6-6 6z"/></svg>
                                                <span id="tooltip-pop">54%</span>
                                            </div>
                                            <!-- Tooltip nub/tail -->
                                            <div class="absolute -bottom-1.5 left-1/2 transform -translate-x-1/2 w-3 h-3 bg-white/90 rotate-45"></div>
                                        </div>
                                        
                                        <!-- Tooltip Target Ring -->
                                        <div id="tooltip-ring" class="absolute w-3 h-3 rounded-full border-2 border-white bg-transparent shadow opacity-0 pointer-events-none z-50 transform -translate-x-1/2 -translate-y-1/2"></div>

                                        <!-- Bottom POP Bar -->
                                        <div id="bottom-pop-bar" class="absolute bottom-0 left-0 w-full h-9 flex overflow-hidden border-t border-[#2b3548] z-30">
                                            <!-- Rendered by JS -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <style>
                        .no-scrollbar::-webkit-scrollbar { display: none; }
                        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
                        .striped-bg { background: repeating-linear-gradient(45deg, #2b3548, #2b3548 4px, #1b212f 4px, #1b212f 8px); }
                        .rain-drop { position: absolute; width: 1px; height: 10px; background: rgba(255,255,255,0.4); animation: fall linear infinite; }
                        .rain-splash { position: absolute; width: 6px; height: 2px; border-radius: 50%; border: 1px solid rgba(255,255,255,0.4); bottom: 10px; animation: splash linear infinite; }
                        @keyframes fall { 0% { transform: translateY(-50px); opacity: 0; } 20% { opacity: 1; } 100% { transform: translateY(150px); opacity: 0; } }
                        @keyframes splash { 0% { transform: scale(0.5); opacity: 1; } 100% { transform: scale(2); opacity: 0; } }
                        </style>
                    </div>
                </div>
            </div>

            <!-- KPI CARDS -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <!-- Small Weather Widget -->
                <div id="small-weather-card" class="bg-[#0f1722] bg-cover bg-center bg-no-repeat p-4 rounded-lg flex flex-col justify-between border border-[#1f2937] relative overflow-hidden group">
                    <div class="absolute inset-0 bg-[#0f1722]/80 backdrop-blur-sm z-0"></div>
                    <div class="relative z-10 flex flex-col h-full">
                        <div class="flex justify-between items-center mb-2">
                            <p class="text-[10px] text-gray-300 uppercase font-bold tracking-wider"><?= __('weather_alert') ?></p>
                            <select id="small-weather-city" class="bg-[#0a1118]/80 text-xs text-gray-300 border border-[#1f2937] rounded px-1 outline-none backdrop-blur-md">
                                <option value="Hanoi">Hanoi</option>
                                <option value="Hai Phong">Hai Phong</option>
                                <option value="Hung Yen">Hung Yen</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-3 my-2">
                            <img id="small-weather-icon" src="" alt="Weather" class="w-10 h-10 drop-shadow-md hidden" />
                            <div>
                                <h3 class="text-2xl font-bold text-white font-mono" id="small-weather-temp">--°C</h3>
                                <p class="text-[10px] text-gray-200" id="small-weather-desc">Loading...</p>
                            </div>
                        </div>
                        <p class="text-[9px] mt-1 font-medium text-green-400" id="small-weather-msg"></p>
                        
                        <div class="mt-auto pt-3 border-t border-white/10 text-right">
                            <button onclick="toggleAdvancedWeather()" class="text-[10px] text-[#3b82f6] hover:text-[#60a5fa] font-semibold transition-colors">
                                <?= __('see_more_info') ?>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-[#0f1722] p-6 rounded-lg border border-[#1f2937]">
                    <p class="text-xs text-gray-500 uppercase font-semibold"><?= __('yield_analytics') ?></p>
                    <h3 class="text-3xl font-bold text-white mt-2 font-mono">0.0%</h3>
                    <p class="text-xs text-gray-500 mt-1"><?= __('no_data_yet') ?></p>
                </div>
                <div class="bg-[#0f1722] p-6 rounded-lg border border-[#1f2937]">
                    <p class="text-xs text-gray-500 uppercase font-semibold"><?= __('export_demand') ?></p>
                    <h3 class="text-3xl font-bold text-white mt-2 font-mono">0 <span class="text-sm text-gray-500 font-normal"><?= __('units') ?></span></h3>
                    <p class="text-xs text-gray-500 mt-1"><?= __('awaiting_new_orders') ?></p>
                </div>
                <!-- Thẻ cảnh báo trỏ trực tiếp sang trang FEFO khi bấm vào[cite: 10] -->
                <a href="production_FEFO.php" class="bg-[#b91c1c]/90 hover:bg-[#b91c1c] p-6 rounded-lg border border-red-500/50 transition-all shadow-[0_0_15px_rgba(220,38,38,0.2)] block group">
                    <p class="text-xs text-red-200 uppercase font-semibold"><?= __('expiring_batches') ?></p>
                    <h3 class="text-3xl font-bold text-white mt-2 font-mono"><?= $expiringCount ?></h3>
                    <p class="text-xs text-red-100 mt-1 font-medium group-hover:underline"><?= __('fefo_allocation_required') ?> &rarr;</p>
                </a>
            </div>

            <!-- TABLE -->
            <div class="bg-[#0f1722] rounded-lg border border-[#1f2937] overflow-hidden">
                <div class="p-5 border-b border-[#1f2937] flex justify-between items-center bg-[#0b121c]">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider"><?= __('current_production_status') ?></h3>
                    <button onclick="window.location.href='production_FEFO.php'" class="bg-[#10b981] text-gray-900 text-xs font-bold px-4 py-2 rounded hover:bg-[#059669] transition"><?= __('view_fefo_alerts') ?></button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-gray-500 text-[10px] uppercase bg-[#0a1118] border-b border-[#374151]">
                                <th class="py-3 px-5"><?= __('batch_id') ?></th>
                                <th class="py-3 px-5"><?= __('product_name') ?></th>
                                <th class="py-3 px-5"><?= __('volume_available') ?></th>
                                <th class="py-3 px-5"><?= __('expiry_fefo') ?></th>
                                <th class="py-3 px-5 text-center"><?= __('action') ?></th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-[#1f2937]">
                            <?php if (empty($batches)): ?>
                                <tr><td colspan="5" class="p-6 text-center text-gray-500 italic"><?= __('no_production_batches') ?></td></tr>
                            <?php else: ?>
                                <?php foreach ($batches as $row): ?>
                                    <tr class="hover:bg-[#131c26] transition-colors">
                                        <td class="py-3 px-5 text-[#10b981] font-mono text-xs font-semibold"><?= htmlspecialchars($row['BCH_batch_id']) ?></td>
                                        <td class="py-3 px-5 text-gray-200"><?= htmlspecialchars($row['PRD_product_name']) ?></td>
                                        <td class="py-3 px-5 font-mono text-gray-300"><?= number_format($row['BCH_available_stock_kg'], 2) ?> kg</td>
                                        <td class="py-3 px-5 font-mono text-red-400 text-xs"><?= htmlspecialchars($row['BCH_expiry_date']) ?></td>
                                        <td class="py-3 px-5 text-center">
                                            <button onclick="openModal('<?= htmlspecialchars($row['BCH_batch_id']) ?>', <?= $row['BCH_available_stock_kg'] ?>)" class="bg-[#1f2937] border border-[#374151] text-gray-200 text-xs font-bold px-3 py-1.5 rounded hover:bg-[#10b981] hover:text-gray-900 transition"><?= __('allocate') ?></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- MODAL ALLOCATION (Được đưa sang từ trang FEFO) -->
    <div id="allocationModal" class="fixed inset-0 z-50 hidden bg-black/80 backdrop-blur-sm flex items-center justify-center">
        <div class="bg-[#0f1722] rounded-xl border border-[#1f2937] shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
            <div class="p-4 border-b border-[#1f2937] flex justify-between items-center bg-[#0a1118]">
                <h3 class="text-[#10b981] font-bold flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#10b981]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    Batch Allocation
                </h3>
                <button type="button" onclick="closeModal()" class="text-gray-500 hover:text-white transition-colors">✕</button>
            </div>
            <form action="../backend/connection/process_allocation.php" method="POST" class="p-6">
                <input type="hidden" name="batch_id" id="modal_batch_id">
                
                <div class="space-y-3 mb-6 text-sm">
                    <div class="flex justify-between border-b border-[#1f2937]/50 pb-2">
                        <span class="text-gray-500">Target Batch ID:</span>
                        <span class="text-[#10b981] font-mono font-bold bg-[#10b981]/10 px-2 py-0.5 rounded" id="display_batch_id">--</span>
                    </div>
                    <div class="flex justify-between border-b border-[#1f2937]/50 pb-2">
                        <span class="text-gray-500">Max Stock Available:</span>
                        <span class="text-gray-300 font-mono font-bold" id="display_max_stock">0.00 kg</span>
                    </div>
                </div>
                
                <div class="mb-8">
                    <label class="block text-xs text-gray-400 uppercase tracking-wider mb-2 font-bold">Allocation Quantity (kg)</label>
                    <div class="relative">
                        <input type="number" step="0.01" min="0.01" name="allocate_qty" id="input_qty" required
                            oninput="this.value = this.value.replace(/[^0-9.]/g, '')"
                            class="w-full bg-[#0a1118] border border-[#374151] text-white text-xl font-mono rounded p-3 pl-4 pr-12 focus:outline-none focus:border-[#10b981] focus:ring-1 focus:ring-[#10b981] transition-all">
                        <span class="absolute right-4 top-3.5 text-gray-500 font-bold text-sm">KG</span>
                    </div>
                </div>
                
                <div class="flex gap-3">
                    <button type="button" onclick="closeModal()" class="flex-1 bg-transparent hover:bg-[#1f2937] border border-[#374151] text-gray-300 text-sm font-bold py-3 rounded transition-colors">CANCEL</button>
                    <button type="submit" class="flex-1 bg-[#10b981] hover:bg-[#059669] text-gray-900 text-sm font-bold py-3 rounded transition-colors shadow-[0_0_15px_rgba(16,185,129,0.3)]">ALLOCATE NOW</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPT ĐIỀU KHIỂN MODAL -->
    <script>
        function openModal(batchId, availableStock) {
            document.getElementById('modal_batch_id').value = batchId;
            document.getElementById('display_batch_id').innerText = batchId;
            document.getElementById('display_max_stock').innerText = parseFloat(availableStock).toFixed(2) + ' kg';
            
            let inputQty = document.getElementById('input_qty');
            inputQty.max = availableStock;
            inputQty.value = '';
            
            document.getElementById('allocationModal').classList.remove('hidden');
        }
        function closeModal() {
            document.getElementById('allocationModal').classList.add('hidden');
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let weatherData = null;
            let isFahrenheit = false;
            let selectedDateStr = null;

            const citySelect = document.getElementById('adv-weather-city');
            const btnC = document.getElementById('btn-c');
            const btnF = document.getElementById('btn-f');

            function getTemp(celsius) {
                if (isFahrenheit) return Math.round(celsius * 9/5 + 32);
                return Math.round(celsius);
            }

            function fetchAdvancedWeather(city) {
                fetch('../backend/api/get_weather_forecast.php?city=' + encodeURIComponent(city))
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) {
                            console.error(data.error);
                            document.getElementById('cur-cond').innerText = 'Error loading weather';
                            return;
                        }
                        weatherData = data;
                        selectedDateStr = data.daily[0].dateStr;
                        renderWeather();
                    })
                    .catch(err => {
                        console.error('Weather fetch error:', err);
                        document.getElementById('cur-cond').innerText = 'Error loading weather';
                    });
            }

            function renderWeather() {
                if (!weatherData) return;
                const data = weatherData;
                
                if (isFahrenheit) {
                    btnF.className = "px-3 py-1 bg-white text-black rounded-full text-xs font-bold shadow cursor-pointer";
                    btnC.className = "px-3 py-1 text-white hover:bg-white/10 rounded-full text-xs font-bold transition cursor-pointer";
                } else {
                    btnC.className = "px-3 py-1 bg-white text-black rounded-full text-xs font-bold shadow cursor-pointer";
                    btnF.className = "px-3 py-1 text-white hover:bg-white/10 rounded-full text-xs font-bold transition cursor-pointer";
                }

                const selectedDaily = data.daily.find(d => d.dateStr === selectedDateStr) || data.daily[0];
                const isToday = selectedDateStr === data.daily[0].dateStr;

                // 1. Render Frame 1
                const displayTemp = isToday ? data.current.temp : ((selectedDaily.temp_max + selectedDaily.temp_min) / 2);
                const displayCond = isToday ? data.current.description : "Forecast";
                const displayFeels = isToday ? ('Feels like ' + getTemp(data.current.feels_like) + (isFahrenheit ? '°F' : '°C')) : ('Min: ' + getTemp(selectedDaily.temp_min) + '° / Max: ' + getTemp(selectedDaily.temp_max) + '°');
                
                document.getElementById('cur-date').innerText = selectedDaily.day_name + ', ' + selectedDateStr;
                document.getElementById('cur-temp').innerText = getTemp(displayTemp) + '°';
                document.getElementById('cur-cond').innerText = displayCond;
                document.getElementById('cur-feels').innerText = displayFeels;

                // Real-time clock update
                function updateClock() {
                    const timeEl = document.getElementById('cur-time');
                    if (!isToday) {
                        if (timeEl) timeEl.style.display = 'none';
                        return;
                    }
                    if (timeEl) timeEl.style.display = 'block';
                    
                    const now = new Date();
                    let hours = now.getHours();
                    let minutes = now.getMinutes();
                    const ampm = hours >= 12 ? 'PM' : 'AM';
                    hours = hours % 12;
                    hours = hours ? hours : 12;
                    minutes = minutes < 10 ? '0' + minutes : minutes;
                    if (timeEl) timeEl.innerText = hours + ':' + minutes + ' ' + ampm;
                }
                updateClock();
                if (!window.clockInterval) {
                    window.clockInterval = setInterval(updateClock, 60000);
                }

                const curBg = document.getElementById('cur-bg');

                // Dynamic Background Logic (Use data.current for Today, or selectedDaily for future dates)
                let targetIcon = (isToday && data.current && data.current.icon) ? data.current.icon : (selectedDaily.icon || '');
                let targetCond = (isToday && data.current && data.current.condition) ? data.current.condition : (selectedDaily.condition || '');
                let targetDesc = (isToday && data.current && data.current.description) ? data.current.description : (selectedDaily.description || '');
                
                let iconCode = targetIcon.toLowerCase();
                let condText = (targetCond + ' ' + targetDesc).toLowerCase();
                let bgUrl = '';

                if (iconCode.includes('11') || condText.includes('thunderstorm')) {
                    // Thunderstorm
                    bgUrl = 'https://images.unsplash.com/photo-1605727216801-e27ce1d0ce49?auto=format&fit=crop&w=1000&q=80';
                } else if (iconCode.includes('13') || condText.includes('snow')) {
                    // Snow
                    bgUrl = 'https://images.unsplash.com/photo-1483664852095-d6cc6870702d?auto=format&fit=crop&w=1000&q=80';
                } else if (iconCode.includes('09') || iconCode.includes('10') || condText.includes('rain') || condText.includes('drizzle')) {
                    // Rain / Heavy Rain
                    bgUrl = 'https://images.unsplash.com/photo-1519692933481-e162a57d6721?auto=format&fit=crop&w=1000&q=80';
                } else if (iconCode.includes('04') || condText.includes('overcast')) {
                    // Overcast Clouds
                    bgUrl = 'https://images.unsplash.com/photo-1513002749550-c59d786b8e6c?auto=format&fit=crop&w=1000&q=80';
                } else if (iconCode.includes('02') || iconCode.includes('03') || condText.includes('cloud')) {
                    // Scattered / Few Clouds
                    bgUrl = 'https://images.unsplash.com/photo-1534088568595-a066f410cbda?auto=format&fit=crop&w=1000&q=80';
                } else if (iconCode.includes('50') || condText.includes('mist') || condText.includes('fog') || condText.includes('haze')) {
                    // Mist / Fog
                    bgUrl = 'https://images.unsplash.com/photo-1487621167305-5d248087c724?auto=format&fit=crop&w=1000&q=80';
                } else {
                    // Clear / Sunny
                    bgUrl = 'https://images.unsplash.com/photo-1601297183314-046603a1d137?auto=format&fit=crop&w=1000&q=80';
                }

                curBg.style.backgroundImage = `url('${bgUrl}')`;
                
                // Also update small widget background
                document.getElementById('small-weather-card').style.backgroundImage = `url('${bgUrl}')`;

                // Alert Logic
                const alertBox = document.getElementById('weather-alert-box');
                const alertMsg = document.getElementById('weather-alert-msg');

                if (isToday) {
                    alertBox.style.display = 'flex';
                    if (data.current.alert_level === 'Warning') {
                        alertBox.className = 'flex px-3 py-1.5 rounded border bg-' + data.current.alert_color + '-500/10 border-' + data.current.alert_color + '-500/30 backdrop-blur-md';
                        alertMsg.className = 'text-xs font-bold text-' + data.current.alert_color + '-400';
                        curBg.className = 'lg:col-span-5 bg-cover bg-center rounded-2xl p-6 h-[300px] flex flex-col justify-between transition-colors border border-' + data.current.alert_color + '-500/50 shadow-[0_0_15px_rgba(220,38,38,0.1)] relative overflow-hidden w-full min-w-0';
                    } else {
                        alertBox.className = 'flex px-3 py-1.5 rounded border bg-green-500/10 border-green-500/20 backdrop-blur-md';
                        alertMsg.className = 'text-xs font-medium text-green-400';
                        curBg.className = 'lg:col-span-5 bg-cover bg-center border border-[#1f2937] rounded-2xl p-6 h-[300px] flex flex-col justify-between shadow-lg transition-colors relative overflow-hidden w-full min-w-0';
                    }
                    alertMsg.innerText = data.current.alert_message;
                } else {
                    alertBox.style.display = 'none';
                    curBg.className = 'lg:col-span-5 bg-cover bg-center border border-[#1f2937] rounded-2xl p-6 h-[300px] flex flex-col justify-between shadow-lg transition-colors relative overflow-hidden w-full min-w-0';
                }
                
                // Update Small Widget Data (always shows current weather)
                document.getElementById('small-weather-temp').innerText = getTemp(data.current.temp) + '°';
                document.getElementById('small-weather-desc').innerText = data.current.condition + ' - ' + data.current.description;
                const smallIcon = document.getElementById('small-weather-icon');
                smallIcon.src = `https://openweathermap.org/img/wn/${data.current.icon}.png`;
                smallIcon.classList.remove('hidden');
                
                const smallMsg = document.getElementById('small-weather-msg');
                smallMsg.innerText = data.current.alert_message;
                smallMsg.className = `text-[9px] mt-1 font-medium text-${data.current.alert_color}-400`;

                // 2. Render Daily (Top bar)
                const dailyContainer = document.getElementById('daily-container');
                dailyContainer.innerHTML = '';
                data.daily.forEach((day) => {
                    let activeClass = day.dateStr === selectedDateStr 
                        ? 'bg-orange-500 text-white border-orange-500 shadow-lg' 
                        : 'bg-[#0f1722] text-gray-300 border-[#1f2937] hover:bg-[#1f2937]';
                    dailyContainer.innerHTML += `
                        <div onclick="window.selectDate('${day.dateStr}')" class="px-5 py-2 rounded-2xl border cursor-pointer transition-colors flex items-center gap-3 min-w-max ${activeClass}">
                            <span class="font-medium text-sm">${day.day_name === 'Today' ? '<?= __('today') ?>' : day.day_name}</span>
                            <span class="font-bold">${getTemp(day.temp_max)}°</span>
                            <img src="https://openweathermap.org/img/wn/${day.icon}.png" class="w-8 h-8 drop-shadow-md" />
                        </div>
                    `;
                });

                // 4. Render Hourly (Frame 2)
                const hourlyData = data.hourly_by_date[selectedDateStr] || [];
                const hourlyLabels = hourlyData.map(h => h.time);
                const hourlyTemps = hourlyData.map(h => getTemp(h.temp));
                const hourlyFeels = hourlyData.map(h => getTemp(h.feels_like));
                const hourlyPop = hourlyData.map(h => h.pop);
                
                if (window.weatherChart) window.weatherChart.destroy();
                
                const topLabels = document.getElementById('top-labels');
                const bottomPopBar = document.getElementById('bottom-pop-bar');
                const chartInner = document.getElementById('chart-inner-wrapper');
                const hourlyScrollArea = document.getElementById('hourly-scroll-area');
                
                if (hourlyData.length > 0) {
                    // Reset scroll container content if it was empty
                    if (!topLabels) return; // safety
                    
                    // Fill 100% width of chart container
                    chartInner.style.width = '100%';
                    const containerWidth = chartInner.clientWidth || hourlyScrollArea.clientWidth || 600;

                    // Padding for first and last points (40px) so 00h and 22h labels are never clipped by Y-axis or right border
                    const paddingX = 40;
                    const usableWidth = containerWidth - (paddingX * 2);

                    // Top labels & Bottom POP bar
                    topLabels.innerHTML = '';
                    bottomPopBar.innerHTML = '';
                    
                    const currentTime = new Date();
                    const currentHour = currentTime.getHours();
                    
                    // Render HTML overlays for each data point
                    hourlyData.forEach((h, i) => {
                        const pxPos = hourlyData.length > 1 ? (paddingX + (i / (hourlyData.length - 1)) * usableWidth) : (containerWidth / 2);
                        
                        // Top label
                        topLabels.innerHTML += `
                            <div class="absolute flex flex-col items-center justify-end transform -translate-x-1/2 w-16" style="left: ${pxPos}px; bottom: 0;">
                                <span class="text-[11px] text-gray-300 font-medium mb-0.5">${h.time}</span>
                                <img src="https://openweathermap.org/img/wn/${h.icon}.png" class="w-8 h-8 -my-1" />
                                <span class="text-xs font-bold text-gray-200 mt-0.5">${getTemp(h.temp)}°</span>
                            </div>
                        `;
                        
                        // Bottom POP bar segment
                        const hHour = parseInt(h.time.split(':')[0]);
                        const isPast = (selectedDateStr === data.daily[0].dateStr && hHour < currentHour);
                        const popBg = isPast ? 'striped-bg opacity-50' : 'bg-[#1e3a8a]'; 
                        
                        bottomPopBar.innerHTML += `
                            <div class="flex-1 flex items-center justify-center border-r border-[#2b3548] last:border-0 ${popBg}">
                                ${!isPast ? `<svg class="w-2.5 h-2.5 text-blue-300 fill-current mr-1" viewBox="0 0 16 16"><path d="M8 16c-3.3 0-6-2.7-6-6 0-3.5 6-10 6-10s6 6.5 6 10c0 3.3-2.7 6-6 6z"/></svg><span class="text-[9px] font-bold text-blue-100">${h.pop}%</span>` : ''}
                            </div>
                        `;
                    });

                    // Dynamic Y-axis limits
                    const allTemps = [...hourlyTemps, ...hourlyFeels].filter(t => t > 0);
                    let maxTemp = allTemps.length ? Math.max(...allTemps) : 35;
                    let minTemp = allTemps.length ? Math.min(...allTemps) : 20;
                    
                    let yMax = 50;
                    let yMin = 10;

                    if (maxTemp > 45) yMax = Math.ceil((maxTemp + 2) / 10) * 10;
                    if (minTemp < 12) yMin = Math.floor((minTemp - 2) / 10) * 10;
                    if ((yMax - yMin) < 40) {
                        yMin = Math.max(0, yMax - 40);
                    }

                    // Rain Columns plugin
                    const rainColumnsPlugin = {
                        id: 'rainColumns',
                        beforeDatasetsDraw(chart, args, pluginOptions) {
                            if (!chart.chartArea) return;
                            const {top, bottom, left, right} = chart.chartArea;
                            const {ctx, scales: {x}} = chart;
                            if (!x) return;
                            ctx.save();
                            hourlyPop.forEach((pop, index) => {
                                if (pop > 40) { 
                                    const xPos = x.getPixelForValue(index);
                                    const width = (right - left) / Math.max(1, (hourlyPop.length - 1));
                                    
                                    ctx.fillStyle = 'rgba(59, 130, 246, 0.2)'; 
                                    ctx.fillRect(xPos - (width/2), top, width, bottom - top);
                                    
                                    // 3-5 giọt nước mờ mờ trên filter đó
                                    ctx.strokeStyle = 'rgba(255, 255, 255, 0.3)';
                                    ctx.lineWidth = 1;
                                    ctx.beginPath();
                                    ctx.moveTo(xPos - 5, top + 20); ctx.lineTo(xPos - 5, top + 35);
                                    ctx.moveTo(xPos + 10, top + 40); ctx.lineTo(xPos + 10, top + 55);
                                    ctx.moveTo(xPos, top + 70); ctx.lineTo(xPos, top + 90);
                                    ctx.moveTo(xPos - 12, top + 60); ctx.lineTo(xPos - 12, top + 75);
                                    ctx.stroke();
                                }
                            });
                            ctx.restore();
                        }
                    };

                    const ctx = document.getElementById('hourly-chart').getContext('2d');
                    let areaGradient = ctx.createLinearGradient(0, 0, 0, 170);
                    areaGradient.addColorStop(0, 'rgba(220, 38, 38, 0.6)'); // Red peak
                    areaGradient.addColorStop(0.5, 'rgba(249, 115, 22, 0.4)'); // Orange mid
                    areaGradient.addColorStop(1, 'rgba(180, 140, 100, 0.6)'); // Sand bottom

                    const cleanFeels = hourlyFeels.map((f, idx) => (f && f > 0) ? f : hourlyTemps[idx]);

                    window.weatherChart = new Chart(ctx, {
                        type: 'line',
                        plugins: [rainColumnsPlugin],
                        data: {
                            labels: hourlyLabels,
                            datasets: [
                                {
                                    label: 'Feels Like',
                                    data: cleanFeels,
                                    borderColor: 'rgba(229, 231, 235, 0.8)',
                                    borderWidth: 2,
                                    borderDash: [4, 4],
                                    tension: 0.4,
                                    fill: false,
                                    pointRadius: 0,
                                    pointHoverRadius: 0,
                                    hidden: !document.getElementById('feels-like-toggle').checked
                                },
                                {
                                    label: 'Actual Temp',
                                    data: hourlyTemps,
                                    borderColor: 'rgba(245, 158, 11, 0.9)',
                                    backgroundColor: areaGradient,
                                    borderWidth: 2,
                                    tension: 0.4,
                                    fill: 'start',
                                    pointRadius: 0,
                                    pointHoverRadius: 0
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false,
                            },
                            plugins: { 
                                legend: { display: false }, 
                                tooltip: { 
                                    enabled: false, 
                                    external: function(context) {
                                        let tooltipEl = document.getElementById('custom-tooltip');
                                        let tooltipLine = document.getElementById('tooltip-line');
                                        let tooltipRing = document.getElementById('tooltip-ring');

                                        const tooltipModel = context.tooltip;
                                        if (tooltipModel.opacity === 0) {
                                            tooltipEl.style.opacity = 0;
                                            tooltipLine.style.opacity = 0;
                                            tooltipRing.style.opacity = 0;
                                            return;
                                        }

                                        if (tooltipModel.body) {
                                            const dataIndex = tooltipModel.dataPoints[0].dataIndex;
                                            const h = hourlyData[dataIndex];
                                            
                                            document.getElementById('tooltip-time').innerText = h.time;
                                            document.getElementById('tooltip-icon').src = `https://openweathermap.org/img/wn/${h.icon}.png`;
                                            document.getElementById('tooltip-temp').innerText = getTemp(h.temp) + '°';
                                            document.getElementById('tooltip-pop').innerText = h.pop + '%';
                                        }

                                        const xPos = tooltipModel.caretX;
                                        const yPos = tooltipModel.dataPoints[0].element.y + 48; 

                                        tooltipEl.style.opacity = 1;
                                        tooltipEl.style.left = xPos + 'px';
                                        
                                        tooltipLine.style.opacity = 1;
                                        tooltipLine.style.left = xPos + 'px';
                                        
                                        tooltipRing.style.opacity = 1;
                                        tooltipRing.style.left = xPos + 'px';
                                        tooltipRing.style.top = yPos + 'px';
                                    }
                                } 
                            },
                            scales: {
                                y: { 
                                    display: false, 
                                    min: yMin, 
                                    max: yMax 
                                },
                                x: { display: false, offset: false }
                            },
                            layout: { padding: { top: 20, bottom: 0, left: 30, right: 30 } }
                        }
                    });
                    
                    // Render Sunrise/Sunset
                    const sunEvents = document.getElementById('sun-events');
                    sunEvents.innerHTML = '';
                    
                    if (data.sunrise && data.sunset && selectedDateStr === data.daily[0].dateStr) {
                        const getPos = (timeStr) => {
                            const [h, m] = timeStr.split(':').map(Number);
                            const decimalTime = h + (m/60);
                            return (decimalTime / 24) * 100;
                        };
                        
                        const risePos = getPos(data.sunrise);
                        const setPos = getPos(data.sunset);
                        
                        sunEvents.innerHTML = `
                            <div class="absolute bottom-10 flex items-center gap-1 transform -translate-x-1/2" style="left: ${risePos}%;">
                                <span class="text-[9px] text-blue-400 font-bold">&uarr;</span>
                                <div class="w-4 h-4 bg-orange-400 rounded-full flex items-center justify-center overflow-hidden">
                                    <div class="w-full h-1/2 bg-transparent absolute top-0"></div>
                                </div>
                                <span class="text-[10px] text-gray-300 font-medium">${data.sunrise}</span>
                            </div>
                            <div class="absolute bottom-10 flex items-center gap-1 transform -translate-x-1/2" style="left: ${setPos}%;">
                                <span class="text-[9px] text-blue-400 font-bold">&darr;</span>
                                <div class="w-4 h-4 bg-orange-500 rounded-full flex items-center justify-center overflow-hidden">
                                    <div class="w-full h-1/2 bg-transparent absolute top-0"></div>
                                </div>
                                <span class="text-[10px] text-gray-300 font-medium">${data.sunset}</span>
                            </div>
                        `;
                    }

                } else {
                    hourlyScrollArea.innerHTML = '<p class="text-xs text-gray-500 mt-10 w-full text-center">No hourly forecast available for this date.</p>';
                }
            }

            window.selectDate = function(dateStr) {
                selectedDateStr = dateStr;
                renderWeather();
            }
            
            window.toggleAdvancedWeather = function() {
                const advContainer = document.getElementById('advanced-weather-container');
                if (advContainer.style.display === 'none') {
                    advContainer.style.display = 'block';
                    if (window.weatherChart) {
                        window.weatherChart.resize(); // Force resize when container becomes visible
                    }
                } else {
                    advContainer.style.display = 'none';
                }
            }

            const feelsToggle = document.getElementById('feels-like-toggle');
            if (feelsToggle) {
                feelsToggle.addEventListener('change', (e) => {
                    if (window.weatherChart) {
                        window.weatherChart.data.datasets[0].hidden = !e.target.checked;
                        window.weatherChart.update();
                    }
                });
            }

            btnC.addEventListener('click', () => { isFahrenheit = false; renderWeather(); });
            btnF.addEventListener('click', () => { isFahrenheit = true; renderWeather(); });
            
            citySelect.addEventListener('change', (e) => {
                document.getElementById('small-weather-city').value = e.target.value;
                fetchAdvancedWeather(e.target.value);
            });
            
            document.getElementById('small-weather-city').addEventListener('change', (e) => {
                citySelect.value = e.target.value;
                fetchAdvancedWeather(e.target.value);
            });

            fetchAdvancedWeather(citySelect.value);
        });
    </script>
</body>
</html>