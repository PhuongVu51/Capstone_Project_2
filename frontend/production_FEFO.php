<?php
// Đường dẫn: frontend/production_FEFO.php
require_once '../backend/includes/auth.php';
require_role(['Production_Manager', 'Director'], 'login.php');
require_once '../backend/connection/db_connect.php';

try {
    // Truy vấn các lô hàng sắp hết hạn trong 48h tới
    $sql = "
        SELECT 
            b.BCH_batch_id, 
            p.PRD_product_name, 
            b.BCH_expiry_date, 
            b.BCH_available_stock_kg, 
            z.STZ_zone_name, 
            p.PRD_unit_price
        FROM BATCHES b
        JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
        JOIN STORAGE_ZONES z ON b.BCH_zone_id = z.STZ_zone_id
        WHERE b.BCH_available_stock_kg > 0 
        AND b.BCH_expiry_date <= DATE_ADD(NOW(), INTERVAL 48 HOUR)
        ORDER BY b.BCH_expiry_date ASC
    ";
    $stmt = $pdo->query($sql);
    $expiringBatches = $stmt->fetchAll();

    // Tính toán chỉ số KPIs
    $totalRiskBatches = count($expiringBatches);
    $valueAtStake = 0;
    foreach($expiringBatches as $batch) {
        $valueAtStake += ($batch['BCH_available_stock_kg'] * $batch['PRD_unit_price']);
    }
} catch (PDOException $e) {
    die("Lỗi truy vấn CSDL: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FEFO Alerts | ProSync Industrial</title>
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
        <!-- TOP NAV -->
        <header class="h-16 border-b border-[#1f2937] bg-[#0a1118] flex items-center justify-between px-8 sticky top-0 z-10">
            <div class="flex-1 max-w-xl">
                <div class="relative">
                    <input type="text" placeholder="Search batch or product..." class="w-full bg-[#0f1722] border border-[#1f2937] text-sm text-gray-300 rounded py-2 pl-10 pr-4 focus:outline-none focus:border-[#10b981] transition-colors">
                    <svg class="w-4 h-4 absolute left-3 top-2.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
            <div class="flex items-center gap-4 ml-4">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-semibold text-white"><?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?></p>
                </div>
                <div class="w-8 h-8 rounded-full bg-gray-700 overflow-hidden border border-[#1f2937]">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['full_name'] ?? 'User') ?>&background=10b981&color=fff" alt="Avatar">
                </div>
            </div>
        </header>

        <div class="p-8 overflow-y-auto">
            
            <!-- HEADER TITLES -->
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-end mb-8 gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="flex items-center justify-center w-5 h-5 rounded-full bg-red-500 text-white text-xs font-bold">!</span>
                        <span class="text-red-500 text-sm font-bold uppercase tracking-wider">Critical Alert Level</span>
                    </div>
                    <h1 class="text-3xl font-bold text-[#10b981] mb-2">FEFO Red Alerts</h1>
                    <p class="text-gray-400 text-sm max-w-2xl">First-Expired, First-Out (FEFO) critical priority inventory. The following batches are within 48 hours of expiry and must be allocated immediately.</p>
                </div>
                
                <div class="flex gap-4">
                    <div class="bg-[#b91c1c] p-4 rounded-lg min-w-[140px] text-center border border-red-500/50 shadow-[0_0_15px_rgba(220,38,38,0.2)]">
                        <p class="text-[10px] text-red-200 uppercase font-bold tracking-wider mb-1">Total at risk</p>
                        <h3 class="text-2xl font-bold text-white"><?= $totalRiskBatches ?> Batches</h3>
                    </div>
                    <div class="bg-[#0f1722] p-4 rounded-lg min-w-[140px] text-center border border-[#1f2937]">
                        <p class="text-[10px] text-gray-500 uppercase font-bold tracking-wider mb-1">Value at stake</p>
                        <h3 class="text-2xl font-bold text-[#10b981]">$<?= number_format($valueAtStake, 2) ?></h3>
                    </div>
                </div>
            </div>

            <!-- CHARTS ROW -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <div class="bg-[#0f1722] p-5 rounded-lg border border-[#1f2937]">
                    <h3 class="text-[#10b981] font-bold text-sm mb-1">Expiration Velocity</h3>
                    <p class="text-xs text-gray-500 mb-4">Batches expiring per shift</p>
                    <div class="h-32 flex items-end justify-between gap-2 px-2">
                        <div class="w-full bg-red-300 h-10 rounded-t"></div>
                        <div class="w-full bg-red-400 h-20 rounded-t"></div>
                        <div class="w-full bg-red-300 h-14 rounded-t"></div>
                        <div class="w-full bg-red-400 h-28 rounded-t"></div>
                        <div class="w-full bg-red-500/50 h-16 rounded-t"></div>
                        <div class="w-full bg-red-400 h-24 rounded-t"></div>
                    </div>
                </div>
                <div class="lg:col-span-2 bg-[#0f1722] p-5 rounded-lg border border-[#1f2937] flex items-center justify-between">
                    <div class="flex-1 pr-8">
                        <h3 class="text-[#10b981] font-bold text-sm mb-6">Storage Zones Affected</h3>
                        <div class="mb-4">
                            <div class="flex justify-between text-xs font-bold text-white mb-2"><span>Cold Storage Alpha</span><span>65% Capacity</span></div>
                            <div class="w-full bg-[#0a1118] h-2 rounded-full overflow-hidden border border-[#1f2937]">
                                <div class="bg-[#10b981] h-2" style="width: 65%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs font-bold text-white mb-2"><span>Ambient Sector 4</span><span>22% Capacity</span></div>
                            <div class="w-full bg-[#0a1118] h-2 rounded-full overflow-hidden border border-[#1f2937]">
                                <div class="bg-[#374151] h-2" style="width: 22%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="w-40 h-40 bg-[#0a1118] rounded-xl border border-[#1f2937] flex items-center justify-center relative">
                        <div class="text-center z-10">
                            <h2 class="text-3xl font-bold text-[#10b981]">75%</h2>
                            <p class="text-[10px] text-gray-500 uppercase tracking-widest mt-1">Waste Risk</p>
                        </div>
                        <svg class="absolute inset-0 w-full h-full text-red-400/20" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="currentColor" stroke-width="8" stroke-dasharray="60 180" stroke-linecap="round"/></svg>
                    </div>
                </div>
            </div>

            <!-- DATA TABLE -->
            <div class="bg-[#0f1722] rounded-lg border border-[#1f2937] overflow-hidden">
                <div class="p-5 border-b border-[#1f2937] flex justify-between items-center bg-[#0b121c]">
                    <div class="flex items-center gap-3">
                        <h3 class="text-[#10b981] font-bold">Expiring Batches List</h3>
                        <span class="bg-[#b91c1c] text-white text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded">Active Alerts</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-[#0a1118] text-[11px] uppercase tracking-wider text-gray-500 border-b border-[#374151]">
                                <th class="p-4 font-bold">Batch ID</th>
                                <th class="p-4 font-bold">Product Name</th>
                                <th class="p-4 font-bold">Expiry Date</th>
                                <th class="p-4 font-bold">Current Quantity</th>
                                <th class="p-4 font-bold">Storage Zone</th>
                                <th class="p-4 font-bold text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-[#1f2937]/50">
                            <?php if (empty($expiringBatches)): ?>
                                <tr><td colspan="6" class="p-8 text-center text-gray-500 italic">No critical batches found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($expiringBatches as $batch): ?>
                                    <tr class="hover:bg-[#131c26] transition-colors border-l-2 border-transparent hover:border-red-500">
                                        <td class="p-4">
                                            <span class="bg-[#10b981] text-gray-900 font-mono font-bold text-xs px-2 py-1 rounded inline-block max-w-[120px] truncate" title="<?= htmlspecialchars($batch['BCH_batch_id']) ?>">
                                                <?= htmlspecialchars($batch['BCH_batch_id']) ?>
                                            </span>
                                        </td>
                                        <td class="p-4 text-white font-medium flex items-center gap-3">
                                            <div class="w-8 h-8 rounded bg-[#1f2937] flex items-center justify-center border border-[#374151] shrink-0">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                            </div>
                                            <span class="truncate max-w-[180px]" title="<?= htmlspecialchars($batch['PRD_product_name']) ?>">
                                                <?= htmlspecialchars($batch['PRD_product_name']) ?>
                                            </span>
                                        </td>
                                        <td class="p-4 font-mono text-red-300 text-xs flex items-center gap-2">
                                            <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            <?= date('Y-m-d H:i', strtotime($batch['BCH_expiry_date'])) ?>
                                        </td>
                                        <td class="p-4 font-mono text-gray-200"><?= number_format($batch['BCH_available_stock_kg'], 2) ?> <span class="text-xs text-gray-500 font-sans">kg</span></td>
                                        <td class="p-4 text-gray-400 text-xs"><?= htmlspecialchars($batch['STZ_zone_name']) ?></td>
                                        <td class="p-4 text-center">
                                            <button onclick="openModal('<?= htmlspecialchars($batch['BCH_batch_id']) ?>', <?= $batch['BCH_available_stock_kg'] ?>)" 
                                                    class="border border-[#10b981] text-[#10b981] hover:bg-[#10b981] hover:text-gray-900 px-4 py-1.5 rounded text-xs font-bold transition-colors shadow-[0_0_10px_rgba(16,185,129,0.1)]">
                                                View Details
                                            </button>
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

    <!-- MODAL ALLOCATION -->
    <div id="allocationModal" class="fixed inset-0 z-50 hidden bg-black/80 backdrop-blur-sm flex items-center justify-center">
        <div class="bg-[#0f1722] rounded-xl border border-[#1f2937] shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
            <div class="p-4 border-b border-[#1f2937] flex justify-between items-center bg-[#0a1118]">
                <h3 class="text-[#10b981] font-bold flex items-center gap-2">
                    <svg class="w-4 h-4 text-red-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    Emergency FEFO Allocation
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
                        <span class="text-red-300 font-mono font-bold" id="display_max_stock">0.00 kg</span>
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
                    <p class="text-[10px] text-yellow-500 mt-2 font-medium">⚠️ Note: Letters and special characters are restricted.</p>
                </div>
                
                <div class="flex gap-3">
                    <button type="button" onclick="closeModal()" class="flex-1 bg-transparent hover:bg-[#1f2937] border border-[#374151] text-gray-300 text-sm font-bold py-3 rounded transition-colors">CANCEL</button>
                    <button type="submit" class="flex-1 bg-[#10b981] hover:bg-[#059669] text-gray-900 text-sm font-bold py-3 rounded transition-colors shadow-[0_0_15px_rgba(16,185,129,0.3)]">ALLOCATE NOW</button>
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
            inputQty.value = '';
            
            document.getElementById('allocationModal').classList.remove('hidden');
        }
        function closeModal() {
            document.getElementById('allocationModal').classList.add('hidden');
        }
    </script>
</body>
</html>