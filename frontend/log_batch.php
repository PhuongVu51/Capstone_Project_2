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
    <link rel="stylesheet" href="assets/css/searchable_select.css">
    <script src="assets/js/searchable_select.js"></script>
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
            <a href="dashboard_warehouse.php" class="bg-[#1f2937] hover:bg-[#374151] border border-[#374151] text-gray-300 font-bold px-4 py-2 rounded text-sm transition-colors flex items-center">
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
                        else if ($_GET['error'] == 'past_expiry_date') echo 'Lỗi thời gian: Ngày hết hạn không thể ở trong quá khứ hoặc nhỏ hơn thời gian thực tế!';
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
                                <option value="<?= intval($p['PRD_product_id']) ?>"><?= '[' . intval($p['PRD_product_id']) . '] ' . htmlspecialchars(t_product($p['PRD_product_name'])) ?></option>
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
                        <select id="zone-select" name="zone_id" required class="w-full bg-[#0b1722] border border-[#374151] text-white rounded-lg p-2.5 focus:border-[#10b981] focus:ring-1 focus:ring-[#10b981] focus:outline-none transition-colors">
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
                    <div class="flex items-center">
                        <div class="w-full p-3 bg-[#07121a] rounded-lg border border-[#1f2937] flex items-center gap-3">
                            <div class="p-2 bg-[#10b981]/10 rounded-full border border-[#10b981]/30 text-[#10b981] shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div class="text-xs text-gray-400">
                                <span class="font-bold text-gray-200 block mb-0.5">Tự động Quản lý Thời gian:</span>
                                Thời gian nhập kho được ghi nhận <span class="text-[#10b981] font-semibold">Real-Time (NOW)</span>. Hạn sử dụng sẽ tự động tính theo Shelf Life của sản phẩm.
                            </div>
                        </div>
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

            var zoneSelect = document.getElementById('zone-select');

            var searchableProduct = new SearchableSelect(productSelect, {
                placeholder: '<?= __('select_product') ?>',
                noResultsText: 'Không tìm thấy sản phẩm'
            });
            var searchableSupplier = new SearchableSelect(supplierSelect, {
                placeholder: '<?= __('select_supplier') ?>',
                noResultsText: 'Không tìm thấy nhà cung cấp'
            });
            var searchableZone = new SearchableSelect(zoneSelect, {
                placeholder: '<?= __('select_zone') ?>',
                noResultsText: 'Không tìm thấy khu vực kho'
            });
            var searchableShift = new SearchableSelect(shiftSelect, {
                placeholder: '<?= __('select_shift') ?>',
                noResultsText: 'Không tìm thấy ca làm việc'
            });

            function setSupplierOptions(suppliers) {
                supplierSelect.innerHTML = '<option value="">Select Supplier...</option>';
                if (Array.isArray(suppliers) && suppliers.length > 0) {
                    suppliers.forEach(function(supplier) {
                        var option = document.createElement('option');
                        option.value = supplier.SUP_supplier_id;
                        option.textContent = supplier.SUP_supplier_name;
                        supplierSelect.appendChild(option);
                    });
                }
                if (searchableSupplier) searchableSupplier.refresh();
            }

            function fetchSuppliers(productId) {
                supplierSelect.innerHTML = '<option value="">Loading suppliers...</option>';
                if (searchableSupplier) searchableSupplier.refresh();
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
                    if (searchableSupplier) searchableSupplier.refresh();
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
                    if (searchableShift) searchableShift.updateInputValueFromSelect();
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
            if (searchableSupplier) searchableSupplier.refresh();

            // Client-side date validation
            var batchForm = document.querySelector('form');
            if (batchForm) {
                batchForm.addEventListener('submit', function(e) {
                    if (!expiryInput.value) return;
                    var selectedDate = new Date(expiryInput.value);
                    var now = new Date();
                    if (selectedDate <= now) {
                        e.preventDefault();
                        var modal = document.getElementById('date-error-modal');
                        var msg = document.getElementById('date-error-modal-msg');
                        if (msg) {
                            msg.innerHTML = 'Hệ thống chạy thời gian thực! Ngày hết hạn được chọn (<b>' + selectedDate.toLocaleDateString('vi-VN') + ' ' + selectedDate.toLocaleTimeString('vi-VN') + '</b>) thuộc về quá khứ hoặc nhỏ hơn thời gian hiện tại (<b>' + now.toLocaleDateString('vi-VN') + ' ' + now.toLocaleTimeString('vi-VN') + '</b>).<br><br><span class="text-red-400 font-semibold">Lô hàng này sẽ không được duyệt nhập kho! Vui lòng chọn lại ngày hết hạn hợp lệ.</span>';
                        }
                        if (modal) {
                            modal.classList.remove('hidden');
                        }
                    }
                });
            }
        });

        function closeDateErrorModal() {
            var modal = document.getElementById('date-error-modal');
            if (modal) modal.classList.add('hidden');
        }
    </script>

    <!-- Date Error Modal Popup -->
    <div id="date-error-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center hidden transition-all duration-300">
        <div class="bg-[#0e1b2a] border border-red-500/50 rounded-xl p-6 max-w-md w-full mx-4 shadow-2xl shadow-red-950/60">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-red-500/10 rounded-full border border-red-500/30 text-red-400 shrink-0">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white mb-2">Lỗi Không Hợp Thời Gian Thực!</h3>
                    <p id="date-error-modal-msg" class="text-sm text-gray-300 leading-relaxed mb-5"></p>
                    <button type="button" onclick="closeDateErrorModal()" class="w-full py-2.5 px-4 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-500 hover:to-red-600 text-white font-bold rounded-lg shadow-lg shadow-red-950/50 transition-all flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        Đã hiểu & Chọn lại ngày
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>