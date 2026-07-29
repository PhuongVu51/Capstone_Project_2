<?php
require_once '../backend/includes/auth.php'; // For session and i18n
require_once '../backend/connection/db_connect.php';

$batchId = isset($_GET['batch']) ? trim($_GET['batch']) : '';
$batchData = null;
$error = null;
$lang = $_SESSION['lang'] ?? 'vi';

if ($batchId === '') {
    $error = ($lang === 'en') ? "Please provide a valid Batch ID." : "Vui lòng cung cấp mã lô hàng (Batch ID).";
} else {
    try {
        $productNameCol = ($lang === 'en') ? 'COALESCE(p.PRD_product_name_en, p.PRD_product_name)' : 'p.PRD_product_name';
        
        $sql = "
            SELECT 
                b.BCH_batch_id,
                $productNameCol as PRD_product_name,
                b.BCH_received_date,
                s.SUP_supplier_name,
                b.BCH_current_stage,
                q.QCI_actual_yield_pct,
                b.BCH_expiry_date
            FROM BATCHES b
            JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
            JOIN SUPPLIERS s ON b.BCH_supplier_id = s.SUP_supplier_id
            LEFT JOIN QC_INSPECTIONS q ON b.BCH_batch_id = q.QCI_batch_id
            WHERE b.BCH_batch_id = :batch_id
            LIMIT 1
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['batch_id' => $batchId]);
        $batchData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$batchData) {
            $error = ($lang === 'en') ? "Batch not found." : "Không tìm thấy lô hàng với mã này.";
        }
    } catch (PDOException $e) {
        $error = ($lang === 'en') ? "An error occurred while retrieving data." : "Có lỗi xảy ra khi truy xuất dữ liệu.";
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Traceability | F&G FOOD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #06121a; color: #e5e7eb; font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center bg-[#06121a] pt-12 px-4 pb-24 overflow-x-hidden">
    
    <div class="w-full max-w-2xl">
        <!-- Header / Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center p-3 rounded-full bg-[#10b981]/10 border border-[#10b981]/20 mb-4">
                <svg class="w-8 h-8 text-[#10b981]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
            </div>
            <h1 class="text-3xl font-bold text-white tracking-tight">F&G FOOD</h1>
            <p class="text-[#10b981] font-bold uppercase tracking-[0.2em] mt-2 text-sm">Product Traceability</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-[#3b0d0d] border border-red-500/30 rounded-xl p-8 text-center mt-12 shadow-2xl">
                <svg class="w-16 h-16 text-red-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <h2 class="text-xl font-bold text-white mb-2"><?= ($lang === 'en') ? 'Oops!' : 'Rất tiếc!' ?></h2>
                <p class="text-red-300"><?= htmlspecialchars($error) ?></p>
            </div>
        <?php else: ?>
            <?php
                $stage = $batchData['BCH_current_stage'] ?? '';
                $badgeClasses = 'bg-slate-800 text-slate-300 border-slate-600'; // default
                
                if (stripos($stage, 'quarantine') !== false) {
                    $badgeClasses = 'bg-amber-500/10 text-amber-400 border-amber-500/30';
                } elseif (stripos($stage, 'ready') !== false || stripos($stage, 'in stock') !== false) {
                    $badgeClasses = 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30';
                } elseif (stripos($stage, 'export') !== false) {
                    $badgeClasses = 'bg-blue-500/10 text-blue-400 border-blue-500/30';
                }
            ?>
            <div class="bg-[#0f1722] border border-[#1f2937] shadow-2xl rounded-2xl overflow-hidden relative">
                <!-- Top banner pattern -->
                <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-[#10b981] to-[#3b82f6]"></div>
                
                <div class="p-6 md:p-8">
                    <!-- Batch Header -->
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-slate-800 pb-6 mb-6">
                        <div>
                            <span class="text-xs text-gray-400 uppercase tracking-wider font-semibold">Batch ID</span>
                            <h2 class="text-2xl font-mono font-bold text-white mt-1 break-all"><?= htmlspecialchars($batchData['BCH_batch_id']) ?></h2>
                        </div>
                        <div>
                            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-bold border uppercase tracking-wider <?= $badgeClasses ?>">
                                <?= htmlspecialchars($stage) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Details Grid -->
                    <div class="space-y-6">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold mb-1"><?= ($lang === 'en') ? 'Product Name' : 'Tên Sản Phẩm' ?></p>
                            <p class="text-lg font-bold text-white break-words"><?= htmlspecialchars($batchData['PRD_product_name']) ?></p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-[#07121a] p-4 rounded-xl border border-[#1f2937]">
                                <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold mb-1"><?= ($lang === 'en') ? 'Supplier' : 'Nhà Cung Cấp' ?></p>
                                <p class="text-base text-gray-200"><?= htmlspecialchars($batchData['SUP_supplier_name']) ?></p>
                            </div>
                            
                            <div class="bg-[#07121a] p-4 rounded-xl border border-[#1f2937]">
                                <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold mb-1"><?= ($lang === 'en') ? 'Received Date' : 'Ngày Nhập' ?></p>
                                <p class="text-base text-gray-200"><?= date('d M Y', strtotime($batchData['BCH_received_date'])) ?></p>
                            </div>
                            
                            <div class="bg-[#07121a] p-4 rounded-xl border border-[#1f2937]">
                                <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold mb-1"><?= ($lang === 'en') ? 'QC Actual Yield' : 'Tỷ Lệ Thu Hồi QC' ?></p>
                                <p class="text-base font-bold <?= $batchData['QCI_actual_yield_pct'] ? 'text-emerald-400' : 'text-gray-400' ?>">
                                    <?= $batchData['QCI_actual_yield_pct'] !== null ? number_format((float) $batchData['QCI_actual_yield_pct'], 1) . '%' : 'N/A' ?>
                                </p>
                            </div>
                            
                            <div class="bg-[#07121a] p-4 rounded-xl border border-[#1f2937]">
                                <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold mb-1"><?= ($lang === 'en') ? 'Expiry Date' : 'Hạn Sử Dụng' ?></p>
                                <p class="text-base text-gray-200"><?= $batchData['BCH_expiry_date'] ? date('d M Y', strtotime($batchData['BCH_expiry_date'])) : 'N/A' ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-[#07121a] p-4 text-center border-t border-[#1f2937]">
                    <p class="text-xs text-gray-500">Verified by F&G FOOD Traceability System &bull; <?= date('Y') ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
