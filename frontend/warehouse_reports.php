<?php

require_once '../backend/includes/auth.php';

require_role(
    ['Warehouse_Staff','Production_Manager','Director'],
    'login.php'
);

require_once '../backend/controllers/WarehouseReportController.php';

try{
    $controller = new WarehouseReportController();
    $data = $controller->loadReportData();
    extract($data);
}catch(Exception $e){
    die("Error loading warehouse report: ".$e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Warehouse Reports | ProSync</title>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

body{
    background:#06121a;
    color:#d1d5db;
    font-family:'Inter',sans-serif;
}

.custom-scrollbar::-webkit-scrollbar{
    width:6px;
    height:6px;
}

.custom-scrollbar::-webkit-scrollbar-track{
    background:#0b121c;
}

.custom-scrollbar::-webkit-scrollbar-thumb{
    background:#1f2937;
    border-radius:4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover{
    background:#374151;
}

</style>

</head>

<body class="min-h-screen overflow-x-hidden flex">

<?php include 'includes/warehouse_sidebar.php'; ?>

<main class="md:ml-64 p-6 md:p-8 pt-24 md:pt-8 w-full">

    <!-- HEADER -->

    <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 pb-4 border-b border-[#1f2937] gap-4">

        <div>
            <h1 class="text-2xl font-bold text-white tracking-wide">
                Warehouse Analytics
            </h1>

            <p class="text-gray-500 text-sm mt-1">
                Inventory Monitoring & Warehouse Health Overview
            </p>
        </div>

        <button
            onclick="window.print()"
            class="bg-[#1f2937] hover:bg-[#374151] border border-[#374151]
                   text-gray-300 font-bold px-4 py-2 rounded text-sm
                   transition-colors">

            Export PDF

        </button>

    </header>

    <!-- KPI -->

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">

        <div class="bg-[#0f1722] p-5 rounded-lg border border-[#1f2937]">
            <p class="text-[11px] text-gray-500 uppercase font-semibold tracking-wider">
                Total Stock
            </p>

            <h3 class="text-3xl font-bold text-white mt-2 font-mono">
                <?= number_format($totalStock,1) ?>
                <span class="text-sm text-gray-500">
                    KG
                </span>
            </h3>
        </div>

        <div class="bg-[#0f1722] p-5 rounded-lg border border-[#1f2937]">
            <p class="text-[11px] text-gray-500 uppercase font-semibold tracking-wider">
                Active Batches
            </p>

            <h3 class="text-3xl font-bold text-white mt-2 font-mono">
                <?= $totalBatches ?>
            </h3>
        </div>

        <div class="bg-[#2a2112] p-5 rounded-lg border border-yellow-800/30">
            <p class="text-[11px] text-yellow-400 uppercase font-semibold tracking-wider">
                Expiring Soon
            </p>

            <h3 class="text-3xl font-bold text-yellow-400 mt-2 font-mono">
                <?= $expiringCount ?>
            </h3>
        </div>

        <div class="bg-[#2a1215] p-5 rounded-lg border border-red-900/30">
            <p class="text-[11px] text-red-400 uppercase font-semibold tracking-wider">
                Total Outbound
            </p>

            <h3 class="text-3xl font-bold text-red-500 mt-2 font-mono">
                <?= number_format($totalOutbound,1) ?>

                <span class="text-sm text-red-800">
                    KG
                </span>
            </h3>
        </div>

    </div>

    <!-- CHART + ALERT -->

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 items-stretch">

        <!-- CHART -->

        <div class="bg-[#0f1722] rounded-lg border border-[#1f2937] p-5 flex flex-col">

            <div>
                <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-1">
                    Inventory Distribution
                </h3>

                <p class="text-xs text-gray-500 mb-6">
                    Available stock by product
                </p>
            </div>

            <div class="relative h-[260px]">

                <?php if(empty($chartLabels)): ?>

                    <div class="h-full flex items-center justify-center text-gray-600 italic text-sm">
                        No inventory data available
                    </div>

                <?php else: ?>

                    <canvas id="stockChart"></canvas>

                <?php endif; ?>

            </div>

        </div>

        <!-- ALERTS -->

        <div class="lg:col-span-2 bg-[#0f1722] rounded-lg border border-[#1f2937] flex flex-col min-w-0 max-h-[360px]">

            <div class="p-4 border-b border-[#1f2937] bg-[#0b121c] shrink-0">

                <h3 class="text-sm font-bold text-white uppercase tracking-wider">
                    Warehouse Alerts
                </h3>

            </div>

            <div class="overflow-y-auto flex-1 custom-scrollbar bg-[#091018]">

                <table class="w-full text-left">

                    <thead class="sticky top-0 bg-[#0b121c] text-[10px] uppercase text-gray-500 z-10">

                    <tr>

                        <th class="py-3 pl-6">
                            Batch
                        </th>

                        <th class="py-3">
                            Product
                        </th>

                        <th class="py-3">
                            Status
                        </th>

                        <th class="py-3 pr-6 text-right">
                            Available Stock
                        </th>

                    </tr>

                    </thead>

                    <tbody class="divide-y divide-[#1f2937]/50 text-sm">

                    <?php if(empty($criticalBatches)): ?>

                        <tr>
                            <td colspan="4"
                                class="p-8 text-center text-gray-600 italic">

                                No warehouse alerts detected.

                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach($criticalBatches as $batch): ?>

                            <tr class="hover:bg-[#131c26] transition-colors">

                                <td class="py-3 pl-6">

                                    <div class="font-mono text-cyan-400 font-bold text-xs">
                                        #<?= htmlspecialchars($batch['BCH_batch_id']) ?>
                                    </div>

                                </td>

                                <td class="py-3">

                                    <div class="text-gray-300 text-xs">
                                        <?= htmlspecialchars($batch['PRD_product_name']) ?>
                                    </div>

                                </td>

                                <td class="py-3">

                                    <?php if($batch['BCH_health_status']=='Critical'): ?>

                                        <span class="bg-red-500/20 text-red-400 border border-red-900/40 px-2 py-1 rounded text-[10px] uppercase font-bold">
                                            Critical
                                        </span>

                                    <?php else: ?>

                                        <span class="bg-yellow-500/20 text-yellow-400 border border-yellow-900/40 px-2 py-1 rounded text-[10px] uppercase font-bold">
                                            Warning
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td class="py-3 pr-6 text-right font-mono">

                                    <?= number_format($batch['BCH_available_stock_kg'],1) ?>

                                    <span class="text-gray-500 text-xs">
                                        kg
                                    </span>

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

<script>

const chartLabels = <?= json_encode($chartLabels) ?>;
const chartData = <?= json_encode($chartData) ?>;

if(document.getElementById('stockChart') && chartLabels.length > 0){

    Chart.defaults.color = '#9ca3af';

    new Chart(
        document.getElementById('stockChart'),
        {
            type:'doughnut',

            data:{
                labels:chartLabels,

                datasets:[
                {
                    data:chartData,

                    backgroundColor:[
                        '#06b6d4',
                        '#10b981',
                        '#3b82f6',
                        '#8b5cf6',
                        '#f59e0b',
                        '#ef4444'
                    ],

                    borderColor:'#0f1722',
                    borderWidth:2
                }]
            },

            options:{
                responsive:true,
                maintainAspectRatio:false,

                plugins:{
                    legend:{
                        position:'right',
                        labels:{
                            boxWidth:10,
                            padding:12,
                            font:{
                                size:10
                            }
                        }
                    }
                }
            }
        }
    );
}

</script>

</body>
</html>