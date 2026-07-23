<?php
// Đường dẫn: frontend/dashboard_production.php
require_once '../backend/includes/auth.php';
require_role(['Production_Manager', 'Director'], 'login.php');
require_once '../backend/connection/db_connect.php';

try {
    // Lấy thông tin thống kê sơ bộ cho dashboard
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM BATCHES WHERE BCH_expiry_date <= DATE_ADD(NOW(), INTERVAL 48 HOUR) AND BCH_available_stock_kg > 0");
    $expiringCount = $stmt->fetch()['count'] ?? 0;

    // Lấy danh sách lô hàng hiện tại
    $batchesStmt = $pdo->query("
        SELECT b.BCH_batch_id, p.PRD_product_name, b.BCH_available_stock_kg, b.BCH_expiry_date 
        FROM BATCHES b 
        JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id 
        ORDER BY b.BCH_expiry_date ASC 
        LIMIT 10
    ");
    $batches = $batchesStmt->fetchAll();
} catch (PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Dashboard | ProSync Industrial</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --bg-dark: #0a1118; --bg-card: #0f1722; --accent-green: #10b981; --border-color: #1f2937; }
        body { background-color: var(--bg-dark); font-family: 'Inter', sans-serif; color: #d1d5db; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg-dark); }
        ::-webkit-scrollbar-thumb { background: #1f2937; border-radius: 4px; }
    </style>
</head>
<body class="min-h-screen flex overflow-x-hidden">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-[#0f1722] border-r border-[#1f2937] flex flex-col hidden md:flex shrink-0">
        <div class="p-6 border-b border-[#1f2937]">
            <h2 class="text-[#10b981] font-bold text-xl tracking-wide">
                F&G FOOD
            </h2>
            <p class="text-xs text-gray-500 mt-1">Production Unit 04</p>
        </div>
        
        <!-- THANH MENU ĐÃ ĐƯỢC KẾT NỐI ĐÚNG ĐƯỜNG DẪN -->
        <nav class="flex-1 p-4 space-y-2">
            <a href="dashboard_production.php" class="flex items-center gap-3 px-4 py-3 bg-[#10b981] text-gray-900 font-semibold rounded-md shadow-[0_0_10px_rgba(16,185,129,0.3)]">
                Dashboard
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 text-gray-400 hover:text-white hover:bg-[#1f2937] rounded-md transition-colors">
                Inventory
            </a>
            <a href="production_FEFO.php" class="flex items-center gap-3 px-4 py-3 text-gray-400 hover:text-white hover:bg-[#1f2937] rounded-md transition-colors">
                FEFO Alerts
            </a>
        </nav>

        <div class="p-4 border-t border-[#1f2937]">
            <a href="../backend/connection/logout.php" class="flex items-center gap-3 px-4 py-2 text-gray-400 hover:text-red-400 transition-colors text-sm">Log Out</a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 border-b border-[#1f2937] bg-[#0a1118] flex items-center justify-between px-8 sticky top-0 z-10">
            <h1 class="text-xl font-bold text-white">Dashboard Overview</h1>
            <div class="flex items-center gap-4">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-semibold text-white"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Production Manager') ?></p>
                    <p class="text-[10px] text-[#10b981]">Production Manager</p>
                </div>
            </div>
        </header>

        <div class="p-8 overflow-y-auto">
            <!-- KPI CARDS -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-[#0f1722] p-6 rounded-lg border border-[#1f2937]">
                    <p class="text-xs text-gray-500 uppercase font-semibold">Yield Analytics</p>
                    <h3 class="text-3xl font-bold text-white mt-2 font-mono">0.0%</h3>
                    <p class="text-xs text-gray-500 mt-1">No Data Yet</p>
                </div>
                <div class="bg-[#0f1722] p-6 rounded-lg border border-[#1f2937]">
                    <p class="text-xs text-gray-500 uppercase font-semibold">Export Demand</p>
                    <h3 class="text-3xl font-bold text-white mt-2 font-mono">0 <span class="text-sm text-gray-500 font-normal">units</span></h3>
                    <p class="text-xs text-gray-500 mt-1">Awaiting new orders</p>
                </div>
                <!-- Thẻ cảnh báo trỏ trực tiếp sang trang FEFO khi bấm vào -->
                <a href="production_FEFO.php" class="bg-[#b91c1c]/90 hover:bg-[#b91c1c] p-6 rounded-lg border border-red-500/50 transition-all shadow-[0_0_15px_rgba(220,38,38,0.2)] block group">
                    <p class="text-xs text-red-200 uppercase font-semibold">Expiring Batches (48H)</p>
                    <h3 class="text-3xl font-bold text-white mt-2 font-mono"><?= $expiringCount ?></h3>
                    <p class="text-xs text-red-100 mt-1 font-medium group-hover:underline">IMMEDIATE FEFO ALLOCATION REQUIRED &rarr;</p>
                </a>
            </div>

            <!-- TABLE -->
            <div class="bg-[#0f1722] rounded-lg border border-[#1f2937] overflow-hidden">
                <div class="p-5 border-b border-[#1f2937] flex justify-between items-center bg-[#0b121c]">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Current Production Status</h3>
                    <button onclick="window.location.href='production_FEFO.php'" class="bg-[#10b981] text-gray-900 text-xs font-bold px-4 py-2 rounded hover:bg-[#059669] transition">View FEFO Alerts</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-gray-500 text-[10px] uppercase bg-[#0a1118] border-b border-[#374151]">
                                <th class="py-3 px-5">Batch ID</th>
                                <th class="py-3 px-5">Product Name</th>
                                <th class="py-3 px-5">Volume (Available)</th>
                                <th class="py-3 px-5">Expiry (FEFO)</th>
                                <th class="py-3 px-5 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-[#1f2937]">
                            <?php if (empty($batches)): ?>
                                <tr><td colspan="5" class="p-6 text-center text-gray-500 italic">No production batches found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($batches as $row): ?>
                                    <tr class="hover:bg-[#131c26] transition-colors">
                                        <td class="py-3 px-5 text-[#10b981] font-mono text-xs font-semibold"><?= htmlspecialchars($row['BCH_batch_id']) ?></td>
                                        <td class="py-3 px-5 text-gray-200"><?= htmlspecialchars($row['PRD_product_name']) ?></td>
                                        <td class="py-3 px-5 font-mono text-gray-300"><?= number_format($row['BCH_available_stock_kg'], 2) ?> kg</td>
                                        <td class="py-3 px-5 font-mono text-red-400 text-xs"><?= htmlspecialchars($row['BCH_expiry_date']) ?></td>
                                        <td class="py-3 px-5 text-center">
                                            <a href="production_FEFO.php" class="bg-[#1f2937] border border-[#374151] text-gray-200 text-xs font-bold px-3 py-1.5 rounded hover:bg-[#10b981] hover:text-gray-900 transition">Allocate</a>
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
</body>
</html>