<?php
require_once '../backend/includes/auth.php';
require_role(['Warehouse_Staff']);
require_once '../backend/connection/db_connect.php';

try {
    $lang = $_SESSION['lang'] ?? 'vi';
    $productNameCol = ($lang === 'en') ? 'COALESCE(PRD_product_name_en, PRD_product_name)' : 'PRD_product_name';
    $zoneNameCol = ($lang === 'en') ? 'COALESCE(STZ_zone_name_en, STZ_zone_name)' : 'STZ_zone_name';
    $products = $pdo->query("SELECT PRD_product_id, $productNameCol AS PRD_product_name, PRD_shelf_life_days FROM PRODUCTS")->fetchAll();
    $shifts = $pdo->query("SELECT SHF_shift_id, SHF_shift_date, SHF_shift_type FROM SHIFTS WHERE SHF_status = 'Open'")->fetchAll();
    $zones = $pdo->query("SELECT STZ_zone_id, $zoneNameCol AS STZ_zone_name FROM STORAGE_ZONES")->fetchAll();
} catch (PDOException $e) {
    die("Lỗi cơ sở dữ liệu: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Log New Batch | F&G FOOD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #06121a; color: #d1d5db; font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen overflow-x-hidden flex bg-[#06121a]">
    <?php include 'includes/sidebar.php'; ?>

    <main class="md:ml-64 p-6 md:p-8 pt-24 md:pt-8 w-full">
        <!-- HEADER -->
        <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 pb-4 border-b border-[#1f2937] gap-4">
            <div>
                <h1 class="text-2xl font-bold text-white tracking-wide">
                    <?= __('log_new_batch_title') ?>
                </h1>
                <p class="text-gray-500 text-sm mt-1">
                    Log incoming raw materials and update inventory
                </p>
            </div>
            <a href="dashboard_warehouse.php" class="bg-[#1f2937] hover:bg-[#374151] border border-[#374151] text-gray-300 font-bold px-4 py-2 rounded text-sm transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                <?= __('back_to_dashboard') ?>
            </a>
        </header>

        <div class="max-w-4xl mx-auto bg-[#07121a] p-8 rounded-xl border border-[#1f2937] shadow-lg shadow-[#00000050]">
            <?php if (isset($_GET['error'])): ?>
                <div class="mb-6 p-4 bg-red-900/40 text-red-200 rounded-lg border border-red-800 flex items-center gap-3">
                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>
                    <?php
                        if ($_GET['error'] == 'missing_fields') echo __('error_missing_fields');
                        else if ($_GET['error'] == 'db_error') echo __('error_db');
                    ?>
                    </span>
                </div>
            <?php endif; ?>

            <form action="../backend/controllers/StockController.php?action=stock_in" method="POST" class="space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2"><?= __('product_asterisk') ?></label>
                        <select id="product-select" name="product_id" required class="w-full bg-[#0b1722] border border-[#374151] text-white rounded-lg p-2.5 focus:border-[#10b981] focus:ring-1 focus:ring-[#10b981] focus:outline-none transition-colors">
                            <option value=""><?= __('select_product') ?></option>
                            <?php foreach($products as $p): ?>
                                <option value="<?= intval($p['PRD_product_id']) ?>"><?= '[' . intval($p['PRD_product_id']) . '] ' . htmlspecialchars($p['PRD_product_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2"><?= __('supplier') ?></label>
                        <select id="supplier-select" name="supplier_id" class="w-full bg-[#0b1722] border border-[#374151] text-white rounded-lg p-2.5 focus:border-[#10b981] focus:ring-1 focus:ring-[#10b981] focus:outline-none transition-colors">
                            <option value=""><?= __('select_supplier') ?></option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2"><?= __('initial_volume_asterisk') ?></label>
                        <input type="number" step="0.01" name="initial_volume" required placeholder="0.00"
                               class="w-full bg-[#0b1722] border border-[#374151] text-white rounded-lg p-2.5 focus:border-[#10b981] focus:ring-1 focus:ring-[#10b981] focus:outline-none transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2"><?= __('storage_zone_asterisk') ?></label>
                        <select name="zone_id" required class="w-full bg-[#0b1722] border border-[#374151] text-white rounded-lg p-2.5 focus:border-[#10b981] focus:ring-1 focus:ring-[#10b981] focus:outline-none transition-colors">
                            <option value=""><?= __('select_zone') ?></option>
                            <?php foreach($zones as $z): ?>
                                <option value="<?= $z['STZ_zone_id'] ?>"><?= htmlspecialchars($z['STZ_zone_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2"><?= __('shift') ?></label>
                        <select id="shift-select" name="shift_id" class="w-full bg-[#0b1722] border border-[#374151] text-white rounded-lg p-2.5 focus:border-[#10b981] focus:ring-1 focus:ring-[#10b981] focus:outline-none transition-colors">
                            <option value=""><?= __('select_shift') ?></option>
                            <?php foreach($shifts as $sh): ?>
                                <option value="<?= $sh['SHF_shift_id'] ?>">
                                    <?= htmlspecialchars($sh['SHF_shift_date'] . ' - ' . $sh['SHF_shift_type']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2"><?= __('expiry_date') ?></label>
                        <input type="datetime-local" id="expiry-date-input" name="expiry_date" 
                               class="w-full bg-[#0b1722] border border-[#374151] text-white rounded-lg p-2.5 focus:border-[#10b981] focus:ring-1 focus:ring-[#10b981] focus:outline-none transition-colors [color-scheme:dark]">
                    </div>
                </div>

                <div class="pt-6 mt-4 border-t border-[#1f2937]">
                    <button type="submit" class="w-full bg-gradient-to-r from-[#10b981] to-[#059669] text-white font-bold px-4 py-3 rounded-lg shadow hover:shadow-lg hover:from-[#34d399] hover:to-[#10b981] transform hover:-translate-y-0.5 transition-all flex justify-center items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <?= __('confirm_stock_in') ?>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var productSelect = document.getElementById('product-select');
            var supplierSelect = document.getElementById('supplier-select');
            var shiftSelect = document.getElementById('shift-select');
            var expiryInput = document.getElementById('expiry-date-input');
            var productShelfLifeMap = <?= json_encode(array_column($products, 'PRD_shelf_life_days', 'PRD_product_id')) ?>;

            function setSupplierOptions(suppliers) {
                supplierSelect.innerHTML = '<option value="">Select Supplier...</option>';
                if (!Array.isArray(suppliers) || suppliers.length === 0) {
                    return;
                }
                suppliers.forEach(function(supplier) {
                    var option = document.createElement('option');
                    option.value = supplier.SUP_supplier_id;
                    option.textContent = supplier.SUP_supplier_name;
                    supplierSelect.appendChild(option);
                });
            }

            function fetchSuppliers(productId) {
                supplierSelect.innerHTML = '<option value="">Loading suppliers...</option>';
                if (!productId) {
                    setSupplierOptions([]);
                    return;
                }

                fetch('../backend/controllers/StockController.php?action=fetch_suppliers&product_id=' + encodeURIComponent(productId), {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(function(data) {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    setSupplierOptions(data);
                })
                .catch(function(error) {
                    console.error('Error fetching suppliers:', error);
                    supplierSelect.innerHTML = '<option value="">Select Supplier...</option>';
                });
            }

            function getShiftTypeByTime(date) {
                var hour = date.getHours();
                if (hour >= 6 && hour < 14) {
                    return 'Morning';
                }
                if (hour >= 14 && hour < 22) {
                    return 'Afternoon';
                }
                return 'Overtime';
            }

            function formatDateTimeLocal(date) {
                var year = date.getFullYear();
                var month = String(date.getMonth() + 1).padStart(2, '0');
                var day = String(date.getDate()).padStart(2, '0');
                var hours = String(date.getHours()).padStart(2, '0');
                var minutes = String(date.getMinutes()).padStart(2, '0');
                return year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
            }

            function autoFillShift() {
                var today = new Date();
                var dateString = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');
                var shiftType = getShiftTypeByTime(today);
                var expectedLabel = dateString + ' - ' + shiftType;
                var matchedOption = Array.from(shiftSelect.options).find(function(option) {
                    return option.textContent.trim() === expectedLabel;
                });

                if (matchedOption) {
                    shiftSelect.value = matchedOption.value;
                }
            }

            function autoFillExpiryDate(productId) {
                if (!productId) {
                    expiryInput.value = '';
                    return;
                }

                var shelfLifeDays = Number(productShelfLifeMap[productId] || 0);
                if (!shelfLifeDays) {
                    expiryInput.value = '';
                    return;
                }

                var expiryDate = new Date();
                expiryDate.setDate(expiryDate.getDate() + shelfLifeDays);
                expiryInput.value = formatDateTimeLocal(expiryDate);
            }

            productSelect.addEventListener('change', function() {
                fetchSuppliers(this.value);
                autoFillExpiryDate(this.value);
            });

            autoFillShift();
            autoFillExpiryDate(productSelect.value);

            // initial reset to make sure no suppliers are shown until a product is selected
            supplierSelect.innerHTML = '<option value="">Select Supplier...</option>';
        });
    </script>
</body>
</html>
