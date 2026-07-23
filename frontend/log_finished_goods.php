<?php
// Đường dẫn: frontend/log_finished_goods.php
require_once '../backend/includes/auth.php';
require_role(['Production_Manager', 'Director'], 'login.php');
require_once '../backend/connection/db_connect.php';

// Tự động generate Batch ID gợi ý (Ví dụ: FG-YYYYMMDD-XXXX)
$suggested_batch_id = 'FG-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Finished Goods | ProSync Industrial</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --bg-dark: #0a1118; --bg-card: #0f1722; --accent-green: #10b981; --border-color: #1f2937; }
        body { background-color: var(--bg-dark); font-family: 'Inter', sans-serif; color: #d1d5db; }
    </style>
</head>
<body class="min-h-screen flex overflow-x-hidden">

    <!-- GỌI FILE SIDEBAR DÙNG CHUNG -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="flex-1 flex flex-col min-w-0 md:ml-64">
        <header class="h-16 border-b border-[#1f2937] bg-[#0a1118] flex items-center px-8 sticky top-0 z-10">
            <h1 class="text-xl font-bold text-white">Finished Goods Declaration</h1>
        </header>

        <div class="p-8 overflow-y-auto flex justify-center">
            <div class="bg-[#0f1722] rounded-lg border border-[#1f2937] w-full max-w-2xl p-8 shadow-xl">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-[#10b981]">Log New Production Batch</h2>
                    <p class="text-sm text-gray-500 mt-1">Register newly manufactured goods into the system inventory.</p>
                </div>

                <form action="../backend/connection/process_finished_goods.php" method="POST" class="space-y-6">
                    
                    <!-- Batch ID & Ngày sản xuất -->
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Batch ID</label>
                            <input type="text" name="batch_id" value="<?= $suggested_batch_id ?>" required class="w-full bg-[#0a1118] border border-[#374151] text-[#10b981] font-mono font-bold rounded p-3 focus:outline-none focus:border-[#10b981] transition-colors">
                            <p class="text-[10px] text-gray-500 mt-1">Auto-generated. You can modify if needed.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Manufacture Date</label>
                            <input type="date" name="mfg_date" value="<?= date('Y-m-d') ?>" required class="w-full bg-[#0a1118] border border-[#374151] text-white rounded p-3 focus:outline-none focus:border-[#10b981] transition-colors">
                        </div>
                    </div>

                    <!-- Thành phẩm -->
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Finished Product</label>
                        <select name="product_id" required class="w-full bg-[#0a1118] border border-[#374151] text-white rounded p-3 focus:outline-none focus:border-[#10b981] transition-colors">
                            <option value="">-- Select Product --</option>
                            <option value="FG01">DTLA20OZ - Dưa thái lát 20oz</option>
                            <option value="FG02">BUOIDAXANH1 - Bưởi da xanh (1-1.2kg)</option>
                            <option value="FG03">DM20DM340HS - Dứa miếng 20oz</option>
                        </select>
                    </div>

                    <!-- Số lượng & Hạn sử dụng -->
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Yield Quantity (kg/units)</label>
                            <input type="number" step="0.01" name="yield_quantity" required class="w-full bg-[#0a1118] border border-[#374151] text-white font-mono text-lg rounded p-3 focus:outline-none focus:border-[#10b981] transition-colors" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Expiry Date (FEFO Base)</label>
                            <input type="date" name="exp_date" required class="w-full bg-[#0a1118] border border-[#374151] text-red-300 rounded p-3 focus:outline-none focus:border-[#10b981] transition-colors">
                        </div>
                    </div>

                    <!-- QC Status -->
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Initial QC Status</label>
                        <select name="qc_status" class="w-full bg-[#0a1118] border border-[#374151] text-white rounded p-3 focus:outline-none focus:border-[#10b981] transition-colors">
                            <option value="Pending" selected>Pending QC Inspection (Sent to QC Queue)</option>
                            <option value="Passed">Pre-approved / Fast Track</option>
                        </select>
                    </div>

                    <!-- Nút Submit -->
                    <div class="flex gap-4 pt-4 border-t border-[#1f2937]">
                        <button type="button" onclick="history.back()" class="px-6 py-3 border border-[#374151] text-gray-300 rounded hover:bg-[#1f2937] transition-colors font-bold text-sm">Cancel</button>
                        <button type="submit" class="flex-1 bg-[#10b981] text-gray-900 rounded hover:bg-[#059669] transition-colors font-bold text-sm shadow-[0_0_15px_rgba(16,185,129,0.3)]">Log Batch to Inventory</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>