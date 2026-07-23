<?php
require_once '../backend/includes/auth.php';
require_role(['Production_Manager'], 'login.php');
require_once '../backend/connection/db_connect.php';

$batchId = $_GET['batch_id'] ?? '';

if (empty($batchId)) {
    die("Batch ID is missing.");
}

try {
    $stmt = $pdo->prepare("SELECT b.*, p.PRD_product_name, z.STZ_zone_name 
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
            <h1 class="text-2xl font-bold text-blue-400">Request Material (Outbound)</h1>
            <a href="inventory.php" class="text-sm text-gray-400 hover:text-white transition-colors">← Back to Inventory</a>
        </div>

        <div class="bg-[#04121a] p-4 rounded border border-[#1f2937] mb-6">
            <h2 class="text-sm font-semibold text-gray-300 uppercase tracking-wider mb-2">Batch Details</h2>
            <div class="grid grid-cols-2 gap-4 text-sm text-gray-400">
                <p><strong class="text-gray-300">Batch ID:</strong> <?php echo htmlspecialchars($batch['BCH_batch_id']); ?></p>
                <p><strong class="text-gray-300">Product:</strong> <?php echo htmlspecialchars($batch['PRD_product_name']); ?></p>
                <p><strong class="text-gray-300">Available Stock:</strong> <span class="text-[#10b981] font-bold"><?php echo htmlspecialchars(number_format($batch['BCH_available_stock_kg'], 2)); ?> kg</span></p>
                <p><strong class="text-gray-300">Storage Zone:</strong> <?php echo htmlspecialchars($batch['STZ_zone_name']); ?></p>
            </div>
        </div>

        <form action="../backend/connection/process_allocation.php" method="POST" class="space-y-4">
            <input type="hidden" name="batch_id" value="<?php echo htmlspecialchars($batch['BCH_batch_id']); ?>">
            
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Allocate Quantity (kg) *</label>
                <input type="number" step="0.01" name="allocate_qty" required min="0.01" max="<?php echo $batch['BCH_available_stock_kg']; ?>"
                    class="w-full bg-[#04121a] border border-[#1f2937] text-white rounded p-3 focus:border-blue-400 focus:outline-none transition-colors"
                    placeholder="Enter amount to request...">
                <p class="text-xs text-gray-500 mt-2">Maximum allocatable amount is <?php echo number_format($batch['BCH_available_stock_kg'], 2); ?> kg.</p>
            </div>

            <div class="pt-4 flex gap-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 px-6 rounded transition-colors w-full">
                    Confirm Allocation
                </button>
                <a href="inventory.php" class="block w-full text-center py-3 px-6 rounded border border-gray-600 hover:bg-gray-800 transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</body>
</html>
