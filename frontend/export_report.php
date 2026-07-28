<?php
// Đường dẫn: frontend/export_report.php
require_once '../backend/includes/auth.php';
require_role(['Warehouse_Staff', 'Production_Manager', 'Director', 'QC'], 'login.php');
require_once '../backend/connection/db_connect.php';

$lang = $_SESSION['lang'] ?? 'vi';
$report_type = $_GET['type'] ?? 'inventory';
$productNameCol = ($lang === 'en') ? 'COALESCE(p.PRD_product_name_en, p.PRD_product_name)' : 'p.PRD_product_name';
$supplierNameCol = ($lang === 'en') ? 'COALESCE(s.SUP_supplier_name_en, s.SUP_supplier_name)' : 's.SUP_supplier_name';

// Fetch sample data for report preview based on type
try {
    if ($report_type === 'qc') {
        $stmt = $pdo->query("
            SELECT q.QCI_inspection_id, q.QCI_batch_id, q.QCI_inspector_comments, q.QCI_rejected_quantity_kg, q.QCI_inspection_date, b.BCH_current_stage, $productNameCol AS PRD_product_name
            FROM QC_INSPECTIONS q
            LEFT JOIN BATCHES b ON q.QCI_batch_id = b.BCH_batch_id
            LEFT JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
            ORDER BY q.QCI_inspection_date DESC LIMIT 20
        ");
        $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $reportTitle = ($lang === 'en') ? 'QC Inspection Audit Report' : 'Báo Cáo Kiểm Định Chất Lượng QC';
    } elseif ($report_type === 'movements') {
        $stmt = $pdo->query("
            SELECT sm.STM_reference_code, sm.STM_batch_id, sm.STM_movement_type, sm.STM_quantity_kg, sm.STM_timestamp, u.USR_full_name
            FROM STOCK_MOVEMENTS sm
            LEFT JOIN USERS u ON sm.STM_user_id = u.USR_user_id
            ORDER BY sm.STM_timestamp DESC LIMIT 20
        ");
        $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $reportTitle = ($lang === 'en') ? 'Stock Movements & Transfer Audit Log' : 'Nhật Ký Nhập Xuất & Luân Chuyển Kho';
    } else {
        // Default Inventory Report
        $stmt = $pdo->query("
            SELECT b.BCH_batch_id, $productNameCol AS PRD_product_name, $supplierNameCol AS SUP_supplier_name, b.BCH_available_stock_kg, b.BCH_received_date, b.BCH_expiry_date, b.BCH_current_stage
            FROM BATCHES b
            LEFT JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
            LEFT JOIN SUPPLIERS s ON b.BCH_supplier_id = s.SUP_supplier_id
            ORDER BY b.BCH_received_date DESC LIMIT 20
        ");
        $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $reportTitle = ($lang === 'en') ? 'Warehouse Inventory & Stock Ledger Report' : 'Báo Cáo Tồn Kho & Sổ Cái Hàng Hóa';
    }
} catch (PDOException $e) {
    $reportData = [];
    $reportTitle = 'Operational Report';
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($reportTitle) ?> | F&G FOOD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #06121a; color: #d1d5db; font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="flex min-h-screen">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 p-8 md:ml-64 pt-24 md:pt-8">
        
        <!-- Header -->
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm text-gray-400 mb-1">
                    <a href="dashboard_warehouse.php" class="hover:text-emerald-400 transition-colors"><?= ($lang === 'en') ? 'Warehouse' : 'Quản Lý Kho' ?></a>
                    <span>/</span>
                    <span class="text-emerald-400 font-medium"><?= ($lang === 'en') ? 'Export Reports' : 'Xuất Báo Cáo' ?></span>
                </div>
                <h1 class="text-3xl font-bold text-white tracking-tight"><?= htmlspecialchars($reportTitle) ?></h1>
                <p class="text-sm text-gray-400 mt-1"><?= ($lang === 'en') ? 'Generate and export operational ledger data to CSV / Excel' : 'Xuất báo cáo chi tiết tồn kho và vận hành theo định dạng CSV / Excel' ?></p>
            </div>
            
            <div class="flex items-center gap-3">
                <button onclick="downloadReportCSV()" class="flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-gray-950 font-semibold px-5 py-2.5 rounded-lg shadow-lg shadow-emerald-500/20 transition-all cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span><?= ($lang === 'en') ? 'Download CSV' : 'Tải File CSV' ?></span>
                </button>
                <button onclick="window.print()" class="flex items-center gap-2 bg-slate-800 hover:bg-slate-700 text-gray-200 border border-slate-700 px-4 py-2.5 rounded-lg transition-all cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span><?= ($lang === 'en') ? 'Print' : 'In Báo Cáo' ?></span>
                </button>
            </div>
        </header>

        <!-- Filters Form -->
        <div class="bg-[#0f1722] p-6 rounded-xl border border-slate-800 mb-8 shadow-xl">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2"><?= ($lang === 'en') ? 'Report Type' : 'Loại Báo Cáo' ?></label>
                    <select name="type" onchange="this.form.submit()" class="w-full bg-[#162232] border border-slate-700 text-white rounded-lg px-4 py-2.5 focus:outline-none focus:border-emerald-500">
                        <option value="inventory" <?= $report_type === 'inventory' ? 'selected' : '' ?>><?= ($lang === 'en') ? 'Inventory Ledger' : 'Tồn Kho Hàng Hóa' ?></option>
                        <option value="qc" <?= $report_type === 'qc' ? 'selected' : '' ?>><?= ($lang === 'en') ? 'QC Inspections' : 'Kiểm Định QC' ?></option>
                        <option value="movements" <?= $report_type === 'movements' ? 'selected' : '' ?>><?= ($lang === 'en') ? 'Stock Movements' : 'Lịch Sử Nhập Xuất' ?></option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2"><?= ($lang === 'en') ? 'From Date' : 'Từ Ngày' ?></label>
                    <input type="date" name="from_date" value="<?= date('Y-m-01') ?>" class="w-full bg-[#162232] border border-slate-700 text-white rounded-lg px-4 py-2.5 focus:outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2"><?= ($lang === 'en') ? 'To Date' : 'Đến Ngày' ?></label>
                    <input type="date" name="to_date" value="<?= date('Y-m-d') ?>" class="w-full bg-[#162232] border border-slate-700 text-white rounded-lg px-4 py-2.5 focus:outline-none focus:border-emerald-500">
                </div>
                <div>
                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-medium py-2.5 rounded-lg transition-all">
                        <?= ($lang === 'en') ? 'Apply Filter' : 'Áp Dụng Lọc' ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Data Preview Table -->
        <div class="bg-[#0f1722] rounded-xl border border-slate-800 overflow-hidden shadow-xl">
            <div class="p-5 border-b border-slate-800 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-white"><?= ($lang === 'en') ? 'Live Preview Data' : 'Xem Trước Dữ Liệu' ?></h3>
                <span class="text-xs bg-emerald-500/10 text-emerald-400 px-3 py-1 rounded-full border border-emerald-500/20 font-medium">
                    <?= count($reportData) ?> <?= ($lang === 'en') ? 'records loaded' : 'bản ghi' ?>
                </span>
            </div>

            <div class="overflow-x-auto">
                <table id="reportTable" class="w-full text-left text-sm text-gray-300">
                    <thead class="bg-[#162232] text-xs uppercase text-gray-400 border-b border-slate-800">
                        <tr>
                            <?php if ($report_type === 'qc'): ?>
                                <th class="p-4">Batch ID</th>
                                <th class="p-4">Product Name</th>
                                <th class="p-4">Rejected Qty (kg)</th>
                                <th class="p-4">Inspection Date</th>
                                <th class="p-4">Stage</th>
                                <th class="p-4">Comments</th>
                            <?php elseif ($report_type === 'movements'): ?>
                                <th class="p-4">Ref Code</th>
                                <th class="p-4">Batch ID</th>
                                <th class="p-4">Type</th>
                                <th class="p-4">Quantity (kg)</th>
                                <th class="p-4">Timestamp</th>
                                <th class="p-4">Operator</th>
                            <?php else: ?>
                                <th class="p-4">Batch ID</th>
                                <th class="p-4">Product Name</th>
                                <th class="p-4">Supplier</th>
                                <th class="p-4">Available Stock (kg)</th>
                                <th class="p-4">Received Date</th>
                                <th class="p-4">Expiry Date</th>
                                <th class="p-4">Stage</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <?php if (empty($reportData)): ?>
                            <tr>
                                <td colspan="7" class="p-8 text-center text-gray-500"><?= ($lang === 'en') ? 'No report data found.' : 'Không tìm thấy dữ liệu báo cáo.' ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reportData as $row): ?>
                                <tr class="hover:bg-slate-800/40 transition-colors">
                                    <?php foreach ($row as $key => $val): ?>
                                        <td class="p-4 font-mono text-xs text-gray-200"><?= htmlspecialchars($val ?? '-') ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <script>
        function downloadReportCSV() {
            const table = document.getElementById('reportTable');
            let csv = [];
            for (let row of table.rows) {
                let cols = [];
                for (let cell of row.cells) {
                    cols.push('"' + cell.innerText.replace(/"/g, '""') + '"');
                }
                csv.push(cols.join(','));
            }
            const blob = new Blob(['\uFEFF' + csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'FG_FOOD_<?= htmlspecialchars($report_type) ?>_Report_<?= date('Ymd') ?>.csv';
            link.click();
        }
    </script>
</body>
</html>
