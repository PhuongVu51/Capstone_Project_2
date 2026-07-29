<?php
require_once '../backend/includes/auth.php';
require_role(['Warehouse_Staff', 'Production_Manager', 'Director'], 'login.php');
require_once '../backend/models/ShiftModel.php';

$shiftModel = new ShiftModel();
$dateFilter = $_GET['date'] ?? '';

if ($dateFilter && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFilter)) {
    $dateFilter = '';
}

$closedShifts = $shiftModel->getClosedShiftHistory($dateFilter ?: null);

function shift_history_dt($value)
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
    <title><?= __('closed_shift_history') ?> | F&amp;G FOOD</title>
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
                <p class="text-xs uppercase tracking-[0.2em] text-[#10b981] font-bold"><?= __('warehouse_shift_log') ?></p>
                <h1 class="text-2xl md:text-3xl font-bold text-white mt-2"><?= __('closed_shift_history') ?></h1>
                <p class="text-sm text-gray-500 mt-1"><?= __('closed_shift_history_desc') ?></p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="shift_close.php" class="px-4 py-2 rounded bg-red-500 text-white font-bold hover:bg-red-400 transition-colors text-sm">
                    <?= __('close_shift') ?>
                </a>
                <a href="dashboard_warehouse.php" class="px-4 py-2 rounded border border-[#374151] bg-[#1f2937] text-gray-300 hover:text-white transition-colors text-sm font-semibold">
                    <?= __('back_to_dashboard') ?>
                </a>
            </div>
        </header>

        <section class="bg-[#07121a] border border-[#1f2937] rounded-lg p-5 mb-6">
            <form method="GET" class="flex flex-col md:flex-row md:items-end gap-4">
                <div>
                    <label for="date" class="block text-xs text-gray-500 uppercase tracking-wider mb-2"><?= __('filter_by_date') ?></label>
                    <input id="date" name="date" type="date" value="<?= htmlspecialchars($dateFilter) ?>" class="bg-[#0b1722] border border-[#374151] text-white rounded px-3 py-2 [color-scheme:dark] focus:outline-none focus:border-[#10b981]">
                </div>
                <button type="submit" class="px-5 py-2 rounded bg-[#10b981] text-gray-950 font-bold hover:bg-[#34d399] transition-colors">
                    <?= __('apply_filter') ?>
                </button>
                <?php if ($dateFilter): ?>
                    <a href="shift_history.php" class="px-5 py-2 rounded border border-[#374151] text-gray-300 hover:text-white transition-colors">
                        <?= __('clear_filter') ?>
                    </a>
                <?php endif; ?>
            </form>
        </section>

        <section class="space-y-5">
            <?php if (empty($closedShifts)): ?>
                <div class="bg-[#07121a] border border-[#1f2937] rounded-lg p-8 text-center">
                    <h2 class="text-xl font-bold text-white"><?= __('no_closed_shifts_title') ?></h2>
                    <p class="text-gray-500 mt-2"><?= __('no_closed_shifts_desc') ?></p>
                </div>
            <?php else: ?>
                <?php $currentDate = null; ?>
                <?php foreach ($closedShifts as $shift): ?>
                    <?php if ($currentDate !== $shift['SHF_shift_date']): ?>
                        <?php $currentDate = $shift['SHF_shift_date']; ?>
                        <h2 class="text-sm uppercase tracking-[0.2em] text-[#10b981] font-bold pt-2">
                            <?= date('d/m/Y', strtotime($currentDate)) ?>
                        </h2>
                    <?php endif; ?>

                    <?php $summary = $shift['summary']; ?>
                    <article class="bg-[#07121a] border border-[#1f2937] rounded-lg overflow-hidden">
                        <div class="p-5 border-b border-[#1f2937] flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-bold text-white">
                                    <?= htmlspecialchars($shift['SHF_shift_type']) ?> #<?= (int) $shift['SHF_shift_id'] ?>
                                </h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    <?= __('shift_closed_at') ?> <?= htmlspecialchars(shift_history_dt($shift['SHF_closed_at'])) ?>
                                    <?php if (!empty($shift['closed_by_name'])): ?>
                                        &middot; <?= __('closed_by') ?> <?= htmlspecialchars($shift['closed_by_name']) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <span class="inline-flex w-fit px-3 py-1 rounded bg-[#10b981]/10 text-[#10b981] border border-[#10b981]/30 text-xs font-bold uppercase">
                                <?= __('status_closed') ?>
                            </span>
                        </div>

                        <div class="grid grid-cols-2 lg:grid-cols-5 gap-px bg-[#1f2937]">
                            <div class="bg-[#07121a] p-4">
                                <p class="text-[11px] text-gray-500 uppercase"><?= __('total_stock_in_kg') ?></p>
                                <p class="text-lg font-mono font-bold text-white mt-1"><?= number_format((float) $summary['total_in_kg'], 2) ?></p>
                            </div>
                            <div class="bg-[#07121a] p-4">
                                <p class="text-[11px] text-gray-500 uppercase"><?= __('total_stock_out_kg') ?></p>
                                <p class="text-lg font-mono font-bold text-white mt-1"><?= number_format((float) $summary['total_out_kg'], 2) ?></p>
                            </div>
                            <div class="bg-[#07121a] p-4">
                                <p class="text-[11px] text-gray-500 uppercase"><?= __('batches_reviewed') ?></p>
                                <p class="text-lg font-mono font-bold text-white mt-1"><?= number_format((int) $summary['batch_count']) ?></p>
                            </div>
                            <div class="bg-[#07121a] p-4">
                                <p class="text-[11px] text-gray-500 uppercase"><?= __('movements') ?></p>
                                <p class="text-lg font-mono font-bold text-white mt-1"><?= number_format((int) $summary['movement_count']) ?></p>
                            </div>
                            <div class="bg-[#07121a] p-4">
                                <p class="text-[11px] text-gray-500 uppercase"><?= __('incidents_if_any') ?></p>
                                <p class="text-lg font-mono font-bold text-white mt-1"><?= number_format((int) $summary['incident_count']) ?></p>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
