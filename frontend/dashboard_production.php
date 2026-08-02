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
            <!-- KPI CARDS -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <!-- Weather Alert Card -->
                <div class="bg-[#0f1722] p-6 rounded-lg border border-[#1f2937] flex flex-col justify-between" id="weather-card">
                    <div class="flex justify-between items-start">
                        <p class="text-xs text-gray-500 uppercase font-semibold"><?= __('weather_alert') ?></p>
                        <select id="weather-city" class="bg-[#0a1118] text-xs text-gray-300 border border-[#1f2937] rounded px-2 py-1 outline-none">
                            <option value="Hanoi">Hanoi</option>
                            <option value="Hai Phong">Hai Phong</option>
                            <option value="Hung Yen">Hung Yen</option>
                        </select>
                    </div>
                    <div class="mt-2 flex items-center gap-3">
                        <img id="weather-icon" src="" alt="Weather" class="w-10 h-10 hidden" />
                        <div>
                            <h3 class="text-2xl font-bold text-white font-mono" id="weather-temp">--°C</h3>
                            <p class="text-xs text-gray-400" id="weather-desc">Loading...</p>
                        </div>
                    </div>
                    <p class="text-[10px] mt-2 font-medium" id="weather-msg"></p>
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
            const citySelect = document.getElementById('weather-city');
            
            function fetchWeather(city) {
                fetch('../backend/api/get_weather.php?city=' + encodeURIComponent(city))
                    .then(res => res.json())
                    .then(data => {
                        if(data.error) {
                            document.getElementById('weather-desc').innerText = '<?= __('weather_error') ?>';
                            return;
                        }
                        
                        document.getElementById('weather-temp').innerText = data.temp + '°C';
                        document.getElementById('weather-desc').innerText = data.condition + ' - ' + data.description;
                        
                        const iconEl = document.getElementById('weather-icon');
                        iconEl.src = `https://openweathermap.org/img/wn/${data.icon}.png`;
                        iconEl.classList.remove('hidden');

                        const msgEl = document.getElementById('weather-msg');
                        msgEl.innerText = data.alert_message;
                        
                        const cardEl = document.getElementById('weather-card');
                        if(data.alert_level === 'Warning') {
                            cardEl.classList.add('border-' + data.alert_color + '-500/50', 'shadow-[0_0_15px_rgba(220,38,38,0.1)]');
                            msgEl.classList.add('text-' + data.alert_color + '-400');
                            msgEl.classList.remove('text-green-400');
                        } else {
                            cardEl.classList.remove('border-red-500/50', 'border-orange-500/50', 'shadow-[0_0_15px_rgba(220,38,38,0.1)]');
                            msgEl.classList.add('text-green-400');
                            msgEl.classList.remove('text-red-400', 'text-orange-400');
                        }
                    })
                    .catch(err => {
                        console.error('Weather fetch error:', err);
                        document.getElementById('weather-desc').innerText = '<?= __('weather_conn_error') ?>';
                    });
            }

            citySelect.addEventListener('change', (e) => fetchWeather(e.target.value));
            
            // Initial fetch
            fetchWeather(citySelect.value);
        });
    </script>
</body>
</html>