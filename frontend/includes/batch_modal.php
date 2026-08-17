<?php
// Shared Batch Detail & Batch Edit Modals
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$lang = $_SESSION['lang'] ?? 'vi';

$zones_list = [];
try {
    if (isset($pdo)) {
        $zoneNameCol = ($lang === 'en') ? 'COALESCE(STZ_zone_name_en, STZ_zone_name)' : 'STZ_zone_name';
        $stmt_z = $pdo->query("SELECT STZ_zone_id, $zoneNameCol AS STZ_zone_name FROM STORAGE_ZONES");
        $zones_list = $stmt_z->fetchAll(PDO::FETCH_ASSOC);
        if ($lang === 'en' && !empty($zones_list)) {
            foreach ($zones_list as &$z) {
                if (function_exists('translate_zone_name')) {
                    $z['STZ_zone_name'] = translate_zone_name($z['STZ_zone_name']);
                }
            }
        }
    }
} catch (Exception $e) {}
?>

<!-- BATCH DETAIL MODAL -->
<div id="batch-detail-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center hidden transition-all duration-300">
    <div class="bg-[#0e1b2a] border border-[#1f2937] rounded-xl p-6 max-w-2xl w-full mx-4 shadow-2xl overflow-y-auto max-h-[90vh]">
        <div class="flex justify-between items-center pb-4 mb-4 border-b border-[#1f2937]">
            <div>
                <span class="text-xs uppercase tracking-wider text-[#10b981] font-bold"><?= ($lang === 'en') ? 'Batch Details' : 'Chi tiết Lô hàng' ?></span>
                <h3 id="bd-batch-id" class="text-xl font-bold text-white font-mono mt-0.5">BATCH_ID</h3>
            </div>
            <button type="button" onclick="closeBatchDetailModal()" class="p-2 text-gray-400 hover:text-white hover:bg-[#162a3d] rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-6">
            <div class="bg-[#07121a] p-3.5 rounded-lg border border-[#1f2937]">
                <p class="text-xs text-gray-400 font-semibold uppercase"><?= ($lang === 'en') ? 'Product / Item' : 'Mặt hàng / Sản phẩm' ?></p>
                <p id="bd-product-name" class="text-white font-bold mt-1 text-base">-</p>
                <p id="bd-material-grade" class="text-xs text-gray-400 mt-0.5">-</p>
            </div>
            <div class="bg-[#07121a] p-3.5 rounded-lg border border-[#1f2937]">
                <p class="text-xs text-gray-400 font-semibold uppercase"><?= ($lang === 'en') ? 'Supplier' : 'Nhà cung cấp' ?></p>
                <p id="bd-supplier-name" class="text-white font-bold mt-1 text-base">-</p>
            </div>
            <div class="bg-[#07121a] p-3.5 rounded-lg border border-[#1f2937]">
                <p class="text-xs text-gray-400 font-semibold uppercase"><?= ($lang === 'en') ? 'Available / Initial Stock' : 'Tồn kho hiện tại / Ban đầu' ?></p>
                <p class="mt-1">
                    <span id="bd-available-stock" class="text-[#10b981] font-bold text-base">-</span> / 
                    <span id="bd-initial-volume" class="text-gray-300 font-semibold">-</span>
                </p>
            </div>
            <div class="bg-[#07121a] p-3.5 rounded-lg border border-[#1f2937]">
                <p class="text-xs text-gray-400 font-semibold uppercase"><?= ($lang === 'en') ? 'Storage Zone' : 'Khu vực kho' ?></p>
                <p id="bd-zone-name" class="text-white font-bold mt-1 text-base">-</p>
            </div>
            <div class="bg-[#07121a] p-3.5 rounded-lg border border-[#1f2937]">
                <p class="text-xs text-gray-400 font-semibold uppercase"><?= ($lang === 'en') ? 'Received Date' : 'Ngày nhập kho' ?></p>
                <p id="bd-received-date" class="text-gray-200 mt-1 font-mono">-</p>
            </div>
            <div class="bg-[#07121a] p-3.5 rounded-lg border border-[#1f2937]">
                <p class="text-xs text-gray-400 font-semibold uppercase"><?= ($lang === 'en') ? 'Expiry Date' : 'Ngày hết hạn' ?></p>
                <p id="bd-expiry-date" class="text-amber-400 font-bold mt-1 font-mono">-</p>
            </div>
            <div class="bg-[#07121a] p-3.5 rounded-lg border border-[#1f2937]">
                <p class="text-xs text-gray-400 font-semibold uppercase"><?= ($lang === 'en') ? 'Health Status' : 'Trạng thái sức khỏe' ?></p>
                <span id="bd-health-status" class="inline-block mt-1 px-2.5 py-0.5 rounded text-xs font-bold uppercase">-</span>
            </div>
            <div class="bg-[#07121a] p-3.5 rounded-lg border border-[#1f2937]">
                <p class="text-xs text-gray-400 font-semibold uppercase"><?= ($lang === 'en') ? 'QC Stage' : 'Giai đoạn kiểm định (QC Stage)' ?></p>
                <p id="bd-current-stage" class="text-blue-400 font-bold mt-1 uppercase text-xs tracking-wider">-</p>
            </div>
        </div>

        <div class="border-t border-[#1f2937] pt-4">
            <h4 class="text-sm font-bold text-white mb-3"><?= ($lang === 'en') ? 'Stock Movements History' : 'Lịch sử Luân chuyển lô hàng (Stock Movements)' ?></h4>
            <div class="max-h-48 overflow-y-auto rounded-lg border border-[#1f2937]">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#07121a] text-gray-400 uppercase">
                        <tr>
                            <th class="p-2.5"><?= ($lang === 'en') ? 'Ref Code' : 'Mã Giao dịch' ?></th>
                            <th class="p-2.5"><?= ($lang === 'en') ? 'Type' : 'Loại' ?></th>
                            <th class="p-2.5"><?= ($lang === 'en') ? 'Quantity' : 'Số lượng' ?></th>
                            <th class="p-2.5"><?= ($lang === 'en') ? 'Timestamp' : 'Thời gian' ?></th>
                            <th class="p-2.5"><?= ($lang === 'en') ? 'Operator' : 'Người thực hiện' ?></th>
                        </tr>
                    </thead>
                    <tbody id="bd-movements-body" class="divide-y divide-[#1f2937]">
                        <tr><td colspan="5" class="p-4 text-center text-gray-500"><?= ($lang === 'en') ? 'No movement records found.' : 'Chưa có thông tin luân chuyển.' ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6 pt-4 border-t border-[#1f2937] flex justify-end gap-3">
            <button type="button" onclick="closeBatchDetailModal()" class="px-5 py-2.5 bg-[#162a3d] hover:bg-[#1f3850] text-gray-200 font-bold rounded-lg text-sm transition-colors">
                <?= ($lang === 'en') ? 'Close' : 'Đóng' ?>
            </button>
        </div>
    </div>
</div>

<!-- BATCH EDIT MODAL -->
<div id="batch-edit-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center hidden transition-all duration-300">
    <div class="bg-[#0e1b2a] border border-[#1f2937] rounded-xl p-6 max-w-lg w-full mx-4 shadow-2xl">
        <div class="flex justify-between items-center pb-4 mb-4 border-b border-[#1f2937]">
            <div>
                <span class="text-xs uppercase tracking-wider text-amber-400 font-bold"><?= ($lang === 'en') ? 'Edit Batch Details' : 'Chỉnh sửa thông tin Lô hàng' ?></span>
                <h3 id="be-batch-id-title" class="text-xl font-bold text-white font-mono mt-0.5">BATCH_ID</h3>
            </div>
            <button type="button" onclick="closeBatchEditModal()" class="p-2 text-gray-400 hover:text-white hover:bg-[#162a3d] rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <form action="../backend/controllers/StockController.php?action=update_batch_full" method="POST" id="be-form" class="space-y-4">
            <input type="hidden" name="batch_id" id="be-batch-id">
            <input type="hidden" name="redirect" id="be-redirect" value="dashboard_warehouse.php">

            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2"><?= ($lang === 'en') ? 'Storage Zone' : 'Khu vực lưu trữ (Storage Zone)' ?></label>
                <select name="zone_id" id="be-zone-id" required class="w-full bg-[#07121a] border border-[#374151] text-white rounded-lg p-2.5 text-sm focus:border-[#10b981] focus:outline-none">
                    <?php foreach ($zones_list as $z): ?>
                        <option value="<?= $z['STZ_zone_id'] ?>"><?= htmlspecialchars($z['STZ_zone_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2"><?= ($lang === 'en') ? 'Expiry Date' : 'Ngày hết hạn (Expiry Date)' ?></label>
                <input type="datetime-local" name="expiry_date" id="be-expiry-date" required class="w-full bg-[#07121a] border border-[#374151] text-white rounded-lg p-2.5 text-sm focus:border-[#10b981] focus:outline-none [color-scheme:dark]">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2"><?= ($lang === 'en') ? 'Available Stock (kg)' : 'Tồn kho khả dụng (kg)' ?></label>
                <input type="number" step="0.01" min="0" name="available_stock" id="be-available-stock" required class="w-full bg-[#07121a] border border-[#374151] text-white rounded-lg p-2.5 text-sm focus:border-[#10b981] focus:outline-none font-mono">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2"><?= ($lang === 'en') ? 'Health Status' : 'Trạng thái sức khỏe (Health Status)' ?></label>
                <select name="health_status" id="be-health-status" class="w-full bg-[#07121a] border border-[#374151] text-white rounded-lg p-2.5 text-sm focus:border-[#10b981] focus:outline-none">
                    <option value="Good"><?= ($lang === 'en') ? 'Good' : 'Good (Tốt)' ?></option>
                    <option value="Warning"><?= ($lang === 'en') ? 'Warning' : 'Warning (Cảnh báo)' ?></option>
                    <option value="Critical"><?= ($lang === 'en') ? 'Critical' : 'Critical (Nguy cơ hỏng)' ?></option>
                </select>
            </div>

            <div id="be-error-msg" class="hidden p-3 bg-red-900/40 text-red-200 text-xs rounded border border-red-800 font-semibold"></div>

            <div class="pt-4 border-t border-[#1f2937] flex gap-3">
                <button type="button" onclick="closeBatchEditModal()" class="flex-1 py-2.5 border border-[#374151] text-gray-300 rounded-lg hover:bg-[#1f2937] transition-colors font-bold text-sm">
                    <?= ($lang === 'en') ? 'Cancel' : 'Hủy' ?>
                </button>
                <button type="submit" class="flex-1 py-2.5 bg-gradient-to-r from-[#10b981] to-[#059669] text-white font-bold rounded-lg shadow hover:from-[#34d399] hover:to-[#10b981] transition-all text-sm">
                    <?= ($lang === 'en') ? 'Save Changes' : 'Lưu Thay Đổi' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const batchModalLang = '<?= $lang ?>';
const txtBatchNotFound = batchModalLang === 'en' ? 'Batch details not found!' : 'Không tìm thấy thông tin lô hàng!';
const txtBatchLoadError = batchModalLang === 'en' ? 'Failed to load batch details!' : 'Lỗi tải thông tin lô hàng!';
const txtGradeLabel = batchModalLang === 'en' ? 'Grade: ' : 'Phân loại: ';
const txtNoMovements = batchModalLang === 'en' ? 'No movement records found.' : 'Chưa có thông tin luân chuyển.';
const txtExpiryPastError = batchModalLang === 'en' 
    ? 'Real-time validation error: Expiry date cannot be in the past!' 
    : 'Lỗi thời gian thực: Ngày hết hạn không thể thuộc về quá khứ hoặc nhỏ hơn ngày hiện tại!';

function formatDateDisplay(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
}

function openBatchDetailModal(batchId) {
    if (!batchId) return;
    fetch('../backend/controllers/StockController.php?action=get_batch_detail&batch_id=' + encodeURIComponent(batchId))
        .then(res => {
            if (!res.ok) {
                throw new Error('Network response was not ok: ' + res.status);
            }
            return res.json();
        })
        .then(res => {
            if (res.success && res.data) {
                const b = res.data;
                document.getElementById('bd-batch-id').innerText = b.BCH_batch_id;
                document.getElementById('bd-product-name').innerText = b.PRD_product_name || '-';
                document.getElementById('bd-material-grade').innerText = b.PRD_material_grade ? txtGradeLabel + b.PRD_material_grade : '';
                document.getElementById('bd-supplier-name').innerText = b.SUP_supplier_name || '-';
                document.getElementById('bd-available-stock').innerText = Number(b.BCH_available_stock_kg || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' kg';
                document.getElementById('bd-initial-volume').innerText = Number(b.BCH_initial_volume_kg || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' kg';
                document.getElementById('bd-zone-name').innerText = b.STZ_zone_name || '-';
                document.getElementById('bd-received-date').innerText = formatDateDisplay(b.BCH_received_date);
                document.getElementById('bd-expiry-date').innerText = formatDateDisplay(b.BCH_expiry_date);
                document.getElementById('bd-current-stage').innerText = b.BCH_current_stage || '-';
                
                const hs = document.getElementById('bd-health-status');
                hs.innerText = b.BCH_health_status || 'Good';
                if (b.BCH_health_status === 'Critical') {
                    hs.className = 'inline-block mt-1 px-2.5 py-0.5 rounded text-xs font-bold uppercase bg-red-950 text-red-400 border border-red-800';
                } else if (b.BCH_health_status === 'Warning') {
                    hs.className = 'inline-block mt-1 px-2.5 py-0.5 rounded text-xs font-bold uppercase bg-amber-950 text-amber-400 border border-amber-800';
                } else {
                    hs.className = 'inline-block mt-1 px-2.5 py-0.5 rounded text-xs font-bold uppercase bg-emerald-950 text-emerald-400 border border-emerald-800';
                }

                // Populate movements
                const mBody = document.getElementById('bd-movements-body');
                mBody.innerHTML = '';
                if (b.movements && b.movements.length > 0) {
                    b.movements.forEach(m => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td class="p-2.5 font-mono text-[#10b981]">${m.STM_reference_code || '-'}</td>
                            <td class="p-2.5"><span class="px-2 py-0.5 rounded text-[10px] font-bold ${m.STM_movement_type==='IN'?'bg-emerald-950 text-emerald-300':(m.STM_movement_type==='OUT'?'bg-red-950 text-red-300':'bg-blue-950 text-blue-300')}">${m.STM_movement_type}</span></td>
                            <td class="p-2.5 font-mono font-bold">${Number(m.STM_quantity_kg).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} kg</td>
                            <td class="p-2.5 text-gray-400">${m.STM_timestamp || '-'}</td>
                            <td class="p-2.5 text-gray-300">${m.USR_full_name || '-'}</td>
                        `;
                        mBody.appendChild(tr);
                    });
                } else {
                    mBody.innerHTML = `<tr><td colspan="5" class="p-4 text-center text-gray-500">${txtNoMovements}</td></tr>`;
                }

                document.getElementById('batch-detail-modal').classList.remove('hidden');
            } else {
                alert(txtBatchNotFound);
            }
        })
        .catch(err => {
            console.error(err);
            alert(txtBatchLoadError);
        });
}

function closeBatchDetailModal() {
    document.getElementById('batch-detail-modal').classList.add('hidden');
}

function openBatchEditModal(batchId, redirectPage = 'dashboard_warehouse.php') {
    if (!batchId) return;
    document.getElementById('be-redirect').value = redirectPage;
    document.getElementById('be-error-msg').classList.add('hidden');

    fetch('../backend/controllers/StockController.php?action=get_batch_detail&batch_id=' + encodeURIComponent(batchId))
        .then(res => {
            if (!res.ok) throw new Error('Network error: ' + res.status);
            return res.json();
        })
        .then(res => {
            if (res.success && res.data) {
                const b = res.data;
                document.getElementById('be-batch-id-title').innerText = b.BCH_batch_id;
                document.getElementById('be-batch-id').value = b.BCH_batch_id;
                if (document.getElementById('be-zone-id')) document.getElementById('be-zone-id').value = b.BCH_zone_id;
                
                // Format datetime-local string
                if (b.BCH_expiry_date) {
                    const dt = new Date(b.BCH_expiry_date);
                    if (!isNaN(dt.getTime())) {
                        const yr = dt.getFullYear();
                        const mo = String(dt.getMonth() + 1).padStart(2, '0');
                        const da = String(dt.getDate()).padStart(2, '0');
                        const hr = String(dt.getHours()).padStart(2, '0');
                        const mi = String(dt.getMinutes()).padStart(2, '0');
                        document.getElementById('be-expiry-date').value = `${yr}-${mo}-${da}T${hr}:${mi}`;
                    }
                }
                
                document.getElementById('be-available-stock').value = b.BCH_available_stock_kg;
                document.getElementById('be-health-status').value = b.BCH_health_status || 'Good';

                document.getElementById('batch-edit-modal').classList.remove('hidden');
            }
        })
        .catch(err => {
            console.error(err);
            alert(txtBatchLoadError);
        });
}

function closeBatchEditModal() {
    document.getElementById('batch-edit-modal').classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', function() {
    const editForm = document.getElementById('be-form');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            const expiryVal = document.getElementById('be-expiry-date').value;
            if (expiryVal) {
                const exp = new Date(expiryVal);
                const now = new Date();
                if (exp <= now) {
                    e.preventDefault();
                    const err = document.getElementById('be-error-msg');
                    err.innerText = txtExpiryPastError;
                    err.classList.remove('hidden');
                }
            }
        });
    }
});
</script>
