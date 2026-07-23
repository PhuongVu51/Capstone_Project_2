<?php
// Đường dẫn: frontend/qc_dashboard.php

require_once '../backend/includes/auth.php';
require_role(['QC', 'Production_Manager', 'Director'], 'login.php');
require_once '../backend/controllers/QcDashboardController.php';

try {
    $controller = new QcDashboardController();
    $data = $controller->loadDashboard();
    extract($data); 

} catch (Exception $e) {
    die("Lỗi Hệ thống: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProSync Industrial - QC Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #06121a; color: #d1d5db; font-family: 'Inter', sans-serif; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #0f1722; }
        ::-webkit-scrollbar-thumb { background: #1f2937; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #374151; }
    </style>
</head>
<body class="min-h-screen overflow-x-hidden"> 

    <?php include 'includes/sidebar.php'; ?>

    <main class="md:ml-64 p-6 md:p-8 pt-24 md:pt-8 transition-all duration-300 w-full md:w-[calc(100%-256px)]">
        
        <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8 pb-4 border-b border-[#1f2937] gap-4">
            <h1 class="text-2xl font-bold text-white whitespace-nowrap tracking-wide">QC Overview</h1>
            <div class="relative w-full lg:max-w-md flex-1">
                <input type="text" id="searchInput" onkeyup="filterLogs()" placeholder="Search batch ID or sensor..." class="w-full bg-[#0f1722] border border-[#1f2937] text-sm text-gray-300 rounded py-2.5 pl-10 pr-4 focus:outline-none focus:border-[#10b981] transition-colors">
                <svg class="w-4 h-4 absolute left-3.5 top-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </header>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <div class="bg-[#0f1722] p-5 rounded-lg border border-[#1f2937] relative overflow-hidden">
                <p class="text-[11px] text-gray-500 uppercase font-semibold tracking-wider">Quality Pass Rate</p>
                <h3 class="text-3xl font-bold text-white mt-2 font-mono"><?= $passRate ?>%</h3>
                <div class="flex items-center gap-1 mt-2">
                    <svg class="w-3 h-3 text-[#10b981]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                    <p class="text-[11px] text-[#10b981] font-medium">2.4% since last shift</p>
                </div>
            </div>

            <div class="bg-[#0f1722] p-5 rounded-lg border border-[#1f2937]">
                <p class="text-[11px] text-gray-500 uppercase font-semibold tracking-wider">Pending Batch List</p>
                <h3 class="text-3xl font-bold text-white mt-2 font-mono"><?= $pendingCount ?></h3>
                <p class="text-[11px] text-yellow-500 mt-2 font-medium">+3 since previous hour</p>
            </div>

            <div class="bg-[#0f1722] p-5 rounded-lg border border-[#1f2937] flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <p class="text-[11px] text-gray-500 uppercase font-semibold tracking-wider">Defect Ratio</p>
                    <span class="bg-[#10b981]/20 text-[#10b981] text-[10px] px-2 py-0.5 rounded border border-[#10b981]/30 font-bold uppercase tracking-wide">Stable</span>
                </div>
                <h3 class="text-3xl font-bold text-white mt-2 font-mono"><?= $defectRatio ?>%</h3>
                <p class="text-[11px] text-gray-500 mt-2">Within acceptable range (< 5%)</p>
            </div>

            <div class="bg-[#0f1722] p-5 rounded-lg border border-[#1f2937]">
                <p class="text-[11px] text-gray-500 uppercase font-semibold tracking-wider">Instrument Status</p>
                <div class="flex items-center gap-2 mt-3">
                    <span class="relative flex h-3 w-3">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#10b981] opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-3 w-3 bg-[#10b981]"></span>
                    </span>
                    <h3 class="text-xl font-bold text-white">Active</h3>
                </div>
                <p class="text-[11px] text-gray-500 mt-2">Last check: 10 mins ago</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-[#0f1722] rounded-lg border border-[#1f2937] p-5 min-w-0">
                <div class="flex justify-between items-center mb-1">
                    <h3 class="text-base font-bold text-white">QC Pass/Fail Trends</h3>
                    <select class="bg-[#1f2937] border border-[#374151] text-xs text-gray-300 rounded px-2 py-1 outline-none focus:border-[#10b981]">
                        <option>Today</option>
                        <option selected>Last 7 Days</option>
                        <option>This Month</option>
                    </select>
                </div>
                <p class="text-xs text-gray-500 mb-4">Hourly distribution across Shift Alpha</p>
                <div class="relative h-[240px] w-full"><canvas id="trendsChart"></canvas></div>
                <div class="flex items-center justify-center gap-6 mt-4 text-xs font-medium text-gray-400">
                    <span class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-[#10b981]"></span>Pass Rate</span>
                    <span class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-[#ef4444]"></span>Defect Rate</span>
                </div>
            </div>

            <div class="bg-[#0f1722] rounded-lg border border-[#1f2937] overflow-hidden min-w-0 flex flex-col">
                <div class="p-5 border-b border-[#1f2937]">
                    <h3 class="text-base font-bold text-white">Recent Activity</h3>
                    <p class="text-xs text-gray-500 mt-1">Latest inspection logs</p>
                </div>
                <div class="overflow-y-auto flex-1 p-0">
                    <table class="w-full text-left border-collapse" id="logsTable">
                        <thead class="text-gray-500 text-[10px] uppercase bg-[#0b121c] sticky top-0 z-10">
                            <tr>
                                <th class="py-3 px-5 font-semibold tracking-wider">Batch ID</th>
                                <th class="py-3 px-5 font-semibold tracking-wider text-right">Usable</th>
                                <th class="py-3 px-5 font-semibold tracking-wider text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-[#1f2937]">
                            <?php if (empty($recentActivities)): ?>
                                <tr><td colspan="3" class="p-5 text-center text-gray-600 text-xs italic">No logs found yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentActivities as $act): ?>
                                    <tr class="hover:bg-[#131c26] transition-colors log-row">
                                        <td class="py-3 px-5 text-[#10b981] font-mono text-xs font-semibold batch-id">#<?= htmlspecialchars($act['QCI_batch_id']) ?></td>
                                        <td class="py-3 px-5 text-gray-200 text-right font-mono text-xs"><?= number_format($act['QCI_usable_weight_kg'], 1) ?> kg</td>
                                        <td class="py-3 px-5 destination text-xs text-center">
                                            <?php if(strtolower($act['QCI_destination']) == 'production'): ?>
                                                <span class="text-[#10b981] bg-[#10b981]/10 px-2 py-1 rounded">Passed</span>
                                            <?php else: ?>
                                                <span class="text-gray-400"><?= htmlspecialchars($act['QCI_destination'] ?: 'Pending') ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-t border-[#1f2937] text-center bg-[#0b121c]">
                    <a href="qc_inspections.php" class="text-xs text-[#10b981] hover:text-white font-semibold transition-colors">View All Inspections →</a>
                </div>
            </div>
        </div>
    </main>

    <script>
        function filterLogs() {
            let input = document.getElementById("searchInput").value.toUpperCase();
            let table = document.getElementById("logsTable");
            let rows = table.getElementsByClassName("log-row");
            for (let i = 0; i < rows.length; i++) {
                let batchId = rows[i].getElementsByClassName("batch-id")[0].innerText;
                let destination = rows[i].getElementsByClassName("destination")[0].innerText;
                let rowText = (batchId + " " + destination).toUpperCase();
                rows[i].style.display = rowText.indexOf(input) > -1 ? "" : "none";
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const trendLabels = <?= json_encode($trendLabels) ?>;
        const trendPass = <?= json_encode($trendPass) ?>;
        const trendFail = <?= json_encode($trendFail) ?>;

        (function renderTrends(){
            const ctx = document.getElementById('trendsChart');
            if (!ctx) return;
            Chart.defaults.color = '#6b7280';
            Chart.defaults.font.family = 'Inter';
            new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: trendLabels.length ? trendLabels : ['No Data'],
                    datasets: [
                        { label: 'Pass %', data: trendPass.length ? trendPass : [0], backgroundColor: '#10b981', borderRadius: 4, barThickness: 12 },
                        { label: 'Fail %', data: trendFail.length ? trendFail : [0], backgroundColor: '#ef4444', borderRadius: 4, barThickness: 12 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1f2937', titleColor: '#fff', bodyColor: '#d1d5db', borderColor: '#374151', borderWidth: 1, padding: 10 } },
                    scales: {
                        y: { beginAtZero: true, max: 100, grid: { color: '#1f2937', drawBorder: false }, ticks: { stepSize: 25, callback: function(value) { return value + '%' } } },
                        x: { grid: { display: false, drawBorder: false } }
                    }
                }
            });
        })();
    </script>
</body>
</html>