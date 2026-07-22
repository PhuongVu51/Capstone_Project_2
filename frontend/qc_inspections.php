<?php
// Đường dẫn: frontend/qc_inspections.php
require_once '../backend/includes/auth.php';
require_role(['QC', 'Production_Manager', 'Director'], 'login.php');
require_once '../backend/controllers/QcInspectionController.php';

$controller = new QcInspectionController();
$viewData = $controller->handleListQueue();
extract($viewData);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QC Pending Inspections | ProSync</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #06121a; color: #d1d5db; font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen overflow-x-hidden">

    <?php include 'includes/qc_sidebar.php'; ?>

    <main class="md:ml-64 p-6 md:p-8 pt-24 md:pt-8 w-full md:w-[calc(100%-256px)] flex flex-col justify-between min-h-screen">
        <div>
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8 pb-4 border-b border-[#1f2937] gap-4">
                <div class="flex flex-col">
                    <h1 class="text-2xl font-bold text-white whitespace-nowrap tracking-wide">Pending Inspections</h1>
                </div>
                
                <div class="relative w-full lg:max-w-md flex-1">
                    <input type="text" id="searchInput" onkeyup="filterQueue()" placeholder="Search batches or products..." 
                           class="w-full bg-[#0f1722] border border-[#1f2937] text-sm text-gray-300 rounded py-2.5 pl-10 pr-4 focus:outline-none focus:border-[#10b981] transition-colors">
                    <svg class="w-4 h-4 absolute left-3.5 top-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </header>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
                <div class="bg-[#0f1722] p-5 rounded-lg border border-[#1f2937]">
                    <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider">Active Queue</p>
                    <h3 class="text-3xl font-bold text-white mt-2 font-mono"><?= $activeCount ?></h3>
                    <div class="flex items-center gap-1 mt-2 text-[11px]">
                        <?php if($highCount > 0): ?>
                            <span class="text-red-400 font-bold tracking-wide">⚠ <?= $highCount ?> HIGH PRIORITY</span>
                        <?php else: ?>
                            <span class="text-gray-500">Normal load</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-[#0f1722] p-5 rounded-lg border border-[#1f2937]">
                    <div class="flex justify-between items-center">
                        <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider">Batches Processed</p>
                        <?php $progressPct = ($totalBatches > 0) ? round(($processedCount / $totalBatches) * 100) : 0; ?>
                        <span class="text-[10px] text-[#10b981] font-bold"><?= $progressPct ?>%</span>
                    </div>
                    
                    <h3 class="text-3xl font-bold text-white mt-2 font-mono">
                        <?= number_format($processedCount) ?> <span class="text-lg text-gray-600 font-normal">/ <?= number_format($totalBatches) ?></span>
                    </h3>
                    
                    <div class="mt-3 bg-[#04121a] rounded-full h-1 w-full overflow-hidden border border-[#1f2937]">
                        <div class="bg-[#10b981] h-1 rounded-full shadow-[0_0_8px_#10b981]" style="width: <?= $progressPct ?>%"></div>
                    </div>
                </div>

                <div class="bg-[#0f1722] p-5 rounded-lg border border-[#1f2937]">
                    <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider">Average Lead Time</p>
                    <h3 class="text-3xl font-bold text-white mt-2 font-mono"><?= $avgLeadTimeMins ?> <span class="text-sm text-gray-500 font-normal">mins</span></h3>
                    <div class="flex items-center gap-1 mt-2">
                        <?php if ($isDecrease): ?>
                            <svg class="w-3 h-3 text-[#10b981]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                            <p class="text-[11px] text-[#10b981] font-medium"><?= $leadTimePct ?>% from last shift</p>
                        <?php else: ?>
                            <svg class="w-3 h-3 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                            <p class="text-[11px] text-red-400 font-medium">+<?= $leadTimePct ?>% from last shift</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-end gap-4 mb-4">
                <div class="flex items-center gap-3">
                    <select id="priorityFilter" onchange="filterQueue()" class="bg-[#0f1722] border border-[#1f2937] text-sm text-gray-300 rounded px-3 py-2 outline-none focus:border-[#10b981]">
                        <option value="">All Priorities</option>
                        <option value="High">High Priority</option>
                        <option value="Medium">Medium Priority</option>
                    </select>
                </div>
            </div>

            <div class="bg-[#0f1722] rounded-lg border border-[#1f2937] overflow-hidden flex flex-col min-w-0">
                <div class="p-5 border-b border-[#1f2937] flex justify-between items-center bg-[#0b121c]">
                    <div>
                        <h3 class="text-base font-bold text-white">Inspection Queue</h3>
                    </div>
                    <span class="text-xs bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-3 py-1 rounded-full font-mono font-bold flex items-center gap-2 shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Auto-Refresh: ON
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse" id="queueTable">
                        <thead class="text-gray-500 text-[10px] uppercase bg-[#0b121c] sticky top-0 z-10">
                            <tr>
                                <th class="py-3 px-5 font-semibold tracking-wider">Batch ID</th>
                                <th class="py-3 px-5 font-semibold tracking-wider">Product Name</th>
                                <th class="py-3 px-5 font-semibold tracking-wider">Received Date</th>
                                <th class="py-3 px-5 font-semibold tracking-wider text-right">Quantity</th>
                                <th class="py-3 px-5 font-semibold tracking-wider text-center">Priority</th>
                                <th class="py-3 px-5 font-semibold tracking-wider text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-[#1f2937]">
                            <?php if (empty($queue)): ?>
                                <tr><td colspan="6" class="p-8 text-center text-gray-600 italic">No batches currently awaiting inspection.</td></tr>
                            <?php else: ?>
                                <?php foreach ($queue as $item): ?>
                                    <tr class="hover:bg-[#131c26] transition-colors queue-row">
                                        <td class="py-4 px-5 text-[#10b981] font-mono font-semibold search-target">#<?= htmlspecialchars($item['BCH_batch_id']) ?></td>
                                        <td class="py-4 px-5 text-gray-200 font-medium search-target"><?= htmlspecialchars($item['PRD_product_name']) ?></td>
                                        <td class="py-4 px-5 text-gray-500 font-mono text-xs"><?= date('Y-m-d H:i', strtotime($item['BCH_received_date'])) ?></td>
                                        <td class="py-4 px-5 text-right text-gray-200 font-mono"><?= number_format($item['BCH_initial_volume_kg'], 1) ?> KG</td>
                                        <td class="py-4 px-5 text-center priority-target">
                                            <?php if (strtolower($item['BCH_priority']) === 'high'): ?>
                                                <span class="text-red-400 bg-red-500/10 border border-red-500/20 px-2.5 py-1 rounded text-[10px] font-bold uppercase tracking-wider">High</span>
                                            <?php else: ?>
                                                <span class="text-yellow-400 bg-yellow-500/10 border border-yellow-500/20 px-2.5 py-1 rounded text-[10px] font-bold uppercase tracking-wider">Medium</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4 px-5 text-center">
                                            <a href="qc_perform_inspection.php?batch_id=<?= urlencode($item['BCH_batch_id']) ?>" 
                                               class="inline-block bg-[#1f2937] border border-[#374151] text-gray-300 font-bold px-4 py-2 rounded text-xs hover:bg-[#10b981] hover:text-gray-900 transition-all shadow-sm tracking-wide">
                                                START INSPECTION
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="p-4 border-t border-[#1f2937] bg-[#0b121c] flex justify-between items-center flex-col sm:flex-row gap-4">
                    <p class="text-xs text-gray-500">Showing <span class="text-gray-300 font-semibold">1-<?= count($queue) ?></span> of <span class="text-gray-300 font-semibold"><?= $activeCount ?></span> inspection batches</p>
                    <div class="flex items-center gap-1 border border-[#1f2937] rounded overflow-hidden">
                        <button class="px-3 py-1.5 bg-[#0f1722] text-gray-500 text-xs hover:text-white disabled:opacity-50" disabled>&larr;</button>
                        <span class="text-xs text-gray-900 font-bold bg-[#10b981] px-3 py-1.5">1</span>
                        <button class="px-3 py-1.5 bg-[#0f1722] text-gray-400 text-xs hover:text-white hover:bg-[#1f2937]">2</button>
                        <button class="px-3 py-1.5 bg-[#0f1722] text-gray-400 text-xs hover:text-white hover:bg-[#1f2937]">&rarr;</button>
                    </div>
                </div>
            </div>
        </div>

        <footer class="mt-8 pt-4 border-t border-[#1f2937] flex flex-col sm:flex-row justify-between text-[11px] text-gray-600 font-medium gap-2">
            <div>Last synchronized with ERP: <span id="syncTimeDisplay" class="text-gray-400 font-bold">Just now</span></div>
            <div class="flex items-center gap-1">
                <svg class="w-3 h-3 text-[#10b981]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                All industrial inspections must adhere to ISO 9001:2015 safety standards.
            </div>
        </footer>
    </main>

    <script>
        // --- TÌM KIẾM ---
        function filterQueue() {
            let input = document.getElementById("searchInput").value.toUpperCase();
            let priorityFilter = document.getElementById("priorityFilter").value.toUpperCase();
            let rows = document.getElementsByClassName("queue-row");

            for (let i = 0; i < rows.length; i++) {
                let textMatch = false;
                let priorityMatch = true;

                let targets = rows[i].getElementsByClassName("search-target");
                let rowText = "";
                for(let j=0; j<targets.length; j++) {
                    rowText += targets[j].innerText.toUpperCase() + " ";
                }
                if(rowText.indexOf(input) > -1) textMatch = true;

                if (priorityFilter !== "") {
                    let pTarget = rows[i].getElementsByClassName("priority-target")[0].innerText.toUpperCase();
                    if(pTarget.indexOf(priorityFilter) === -1) priorityMatch = false;
                }

                if (textMatch && priorityMatch) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
            }
        }

        // --- ĐỒNG HỒ TỰ ĐỘNG ĐẾM GIỜ SYNC ERP ---
        const lastSyncTimestamp = <?= $lastSyncTime ?> * 1000; 

        function updateSyncTime() {
            const now = new Date().getTime();
            const diffMins = Math.floor((now - lastSyncTimestamp) / 60000);
            const display = document.getElementById('syncTimeDisplay');
            
            if (diffMins === 0) {
                display.innerText = "Just now";
            } else if (diffMins === 1) {
                display.innerText = "1 minute ago";
            } else {
                display.innerText = diffMins + " minutes ago";
            }
        }
        // Cho chạy mỗi 60 giây (60000ms) để cập nhật thời gian
        setInterval(updateSyncTime, 60000);
    </script>
</body>
</html>