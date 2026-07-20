<?php
// Đường dẫn: frontend/dashboard_production.php
require_once '../backend/includes/auth.php';
require_role(['Production_Manager', 'Director'], 'login.php');
require_once '../backend/connection/db_connect.php';

try {
    // 1. TRUY VẤN YIELD ANALYTICS (Tỷ lệ thu hồi trung bình)
    $stmtYield = $pdo->query("SELECT AVG(FGD_actual_yield_rate) as avg_yield FROM FINISHED_GOODS");
    $yieldData = $stmtYield->fetch();
    $avgYield = $yieldData['avg_yield'] ? number_format($yieldData['avg_yield'], 1) : '0.0';

    // 2. TRUY VẤN FEFO ALERTS (Đếm số lô hàng sắp hỏng trong 48h tới)
    $stmtFEFO = $pdo->query("SELECT COUNT(*) as alert_count FROM BATCHES WHERE BCH_available_stock_kg > 0 AND BCH_expiry_date <= DATE_ADD(NOW(), INTERVAL 48 HOUR)");
    $fefoCount = $stmtFEFO->fetchColumn();
    // Format thành 2 chữ số (VD: 08 thay vì 8)
    $fefoDisplay = str_pad($fefoCount, 2, '0', STR_PAD_LEFT);

    // 3. TRUY VẤN DANH SÁCH LÔ HÀNG ĐANG SẢN XUẤT / TỒN KHO
    $stmtBatches = $pdo->query("
        SELECT 
            b.BCH_batch_id, 
            p.PRD_product_name, 
            b.BCH_available_stock_kg, 
            b.BCH_expiry_date, 
            b.BCH_current_stage
        FROM BATCHES b
        JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
        WHERE b.BCH_available_stock_kg > 0
        ORDER BY b.BCH_expiry_date ASC
        LIMIT 5
    ");
    $activeBatches = $stmtBatches->fetchAll();

} catch (PDOException $e) {
    die("Lỗi truy vấn cơ sở dữ liệu: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProSync Industrial - Production Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --bg-dark: #0a1118; --bg-card: #0f1722; --accent-green: #10b981; --border-color: #1f2937; }
        body { background-color: var(--bg-dark); font-family: 'Inter', sans-serif; }
        /* Tùy chỉnh thanh cuộn cho mượt */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-dark); }
        ::-webkit-scrollbar-thumb { background: #1f2937; border-radius: 4px; }
    </style>
</head>
<body class="text-gray-300 min-h-screen flex">

    <aside class="w-64 bg-[#0f1722] border-r border-[#1f2937] flex flex-col hidden md:flex">
        <div class="p-6 border-b border-[#1f2937]">
            <h2 class="text-[#10b981] font-bold text-xl tracking-wide flex items-center gap-2">
                <img src="../image/353838036_746744254123717_8058064823033680293_n.jpg" alt="F&G FOOD logo" class="w-5 h-5 object-contain rounded" />
                F&G FOOD
            </h2>
            <p class="text-xs text-gray-500 mt-1">Production Unit 04</p>
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <a href="#" class="flex items-center gap-3 px-4 py-3 bg-[#10b981] text-gray-900 font-semibold rounded-md">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                Dashboard
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 text-gray-400 hover:text-white hover:bg-[#1f2937] rounded-md transition-colors">
                Inventory
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 text-gray-400 hover:text-white hover:bg-[#1f2937] rounded-md transition-colors">
                FEFO Alerts
            </a>
        </nav>
        <div class="p-4 border-t border-[#1f2937]">
            <a href="../backend/connection/logout.php" class="flex items-center gap-3 px-4 py-2 text-gray-400 hover:text-red-400 transition-colors">
                Logout
            </a>
        </div>
    </aside>

    <main class="flex-1 p-8">
        <header class="flex justify-between items-center mb-8 pb-4 border-b border-[#1f2937]">
            <h1 class="text-2xl font-bold text-[#10b981]">Dashboard Overview</h1>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <p class="text-sm font-semibold text-white"><?= htmlspecialchars($_SESSION['full_name']) ?></p>
                    <p class="text-xs text-gray-500">Production Manager</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-gray-700 overflow-hidden border-2 border-[#1f2937]">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['full_name']) ?>&background=10b981&color=fff" alt="Avatar">
                </div>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-[#0f1722] p-6 rounded-xl border border-[#1f2937]">
                <p class="text-xs text-gray-400 uppercase font-semibold mb-2">Yield Analytics</p>
                <div class="flex items-baseline gap-2">
                    <h3 class="text-4xl font-bold text-white"><?= $avgYield ?>%</h3>
                    <?php if ($avgYield > 0): ?>
                        <span class="text-xs text-[#10b981] font-medium">Recorded</span>
                    <?php else: ?>
                        <span class="text-xs text-gray-500 font-medium">No Data Yet</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="bg-[#0f1722] p-6 rounded-xl border border-[#1f2937]">
                <p class="text-xs text-gray-400 uppercase font-semibold mb-2">Export Demand</p>
                <h3 class="text-4xl font-bold text-white mb-1">0 <span class="text-sm text-gray-500">units</span></h3>
                <p class="text-xs text-[#10b981]">Awaiting new orders</p>
            </div>
            <div class="bg-[#7f1d1d] p-6 rounded-xl border border-red-500 relative overflow-hidden">
                <p class="text-xs text-red-200 uppercase font-semibold mb-2">Expiring Batches (48H)</p>
                <h3 class="text-5xl font-black text-white text-center my-2"><?= $fefoDisplay ?></h3>
                <p class="text-xs text-center text-red-100 mt-2 uppercase tracking-wide">
                    <?= $fefoCount > 0 ? 'Immediate FEFO Allocation Required' : 'All Stock Optimal' ?>
                </p>
            </div>
        </div>

        <div class="bg-[#0f1722] rounded-xl border border-[#1f2937] overflow-hidden">
            <div class="p-5 border-b border-[#1f2937] flex justify-between items-center">
                <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#10b981]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                    Current Production Status
                </h3>
                <button class="bg-[#10b981] hover:bg-[#059669] text-gray-900 text-sm font-bold py-2 px-4 rounded transition" 
                        onclick="alert('Please select a specific batch from the list below to allocate.')">
                    Allocate Batch
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#0a1118] text-xs uppercase text-gray-500 border-b border-[#1f2937]">
                            <th class="p-4 font-semibold">Batch ID</th>
                            <th class="p-4 font-semibold">Product Name</th>
                            <th class="p-4 font-semibold">Volume (Available)</th>
                            <th class="p-4 font-semibold">Expiry (FEFO)</th>
                            <th class="p-4 font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        <?php if (empty($activeBatches)): ?>
                            <tr><td colspan="5" class="p-8 text-center text-gray-500">No Active Batches Found</td></tr>
                        <?php else: ?>
                            <?php foreach ($activeBatches as $batch): ?>
                                <tr class="border-b border-[#1f2937] hover:bg-[#1f2937]/30 transition-colors">
                                    <td class="p-4 font-mono text-[#10b981] text-xs"><?= htmlspecialchars($batch['BCH_batch_id']) ?></td>
                                    <td class="p-4 text-white font-medium"><?= htmlspecialchars($batch['PRD_product_name']) ?></td>
                                    <td class="p-4 text-gray-300"><?= number_format($batch['BCH_available_stock_kg'], 2) ?> kg</td>
                                    <td class="p-4 text-gray-400"><?= date('Y-m-d', strtotime($batch['BCH_expiry_date'])) ?></td>
                                    <td class="p-4">
                                        <button onclick="openModal('<?= htmlspecialchars($batch['BCH_batch_id']) ?>', <?= $batch['BCH_available_stock_kg'] ?>)" 
                                                class="bg-[#10b981] hover:bg-[#059669] text-gray-900 px-3 py-1 rounded text-xs font-bold transition">
                                            Allocate
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="allocationModal" class="fixed inset-0 z-50 hidden bg-black/70 flex items-center justify-center">
        <div class="bg-[#0f1722] rounded-xl border border-[#1f2937] shadow-2xl w-full max-w-md overflow-hidden">
            <div class="p-4 border-b border-[#1f2937] flex justify-between items-center bg-[#0a1118]">
                <h3 class="text-white font-semibold">Confirm Material Allocation</h3>
                <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-white">✕</button>
            </div>
            <form action="../backend/connection/process_allocation.php" method="POST" class="p-6">
                <input type="hidden" name="batch_id" id="modal_batch_id">
                <div class="space-y-3 mb-6 text-sm">
                    <div class="flex justify-between border-b border-[#1f2937] pb-2">
                        <span class="text-gray-500">Selected Batch:</span>
                        <span class="text-[#10b981] font-mono" id="display_batch_id">--</span>
                    </div>
                    <div class="flex justify-between border-b border-[#1f2937] pb-2">
                        <span class="text-gray-500">Available Stock:</span>
                        <span class="text-white font-semibold" id="display_max_stock">0.00 kg</span>
                    </div>
                </div>
                <div class="mb-6">
                    <label class="block text-xs text-[#10b981] uppercase mb-2 font-semibold">Allocation Quantity (kg)</label>
                    <input type="number" step="0.01" name="allocate_qty" id="input_qty" required
                        class="w-full bg-[#0a1118] border border-[#1f2937] text-white text-lg rounded p-3 focus:outline-none focus:border-[#10b981] text-right">
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeModal()" class="flex-1 bg-transparent border border-[#1f2937] text-gray-300 py-3 rounded">CANCEL</button>
                    <button type="submit" class="flex-1 bg-[#10b981] text-gray-900 font-bold py-3 rounded">ALLOCATE</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(batchId, availableStock) {
            document.getElementById('modal_batch_id').value = batchId;
            document.getElementById('display_batch_id').innerText = batchId;
            document.getElementById('display_max_stock').innerText = parseFloat(availableStock).toFixed(2) + ' kg';
            let inputQty = document.getElementById('input_qty');
            inputQty.max = availableStock;
            inputQty.min = 1;
            document.getElementById('allocationModal').classList.remove('hidden');
        }
        function closeModal() {
            document.getElementById('allocationModal').classList.add('hidden');
        }
    </script>
</body>
</html>