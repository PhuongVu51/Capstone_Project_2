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
        /* Thanh cuộn mượt chìm chuẩn công nghiệp */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #0b121c; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #1f2937; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #374151; }
    </style>
</head>
<body class="min-h-screen overflow-x-hidden flex">

    <?php include 'includes/sidebar.php'; ?>

    <main class="md:ml-64 p-6 md:p-8 pt-24 md:pt-8 w-full transition-all duration-300">
        
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

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <div class="bg-[#0f1722] p-5 rounded-lg border border-[#1f2937]">
                <p class="text-[11px] text-gray-500 uppercase font-semibold tracking-wider">Total Inspected</p>
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

        <!-- ============================================================ -->
        <!-- WASTE COST ATTRIBUTION (mới) — 3 card tổng quan chi phí hao hụt -->
        <!-- ============================================================ -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
            <div class="bg-[#0f1722] p-5 rounded-lg border border-[#1f2937]">
                <p class="text-[11px] text-gray-500 uppercase font-semibold tracking-wider">Natural Loss Cost</p>
                <h3 class="text-2xl font-bold text-white mt-2 font-mono"><?= $naturalCostTotal ?> <span class="text-sm text-gray-500 font-normal">₫</span></h3>
                <p class="text-[11px] text-gray-500 mt-2">Expected loss (trimming, prep) — not a fault</p>
            </div>

            <div class="bg-[#2a1215] p-5 rounded-lg border border-red-900/30">
                <p class="text-[11px] text-red-400 uppercase font-semibold tracking-wider">Abnormal Loss Cost</p>
                <h3 class="text-2xl font-bold text-red-500 mt-2 font-mono"><?= $abnormalCostTotal ?> <span class="text-sm text-red-800 font-normal">₫</span></h3>
                <p class="text-[11px] text-red-300/70 mt-2">Preventable loss — needs investigation</p>
            </div>

            <div class="bg-[#0f1722] p-5 rounded-lg border border-[#1f2937]">
                <p class="text-[11px] text-gray-500 uppercase font-semibold tracking-wider">% Abnormal / Total Waste Cost</p>
                <h3 class="text-3xl font-bold <?= $abnormalCostPercent > 50 ? 'text-red-500' : 'text-yellow-500' ?> mt-2 font-mono"><?= $abnormalCostPercent ?>%</h3>
                <p class="text-[11px] text-gray-500 mt-2">Higher % = more waste is preventable</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 items-stretch">
            
            <div class="bg-[#0f1722] rounded-lg border border-[#1f2937] p-5 flex flex-col justify-between">
                <div>
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-1">Defect Breakdown</h3>
                    <p class="text-xs text-gray-500 mb-6">Distribution of rejection reasons by weight (KG)</p>
                </div>
                
                <div class="relative h-[240px] w-full flex justify-center items-center">
                    <?php if(empty($chartLabels)): ?>
                        <div class="flex items-center justify-center h-full w-full text-gray-600 text-sm italic">No defect data available.</div>
                    <?php else: ?>
                        <canvas id="defectChart"></canvas>
                    <?php endif; ?>
                </div>
            </div>

            <div class="lg:col-span-2 bg-[#0f1722] rounded-lg border border-[#1f2937] flex flex-col min-w-0 max-h-[346px]">
                <div class="p-4 border-b border-[#1f2937] bg-[#0b121c] shrink-0">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Critical Loss Log</h3>
                </div>
                
                <div class="overflow-y-auto flex-1 p-0 custom-scrollbar bg-[#091018]">
                    <table class="w-full text-left border-collapse layout-fixed">
                        <thead class="text-gray-500 text-[10px] uppercase bg-[#0b121c] sticky top-0 z-10 shadow-[0_1px_0_#1f2937]">
                            <tr>
                                <th class="py-3 pl-6 pr-2 font-semibold tracking-wider bg-[#0b121c] w-[22%]">Batch ID / Product</th>
                                <th class="py-3 px-2 font-semibold tracking-wider bg-[#0b121c] w-[16%]">Supplier</th>
                                <th class="py-3 px-2 font-semibold tracking-wider text-right bg-[#0b121c] w-[12%]">Rejected</th>
                                <th class="py-3 px-2 font-semibold tracking-wider bg-[#0b121c] w-[15%]">Defect Reason</th>
                                <th class="py-3 px-2 font-semibold tracking-wider text-right bg-[#0b121c] w-[16%]">Cost Impact</th>
                                <th class="py-3 pl-2 pr-6 font-semibold tracking-wider text-right bg-[#0b121c] w-[9%]">Yield</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-[#1f2937]/50">
                            <?php if (empty($lossBatches)): ?>
                                <tr><td colspan="6" class="p-8 text-center text-gray-600 italic">Excellent! No major material losses recorded.</td></tr>
                            <?php else: ?>
                                <?php foreach ($lossBatches as $batch): ?>
                                    <tr class="hover:bg-[#131c26] transition-colors">
                                        <td class="py-3 Fraser pl-6 pr-2 vertical-top">
                                            <div class="text-[#10b981] font-mono font-bold text-xs mb-0.5">#<?= htmlspecialchars($batch['QCI_batch_id']) ?></div>
                                            <div class="text-gray-300 text-[11px] whitespace-normal break-words leading-tight font-medium"><?= htmlspecialchars($batch['PRD_product_name']) ?></div>
                                        </td>
                                        <td class="py-3 px-2 text-gray-400 text-xs whitespace-normal break-words leading-tight vertical-top"><?= htmlspecialchars($batch['SUP_supplier_name']) ?></td>
                                        <td class="py-3 px-2 text-red-400 font-mono text-right font-bold vertical-top"><?= number_format($batch['QCI_rotten_weight_kg'], 1) ?> <span class="text-[10px] text-gray-600 font-sans font-normal">kg</span></td>
                                        <td class="py-3 px-2 vertical-top">
                                            <span class="inline-block bg-gray-800/60 text-gray-300 border border-gray-700 px-2 py-0.5 rounded text-[10px] uppercase font-bold tracking-wider whitespace-normal break-words leading-normal max-w-full">
                                                <?= htmlspecialchars($batch['QCI_rejection_reason']) ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-2 text-red-400 font-mono text-right font-bold vertical-top">
                                            <?= number_format($batch['rotten_cost'], 0, ',', '.') ?> <span class="text-[10px] text-gray-600 font-sans font-normal">₫</span>
                                        </td>
                                        <td class="py-3 pl-2 pr-6 text-right vertical-top">
                                            <?php 
                                            $yield = $batch['QCI_actual_yield_pct']; 
                                            $yieldClass = $yield < 80 ? 'text-red-500' : 'text-[#10b981]';
                                            ?>
                                            <span class="font-mono font-black text-xs <?= $yieldClass ?>"><?= number_format($yield, 1) ?>%</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>

        <!-- ============================================================ -->
        <!-- WASTE COST ATTRIBUTION (mới) — biểu đồ theo sản phẩm + xu hướng -->
        <!-- ============================================================ -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-[#0f1722] rounded-lg border border-[#1f2937] p-5">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-1">Waste Cost by Product</h3>
                <p class="text-xs text-gray-500 mb-6">Natural vs Abnormal loss cost (₫), stacked per product</p>
                <div class="relative h-[260px] w-full">
                    <?php if (empty($costChartLabels)): ?>
                        <div class="flex items-center justify-center h-full w-full text-gray-600 text-sm italic">No cost data available.</div>
                    <?php else: ?>
                        <canvas id="costChart"></canvas>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-[#0f1722] rounded-lg border border-[#1f2937] p-5">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-1">Waste Cost Trend</h3>
                <p class="text-xs text-gray-500 mb-6">Daily waste cost (₫) over the most recent receiving dates</p>
                <div class="relative h-[260px] w-full">
                    <?php if (empty($trendLabels)): ?>
                        <div class="flex items-center justify-center h-full w-full text-gray-600 text-sm italic">No trend data available.</div>
                    <?php else: ?>
                        <canvas id="trendChart"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

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
                            '#ef4444', 
                            '#f59e0b', 
                            '#3b82f6', 
                            '#8b5cf6', 
                            '#6b7280'  
                        ],
                        borderColor: '#0f1722',
                        borderWidth: 2,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%', 
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: { boxWidth: 10, padding: 12, font: { size: 10 } }
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

        // ============================================================
        // WASTE COST ATTRIBUTION (mới) — stacked bar theo sản phẩm
        // ============================================================
        const costChartLabels = <?= json_encode($costChartLabels) ?>;
        const costChartNatural = <?= json_encode($costChartNatural) ?>;
        const costChartAbnormal = <?= json_encode($costChartAbnormal) ?>;

        if (document.getElementById('costChart') && costChartLabels.length > 0) {
            new Chart(document.getElementById('costChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: costChartLabels,
                    datasets: [
                        { label: 'Natural Loss Cost (₫)', data: costChartNatural, backgroundColor: '#6b7280' },
                        { label: 'Abnormal Loss Cost (₫)', data: costChartAbnormal, backgroundColor: '#ef4444' }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { stacked: true, grid: { color: '#1f2937' } },
                        y: { stacked: true, grid: { color: '#1f2937' }, ticks: { callback: v => v.toLocaleString('vi-VN') } }
                    },
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 10, padding: 12, font: { size: 10 } } },
                        tooltip: {
                            backgroundColor: '#1f2937', titleColor: '#fff', bodyColor: '#d1d5db',
                            borderColor: '#374151', borderWidth: 1, padding: 10,
                            callbacks: {
                                label: function(context) { return ' ' + context.dataset.label + ': ' + Number(context.raw).toLocaleString('vi-VN') + ' ₫'; }
                            }
                        }
                    }
                }
            });
        }

        // ============================================================
        // WASTE COST ATTRIBUTION (mới) — xu hướng theo ngày
        // ============================================================
        const trendLabels = <?= json_encode($trendLabels) ?>;
        const trendNatural = <?= json_encode($trendNatural) ?>;
        const trendAbnormal = <?= json_encode($trendAbnormal) ?>;

        if (document.getElementById('trendChart') && trendLabels.length > 0) {
            new Chart(document.getElementById('trendChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [
                        { label: 'Natural Cost', data: trendNatural, borderColor: '#6b7280', backgroundColor: 'transparent', tension: 0.3 },
                        { label: 'Abnormal Cost', data: trendAbnormal, borderColor: '#ef4444', backgroundColor: 'transparent', tension: 0.3 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { grid: { color: '#1f2937' } },
                        y: { grid: { color: '#1f2937' }, ticks: { callback: v => v.toLocaleString('vi-VN') } }
                    },
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 10, padding: 12, font: { size: 10 } } },
                        tooltip: {
                            backgroundColor: '#1f2937', titleColor: '#fff', bodyColor: '#d1d5db',
                            borderColor: '#374151', borderWidth: 1, padding: 10,
                            callbacks: {
                                label: function(context) { return ' ' + context.dataset.label + ': ' + Number(context.raw).toLocaleString('vi-VN') + ' ₫'; }
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>