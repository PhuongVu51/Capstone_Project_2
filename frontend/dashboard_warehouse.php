<?php
require_once '../backend/includes/auth.php';
require_role(['Warehouse_Staff', 'Production_Manager', 'Director'], 'login.php');
require_once '../backend/connection/db_connect.php';

try {
    // Total stock (use initial volume as requested)
    $stmtTotal = $pdo->query("SELECT SUM(BCH_initial_volume_kg) as total_kg FROM BATCHES");
    $totalKg = $stmtTotal->fetchColumn() ?? 0;
    $totalUnitsRaw = $totalKg / 5; // 1 unit = 5kg assumption
    $displayTotalUnits = $totalUnitsRaw >= 1000 ? number_format($totalUnitsRaw/1000,1)."k" : number_format($totalUnitsRaw,0);

    // Incoming today
    $stmtIncoming = $pdo->query("SELECT COUNT(*) FROM BATCHES WHERE DATE(BCH_received_date) = CURDATE()");
    $incomingCount = $stmtIncoming->fetchColumn();

    // Warehouse capacity across all zones
    $stmtCap = $pdo->query("SELECT SUM(STZ_current_load_kg) as cur_load, SUM(STZ_max_capacity_kg) as max_cap FROM STORAGE_ZONES");
    $cap = $stmtCap->fetch();
    $capCur = floatval($cap['cur_load'] ?? 0);
    $capMax = floatval($cap['max_cap'] ?? 0);
    $capacityPercent = $capMax > 0 ? ($capCur / $capMax) * 100 : 0;
    $remainingUnits = $capMax > $capCur ? number_format(($capMax - $capCur)/5,1).' units' : '0 units';

    // Recent movements
    $stmtMovements = $pdo->query(
        "SELECT s.STM_reference_code, s.STM_quantity_kg, s.STM_movement_type, s.STM_timestamp, b.BCH_batch_id, p.PRD_product_name
         FROM STOCK_MOVEMENTS s
         JOIN BATCHES b ON s.STM_batch_id = b.BCH_batch_id
         LEFT JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
         ORDER BY s.STM_timestamp DESC LIMIT 5"
    );
    $movements = $stmtMovements->fetchAll();

    // Node status for zone 1 (example)
    $node = $pdo->query("SELECT STZ_current_temp_c, STZ_current_humidity_pct FROM STORAGE_ZONES WHERE STZ_zone_id = 1")->fetch();

} catch (PDOException $e) { die("Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Warehouse Operations | F&G FOOD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #06121a; color: #d1d5db; font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="flex min-h-screen">

    <aside class="w-64 bg-[#0f1722] border-r border-[#1f2937] flex flex-col hidden md:flex">
        <div class="p-6 border-b border-[#1f2937]">
            <h2 class="text-[#10b981] font-bold text-xl tracking-wide flex items-center gap-2">
                <img src="../image/353838036_746744254123717_8058064823033680293_n.jpg" alt="F&G FOOD logo" class="w-5 h-5 object-contain rounded" />
                F&G FOOD
            </h2>
            <p class="text-xs text-gray-500 mt-1">Warehouse Unit 04</p>
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <a href="dashboard_warehouse.php" class="flex items-center gap-3 px-4 py-3 bg-[#10b981] text-gray-900 font-semibold rounded-md">Dashboard</a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 text-gray-400 hover:text-white rounded-md transition-colors">Inventory</a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 text-gray-400 hover:text-white rounded-md transition-colors">Batches</a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 text-gray-400 hover:text-white rounded-md transition-colors">Reports</a>
        </nav>
        <div class="mt-auto p-4 border-t border-[#1f2937]">
            <a href="../backend/connection/logout.php" class="flex items-center gap-3 px-4 py-2 text-gray-400 hover:text-red-400 transition-colors">
                Logout
            </a>
        </div>
    </aside>

    <main class="flex-1 p-8">
        <header class="flex justify-between items-start mb-8">
            <div>
                <h1 class="text-3xl font-bold text-[#10b981]">Node 04 Operations</h1>
                <p class="text-sm text-gray-400 mt-1">Live operational metrics and throughput tracking for current shift.</p>
            </div>

            <div class="flex items-center gap-4">
                <a href="export_report.php" class="inline-block bg-transparent border border-[#203434] text-[#cfeee0] px-4 py-2 rounded">Export Report</a>
                <a href="log_batch.php" class="inline-block bg-[#10b981] text-gray-900 font-bold px-4 py-2 rounded">+ Log New Batch</a>
                <div class="ml-4 text-right">
                    <p class="text-sm font-semibold text-white"><?= htmlspecialchars($_SESSION['full_name']) ?></p>
                    <p class="text-xs text-gray-400">Warehouse Staff</p>
                </div>
                <div class="w-10 h-10 ml-2 rounded-full bg-[#0fd081] flex items-center justify-center font-bold text-black">
                    <?= htmlspecialchars(substr($_SESSION['full_name'],0,2)) ?></div>
            </div>
        </header>

        <div class="grid grid-cols-12 gap-6 mb-6">
            <div class="col-span-12 lg:col-span-4">
                <div class="grid grid-cols-1 gap-4">
                    <div class="bg-[#07121a] p-6 rounded-lg border border-[#102027]">
                        <p class="text-xs text-gray-400 uppercase">Total Stock</p>
                        <div class="flex items-baseline justify-between">
                            <h3 class="text-3xl font-bold text-white mt-2"><?= $displayTotalUnits ?> <span class="text-sm text-gray-400">units</span></h3>
                            <div class="text-xs text-green-400">+2.4% vs prev week</div>
                        </div>
                    </div>

                    <div class="bg-[#07121a] p-6 rounded-lg border border-[#102027]">
                        <p class="text-xs text-gray-400 uppercase">Incoming Today</p>
                        <h3 class="text-2xl font-bold text-white mt-2"><?= $incomingCount ?> <span class="text-sm text-gray-400">batches</span></h3>
                        <p class="text-xs text-gray-400 mt-2"><?php $pending = $pdo->query("SELECT COUNT(*) FROM BATCHES WHERE BCH_current_stage = 'Pending_QC'")->fetchColumn(); ?><?= $pending ?> pending validation</p>
                    </div>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-8">
                <div class="bg-[#07121a] p-6 rounded-lg border border-[#102027]">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-xs text-gray-400 uppercase">Warehouse Capacity</p>
                            <h3 class="text-2xl font-bold text-white mt-1"><?= number_format($capacityPercent,0) ?>%</h3>
                        </div>
                        <div class="text-sm text-gray-400"><?= $remainingUnits ?> remaining</div>
                    </div>
                    <div class="mt-4 bg-[#04121a] rounded-full h-3 overflow-hidden border border-[#0f2b22]">
                        <div style="width:<?= min(100, $capacityPercent) ?>%" class="h-3 bg-gradient-to-r from-[#0fd081] to-[#10b981]"></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500 mt-2"><span>Critical at 95%</span><span>Optimal range</span></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12 lg:col-span-8">
                <div class="bg-[#07121a] p-6 rounded-lg border border-[#102027]">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-white">Live Stock Movements</h3>
                        <div class="flex items-center gap-3 text-sm text-gray-400">
                            <button title="Refresh" class="p-2 bg-[#061b1a] rounded">↻</button>
                            <button title="Filter" class="p-2 bg-[#061b1a] rounded">☰</button>
                        </div>
                    </div>
                    <div class="overflow-hidden">
                        <table class="w-full text-left">
                            <thead class="text-gray-400 text-xs uppercase bg-[#041a1a]">
                                <tr><th class="p-3">Batch ID</th><th class="p-3">Commodity</th><th class="p-3">Quantity</th><th class="p-3">Status</th><th class="p-3">Time</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($movements)): ?>
                                    <tr><td colspan="5" class="p-6 text-center text-gray-500">No movements found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($movements as $m): ?>
                                        <tr class="border-t border-[#0f2420]">
                                            <td class="p-3 text-[#10b981] font-mono"><?= htmlspecialchars($m['BCH_batch_id'] ?? $m['STM_reference_code']) ?></td>
                                            <td class="p-3"><?= htmlspecialchars($m['PRD_product_name'] ?? '') ?></td>
                                            <td class="p-3"><?= number_format($m['STM_quantity_kg'],0) ?> kg</td>
                                            <td class="p-3"><span class="text-xs bg-[#0d3b2f] text-[#9ff1d1] px-2 py-1 rounded"><?= htmlspecialchars($m['STM_movement_type']) ?></span></td>
                                            <td class="p-3 text-sm text-gray-400"><?= date('H:i:s', strtotime($m['STM_timestamp'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-4">
                <div class="bg-[#07121a] p-6 rounded-lg border border-[#102027]">
                    <h3 class="text-lg font-semibold text-white mb-4">Node Status</h3>
                    <div class="mb-4">
                        <img src="/assets/cam-placeholder.jpg" alt="Camera" class="w-full rounded-lg border border-[#09201b]"> 
                    </div>
                    <div class="space-y-3">
                        <div>
                            <div class="flex justify-between text-sm text-gray-400"><span>Environmental Temp</span><span class="text-white"><?= isset($node['STZ_current_temp_c']) ? htmlspecialchars($node['STZ_current_temp_c']).'°C' : '—' ?></span></div>
                            <div class="mt-2 bg-[#04121a] rounded-full h-2"><div style="width:<?= isset($node['STZ_current_temp_c']) ? min(100,($node['STZ_current_temp_c']+10)*2) : 0 ?>%" class="h-2 bg-[#10b981]"></div></div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm text-gray-400"><span>Humidity Level</span><span class="text-white"><?= isset($node['STZ_current_humidity_pct']) ? htmlspecialchars($node['STZ_current_humidity_pct']).'%' : '—' ?></span></div>
                            <div class="mt-2 bg-[#04121a] rounded-full h-2"><div style="width:<?= isset($node['STZ_current_humidity_pct']) ? min(100,$node['STZ_current_humidity_pct']) : 0 ?>%" class="h-2 bg-[#0fd081]"></div></div>
                        </div>
                        <div class="mt-4 p-3 bg-[#05171a] rounded border border-[#0f2923]">
                            <p class="text-xs text-[#10b981]">● Systems Nominal</p>
                            <p class="text-[12px] text-gray-400">All automation nodes reporting nominal performance.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>