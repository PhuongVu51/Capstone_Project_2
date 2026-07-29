<?php
require_once '../backend/includes/auth.php';
require_role(['Warehouse_Staff', 'Production_Manager', 'Director'], 'login.php');
require_once '../backend/models/ShiftModel.php';

$shiftModel = new ShiftModel();
$lang = $_SESSION['lang'] ?? 'vi';
$currentShift = $shiftModel->getCurrentOpenShift();
$movements = [];
$summary = [
    'total_in_kg' => 0,
    'total_out_kg' => 0,
    'batch_count' => 0,
    'movement_count' => 0,
    'incident_count' => 0,
];

if ($currentShift) {
    $movements = $shiftModel->getMovementsForShift($currentShift['SHF_shift_id']);
    $summary = $shiftModel->getShiftSummary($currentShift['SHF_shift_id']);
}

$errorMessages = [
    'invalid_shift' => __('shift_error_invalid'),
    'shift_not_found' => __('shift_error_not_found'),
    'shift_already_closed' => __('shift_error_already_closed'),
];
$errorCode = $_GET['error'] ?? '';
$errorMessage = $errorMessages[$errorCode] ?? ($errorCode ? __('shift_error_close_failed') : '');

function shift_display_name($shift)
{
    if (!$shift) {
        return '';
    }

    return $shift['SHF_shift_date'] . ' - ' . $shift['SHF_shift_type'];
}

function shift_format_dt($value)
{
    if (!$value) {
        return '-';
    }

    return date('H:i d/m/Y', strtotime($value));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('close_shift_review_title') ?> | F&amp;G FOOD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #06121a; color: #d1d5db; font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="flex min-h-screen overflow-x-hidden bg-[#06121a]">
    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 md:ml-64 p-6 md:p-8 pt-24 md:pt-8">
        <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8 pb-4 border-b border-[#1f2937] gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-[#10b981] font-bold"><?= __('shift_closing_workflow') ?></p>
                <h1 class="text-2xl md:text-3xl font-bold text-white mt-2"><?= __('close_shift_review_title') ?></h1>
                <p class="text-sm text-gray-500 mt-1"><?= __('close_shift_review_desc') ?></p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="shift_history.php" class="px-4 py-2 rounded border border-[#374151] bg-[#0f1722] text-gray-300 hover:text-white hover:border-[#10b981] transition-colors text-sm font-semibold">
                    <?= __('closed_shift_history') ?>
                </a>
                <a href="dashboard_warehouse.php" class="px-4 py-2 rounded border border-[#374151] bg-[#1f2937] text-gray-300 hover:text-white transition-colors text-sm font-semibold">
                    <?= __('back_to_dashboard') ?>
                </a>
            </div>
        </header>

        <?php if ($errorMessage): ?>
            <div class="mb-6 p-4 bg-red-900/40 border border-red-700 text-red-100 rounded-lg">
                <?= $errorMessage ?>
            </div>
        <?php endif; ?>

        <?php if (!$currentShift): ?>
            <div class="bg-[#07121a] border border-[#1f2937] rounded-lg p-8 text-center">
                <h2 class="text-xl font-bold text-white"><?= __('no_open_shift_title') ?></h2>
                <p class="text-gray-500 mt-2"><?= __('no_open_shift_desc') ?></p>
                <a href="shift_history.php" class="inline-flex mt-6 px-5 py-2.5 rounded bg-[#10b981] text-gray-950 font-bold hover:bg-[#34d399] transition-colors">
                    <?= __('view_shift_history') ?>
                </a>
            </div>
        <?php else: ?>
            <section class="grid grid-cols-1 xl:grid-cols-4 gap-5 mb-6">
                <div class="xl:col-span-1 bg-[#07121a] border border-[#1f2937] rounded-lg p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wider"><?= __('current_shift') ?></p>
                    <h2 class="text-xl font-bold text-white mt-2"><?= htmlspecialchars(shift_display_name($currentShift)) ?></h2>
                    <p class="text-sm text-[#10b981] mt-2"><?= __('status_open') ?></p>
                </div>
                <div class="bg-[#07121a] border border-[#1f2937] rounded-lg p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wider"><?= __('total_stock_in_kg') ?></p>
                    <h3 class="text-2xl font-bold text-white mt-2 font-mono"><?= number_format((float) $summary['total_in_kg'], 2) ?></h3>
                </div>
                <div class="bg-[#07121a] border border-[#1f2937] rounded-lg p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wider"><?= __('batches_reviewed') ?></p>
                    <h3 class="text-2xl font-bold text-white mt-2 font-mono"><?= number_format((int) $summary['batch_count']) ?></h3>
                </div>
                <div class="bg-[#07121a] border border-[#1f2937] rounded-lg p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wider"><?= __('incidents_if_any') ?></p>
                    <h3 class="text-2xl font-bold text-white mt-2 font-mono"><?= number_format((int) $summary['incident_count']) ?></h3>
                </div>
            </section>

            <section class="bg-[#07121a] border border-[#1f2937] rounded-lg overflow-hidden">
                <div class="p-5 border-b border-[#1f2937] flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-[#10b981] font-bold"><?= __('step_1_review') ?></p>
                        <h2 class="text-lg font-bold text-white mt-1"><?= __('shift_movements_review') ?></h2>
                    </div>
                    <p class="text-sm text-gray-500"><?= number_format((int) $summary['movement_count']) ?> <?= __('movements_recorded') ?></p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left min-w-[900px]">
                        <thead class="bg-[#041a1a] text-gray-400 text-xs uppercase">
                            <tr>
                                <th class="p-3"><?= __('time') ?></th>
                                <th class="p-3"><?= __('reference_code') ?></th>
                                <th class="p-3"><?= __('batch_id') ?></th>
                                <th class="p-3"><?= __('product') ?></th>
                                <th class="p-3"><?= __('movement_type') ?></th>
                                <th class="p-3 text-right"><?= __('quantity') ?></th>
                                <th class="p-3"><?= __('operator') ?></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#102027]">
                            <?php if (empty($movements)): ?>
                                <tr>
                                    <td colspan="7" class="p-8 text-center text-gray-500"><?= __('no_movements_for_shift') ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($movements as $movement): ?>
                                    <?php
                                        $productName = ($lang === 'en' && !empty($movement['PRD_product_name_en']))
                                            ? $movement['PRD_product_name_en']
                                            : $movement['PRD_product_name'];
                                        $typeClass = $movement['STM_movement_type'] === 'IN'
                                            ? 'text-[#10b981] bg-[#10b981]/10'
                                            : ($movement['STM_movement_type'] === 'OUT' ? 'text-blue-300 bg-blue-500/10' : 'text-yellow-300 bg-yellow-500/10');
                                    ?>
                                    <tr class="hover:bg-[#0d1821] transition-colors">
                                        <td class="p-3 text-gray-400 font-mono text-xs"><?= htmlspecialchars(shift_format_dt($movement['STM_timestamp'])) ?></td>
                                        <td class="p-3 text-gray-300 font-mono text-xs"><?= htmlspecialchars($movement['STM_reference_code']) ?></td>
                                        <td class="p-3 text-[#10b981] font-mono text-xs"><?= htmlspecialchars($movement['STM_batch_id']) ?></td>
                                        <td class="p-3 text-gray-200"><?= htmlspecialchars($productName ?? '') ?></td>
                                        <td class="p-3"><span class="px-2 py-1 rounded text-xs font-bold <?= $typeClass ?>"><?= htmlspecialchars($movement['STM_movement_type']) ?></span></td>
                                        <td class="p-3 text-right font-mono text-white"><?= number_format((float) $movement['STM_quantity_kg'], 2) ?> kg</td>
                                        <td class="p-3 text-gray-400"><?= htmlspecialchars($movement['USR_full_name'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="p-5 border-t border-[#1f2937] flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-[#06121a]">
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-yellow-300 font-bold"><?= __('step_2_confirm') ?></p>
                        <p class="text-sm text-gray-400 mt-1"><?= __('close_shift_confirm_hint') ?></p>
                    </div>
                    <button type="button" onclick="openCloseShiftModal()" class="w-full md:w-auto bg-red-500 hover:bg-red-400 text-white font-bold px-6 py-3 rounded shadow-lg shadow-red-950/30 transition-colors">
                        <?= __('close_shift') ?>
                    </button>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <?php if ($currentShift): ?>
        <div id="closeShiftModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/70 backdrop-blur-sm px-4">
            <div class="w-full max-w-lg bg-[#0f1722] border border-red-500/40 rounded-lg shadow-2xl overflow-hidden">
                <div class="p-6 border-b border-[#1f2937]">
                    <p class="text-xs uppercase tracking-[0.2em] text-red-300 font-bold"><?= __('confirm_close_shift') ?></p>
                    <h2 class="text-xl font-bold text-white mt-2"><?= htmlspecialchars(shift_display_name($currentShift)) ?></h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-200 leading-relaxed"><?= __('close_shift_confirm_message') ?></p>
                    <div class="mt-5 grid grid-cols-3 gap-3 text-center">
                        <div class="bg-[#07121a] rounded border border-[#1f2937] p-3">
                            <p class="text-[11px] text-gray-500 uppercase"><?= __('stock_in') ?></p>
                            <p class="text-sm font-mono text-white mt-1"><?= number_format((float) $summary['total_in_kg'], 1) ?> kg</p>
                        </div>
                        <div class="bg-[#07121a] rounded border border-[#1f2937] p-3">
                            <p class="text-[11px] text-gray-500 uppercase"><?= __('batches') ?></p>
                            <p class="text-sm font-mono text-white mt-1"><?= number_format((int) $summary['batch_count']) ?></p>
                        </div>
                        <div class="bg-[#07121a] rounded border border-[#1f2937] p-3">
                            <p class="text-[11px] text-gray-500 uppercase"><?= __('incidents') ?></p>
                            <p class="text-sm font-mono text-white mt-1"><?= number_format((int) $summary['incident_count']) ?></p>
                        </div>
                    </div>
                </div>
                <div class="p-5 bg-[#07121a] border-t border-[#1f2937] flex flex-col sm:flex-row gap-3 sm:justify-end">
                    <button type="button" onclick="closeCloseShiftModal()" class="px-5 py-2.5 rounded border border-[#374151] text-gray-300 hover:text-white hover:bg-[#1f2937] transition-colors font-semibold">
                        <?= __('cancel') ?>
                    </button>
                    <form action="../backend/controllers/ShiftController.php?action=close" method="POST">
                        <input type="hidden" name="shift_id" value="<?= (int) $currentShift['SHF_shift_id'] ?>">
                        <button type="submit" class="w-full sm:w-auto px-5 py-2.5 rounded bg-red-500 hover:bg-red-400 text-white font-bold transition-colors">
                            <?= __('confirm_close_shift') ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <script>
            function openCloseShiftModal() {
                var modal = document.getElementById('closeShiftModal');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function closeCloseShiftModal() {
                var modal = document.getElementById('closeShiftModal');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        </script>
    <?php endif; ?>
</body>
</html>
