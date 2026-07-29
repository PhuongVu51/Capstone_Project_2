<?php
require_once '../backend/includes/auth.php';
require_role(['Warehouse_Staff', 'Production_Manager', 'Director'], 'login.php');
require_once '../backend/controllers/InventoryController.php';

try {
    $controller = new InventoryController();
    $data = $controller->getInventoryData();
    extract($data);
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Inventory | F&G FOOD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #06121a; color: #e5e7eb; font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen flex">
    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 p-6 lg:p-8 md:ml-64 pt-24 md:pt-8">
        <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-6">
            <div>
                <h1 class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-[#10b981] to-[#34d399] tracking-tight">
                <?= __('inventory_management') ?>
                </h1>
                <p class="text-sm text-gray-400 mt-1">Search, filter, and monitor batch inventory across the warehouse.</p>
            </div>
            <div class="flex items-center gap-3">
                <?php if ($userRole === 'Director' || $userRole === 'Warehouse_Staff'): ?>
                    <a href="log_batch.php" class="inline-block bg-[#10b981] text-gray-900 font-semibold px-4 py-2 rounded">+ Log New Batch</a>
                <?php elseif ($userRole === 'Production_Manager'): ?>
                    <a href="request_material.php" class="inline-block bg-[#60a5fa] text-gray-900 font-semibold px-4 py-2 rounded">Request Material</a>
                    <a href="log_finished_goods.php" class="inline-block bg-[#10b981] text-gray-900 font-semibold px-4 py-2 rounded">Log Finished Goods</a>
                <?php endif; ?>
                <div class="text-right">
                    <p class="text-sm font-semibold text-white"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></p>
                    <p class="text-xs text-gray-400"><?php echo htmlspecialchars($userRole); ?></p>
                </div>
            </div>
        </header>

        <?php if (!empty($messages)): ?>
            <div class="mb-4 space-y-2">
                <?php foreach ($messages as $message): ?>
                    <div class="rounded border border-[#0f2b22] bg-[#07161b] px-4 py-3 text-sm text-[#9ff1d1]">
                        <?php echo $message; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <section class="bg-[#07121a] border border-[#102027] rounded-lg p-4 mb-6">
            <form method="GET" class="flex flex-col md:flex-row gap-3">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="<?= __('search_placeholder') ?>" class="flex-1 rounded border border-[#203434] bg-[#06121a] px-3 py-2 text-sm text-gray-200 focus:outline-none focus:border-[#10b981]" />
                <select name="status" class="appearance-none bg-[#0a1118] border border-[#374151] rounded-xl pl-11 pr-10 py-2.5 text-sm text-gray-300 focus:outline-none focus:border-[#10b981] focus:ring-1 focus:ring-[#10b981] transition-all">
                    <option value=""><?= __('filter_status') ?></option>
                    <option value="In Stock" <?php echo $statusFilter === 'In Stock' ? 'selected' : ''; ?>><?= __('in_stock') ?></option>
                    <option value="Low Stock" <?php echo $statusFilter === 'Low Stock' ? 'selected' : ''; ?>><?= __('low_stock') ?></option>
                    <option value="Out of Stock" <?php echo $statusFilter === 'Out of Stock' ? 'selected' : ''; ?>><?= __('out_of_stock') ?></option>
                </select>
                <button type="submit" class="rounded bg-[#10b981] px-4 py-2 text-sm font-semibold text-gray-900">Filter</button>
                <?php if ($search !== '' || $statusFilter !== ''): ?>
                    <a href="inventory.php" class="rounded border border-[#203434] px-4 py-2 text-sm text-gray-300">Clear</a>
                <?php endif; ?>
            </form>
        </section>

        <?php if ($selectedBatch): ?>
            <section class="bg-[#0f1722] border border-emerald-500/30 shadow-2xl rounded-xl p-6 mb-8 relative">
                <div class="flex justify-between items-start gap-4 pb-4 border-b border-slate-800">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs uppercase font-bold tracking-wider text-emerald-400 bg-emerald-500/10 px-2.5 py-1 rounded border border-emerald-500/20">
                                <?= ($lang === 'en') ? 'Selected Batch Details' : 'Chi Tiết Lô Hàng' ?>
                            </span>
                            <span class="text-xs text-gray-400">ID: <?= htmlspecialchars($selectedBatch['BCH_batch_id']); ?></span>
                        </div>
                        <h2 class="text-2xl font-bold text-white mt-1"><?= htmlspecialchars($selectedBatch['PRD_product_name'] ?? 'N/A'); ?></h2>
                    </div>
                    <a href="inventory.php" class="bg-slate-800 hover:bg-slate-700 text-gray-300 px-4 py-2 rounded-lg text-sm transition-all font-medium">
                        <?= ($lang === 'en') ? 'Close' : 'Đóng' ?>
                    </a>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6 text-sm">
                    <div class="bg-[#162232] rounded-lg p-4 border border-slate-800">
                        <p class="text-xs text-gray-400 font-medium"><?= ($lang === 'en') ? 'Storage Zone' : 'Khu Vực Lưu Kho' ?></p>
                        <p class="text-white font-semibold text-base mt-1"><?= htmlspecialchars($selectedBatch['STZ_zone_name'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="bg-[#162232] rounded-lg p-4 border border-slate-800">
                        <p class="text-xs text-gray-400 font-medium"><?= ($lang === 'en') ? 'Available Stock' : 'Tồn Kho Khả Dụng' ?></p>
                        <p class="text-emerald-400 font-bold text-base mt-1"><?= number_format((float) ($selectedBatch['BCH_available_stock_kg'] ?? 0), 2); ?> kg</p>
                    </div>
                    <div class="bg-[#162232] rounded-lg p-4 border border-slate-800">
                        <p class="text-xs text-gray-400 font-medium"><?= ($lang === 'en') ? 'Stage / Status' : 'Trạng Thái' ?></p>
                        <p class="text-amber-400 font-semibold text-base mt-1"><?= htmlspecialchars($selectedBatch['BCH_current_stage'] ?? 'In Stock'); ?></p>
                    </div>
                    <div class="bg-[#162232] rounded-lg p-4 border border-slate-800">
                        <p class="text-xs text-gray-400 font-medium"><?= ($lang === 'en') ? 'Supplier' : 'Nhà Cung Cấp' ?></p>
                        <p class="text-white font-semibold text-base mt-1"><?= htmlspecialchars($selectedBatch['SUP_supplier_name'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <section class="bg-[#07121a] border border-[#102027] rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-[#041a1a] text-gray-400 uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider"><?= __('batch_id') ?></th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider"><?= __('product') ?></th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider"><?= __('available_stock') ?></th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider"><?= __('status') ?></th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider"><?= __('storage_zone') ?></th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider"><?= __('received_date') ?></th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider"><?= __('actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($inventoryItems)): ?>
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center text-gray-500">No inventory records found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($inventoryItems as $item): ?>
                                <?php
                                $status = $item['stock_status'] ?? 'In Stock';
                                $badgeClasses = 'bg-[#0d3b2f] text-[#9ff1d1]';
                                if ($status === 'Low Stock') {
                                    $badgeClasses = 'bg-[#3b2f0d] text-[#facc15]';
                                } elseif ($status === 'Out of Stock') {
                                    $badgeClasses = 'bg-[#3b0d0d] text-[#f87171]';
                                }
                                ?>
                                <tr class="border-t border-[#0f2420]">
                                    <td class="px-4 py-3 font-mono text-[#10b981]">
                                        <a href="inventory.php?view_id=<?php echo urlencode($item['BCH_batch_id']); ?>" class="hover:underline">
                                            <?php echo htmlspecialchars($item['BCH_batch_id']); ?>
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-white"><?php echo htmlspecialchars($item['PRD_product_name'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3"><?php echo number_format((float) ($item['BCH_available_stock_kg'] ?? 0), 2); ?> kg</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded px-2 py-1 text-xs font-medium <?php echo $badgeClasses; ?>">
                                            <?= __($item['stock_status'] == 'In Stock' ? 'in_stock' : ($item['stock_status'] == 'Low Stock' ? 'low_stock' : 'out_of_stock')) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-400"><?php echo htmlspecialchars($item['STZ_zone_name'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3 text-gray-400"><?php echo htmlspecialchars(date('d/m/Y', strtotime($item['BCH_received_date']))); ?></td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex justify-end gap-4 items-center">
                                            <a href="inventory.php?view_id=<?php echo urlencode($item['BCH_batch_id']); ?>" class="text-[#10b981] hover:text-white transition-colors" title="View details">
                                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            </a>
                                            <?php if ($userRole === 'Production_Manager'): ?>
                                                <a href="allocate_batch.php?batch_id=<?= urlencode($item['BCH_batch_id']) ?>" class="p-2 text-blue-400 hover:bg-blue-400/10 rounded-lg transition-colors group" title="<?= __('request_material') ?>">
                                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                </a>
                                            <?php elseif ($userRole === 'Director' || $userRole === 'Warehouse_Staff'): ?>
                                                <a href="log_batch.php?batch_id=<?php echo urlencode($item['BCH_batch_id']); ?>" class="text-blue-400 hover:text-white transition-colors" title="Edit batch">
                                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($userRole === 'Director' || $userRole === 'Warehouse_Staff'): ?>
                                                <form action="../backend/controllers/StockController.php?action=delete_batch" method="POST" class="inline" onsubmit="return confirm('<?= __('delete_confirm') ?>');">
                                                    <input type="hidden" name="delete_batch" value="1" />
                                                    <input type="hidden" name="batch_id" value="<?php echo htmlspecialchars($item['BCH_batch_id']); ?>" />
                                                    <button type="submit" class="p-2 text-red-400 hover:bg-red-400/10 rounded-lg transition-colors group" title="<?= __('delete') ?>">
                                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="flex flex-col md:flex-row justify-between items-center gap-3 mt-6 text-sm text-gray-400">
            <p>Showing <?php echo count($inventoryItems); ?> of <?php echo $totalRecords; ?> records.</p>
            <div class="flex items-center gap-2">
                <?php if ($page > 1): ?>
                    <a href="inventory.php?search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&page=<?php echo max(1, $page - 1); ?>" class="rounded border border-[#203434] px-3 py-2">Prev</a>
                <?php endif; ?>
                <span class="px-3 py-2 rounded bg-[#07121a] border border-[#203434]">Page <?php echo $page; ?> / <?php echo $totalPages; ?></span>
                <?php if ($page < $totalPages): ?>
                    <a href="inventory.php?search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&page=<?php echo min($totalPages, $page + 1); ?>" class="rounded border border-[#203434] px-3 py-2">Next</a>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
