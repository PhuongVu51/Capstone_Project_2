<?php
require_once '../backend/includes/auth.php';
require_role(['Production_Manager'], 'login.php');
require_once '../backend/connection/db_connect.php';

$batchId = $_GET['batch_id'] ?? '';

if (empty($batchId)) {
    die("Batch ID is missing.");
}

try {
    $lang = $_SESSION['lang'] ?? 'vi';
    $productNameCol = ($lang === 'en') ? 'COALESCE(p.PRD_product_name_en, p.PRD_product_name)' : 'p.PRD_product_name';
    $zoneNameCol = ($lang === 'en') ? 'COALESCE(z.STZ_zone_name_en, z.STZ_zone_name)' : 'z.STZ_zone_name';

    $stmt = $pdo->prepare("SELECT b.*, $productNameCol AS PRD_product_name, $zoneNameCol AS STZ_zone_name 
                           FROM BATCHES b 
                           LEFT JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id 
                           LEFT JOIN STORAGE_ZONES z ON b.BCH_zone_id = z.STZ_zone_id 
                           WHERE BCH_batch_id = :batch_id");
    $stmt->execute([':batch_id' => $batchId]);
    $batch = $stmt->fetch();

    if (!$batch) {
        die("Batch not found.");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Allocate Material | F&G FOOD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #06121a; color: #d1d5db; font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="p-8">
    <div class="max-w-2xl mx-auto bg-[#07121a] p-8 rounded-lg border border-[#102027]">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-blue-400"><?= __('request_material_title') ?></h1>
            <a href="inventory.php" class="text-sm text-gray-400 hover:text-white transition-colors"><?= __('back_to_inventory') ?></a>
        </div>

        <div class="bg-[#04121a] p-4 rounded border border-[#1f2937] mb-6">
            <h2 class="text-sm font-semibold text-gray-300 uppercase tracking-wider mb-2"><?= __('batch_details') ?></h2>
            <div class="grid grid-cols-2 gap-4 text-sm text-gray-400">
                <p><strong class="text-gray-300"><?= __('batch_id_colon') ?></strong> <?php echo htmlspecialchars($batch['BCH_batch_id']); ?></p>
                <p><strong class="text-gray-300"><?= __('product_colon') ?></strong> <?php echo htmlspecialchars($batch['PRD_product_name']); ?></p>
                <p><strong class="text-gray-300"><?= __('available_stock_colon') ?></strong> <span class="text-[#10b981] font-bold"><?php echo htmlspecialchars(number_format($batch['BCH_available_stock_kg'], 2)); ?> kg</span></p>
                <p><strong class="text-gray-300"><?= __('storage_zone_colon') ?></strong> <?php echo htmlspecialchars($batch['STZ_zone_name']); ?></p>
            </div>
        </div>

        <form action="../backend/connection/process_allocation.php" method="POST" class="space-y-4">
            <input type="hidden" name="batch_id" value="<?php echo htmlspecialchars($batch['BCH_batch_id']); ?>">
            
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1"><?= __('allocate_quantity_asterisk') ?></label>
                <input type="number" step="0.01" name="allocate_qty" required min="0.01" max="<?php echo $batch['BCH_available_stock_kg']; ?>"
                    class="w-full bg-[#04121a] border border-[#1f2937] text-white rounded p-3 focus:border-blue-400 focus:outline-none transition-colors"
                    placeholder="<?= __('enter_amount_to_request') ?>">
                <p class="text-xs text-gray-500 mt-2"><?= __('max_allocatable_amount') ?> <?php echo number_format($batch['BCH_available_stock_kg'], 2); ?> kg.</p>
            </div>

            <div class="pt-4 flex gap-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 px-6 rounded transition-colors w-full">
                    <?= __('confirm_allocation') ?>
                </button>
                <a href="inventory.php" class="block w-full text-center py-3 px-6 rounded border border-gray-600 hover:bg-gray-800 transition-colors">
                    <?= __('cancel') ?>
                </a>
            </div>
        </form>
    </div>
</body>
</html>
