<?php
// Đường dẫn: frontend/qc_reports.php
require_once '../backend/includes/auth.php';
require_role(['QC', 'Production_Manager', 'Director'], 'login.php');
require_once '../backend/controllers/QcReportController.php';

try {
    $controller = new QcReportController();
    $data = $controller->loadReportData();
    extract($data);
} catch (Exception $e) {
    die("Lỗi tải báo cáo: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loss & Yield Reports | ProSync</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #06121a; color: #d1d5db; font-family: 'Inter', sans-serif; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #0f1722; }
        ::-webkit-scrollbar-thumb { background: #1f2937; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #374151; }
    </style>
</head>
<body class="min-h-screen overflow-x-hidden flex">

    <?php include 'includes/qc_sidebar.php'; ?>

    <main class="md:ml-64 p-6 md:p-8 pt-24 md:pt-8 w-full transition-all duration-300">
        
        <!-- HEADER BÁO CÁO -->
        <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 pb-4 border-b border-[#1f2937] gap-4">
            <div>
                <h1 class="text-2xl font-bold text-white tracking-wide">Loss & Defect Reports</h1>
            </div>
            
            <div class="flex gap-3">
                <button onclick="window.print()" class="bg-[#1f2937] hover:bg-[#374151] border border-[#374151] text-gray-300 font-bold px-4 py-2 rounded text-sm transition-colors shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Export PDF
                </button>
            </div>
        </header>

        <!-- KHỐI 4 THẺ KPI TỔNG QUAN -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <div class="bg-[#0f1722] p-5 rounded-lg border border-[#1f2937]">
                <p class="text-[11px] text-gray-500 uppercase font-semibold tracking-wider">Total Inspected Volume</p>
                <h3 class="text-3xl font-bold text-white mt-2 font-mono"><?= $totalInspected ?> <span class="text-sm text-gray-500 font-normal">KG</span></h3>
            </div>

            <div class="bg-[#2a1215] p-5 rounded-lg border border-red-900/30">
                <p class="text-[11px] text-red-400 uppercase font-semibold tracking-wider">Total Rejected Loss</p>
                <h3 class="text-3xl font-bold text-red-500 mt-2 font-mono"><?= $totalLoss ?> <span class="text-sm text-red-800 font-normal">KG</span></h3>
            </div>

            <div class="bg-[#0f1722] p-5 rounded-lg border border-[#1f2937]">
                <p class="text-[11px] text-gray-500 uppercase font-semibold tracking-wider">Average Defect Rate</p>
                <h3 class="text-3xl font-bold text-white mt-2 font-mono"><?= $defectRate ?>%</h3>
            </div>

            <div class="bg-[#0f1722] p-5 rounded-lg border border-[#1f2937]">
                <p class="text-[11px] text-gray-500 uppercase font-semibold tracking-wider">Primary Defect Reason</p>
                <h3 class="text-xl font-bold text-yellow-500 mt-2 truncate"><?= htmlspecialchars($topReason) ?></h3>
                <p class="text-[11px] text-gray-400 mt-2 font-mono">Accounted for <?= $topReasonKg ?> KG</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            
            <!-- BIỂU ĐỒ TRÒN PHÂN BỔ LỖI (Doughnut Chart) -->
            <div class="bg-[#0f1722] rounded-lg border border-[#1f2937] p-5">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-1">Defect Breakdown</h3>
                <p class="text-xs text-gray-500 mb-6">Distribution of rejection reasons by weight (KG)</p>
                
                <div class="relative h-[250px] w-full flex justify-center">
                    <?php if(empty($chartLabels)): ?>
                        <div class="flex items-center justify-center h-full w-full text-gray-600 text-sm italic">No defect data available.</div>
                    <?php else: ?>
                        <canvas id="defectChart"></canvas>
                    <?php endif; ?>
                </div>
            </div>

            <!-- BẢNG CHI TIẾT CÁC LÔ BỊ LOẠI BỎ NHIỀU NHẤT -->
            <div class="lg:col-span-2 bg-[#0f1722] rounded-lg border border-[#1f2937] flex flex-col min-w-0 overflow-hidden">
                <div class="p-5 border-b border-[#1f2937] bg-[#0b121c]">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Critical Loss Log</h3>
                </div>
                
                <div class="overflow-x-auto flex-1 p-0">
                    <table class="w-full text-left border-collapse">
                        <thead class="text-gray-500 text-[10px] uppercase bg-[#0b121c] sticky top-0 z-10">
                            <tr>
                                <th class="py-3 px-4 font-semibold tracking-wider">Batch ID / Product</th>
                                <th class="py-3 px-4 font-semibold tracking-wider">Supplier</th>
                                <th class="py-3 px-4 font-semibold tracking-wider text-right">Rejected (KG)</th>
                                <th class="py-3 px-4 font-semibold tracking-wider">Defect Reason</th>
                                <th class="py-3 px-4 font-semibold tracking-wider text-right">Yield</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-[#1f2937]">
                            <?php if (empty($lossBatches)): ?>
                                <tr><td colspan="5" class="p-8 text-center text-gray-600 italic">Excellent! No major material losses recorded.</td></tr>
                            <?php else: ?>
                                <?php foreach ($lossBatches as $batch): ?>
                                    <tr class="hover:bg-[#131c26] transition-colors">
                                        <td class="py-3 px-4">
                                            <div class="text-[#10b981] font-mono font-bold text-xs mb-0.5">#<?= htmlspecialchars($batch['QCI_batch_id']) ?></div>
                                            <div class="text-gray-300 text-[11px] truncate w-40"><?= htmlspecialchars($batch['PRD_product_name']) ?></div>
                                        </td>
                                        <td class="py-3 px-4 text-gray-400 text-xs"><?= htmlspecialchars($batch['SUP_supplier_name']) ?></td>
                                        <td class="py-3 px-4 text-red-400 font-mono text-right font-bold"><?= number_format($batch['QCI_rotten_weight_kg'], 1) ?></td>
                                        <td class="py-3 px-4">
                                            <span class="bg-gray-800 text-gray-300 border border-gray-600 px-2 py-1 rounded text-[10px] uppercase font-bold tracking-wider">
                                                <?= htmlspecialchars($batch['QCI_rejection_reason']) ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-right">
                                            <?php 
                                            $yield = $batch['QCI_actual_yield_pct']; 
                                            $yieldClass = $yield < 80 ? 'text-red-500' : 'text-[#10b981]';
                                            ?>
                                            <span class="font-mono font-bold text-xs <?= $yieldClass ?>"><?= number_format($yield, 1) ?>%</span>
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

    <!-- Kịch bản dựng Biểu đồ Chart.js -->
    <script>
        const chartLabels = <?= json_encode($chartLabels) ?>;
        const chartData = <?= json_encode($chartData) ?>;

        if (document.getElementById('defectChart') && chartLabels.length > 0) {
            const ctx = document.getElementById('defectChart').getContext('2d');
            Chart.defaults.color = '#9ca3af';
            Chart.defaults.font.family = 'Inter';

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        data: chartData,
                        backgroundColor: [
                            '#ef4444', // Đỏ rực
                            '#f59e0b', // Vàng cam
                            '#3b82f6', // Xanh dương
                            '#8b5cf6', // Xanh dương nhạt
                            '#6b7280'  // Xám
                        ],
                        borderColor: '#0f1722',
                        borderWidth: 2,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%', // Tạo lỗ rỗng ở giữa
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: { boxWidth: 10, padding: 15, font: { size: 10 } }
                        },
                        tooltip: {
                            backgroundColor: '#1f2937', titleColor: '#fff', bodyColor: '#d1d5db',
                            borderColor: '#374151', borderWidth: 1, padding: 10,
                            callbacks: {
                                label: function(context) { return ' ' + context.raw + ' KG'; }
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>