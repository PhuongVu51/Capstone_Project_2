<?php
// Đường dẫn: frontend/qc_reports.php
require_once '../backend/includes/auth.php';
require_role(['QC', 'Production_Manager', 'Director'], 'login.php');
require_once '../backend/controllers/QcReportController.php';

try {
    $lang = $_SESSION['lang'] ?? 'vi';
    $controller = new QcReportController();
    $data = $controller->loadReportData($lang);
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
                <h1 class="text-2xl font-bold text-white tracking-wide"><?= __('loss_defect_reports') ?></h1>
            </div>
            
            <div class="flex gap-3">
                <button onclick="window.print()" class="bg-[#1f2937] hover:bg-[#374151] border border-[#374151] text-gray-300 font-bold px-4 py-2 rounded text-sm transition-colors shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    <?= __('export_pdf') ?>
                </button>
            </div>
        </header>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <div class="bg-[#0f1722] p-5 rounded-lg border border-[#1f2937]">
                <p class="text-[11px] text-gray-500 uppercase font-semibold tracking-wider"><?= __('total_inspected') ?></p>
                <h3 class="text-3xl font-bold text-white mt-2 font-mono"><?= $totalInspected ?> <span class="text-sm text-gray-500 font-normal">KG</span></h3>
            </div>

            <div class="bg-[#2a1215] p-5 rounded-lg border border-red-900/30">
                <p class="text-[11px] text-red-400 uppercase font-semibold tracking-wider"><?= __('total_rejected_loss') ?></p>
                <h3 class="text-3xl font-bold text-red-500 mt-2 font-mono"><?= $totalLoss ?> <span class="text-sm text-red-800 font-normal">KG</span></h3>
            </div>

            <div class="bg-[#0f1722] p-5 rounded-lg border border-[#1f2937]">
                <p class="text-[11px] text-gray-500 uppercase font-semibold tracking-wider"><?= __('average_defect_rate') ?></p>
                <h3 class="text-3xl font-bold text-white mt-2 font-mono"><?= $defectRate ?>%</h3>
            </div>

            <div class="bg-[#0f1722] p-5 rounded-lg border border-[#1f2937]">
                <p class="text-[11px] text-gray-500 uppercase font-semibold tracking-wider"><?= __('primary_defect_reason') ?></p>
                <h3 class="text-xl font-bold text-yellow-500 mt-2 truncate"><?= htmlspecialchars(__($topReason)) ?></h3>
                <p class="text-[11px] text-gray-400 mt-2 font-mono"><?= __('accounted_for') ?> <?= $topReasonKg ?> KG</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 items-stretch">
            
            <div class="bg-[#0f1722] rounded-lg border border-[#1f2937] p-5 flex flex-col justify-between">
                <div>
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-1"><?= __('defect_breakdown') ?></h3>
                    <p class="text-xs text-gray-500 mb-6"><?= __('distribution_rejection_reasons') ?></p>
                </div>
                
                <div class="relative h-[240px] w-full flex justify-center items-center">
                    <?php if(empty($chartLabels)): ?>
                        <div class="flex items-center justify-center h-full w-full text-gray-600 text-sm italic"><?= __('no_defect_data') ?></div>
                    <?php else: ?>
                        <canvas id="defectChart"></canvas>
                    <?php endif; ?>
                </div>
            </div>

            <div class="lg:col-span-2 bg-[#0f1722] rounded-lg border border-[#1f2937] flex flex-col min-w-0 max-h-[346px]">
                <div class="p-4 border-b border-[#1f2937] bg-[#0b121c] shrink-0">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider"><?= __('critical_loss_log') ?></h3>
                </div>
                
                <div class="overflow-y-auto flex-1 p-0 custom-scrollbar bg-[#091018]">
                    <table class="w-full text-left border-collapse layout-fixed">
                        <thead class="text-gray-500 text-[10px] uppercase bg-[#0b121c] sticky top-0 z-10 shadow-[0_1px_0_#1f2937]">
                            <tr>
                                <th class="py-3 pl-6 pr-2 font-semibold tracking-wider bg-[#0b121c] w-[28%]"><?= __('batch_id_product') ?></th>
                                <th class="py-3 px-2 font-semibold tracking-wider bg-[#0b121c] w-[24%]"><?= __('supplier_report') ?></th>
                                <th class="py-3 px-2 font-semibold tracking-wider text-right bg-[#0b121c] w-[16%]"><?= __('rejected') ?></th>
                                <th class="py-3 px-2 font-semibold tracking-wider bg-[#0b121c] w-[20%]"><?= __('defect_reason') ?></th>
                                <th class="py-3 pl-2 pr-6 font-semibold tracking-wider text-right bg-[#0b121c] w-[12%]"><?= __('yield_pct') ?></th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-[#1f2937]/50">
                            <?php if (empty($lossBatches)): ?>
                                <tr><td colspan="5" class="p-8 text-center text-gray-600 italic"><?= __('no_major_losses') ?></td></tr>
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
                                                <?= htmlspecialchars(__($batch['QCI_rejection_reason'])) ?>
                                            </span>
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

        <!-- Supplier Scorecard Section -->
        <div class="mt-8 pt-8 border-t border-[#1f2937]">
            <h2 class="text-xl font-bold text-white mb-6 uppercase tracking-wide">Supplier Scorecard</h2>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Scorecard Table -->
                <div class="lg:col-span-2 bg-[#0f1722] rounded-lg border border-[#1f2937] flex flex-col min-w-0 max-h-[400px]">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center px-4 py-3 border-b border-[#1f2937] bg-[#0b121c] shrink-0 gap-3">
                        <h3 class="text-sm font-bold text-white uppercase tracking-wider"><?= __('supplier_scorecard', 'Bảng Xếp Hạng') ?></h3>
                        <div class="flex gap-2 w-full sm:w-auto">
                            <select id="scorecardStatusFilter" onchange="filterAndSortScorecard()" class="bg-[#1f2937] text-xs text-gray-300 border border-[#374151] rounded px-2 py-1.5 outline-none w-full sm:w-auto focus:border-blue-500 transition-colors">
                                <option value="all"><?= __('all_statuses', 'Tất cả trạng thái') ?></option>
                                <option value="Warning"><?= __('warning', 'Cảnh báo') ?></option>
                                <option value="Monitor"><?= __('monitor', 'Cần theo dõi') ?></option>
                                <option value="Good">Good</option>
                                <option value="Insufficient Data"><?= __('insufficient_data', 'Chưa đủ dữ liệu') ?></option>
                            </select>
                            <select id="scorecardSort" onchange="filterAndSortScorecard()" class="bg-[#1f2937] text-xs text-gray-300 border border-[#374151] rounded px-2 py-1.5 outline-none w-full sm:w-auto focus:border-blue-500 transition-colors">
                                <option value="desc"><?= __('sort_desc', 'Hao hụt giảm dần') ?></option>
                                <option value="asc"><?= __('sort_asc', 'Hao hụt tăng dần') ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="overflow-y-auto flex-1 p-0 custom-scrollbar bg-[#091018]">
                        <table class="w-full text-left border-collapse">
                            <thead class="text-gray-500 text-[10px] uppercase bg-[#0b121c] sticky top-0 z-10 shadow-[0_1px_0_#1f2937]">
                                <tr>
                                    <th class="py-3 px-4 font-semibold tracking-wider bg-[#0b121c]"><?= __('supplier_name', 'Tên NCC') ?></th>
                                    <th class="py-3 px-4 font-semibold tracking-wider text-right bg-[#0b121c]"><?= __('total_supplied', 'Tổng Cung Cấp') ?></th>
                                    <th class="py-3 px-4 font-semibold tracking-wider text-center bg-[#0b121c]"><?= __('waste_rate', 'Tỷ lệ Hao hụt') ?></th>
                                    <th class="py-3 px-4 font-semibold tracking-wider text-right bg-[#0b121c]"><?= __('waste_cost', 'Chi phí Hao hụt') ?></th>
                                    <th class="py-3 px-4 font-semibold tracking-wider text-center bg-[#0b121c]"><?= __('trend_30d', 'Xu hướng (30d)') ?></th>
                                    <th class="py-3 px-4 font-semibold tracking-wider text-center bg-[#0b121c]"><?= __('status', 'Trạng thái') ?></th>
                                </tr>
                            </thead>
                            <tbody id="scorecardTableBody" class="text-sm divide-y divide-[#1f2937]/50">
                                <?php foreach ($supplierScorecard as $sup): ?>
                                    <?php
                                        if ($sup['waste_pct'] === null) {
                                            $rawStatus = 'Insufficient Data';
                                        } elseif ($sup['waste_pct'] < 5) {
                                            $rawStatus = 'Good';
                                        } elseif ($sup['waste_pct'] <= 15) {
                                            $rawStatus = 'Monitor';
                                        } else {
                                            $rawStatus = 'Warning';
                                        }
                                    ?>
                                    <tr class="hover:bg-[#131c26] transition-colors scorecard-row" 
                                        data-status="<?= $rawStatus ?>" 
                                        data-waste="<?= $sup['waste_pct'] !== null ? $sup['waste_pct'] : -1 ?>">
                                        <td class="py-3 px-4 font-medium text-gray-300"><?= htmlspecialchars($sup['supplier_name']) ?></td>
                                        <td class="py-3 px-4 text-right text-gray-400 font-mono"><?= number_format($sup['total_supplied'], 1) ?> kg</td>
                                        <td class="py-3 px-4 text-center font-mono font-bold <?= $sup['waste_pct'] !== null && $sup['waste_pct'] > 15 ? 'text-red-500' : 'text-gray-300' ?>">
                                            <?= $sup['waste_pct'] !== null ? number_format($sup['waste_pct'], 2) . '%' : '-' ?>
                                        </td>
                                        <td class="py-3 px-4 text-right text-red-400 font-mono">
                                            <?= number_format($sup['waste_cost']) ?> đ
                                        </td>
                                        <td class="py-3 px-4 text-center font-mono">
                                            <?php if ($sup['waste_pct'] === null): ?>
                                                <span class="text-gray-600">-</span>
                                            <?php else: ?>
                                                <?php if ($sup['trend_value'] > 0): ?>
                                                    <span class="text-red-500 flex items-center justify-center gap-1">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                                                        <?= number_format(abs($sup['trend_value']), 1) ?>%
                                                    </span>
                                                <?php elseif ($sup['trend_value'] < 0): ?>
                                                    <span class="text-green-500 flex items-center justify-center gap-1">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                                                        <?= number_format(abs($sup['trend_value']), 1) ?>%
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-gray-500">0%</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] uppercase tracking-wider font-bold <?= $sup['badge_color'] ?>">
                                                <span class="w-1.5 h-1.5 rounded-full <?= $sup['badge_icon_color'] ?>"></span>
                                                <?= $sup['badge_text'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Scorecard Chart -->
                <div class="bg-[#0f1722] rounded-lg border border-[#1f2937] flex flex-col min-w-0 max-h-[400px]">
                    <div class="px-4 py-3 border-b border-[#1f2937] bg-[#0b121c] shrink-0">
                        <h3 class="text-sm font-bold text-white uppercase tracking-wider"><?= __('waste_comparison', 'So sánh Hao hụt') ?></h3>
                        <p class="text-[11px] text-gray-500 mt-0.5"><?= __('waste_rate_between_suppliers', 'Tỷ lệ hao hụt giữa các NCC') ?></p>
                    </div>
                    <div class="overflow-y-auto flex-1 p-4 custom-scrollbar bg-[#091018]">
                        <div class="relative w-full" id="chartWrapper">
                            <canvas id="supplierChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        const rawChartLabels = <?= json_encode($chartLabels) ?>;
        // Translate chart labels if needed (using JS mapping or PHP translation)
        const translatedLabels = {
            'None': '<?= __('no_defects') ?>',
            'Contaminated': '<?= __('material_contamination') ?>',
            'Rotten': '<?= __('spoilage') ?>',
            'Moisture_Anomaly': '<?= __('incorrect_moisture') ?>',
            'Other': '<?= __('other_violation') ?>'
        };
        const chartLabels = rawChartLabels.map(label => translatedLabels[label] || label);
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

        // Supplier Scorecard Chart
        const supplierData = <?= json_encode($supplierScorecard) ?>;
        // Lọc bỏ những nhà cung cấp không có dữ liệu tỷ lệ hao hụt
        const validSuppliers = supplierData.filter(s => s.waste_pct !== null);
        const supLabels = validSuppliers.map(s => s.supplier_name);
        const supWastePct = validSuppliers.map(s => s.waste_pct);
        const supColors = validSuppliers.map(s => {
            if (s.waste_pct < 5) return '#10b981'; // green
            if (s.waste_pct <= 15) return '#f59e0b'; // yellow
            return '#ef4444'; // red
        });

        if (document.getElementById('supplierChart') && supLabels.length > 0) {
            const chartWrapper = document.getElementById('chartWrapper');
            if (chartWrapper) {
                const requiredHeight = Math.max(250, supLabels.length * 40);
                chartWrapper.style.height = requiredHeight + 'px';
            }

            const ctx2 = document.getElementById('supplierChart').getContext('2d');
            new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: supLabels,
                    datasets: [{
                        label: 'Tỷ lệ hao hụt (%)',
                        data: supWastePct,
                        backgroundColor: supColors,
                        borderRadius: 4,
                        barPercentage: 0.6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y', // Biểu đồ ngang dễ đọc tên dài
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1f2937', titleColor: '#fff', bodyColor: '#d1d5db',
                            borderColor: '#374151', borderWidth: 1, padding: 10,
                            callbacks: {
                                label: function(context) { return ' ' + context.raw.toFixed(2) + '%'; }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: '#1f2937' },
                            ticks: { color: '#9ca3af' },
                            title: { display: true, text: 'Tỷ lệ hao hụt (%)', color: '#6b7280', font: { size: 10 } }
                        },
                        y: {
                            grid: { display: false },
                            ticks: { color: '#d1d5db', font: { size: 11 } }
                        }
                    }
                }
            });
        } else if (document.getElementById('supplierChart')) {
            document.getElementById('supplierChart').parentElement.innerHTML = '<div class="text-gray-600 text-sm italic h-full flex items-center">Chưa có dữ liệu hao hụt để hiển thị</div>';
        }

        // JS logic for Filtering and Sorting the Scorecard Table
        function filterAndSortScorecard() {
            const statusFilter = document.getElementById('scorecardStatusFilter').value;
            const sortOrder = document.getElementById('scorecardSort').value;
            
            const tbody = document.getElementById('scorecardTableBody');
            if (!tbody) return;
            const rows = Array.from(tbody.querySelectorAll('.scorecard-row'));
            
            rows.sort((a, b) => {
                let valA = parseFloat(a.getAttribute('data-waste'));
                let valB = parseFloat(b.getAttribute('data-waste'));
                
                // Những row chưa đủ dữ liệu (giá trị -1) luôn đẩy xuống cuối cùng
                if (valA === -1 && valB === -1) return 0;
                if (valA === -1) return 1;
                if (valB === -1) return -1;
                
                return sortOrder === 'asc' ? valA - valB : valB - valA;
            });
            
            rows.forEach(row => {
                const rowStatus = row.getAttribute('data-status');
                if (statusFilter === 'all' || rowStatus === statusFilter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
                tbody.appendChild(row); // Re-append with new order
            });
        }
    </script>
</body>
</html>