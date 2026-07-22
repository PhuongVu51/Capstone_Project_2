<?php
require_once '../backend/includes/auth.php';
require_role(['Warehouse_Staff']);
require_once '../backend/connection/db_connect.php';

try {
    $products = $pdo->query("SELECT PRD_product_id, PRD_product_name, PRD_shelf_life_days FROM PRODUCTS")->fetchAll();
    $shifts = $pdo->query("SELECT SHF_shift_id, SHF_shift_date, SHF_shift_type FROM SHIFTS WHERE SHF_status = 'Open'")->fetchAll();
    $zones = $pdo->query("SELECT STZ_zone_id, STZ_zone_name FROM STORAGE_ZONES")->fetchAll();
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
<body class="p-8">
    <div class="max-w-2xl mx-auto bg-[#07121a] p-8 rounded-lg border border-[#102027]">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-[#10b981]">Log New Batch (Stock-In)</h1>
            <a href="dashboard_warehouse.php" class="text-sm text-gray-400 hover:text-white">← Back to Dashboard</a>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="mb-4 p-3 bg-red-900 text-red-200 rounded border border-red-700">
                <?php
                    if ($_GET['error'] == 'missing_fields') echo "Vui lòng nhập đầy đủ các trường bắt buộc.";
                    else if ($_GET['error'] == 'db_error') echo "Có lỗi xảy ra khi lưu vào cơ sở dữ liệu.";
                ?>
            </div>
        <?php endif; ?>

        <form action="../backend/controllers/StockController.php?action=stock_in" method="POST" class="space-y-4">
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Product *</label>
                    <select id="product-select" name="product_id" required class="w-full bg-[#04121a] border border-[#1f2937] text-white rounded p-2 focus:border-[#10b981] focus:outline-none">
                        <option value="">Select Product...</option>
                        <?php foreach($products as $p): ?>
                            <option value="<?= intval($p['PRD_product_id']) ?>"><?= '[' . intval($p['PRD_product_id']) . '] ' . htmlspecialchars($p['PRD_product_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Supplier</label>
                    <select id="supplier-select" name="supplier_id" class="w-full bg-[#04121a] border border-[#1f2937] text-white rounded p-2 focus:border-[#10b981] focus:outline-none">
                        <option value="">Select Supplier...</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Initial Volume (kg) *</label>
                    <input type="number" step="0.01" name="initial_volume" required 
                           class="w-full bg-[#04121a] border border-[#1f2937] text-white rounded p-2 focus:border-[#10b981] focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Storage Zone *</label>
                    <select name="zone_id" required class="w-full bg-[#04121a] border border-[#1f2937] text-white rounded p-2 focus:border-[#10b981] focus:outline-none">
                        <option value="">Select Zone...</option>
                        <?php foreach($zones as $z): ?>
                            <option value="<?= $z['STZ_zone_id'] ?>"><?= htmlspecialchars($z['STZ_zone_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Shift</label>
                    <select id="shift-select" name="shift_id" class="w-full bg-[#04121a] border border-[#1f2937] text-white rounded p-2 focus:border-[#10b981] focus:outline-none">
                        <option value="">Select Shift...</option>
                        <?php foreach($shifts as $sh): ?>
                            <option value="<?= $sh['SHF_shift_id'] ?>">
                                <?= htmlspecialchars($sh['SHF_shift_date'] . ' - ' . $sh['SHF_shift_type']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Expiry Date</label>
                    <input type="datetime-local" id="expiry-date-input" name="expiry_date" 
                           class="w-full bg-[#04121a] border border-[#1f2937] text-white rounded p-2 focus:border-[#10b981] focus:outline-none [color-scheme:dark]">
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full bg-[#10b981] text-gray-900 font-bold px-4 py-3 rounded hover:bg-[#0ea5e9] transition-colors">
                    Confirm Stock-In
                </button>
            </div>
        </form>
    </div>

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
