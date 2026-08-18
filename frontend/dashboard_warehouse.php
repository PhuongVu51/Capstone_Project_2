<?php
require_once '../backend/includes/auth.php';
require_role(['Warehouse_Staff', 'Production_Manager', 'Director'], 'login.php');
require_once '../backend/controllers/DashboardController.php';

try {
    $controller = new DashboardController();
    $data = $controller->getWarehouseDashboardData();
    extract($data);
} catch (Exception $e) { 
    die("Error: " . $e->getMessage()); 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Warehouse Operations | F&G FOOD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #06121a; color: #d1d5db; font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="flex min-h-screen">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 p-8 md:ml-64 pt-24 md:pt-8">
        <header class="flex justify-between items-start mb-8">
            <div>
                <h1 class="text-3xl font-bold text-[#10b981]"><?= __('node_operations') ?></h1>
                <p class="text-sm text-gray-400 mt-1"><?= __('node_operations_desc') ?></p>
            </div>

            <div class="flex items-center gap-4">
                <a href="shift_history.php" class="inline-block bg-[#0f1722] border border-[#374151] text-gray-300 px-4 py-2 rounded hover:border-[#10b981] hover:text-white transition-colors"><?= __('closed_shift_history') ?></a>
                <?php if ($currentShift): ?>
                    <a href="shift_close.php" class="inline-block bg-red-500 hover:bg-red-400 text-white font-bold px-4 py-2 rounded shadow-lg shadow-red-950/30 transition-colors"><?= __('close_shift') ?></a>
                <?php else: ?>
                    <span class="inline-block bg-[#1f2937] border border-[#374151] text-gray-500 px-4 py-2 rounded cursor-not-allowed"><?= __('close_shift') ?></span>
                <?php endif; ?>
                <a href="export_report.php" class="inline-block bg-transparent border border-[#203434] text-[#cfeee0] px-4 py-2 rounded"><?= __('export_report') ?></a>
                <a href="log_batch.php" class="inline-block bg-[#10b981] text-gray-900 font-bold px-4 py-2 rounded"><?= __('log_new_batch') ?></a>
                <div class="ml-4 text-right">
                    <p class="text-sm font-semibold text-white"><?= htmlspecialchars($_SESSION['full_name']) ?></p>
                    <p class="text-xs text-gray-400"><?= __('warehouse_staff') ?></p>
                </div>
                <div class="w-10 h-10 ml-2 rounded-full bg-[#0fd081] flex items-center justify-center font-bold text-black">
                    <?= htmlspecialchars(substr($_SESSION['full_name'],0,2)) ?></div>
            </div>
        </header>

        <?php if (!empty($closedShift)): ?>
            <div class="mb-6 bg-[#07121a] border border-[#10b981] rounded-lg p-5">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold text-[#10b981] flex items-center gap-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <?= __('shift_closed_successfully') ?>
                        </h2>
                        <p class="text-sm text-gray-400 mt-1">
                            <?= __('shift_closed_at') ?> 
                            <span class="font-mono text-white"><?= date('H:i d/m/Y', strtotime($closedShift['SHF_closed_at'])) ?></span>
                            - <?= htmlspecialchars(translate_shift_name($closedShift['SHF_shift_type'])) ?>
                        </p>
                    </div>
                    <div class="flex gap-4">
                        <div class="bg-[#0f1722] border border-[#1f2937] p-3 rounded">
                            <p class="text-[11px] text-gray-500 uppercase"><?= __('total_stock_in_kg') ?></p>
                            <p class="text-lg font-bold text-white font-mono mt-1"><?= number_format((float) ($closedShiftSummary['total_in_kg'] ?? 0), 2) ?></p>
                        </div>
                        <div class="bg-[#0f1722] border border-[#1f2937] p-3 rounded">
                            <p class="text-[11px] text-gray-500 uppercase"><?= __('batches_reviewed') ?></p>
                            <p class="text-lg font-bold text-white font-mono mt-1"><?= number_format((int) ($closedShiftSummary['batch_count'] ?? 0)) ?></p>
                        </div>
                        <div class="bg-[#0f1722] border border-[#1f2937] p-3 rounded">
                            <p class="text-[11px] text-gray-500 uppercase"><?= __('incidents_if_any') ?></p>
                            <p class="text-lg font-bold text-white font-mono mt-1"><?= number_format((int) ($closedShiftSummary['incident_count'] ?? 0)) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-12 gap-6 mb-6">
            <div class="col-span-12 lg:col-span-4">
                <div class="grid grid-cols-1 gap-4">
                    <div class="bg-[#07121a] p-6 rounded-lg border border-[#102027]">
                        <p class="text-xs text-gray-400 uppercase"><?= __('total_stock') ?></p>
                        <div class="flex items-baseline justify-between">
                            <h3 class="text-3xl font-bold text-white mt-2"><?= $displayTotalUnits ?> <span class="text-sm text-gray-400"><?= __('units') ?></span></h3>
                            <div class="text-xs text-green-400"><?= __('vs_prev_week') ?></div>
                        </div>
                    </div>

                    <div class="bg-[#07121a] p-6 rounded-lg border border-[#102027]">
                        <p class="text-xs text-gray-400 uppercase"><?= __('incoming_today') ?></p>
                        <h3 class="text-2xl font-bold text-white mt-2"><?= $incomingCount ?> <span class="text-sm text-gray-400"><?= __('batches') ?></span></h3>
                        <p class="text-xs text-gray-400 mt-2"><?php $pending = $pdo->query("SELECT COUNT(*) FROM BATCHES WHERE BCH_current_stage = 'Pending_QC'")->fetchColumn(); ?><?= $pending ?> <?= __('pending_validation') ?></p>
                    </div>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-8">
                <div class="bg-[#07121a] p-6 rounded-lg border border-[#102027]">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-xs text-gray-400 uppercase"><?= __('warehouse_capacity') ?></p>
                            <h3 class="text-2xl font-bold text-white mt-1"><?= number_format($capacityPercent,0) ?>%</h3>
                        </div>
                        <div class="text-sm text-gray-400"><?= $remainingUnits ?> <?= __('remaining') ?></div>
                    </div>
                    <div class="mt-4 bg-[#04121a] rounded-full h-3 overflow-hidden border border-[#0f2b22]">
                        <div style="width:<?= min(100, $capacityPercent) ?>%" class="h-3 bg-gradient-to-r from-[#0fd081] to-[#10b981]"></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500 mt-2"><span><?= __('critical_at_95') ?></span><span><?= __('optimal_range') ?></span></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12 lg:col-span-8">
                <div class="bg-[#07121a] p-6 rounded-lg border border-[#102027]">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-white"><?= __('live_stock_movements') ?></h3>
                        <div class="flex items-center gap-3 text-sm text-gray-400">
                            <button title="Refresh" class="p-2 bg-[#061b1a] rounded">↻</button>
                            <button title="Filter" class="p-2 bg-[#061b1a] rounded">☰</button>
                        </div>
                    </div>
                    <div class="overflow-hidden">
                        <table class="w-full text-left">
                            <thead class="text-gray-400 text-xs uppercase bg-[#041a1a]">
                                <tr>
                                    <th class="p-3"><?= __('batch_id') ?></th>
                                    <th class="p-3"><?= __('commodity') ?></th>
                                    <th class="p-3"><?= __('quantity') ?></th>
                                    <th class="p-3"><?= __('status') ?></th>
                                    <th class="p-3"><?= __('time') ?></th>
                                    <th class="p-3 text-right">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($movements)): ?>
                                    <tr><td colspan="6" class="p-6 text-center text-gray-500"><?= __('no_movements_found') ?></td></tr>
                                <?php else: ?>
                                    <?php foreach ($movements as $m): ?>
                                        <?php $realBatchId = $m['BCH_batch_id'] ?? ''; ?>
                                        <tr class="border-t border-[#0f2420] hover:bg-[#091e23] transition-colors">
                                            <td class="p-3 text-[#10b981] font-mono font-bold">
                                                <?php if ($realBatchId): ?>
                                                    <button type="button" onclick="openBatchDetailModal('<?= htmlspecialchars($realBatchId) ?>')" class="hover:underline text-left">
                                                        <?= htmlspecialchars($realBatchId) ?>
                                                    </button>
                                                <?php else: ?>
                                                    <?= htmlspecialchars($m['STM_reference_code']) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-3"><?= htmlspecialchars($m['PRD_product_name'] ?? '') ?></td>
                                            <td class="p-3 font-mono font-bold"><?= number_format($m['STM_quantity_kg'],0) ?> kg</td>
                                            <td class="p-3"><span class="text-xs bg-[#0d3b2f] text-[#9ff1d1] px-2.5 py-1 rounded font-bold"><?= htmlspecialchars($m['STM_movement_type']) ?></span></td>
                                            <td class="p-3 text-sm text-gray-400 font-mono"><?= date('H:i:s', strtotime($m['STM_timestamp'])) ?></td>
                                            <td class="p-3 text-right">
                                                <?php if ($realBatchId): ?>
                                                    <div class="flex justify-end gap-2 items-center">
                                                        <button type="button" onclick="openBatchDetailModal('<?= htmlspecialchars($realBatchId) ?>')" title="Xem chi tiết lô hàng (Detail)" class="p-1.5 bg-[#0f2937] hover:bg-[#10b981]/20 text-[#10b981] rounded border border-[#10b981]/30 transition-all flex items-center gap-1 text-xs">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                        </button>
                                                        <button type="button" onclick="openBatchEditModal('<?= htmlspecialchars($realBatchId) ?>', 'dashboard_warehouse.php')" title="Chỉnh sửa lô hàng" class="p-1.5 bg-[#0f2937] hover:bg-amber-500/20 text-amber-400 rounded border border-amber-500/30 transition-all flex items-center gap-1 text-xs">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-xs text-gray-500">-</span>
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

            <div class="col-span-12 lg:col-span-4">
                <div class="bg-[#07121a] p-6 rounded-lg border border-[#102027]">
                    <h3 class="text-lg font-semibold text-white mb-4"><?= __('node_status') ?></h3>
                    <div class="mb-4">
                        <img src="/assets/cam-placeholder.jpg" alt="Camera" class="w-full rounded-lg border border-[#09201b]"> 
                    </div>
                    <div class="space-y-3">
                        <div>
                            <div class="flex justify-between text-sm text-gray-400"><span><?= __('environmental_temp') ?></span><span class="text-white"><?= isset($node['STZ_current_temp_c']) ? htmlspecialchars($node['STZ_current_temp_c']).'°C' : '—' ?></span></div>
                            <div class="mt-2 bg-[#04121a] rounded-full h-2"><div style="width:<?= isset($node['STZ_current_temp_c']) ? min(100,($node['STZ_current_temp_c']+10)*2) : 0 ?>%" class="h-2 bg-[#10b981]"></div></div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm text-gray-400"><span><?= __('humidity_level') ?></span><span class="text-white"><?= isset($node['STZ_current_humidity_pct']) ? htmlspecialchars($node['STZ_current_humidity_pct']).'%' : '—' ?></span></div>
                            <div class="mt-2 bg-[#04121a] rounded-full h-2"><div style="width:<?= isset($node['STZ_current_humidity_pct']) ? min(100,$node['STZ_current_humidity_pct']) : 0 ?>%" class="h-2 bg-[#0fd081]"></div></div>
                        </div>
                        <div class="mt-4 p-3 bg-[#05171a] rounded border border-[#0f2923]">
                            <p class="text-xs text-[#10b981]"><?= __('systems_nominal') ?></p>
                            <p class="text-[12px] text-gray-400"><?= __('systems_nominal_desc') ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include 'includes/batch_modal.php'; ?>
</body>
</html>
