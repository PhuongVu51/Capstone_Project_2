<?php

require_once '../backend/includes/auth.php';

require_role(
    ['Production_Manager','Director'],
    'login.php'
);

require_once '../backend/controllers/ProductionAnalyticsController.php';

try{

    $controller = new ProductionAnalyticsController();

    $data = $controller->loadAnalyticsData();

    extract($data);

}catch(Exception $e){

    die("Error loading production analytics: ".$e->getMessage());

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Production Analytics | ProSync</title>

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

<?php include 'includes/production_sidebar.php'; ?>

<main class="md:ml-64 p-6 md:p-8 pt-24 md:pt-8 w-full">

    <!-- HEADER -->

    <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 pb-4 border-b border-[#1f2937] gap-4">

        <div>

            <h1 class="text-2xl font-bold text-white tracking-wide">
                Production Analytics
            </h1>

            <p class="text-gray-500 text-sm mt-1">
                Production Performance & Yield Monitoring
            </p>

        </div>

        <button
            onclick="window.print()"
            class="bg-[#1f2937] hover:bg-[#374151]
            border border-[#374151]
            text-gray-300 font-bold px-4 py-2
            rounded text-sm transition-colors
            flex items-center gap-2">

            <svg class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24">

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                </path>

            </svg>

            Export PDF

        </button>

    </header>

    <!-- KPI -->

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">

        <div class="bg-[#0f1722] p-5 rounded-lg border border-[#1f2937]">

            <p class="text-[11px] text-gray-500 uppercase font-semibold tracking-wider">
                Total Production Output
            </p>

            <h3 class="text-3xl font-bold text-white mt-2 font-mono">
                <?= number_format($totalOutput) ?>
            </h3>

        </div>

        <div class="bg-[#0f1722] p-5 rounded-lg border border-[#1f2937]">

            <p class="text-[11px] text-gray-500 uppercase font-semibold tracking-wider">
                Average Yield
            </p>

            <h3 class="text-3xl font-bold text-green-400 mt-2 font-mono">
                <?= $averageYield ?>%
            </h3>

        </div>

        <div class="bg-[#0f1722] p-5 rounded-lg border border-[#1f2937]">

            <p class="text-[11px] text-gray-500 uppercase font-semibold tracking-wider">
                Production Batches
            </p>

            <h3 class="text-3xl font-bold text-white mt-2 font-mono">
                <?= $productionBatches ?>
            </h3>

        </div>

        <div class="bg-[#2a2112] p-5 rounded-lg border border-yellow-800/30">

            <p class="text-[11px] text-yellow-400 uppercase font-semibold tracking-wider">
                Quarantine Inventory
            </p>

            <h3 class="text-3xl font-bold text-yellow-400 mt-2 font-mono">
                <?= $quarantineCount ?>
            </h3>

        </div>

    </div>

    <!-- CHART + TABLE -->

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 items-stretch">

        <!-- CHART -->

        <div class="bg-[#0f1722] rounded-lg border border-[#1f2937] p-5 flex flex-col">

            <div>

                <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-1">
                    Production Output Trend
                </h3>

                <p class="text-xs text-gray-500 mb-6">
                    Latest production output by batch
                </p>

            </div>

            <div class="relative h-[260px]">

                <?php if(empty($chartLabels)): ?>

                    <div class="h-full flex items-center justify-center text-gray-600 italic text-sm">
                        No production data available
                    </div>

                <?php else: ?>

                    <canvas id="productionChart"></canvas>

                <?php endif; ?>

            </div>

        </div>

        <!-- TABLE -->

        <div class="lg:col-span-2 bg-[#0f1722] rounded-lg border border-[#1f2937] flex flex-col min-w-0 max-h-[360px]">

            <div class="p-4 border-b border-[#1f2937] bg-[#0b121c]">

                <h3 class="text-sm font-bold text-white uppercase tracking-wider">
                    Production Performance Log
                </h3>

            </div>

            <div class="overflow-y-auto flex-1 custom-scrollbar bg-[#091018]">

                <table class="w-full text-left">

                    <thead class="sticky top-0 bg-[#0b121c] text-[10px] uppercase text-gray-500 z-10">

                    <tr>

                        <th class="py-3 pl-6">Batch</th>

                        <th class="py-3">Product</th>

                        <th class="py-3 text-right">Output</th>

                        <th class="py-3 text-right">Yield</th>

                        <th class="py-3 pr-6">Status</th>

                    </tr>

                    </thead>

                    <tbody class="divide-y divide-[#1f2937]/50 text-sm">

                    <?php if(empty($productionLog)): ?>

                        <tr>

                            <td colspan="5"
                                class="p-8 text-center text-gray-600 italic">

                                No production records found.

                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach($productionLog as $row): ?>

                            <tr class="hover:bg-[#131c26] transition-colors">

                                <td class="py-3 pl-6">

                                    <span class="font-mono text-[#10b981] font-bold text-xs">
                                        #<?= htmlspecialchars($row['FGD_batch_id']) ?>
                                    </span>

                                </td>

                                <td class="py-3 text-xs text-gray-300">
                                    <?= htmlspecialchars($row['PRD_product_name']) ?>
                                </td>

                                <td class="py-3 text-right font-mono">
                                    <?= number_format($row['FGD_total_cans']) ?>
                                </td>

                                <td class="py-3 text-right font-mono text-green-400">
                                    <?= number_format($row['FGD_actual_yield_rate'],1) ?>%
                                </td>

                                <td class="py-3 pr-6">

                                    <?php if($row['FGD_status']=='Ready_To_Export'): ?>

                                        <span class="bg-green-500/20 text-green-400 border border-green-900/30 px-2 py-1 rounded text-[10px] uppercase font-bold">
                                            Ready
                                        </span>

                                    <?php elseif($row['FGD_status']=='Exported'): ?>

                                        <span class="bg-blue-500/20 text-blue-400 border border-blue-900/30 px-2 py-1 rounded text-[10px] uppercase font-bold">
                                            Exported
                                        </span>

                                    <?php else: ?>

                                        <span class="bg-yellow-500/20 text-yellow-400 border border-yellow-900/30 px-2 py-1 rounded text-[10px] uppercase font-bold">
                                            Quarantine
                                        </span>

                                    <?php endif; ?>

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

if(document.getElementById('productionChart') && chartLabels.length > 0){

    Chart.defaults.color = '#9ca3af';

    new Chart(
        document.getElementById('productionChart'),
        {
            type:'bar',

            data:{
                labels:chartLabels,

                datasets:[
                {
                    label:'Output (Cans)',
                    data:chartData,
                    backgroundColor:'#10b981'
                }]
            },

            options:{
                responsive:true,
                maintainAspectRatio:false,

                plugins:{
                    legend:{
                        display:false
                    }
                },

                scales:{
                    y:{
                        beginAtZero:true
                    }
                }
            }
        }
    );
}

</script>

</body>
</html>