<?php
// Đường dẫn: frontend/manage_requests.php
require_once '../backend/includes/auth.php';
require_role(['Warehouse_Staff', 'Director'], 'login.php');
require_once '../backend/controllers/MaterialRequestController.php';

$controller = new MaterialRequestController();
$data = $controller->getRequestsData();
$requests = $data['requests'];

// Lấy thông báo nếu vừa duyệt xong
$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Requests | F&G FOOD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style> body { background-color: #06121a; font-family: 'Inter', sans-serif; } </style>
</head>
<body class="min-h-screen flex bg-[#06121a] text-gray-300">
    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 md:ml-64 p-6 lg:p-8 pt-24 md:pt-8">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-white mb-2">Material Requisitions</h1>
            <p class="text-gray-400">Review and process material requests submitted by Production Managers.</p>
        </header>

        <!-- Hiển thị thông báo khi duyệt/từ chối thành công -->
        <?php if ($successMsg): ?>
            <div class="mb-6 p-4 bg-emerald-900/40 border border-emerald-700 text-emerald-100 rounded-lg">
                <?= htmlspecialchars($successMsg) ?>
            </div>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            <div class="mb-6 p-4 bg-red-900/40 border border-red-700 text-red-100 rounded-lg">
                <?= htmlspecialchars($errorMsg) ?>
            </div>
        <?php endif; ?>

        <section class="bg-[#07121a] border border-[#1f2937] rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-[#041a1a] text-gray-400 text-xs uppercase">
                        <tr>
                            <th class="p-4">Req ID</th>
                            <th class="p-4">Material</th>
                            <th class="p-4">Quantity (kg)</th>
                            <th class="p-4">Needed By</th>
                            <th class="p-4">Priority</th>
                            <th class="p-4">Requester</th>
                            <th class="p-4">Status</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#102027]">
                        <?php if (empty($requests)): ?>
                            <tr>
                                <td colspan="8" class="p-8 text-center text-gray-500">No material requests found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($requests as $req): ?>
                                <tr class="hover:bg-[#0d1821] transition-colors">
                                    <td class="p-4 font-mono text-emerald-400">#<?= $req['REQ_id'] ?></td>
                                    <td class="p-4 text-white font-medium"><?= htmlspecialchars($req['REQ_material_id']) ?></td>
                                    <td class="p-4 font-mono"><?= number_format($req['REQ_quantity'], 2) ?></td>
                                    <td class="p-4"><?= date('d/m/Y', strtotime($req['REQ_needed_date'])) ?></td>
                                    <td class="p-4">
                                        <?php if ($req['REQ_priority'] === 'Urgent'): ?>
                                            <span class="px-2 py-1 bg-red-500/20 text-red-400 text-xs font-bold rounded">URGent</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 bg-slate-700 text-slate-300 text-xs rounded">Normal</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-gray-400"><?= htmlspecialchars($req['requester_name']) ?></td>
                                    <td class="p-4">
                                        <?php if ($req['REQ_status'] === 'Pending'): ?>
                                            <span class="px-2 py-1 bg-yellow-500/20 text-yellow-400 text-xs font-bold rounded">Pending</span>
                                        <?php elseif ($req['REQ_status'] === 'Approved'): ?>
                                            <span class="px-2 py-1 bg-emerald-500/20 text-emerald-400 text-xs font-bold rounded">Approved</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 bg-red-500/20 text-red-400 text-xs font-bold rounded">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-right">
                                        <?php if ($req['REQ_status'] === 'Pending'): ?>
                                            <form action="../backend/controllers/MaterialRequestController.php?action=process" method="POST" class="inline-flex gap-2">
                                                <input type="hidden" name="request_id" value="<?= $req['REQ_id'] ?>">
                                                <button type="submit" name="action_type" value="Approve" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded transition">Approve</button>
                                                <button type="submit" name="action_type" value="Reject" class="px-3 py-1 border border-red-500/50 hover:bg-red-500/20 text-red-400 text-xs font-bold rounded transition">Reject</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-500 italic">Processed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>