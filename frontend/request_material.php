<?php
// Đường dẫn: frontend/request_material.php
require_once '../backend/includes/auth.php';
require_role(['Production_Manager', 'Director'], 'login.php');
require_once '../backend/connection/db_connect.php';

// (Tùy chọn) Lấy danh sách nguyên vật liệu từ database để đổ vào thẻ <select>
try {
    // Sửa lại: Bỏ điều kiện WHERE PRD_category đi vì bảng không có cột này
    $stmt = $pdo->query("SELECT PRD_product_id, PRD_product_name FROM PRODUCTS");
    $materials = $stmt->fetchAll();
} catch (PDOException $e) {
    // Thêm dòng die này để lỡ có lỗi SQL nó sẽ báo ngay ra màn hình cho dễ sửa
    die("Lỗi truy vấn SQL: " . $e->getMessage()); 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Material | ProSync Industrial</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --bg-dark: #0a1118; --bg-card: #0f1722; --accent-blue: #3b82f6; --border-color: #1f2937; }
        body { background-color: var(--bg-dark); font-family: 'Inter', sans-serif; color: #d1d5db; }
    </style>
</head>
<body class="min-h-screen flex overflow-x-hidden">

    <!-- GỌI FILE SIDEBAR DÙNG CHUNG -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="flex-1 flex flex-col min-w-0 md:ml-64">
        <header class="h-16 border-b border-[#1f2937] bg-[#0a1118] flex items-center px-8 sticky top-0 z-10">
            <h1 class="text-xl font-bold text-white">Material Requisition Form</h1>
        </header>

        <div class="p-8 overflow-y-auto flex justify-center">
            <div class="bg-[#0f1722] rounded-lg border border-[#1f2937] w-full max-w-2xl p-8 shadow-xl">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-[#3b82f6]">New Request</h2>
                    <p class="text-sm text-gray-500 mt-1">Submit a request to the warehouse for raw materials needed in production.</p>
                </div>

                <form action="../backend/connection/process_request.php" method="POST" class="space-y-6">
                    <!-- Chọn vật tư -->
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Select Material</label>
                        <select name="material_id" required class="w-full bg-[#0a1118] border border-[#374151] text-white rounded p-3 focus:outline-none focus:border-[#3b82f6] transition-colors">
                            <option value="">-- Choose a material --</option>
                            
                            <?php foreach ($materials as $mat): ?>
                                <option value="<?= htmlspecialchars($mat['PRD_product_id']) ?>">
                                    <?= htmlspecialchars($mat['PRD_product_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        
                        </select>
                    </div>

                    <!-- Số lượng & Đơn vị -->
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Quantity Required</label>
                            <input type="number" step="0.01" name="quantity" required class="w-full bg-[#0a1118] border border-[#374151] text-white rounded p-3 focus:outline-none focus:border-[#3b82f6] transition-colors" placeholder="e.g. 1500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Needed By (Date)</label>
                            <input type="date" name="needed_date" required class="w-full bg-[#0a1118] border border-[#374151] text-white rounded p-3 focus:outline-none focus:border-[#3b82f6] transition-colors">
                        </div>
                    </div>

                    <!-- Mức độ ưu tiên -->
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Priority Level</label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="priority" value="Normal" checked class="text-[#3b82f6] focus:ring-[#3b82f6] bg-[#0a1118] border-[#374151]">
                                <span class="text-sm text-gray-300">Normal</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="priority" value="Urgent" class="text-red-500 focus:ring-red-500 bg-[#0a1118] border-[#374151]">
                                <span class="text-sm text-red-400 font-bold">Urgent (Production Halting)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Ghi chú -->
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Production Notes</label>
                        <textarea name="notes" rows="3" class="w-full bg-[#0a1118] border border-[#374151] text-white rounded p-3 focus:outline-none focus:border-[#3b82f6] transition-colors" placeholder="Specify production line or special instructions..."></textarea>
                    </div>

                    <!-- Nút Submit -->
                    <div class="flex gap-4 pt-4 border-t border-[#1f2937]">
                        <button type="button" onclick="history.back()" class="px-6 py-3 border border-[#374151] text-gray-300 rounded hover:bg-[#1f2937] transition-colors font-bold text-sm">Cancel</button>
                        <button type="submit" class="flex-1 bg-[#3b82f6] text-white rounded hover:bg-[#2563eb] transition-colors font-bold text-sm shadow-[0_0_15px_rgba(59,130,246,0.3)]">Send Request to Warehouse</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>