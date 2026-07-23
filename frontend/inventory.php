<?php
require_once '../backend/includes/auth.php';
require_role(['Warehouse_Staff', 'Production_Manager', 'Director'], 'login.php');
require_once '../backend/connection/db_connect.php';

$userRole = $_SESSION['role'] ?? 'Warehouse_Staff';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_batch'])) {
    if ($userRole !== 'Director' && $userRole !== 'Warehouse_Staff') {
        $messages[] = 'You do not have permission to delete batches.';
    } else {
        $batchId = trim($_POST['batch_id'] ?? '');

        if ($batchId === '') {
            $messages[] = 'Batch ID is required.';
        } else {
            try {
                $deleteStmt = $pdo->prepare('DELETE FROM BATCHES WHERE BCH_batch_id = :batch_id');
                $deleteStmt->execute([':batch_id' => $batchId]);

                if ($deleteStmt->rowCount() > 0) {
                    $messages[] = 'Batch ' . htmlspecialchars($batchId) . ' deleted successfully.';
                } else {
                    $messages[] = 'No matching batch was found.';
                }
            } catch (PDOException $e) {
                $messages[] = 'Unable to delete batch: ' . $e->getMessage();
            }
        }
    }
}

$conditions = [];
$params = [];

if ($search !== '') {
    $conditions[] = '(b.BCH_batch_id LIKE :search OR p.PRD_product_name LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

if ($statusFilter !== '') {
    $conditions[] = "(CASE WHEN b.BCH_available_stock_kg <= 0 THEN 'Out of Stock' WHEN b.BCH_available_stock_kg < 100 THEN 'Low Stock' ELSE 'In Stock' END = :status)";
    $params[':status'] = $statusFilter;
}

$whereSql = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

$countSql = "SELECT COUNT(*) AS total
    FROM BATCHES b
    LEFT JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
    $whereSql";

$countStmt = $pdo->prepare($countSql);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalRecords = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRecords / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$sql = "SELECT b.BCH_batch_id,
               p.PRD_product_name,
               p.PRD_material_grade,
               b.BCH_initial_volume_kg,
               b.BCH_available_stock_kg,
               b.BCH_current_stage,
               b.BCH_health_status,
               b.BCH_received_date,
               b.BCH_expiry_date,
               z.STZ_zone_name,
               CASE
                   WHEN b.BCH_available_stock_kg <= 0 THEN 'Out of Stock'
                   WHEN b.BCH_available_stock_kg < 100 THEN 'Low Stock'
                   ELSE 'In Stock'
               END AS stock_status
        FROM BATCHES b
        LEFT JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
        LEFT JOIN STORAGE_ZONES z ON b.BCH_zone_id = z.STZ_zone_id
        $whereSql
        ORDER BY b.BCH_received_date DESC
        LIMIT :offset, :perPage";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$stmt->execute();
$inventoryItems = $stmt->fetchAll();

$selectedBatch = null;
if (isset($_GET['view_id']) && trim($_GET['view_id']) !== '') {
    $viewId = trim($_GET['view_id']);
    $detailStmt = $pdo->prepare(
        "SELECT b.BCH_batch_id, p.PRD_product_name, p.PRD_material_grade, b.BCH_initial_volume_kg,
                b.BCH_available_stock_kg, b.BCH_current_stage, b.BCH_health_status,
                b.BCH_received_date, b.BCH_expiry_date, z.STZ_zone_name
         FROM BATCHES b
         LEFT JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
         LEFT JOIN STORAGE_ZONES z ON b.BCH_zone_id = z.STZ_zone_id
         WHERE b.BCH_batch_id = :batch_id"
    );
    $detailStmt->execute([':batch_id' => $viewId]);
    $selectedBatch = $detailStmt->fetch();
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
                <h1 class="text-3xl font-bold text-[#10b981]">Inventory Ledger</h1>
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
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by batch ID or product name" class="flex-1 rounded border border-[#203434] bg-[#06121a] px-3 py-2 text-sm text-gray-200 focus:outline-none focus:border-[#10b981]" />
                <select name="status" class="rounded border border-[#203434] bg-[#06121a] px-3 py-2 text-sm text-gray-200 focus:outline-none focus:border-[#10b981]">
                    <option value="">All Status</option>
                    <option value="In Stock" <?php echo $statusFilter === 'In Stock' ? 'selected' : ''; ?>>In Stock</option>
                    <option value="Low Stock" <?php echo $statusFilter === 'Low Stock' ? 'selected' : ''; ?>>Low Stock</option>
                    <option value="Out of Stock" <?php echo $statusFilter === 'Out of Stock' ? 'selected' : ''; ?>>Out of Stock</option>
                </select>
                <button type="submit" class="rounded bg-[#10b981] px-4 py-2 text-sm font-semibold text-gray-900">Filter</button>
                <?php if ($search !== '' || $statusFilter !== ''): ?>
                    <a href="inventory.php" class="rounded border border-[#203434] px-4 py-2 text-sm text-gray-300">Clear</a>
                <?php endif; ?>
            </form>
        </section>

        <?php if ($selectedBatch): ?>
            <section class="bg-[#07121a] border border-[#102027] rounded-lg p-5 mb-6">
                <div class="flex justify-between items-start gap-4">
                    <div>
                        <p class="text-xs uppercase text-gray-400">Selected Batch</p>
                        <h2 class="text-xl font-semibold text-white mt-1"><?php echo htmlspecialchars($selectedBatch['BCH_batch_id']); ?></h2>
                        <p class="text-sm text-gray-400 mt-2"><?php echo htmlspecialchars($selectedBatch['PRD_product_name'] ?? 'N/A'); ?></p>
                    </div>
                    <a href="inventory.php" class="text-sm text-[#10b981]">Close</a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4 text-sm">
                    <div class="bg-[#06121a] rounded p-3">
                        <p class="text-gray-400">Zone</p>
                        <p class="text-white font-medium"><?php echo htmlspecialchars($selectedBatch['STZ_zone_name'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="bg-[#06121a] rounded p-3">
                        <p class="text-gray-400">Available Stock</p>
                        <p class="text-white font-medium"><?php echo number_format((float) ($selectedBatch['BCH_available_stock_kg'] ?? 0), 2); ?> kg</p>
                    </div>
                    <div class="bg-[#06121a] rounded p-3">
                        <p class="text-gray-400">Stage</p>
                        <p class="text-white font-medium"><?php echo htmlspecialchars($selectedBatch['BCH_current_stage'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <section class="bg-[#07121a] border border-[#102027] rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-[#041a1a] text-gray-400 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3 text-left">Batch ID</th>
                            <th class="px-4 py-3 text-left">Product</th>
                            <th class="px-4 py-3 text-left">Category</th>
                            <th class="px-4 py-3 text-left">Available</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Zone</th>
                            <th class="px-4 py-3 text-left">Received</th>
                            <th class="px-4 py-3 text-left">Actions</th>
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
                                    <td class="px-4 py-3 text-gray-400"><?php echo htmlspecialchars($item['PRD_material_grade'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3"><?php echo number_format((float) ($item['BCH_available_stock_kg'] ?? 0), 2); ?> kg</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded px-2 py-1 text-xs font-medium <?php echo $badgeClasses; ?>"><?php echo htmlspecialchars($status); ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-400"><?php echo htmlspecialchars($item['STZ_zone_name'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3 text-gray-400"><?php echo htmlspecialchars(date('d/m/Y', strtotime($item['BCH_received_date']))); ?></td>
                                    <td class="px-4 py-3">
                                        <div class="flex gap-4 items-center">
                                            <a href="inventory.php?view_id=<?php echo urlencode($item['BCH_batch_id']); ?>" class="text-[#10b981] hover:text-white transition-colors" title="View details">
                                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            </a>
                                            <?php if ($userRole === 'Production_Manager'): ?>
                                                <a href="#" class="text-blue-400 hover:text-white transition-colors" title="Request material from this batch">
                                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                </a>
                                            <?php elseif ($userRole === 'Director' || $userRole === 'Warehouse_Staff'): ?>
                                                <a href="log_batch.php?batch_id=<?php echo urlencode($item['BCH_batch_id']); ?>" class="text-blue-400 hover:text-white transition-colors" title="Edit batch">
                                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($userRole === 'Director' || $userRole === 'Warehouse_Staff'): ?>
                                                <form method="POST" class="inline" onsubmit="return confirm('Delete this batch?');">
                                                    <input type="hidden" name="delete_batch" value="1" />
                                                    <input type="hidden" name="batch_id" value="<?php echo htmlspecialchars($item['BCH_batch_id']); ?>" />
                                                    <button type="submit" class="text-red-400 hover:text-white transition-colors" title="Delete batch">
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
