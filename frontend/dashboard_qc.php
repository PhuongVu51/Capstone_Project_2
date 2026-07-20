<?php
// Đường dẫn: frontend/dashboard_qc.php
require_once '../backend/includes/auth.php';
require_role(['QC', 'Production_Manager', 'Director'], 'login.php');
require_once '../backend/connection/db_connect.php';

try {
    // 1. DỮ LIỆU THẬT: Tỷ lệ qua QC (Quality Pass Rate) và Defect Ratio
    $stmtStats = $pdo->query("
        SELECT 
            (SUM(QCI_usable_weight_kg) / SUM(QCI_usable_weight_kg + QCI_rotten_weight_kg + QCI_natural_loss_weight_kg)) * 100 as pass_rate,
            (SUM(QCI_rotten_weight_kg) / SUM(QCI_usable_weight_kg + QCI_rotten_weight_kg + QCI_natural_loss_weight_kg)) * 100 as defect_ratio
        FROM QC_INSPECTIONS 
        WHERE (QCI_usable_weight_kg + QCI_rotten_weight_kg + QCI_natural_loss_weight_kg) > 0
    ");
    $stats = $stmtStats->fetch();
    $passRate = ($stats['pass_rate'] !== null) ? number_format($stats['pass_rate'], 1) : '0.0';
    $defectRatio = ($stats['defect_ratio'] !== null) ? number_format($stats['defect_ratio'], 1) : '0.0';

    // 2. DỮ LIỆU THẬT: Lô hàng chờ kiểm định (Pending Batches)
    // Đếm những lô có stage là 'Pending_QC' trong bảng BATCHES
    $stmtPending = $pdo->query("SELECT COUNT(*) FROM BATCHES WHERE BCH_current_stage = 'Pending_QC'");
    $pendingCount = $stmtPending->fetchColumn();

    // 3. DỮ LIỆU THẬT: Lịch sử kiểm định gần đây (Recent Inspection Logs)
    // Lấy thông tin từ cả 2 bảng BATCHES và QC_INSPECTIONS
    $stmtActivity = $pdo->query("
        SELECT q.QCI_inspection_id, q.QCI_usable_weight_kg, q.QCI_destination, q.QCI_batch_id 
        FROM QC_INSPECTIONS q 
        ORDER BY q.QCI_inspection_id DESC 
        LIMIT 5
    ");
    $recentActivities = $stmtActivity->fetchAll();

    // 4. TRENDS: aggregate usable vs fail (rotten + natural loss) by shift date (last 7 shifts)
    $stmtTrends = $pdo->query(
        "SELECT s.SHF_shift_date AS shift_date,
                SUM(q.QCI_usable_weight_kg) AS sum_usable,
                SUM(q.QCI_rotten_weight_kg + q.QCI_natural_loss_weight_kg) AS sum_fail
         FROM QC_INSPECTIONS q
         JOIN BATCHES b ON q.QCI_batch_id = b.BCH_batch_id
         JOIN SHIFTS s ON b.BCH_shift_id = s.SHF_shift_id
         GROUP BY s.SHF_shift_date
         ORDER BY s.SHF_shift_date DESC
         LIMIT 7"
    );
    $rawTrends = $stmtTrends->fetchAll();
    // prepare arrays in chronological order
    $trendLabels = [];
    $trendPass = [];
    $trendFail = [];
    if (!empty($rawTrends)) {
        $rawTrends = array_reverse($rawTrends);
        foreach ($rawTrends as $r) {
            $label = date('Y-m-d', strtotime($r['shift_date']));
            $usable = floatval($r['sum_usable']);
            $fail = floatval($r['sum_fail']);
            $total = $usable + $fail;
            $passPct = $total > 0 ? round(($usable / $total) * 100, 1) : 0;
            $failPct = $total > 0 ? round(($fail / $total) * 100, 1) : 0;
            $trendLabels[] = $label;
            $trendPass[] = $passPct;
            $trendFail[] = $failPct;
        }
    }

} catch (PDOException $e) {
    die("Lỗi Database: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ProSync Industrial - QC Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #0a1118; font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="text-gray-300 min-h-screen flex">

    <aside class="w-64 bg-[#0f1722] border-r border-[#1f2937] flex flex-col hidden md:flex">
        <div class="p-6 border-b border-[#1f2937]">
            <h2 class="text-[#10b981] font-bold text-xl flex items-center gap-2">
                <img src="../image/353838036_746744254123717_8058064823033680293_n.jpg" alt="F&G FOOD logo" class="w-5 h-5 object-contain rounded" />
                F&G FOOD
            </h2>
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <a href="dashboard_qc.php" class="flex items-center gap-3 px-4 py-3 bg-[#10b981] text-gray-900 font-semibold rounded-md">Dashboard</a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 text-gray-400 hover:text-white rounded-md transition">Inspections</a>
        </nav>
        <div class="p-4 border-t border-[#1f2937]">
            <a href="../backend/connection/logout.php" class="text-gray-400 hover:text-red-400">Logout</a>
        </div>
    </aside>

    <main class="flex-1 p-8">
        <header class="flex flex-col md:flex-row justify-between items-center mb-8 pb-4 border-b border-[#1f2937] gap-4">
            <h1 class="text-2xl font-bold text-[#10b981]">QC Overview</h1>
            
            <div class="relative w-full md:w-96">
                <input type="text" id="searchInput" onkeyup="filterLogs()" placeholder="Search batch ID or sensor..." 
                       class="w-full bg-[#0f1722] border border-[#1f2937] text-gray-300 rounded-md py-2 pl-10 pr-4 focus:outline-none focus:border-[#10b981]">
                <svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>

            <div class="text-right hidden md:block">
                <p class="text-sm font-semibold text-white"><?= htmlspecialchars($_SESSION['full_name']) ?></p>
                <p class="text-xs text-gray-500">QC Operator</p>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-[#0f1722] p-6 rounded-xl border border-[#1f2937]">
                <p class="text-xs text-gray-400 uppercase">Quality Pass Rate</p>
                <h3 class="text-4xl font-bold text-white mt-2"><?= $passRate ?>%</h3>
            </div>
            <div class="bg-[#0f1722] p-6 rounded-xl border border-[#1f2937]">
                <p class="text-xs text-gray-400 uppercase">Pending Batches</p>
                <h3 class="text-4xl font-bold text-white mt-2"><?= $pendingCount ?></h3>
            </div>
            <div class="bg-[#0f1722] p-6 rounded-xl border border-[#1f2937]">
                <p class="text-xs text-red-400 uppercase">Defect Ratio</p>
                <h3 class="text-4xl font-bold text-white mt-2"><?= $defectRatio ?>%</h3>
            </div>
            <div class="bg-[#0f1722] p-6 rounded-xl border border-[#1f2937]">
                <p class="text-xs text-gray-400 uppercase">Instrument Status</p>
                <h3 class="text-xl font-bold text-[#10b981] mt-3">● Calibrated</h3>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-[#0f1722] rounded-xl border border-[#1f2937] p-6">
                <h3 class="text-lg font-semibold text-white mb-4">QC Pass/Fail Trends</h3>
                <div class="mt-4">
                    <canvas id="trendsChart" class="w-full" style="height:220px"></canvas>
                </div>
                <div class="flex items-center gap-4 mt-4 text-xs text-gray-400">
                    <span class="flex items-center gap-2"><span class="w-3 h-3 rounded bg-[#10b981]"></span>Pass</span>
                    <span class="flex items-center gap-2"><span class="w-3 h-3 rounded bg-[#f87171]"></span>Fail</span>
                </div>
            </div>

            <div class="bg-[#0f1722] rounded-xl border border-[#1f2937] p-6 overflow-hidden">
                <h3 class="text-lg font-semibold text-white mb-4">Recent Activity</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left" id="logsTable">
                        <thead class="text-gray-500 text-xs uppercase">
                            <tr><th class="pb-3">Batch ID</th><th class="pb-3">Usable (kg)</th><th class="pb-3">Destination</th></tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php if (empty($recentActivities)): ?>
                                <tr><td colspan="3" class="text-center pt-4 text-gray-600">No logs found yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentActivities as $act): ?>
                                    <tr class="border-t border-[#1f2937] log-row">
                                        <td class="py-3 text-[#10b981] font-mono batch-id">#<?= htmlspecialchars($act['QCI_batch_id']) ?></td>
                                        <td class="py-3 text-white"><?= number_format($act['QCI_usable_weight_kg'], 2) ?></td>
                                        <td class="py-3 destination"><?= htmlspecialchars($act['QCI_destination']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
        // Prepare trend data from PHP
        const trendLabels = <?= json_encode($trendLabels) ?>;
        const trendPass = <?= json_encode($trendPass) ?>;
        const trendFail = <?= json_encode($trendFail) ?>;

        (function renderTrends(){
            const ctx = document.getElementById('trendsChart');
            if (!ctx) return;
            new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: trendLabels.length ? trendLabels : ['No Data'],
                    datasets: [
                        { label: 'Pass %', data: trendPass.length ? trendPass : [0], backgroundColor: '#10b981' },
                        { label: 'Fail %', data: trendFail.length ? trendFail : [0], backgroundColor: '#f87171' }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, max: 100 }
                    }
                }
            });
        })();
    </script>
</body>
</html>