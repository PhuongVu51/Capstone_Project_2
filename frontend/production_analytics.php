<?php

require_once '../backend/includes/auth.php';

require_role(
    ['Production_Manager','Director'],
    'login.php'
);

require_once '../backend/controllers/ProductionAnalyticsController.php';

try{

    $lang = $_SESSION['lang'] ?? 'vi';
    $controller = new ProductionAnalyticsController();

    $data = $controller->loadAnalyticsData($lang);

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

<?php include 'includes/sidebar.php'; ?>

<main class="md:ml-64 p-6 md:p-8 pt-24 md:pt-8 w-full">

    <!-- HEADER -->

    <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 pb-4 border-b border-[#1f2937] gap-4">

        <div>

            <h1 class="text-2xl font-bold text-white tracking-wide">
                <?= __('production_analytics_title') ?>
            </h1>

            <p class="text-gray-500 text-sm mt-1">
                <?= __('production_analytics_desc') ?>
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

            <?= __('export_pdf') ?>

        </button>

    </header>

    <!-- KPI -->

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">

        <div class="bg-[#0f1722] p-5 rounded-lg border border-[#1f2937]">

            <p class="text-[11px] text-gray-500 uppercase font-semibold tracking-wider">
                <?= __('total_production_output') ?>
            </p>

            <h3 class="text-3xl font-bold text-white mt-2 font-mono">
                <?= number_format($totalOutput) ?>
            </h3>

        </div>

        <div class="bg-[#0f1722] p-5 rounded-lg border border-[#1f2937]">

            <p class="text-[11px] text-gray-500 uppercase font-semibold tracking-wider">
                <?= __('average_yield') ?>
            </p>

            <h3 class="text-3xl font-bold text-green-400 mt-2 font-mono">
                <?= $averageYield ?>%
            </h3>

        </div>

        <div class="bg-[#0f1722] p-5 rounded-lg border border-[#1f2937]">

            <p class="text-[11px] text-gray-500 uppercase font-semibold tracking-wider">
                <?= __('production_batches') ?>
            </p>

            <h3 class="text-3xl font-bold text-white mt-2 font-mono">
                <?= $productionBatches ?>
            </h3>

        </div>

        <div class="bg-[#2a2112] p-5 rounded-lg border border-yellow-800/30">

            <p class="text-[11px] text-yellow-400 uppercase font-semibold tracking-wider">
                <?= __('quarantine_inventory') ?>
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
                    <?= __('production_output_trend') ?>
                </h3>

                <p class="text-xs text-gray-500 mb-6">
                    <?= __('latest_production_output_by_batch') ?>
                </p>

            </div>

            <div class="relative h-[260px]">

                <?php if(empty($chartLabels)): ?>

                    <div class="h-full flex items-center justify-center text-gray-600 italic text-sm">
                        <?= __('no_production_data_available') ?>
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
                    <?= __('production_performance_log') ?>
                </h3>

            </div>

            <div class="overflow-y-auto flex-1 custom-scrollbar bg-[#091018]">

                <table class="w-full text-left">

                    <thead class="sticky top-0 bg-[#0b121c] text-[10px] uppercase text-gray-500 z-10">

                    <tr>
                        <th class="py-3 pl-5 pr-3 w-48 text-left"><?= __('batch') ?></th>
                        <th class="py-3 px-3 text-left"><?= __('product') ?></th>
                        <th class="py-3 px-4 text-right whitespace-nowrap w-24"><?= __('output') ?></th>
                        <th class="py-3 px-4 text-right whitespace-nowrap w-20"><?= __('yield') ?></th>
                        <th class="py-3 pl-4 pr-5 whitespace-nowrap w-32"><?= __('status') ?></th>
                    </tr>

                    </thead>

                    <tbody class="divide-y divide-[#1f2937]/50 text-sm">

                    <?php if(empty($productionLog)): ?>

                        <tr>

                            <td colspan="5"
                                class="p-8 text-center text-gray-600 italic">

                                <?= __('no_production_records_found') ?>

                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach($productionLog as $row): ?>

                            <tr class="hover:bg-[#131c26] transition-colors">

                            <td class="py-4 pl-5 pr-3 w-48">
                                <span class="font-mono text-[#10b981] font-bold text-xs break-all">
                                    #<?= htmlspecialchars($row['FGD_batch_id']) ?>
                                </span>
                            </td>

                            <td class="py-4 px-3 text-xs text-gray-300 max-w-[260px]">
                                <?= htmlspecialchars($row['PRD_product_name']) ?>
                            </td>

                            <td class="py-4 px-4 text-right font-mono whitespace-nowrap w-24">
                                <?= number_format($row['FGD_total_cans']) ?>
                            </td>

                            <td class="py-4 px-4 text-right font-mono text-green-400 whitespace-nowrap w-20">
                                <?= number_format($row['FGD_actual_yield_rate'],1) ?>%
                            </td>

                            <td class="py-4 pl-4 pr-5 whitespace-nowrap w-32">

                                <?php if($row['FGD_status']=='Ready_To_Export'): ?>
                                    <span class="bg-green-500/20 text-green-400 border border-green-900/30 px-2 py-1 rounded text-[10px] uppercase font-bold">
                                        <?= __('status_ready') ?>
                                    </span>
                                <?php elseif($row['FGD_status']=='Exported'): ?>
                                    <span class="bg-blue-500/20 text-blue-400 border border-blue-900/30 px-2 py-1 rounded text-[10px] uppercase font-bold">
                                        <?= __('status_exported') ?>
                                    </span>
                                <?php else: ?>
                                    <span class="bg-yellow-500/20 text-yellow-400 border border-yellow-900/30 px-2 py-1 rounded text-[10px] uppercase font-bold">
                                        <?= __('status_quarantine') ?>
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
                    label: '<?= __('output_cans') ?>',
                    data:chartData,
                    backgroundColor:'#10b981'
                }]
            },

            options:{
                responsive:true,
                maintainAspectRatio:false,

                plugins:{
                    legend:{ display:false },
                    tooltip:{
                        backgroundColor:'#1f2937',
                        titleColor:'#fff',
                        bodyColor:'#d1d5db',
                        callbacks:{
                            title: function(ctx) {
                                // Show full label in tooltip
                                return ctx[0].label;
                            }
                        }
                    }
                },

                scales:{
                    y:{
                        beginAtZero:true,
                        grid:{ color:'#1f2937' },
                        ticks:{ color:'#9ca3af' }
                    },
                    x:{
                        grid:{ display:false },
                        ticks:{
                            color:'#9ca3af',
                            maxRotation: 0,
                            maxTicksLimit: 8,
                            callback: function(val, index) {
                                // Truncate label to first 10 chars
                                const label = this.getLabelForValue(val);
                                return label.length > 12 ? label.substring(0, 12) + '…' : label;
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