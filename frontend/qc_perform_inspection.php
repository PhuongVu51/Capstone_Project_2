<?php
// Đường dẫn: frontend/qc_perform_inspection.php
require_once '../backend/includes/auth.php';
require_role(['QC', 'Production_Manager', 'Director'], 'login.php');
require_once '../backend/controllers/QcInspectionController.php';

try {
    $batch_id = $_GET['batch_id'] ?? '';
    $controller = new QcInspectionController();
    $batch = $controller->handlePerformScreen($batch_id);
} catch (Exception $e) {
    die("Lỗi hệ thống khởi tạo Form: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Execute Protocol #<?= htmlspecialchars($batch['BCH_batch_id']) ?> | ProSync</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #06121a; color: #d1d5db; font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen overflow-x-hidden">

    <?php include 'includes/qc_sidebar.php'; ?>

    <main class="md:ml-64 p-6 md:p-8 pt-24 md:pt-8 w-full md:w-[calc(100%-256px)]">
        
        <div class="mb-6 pb-4 border-b border-[#1f2937]">
            <h1 class="text-2xl font-bold text-white tracking-wide">Batch Inspection #<?= htmlspecialchars($batch['BCH_batch_id']) ?></h1>
            <p class="text-xs text-[#10b981] uppercase font-bold tracking-widest mt-1 flex items-center gap-2">
                <span class="relative flex h-2.5 w-2.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#10b981] opacity-75"></span><span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-[#10b981]"></span></span>
                ACTIVE QC PROTOCOL EXECUTION
            </p>
        </div>

        <form action="../backend/controllers/QcInspectionController.php?action=submit" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="batch_id" value="<?= htmlspecialchars($batch['BCH_batch_id']) ?>">
            <input type="hidden" id="initial_qty" value="<?= $batch['BCH_initial_volume_kg'] ?>">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="space-y-6">
                    
                    <div class="bg-[#0f1722] p-5 rounded-lg border border-[#1f2937]">
                        <h3 class="text-sm font-bold text-white mb-4 uppercase tracking-wider">Material Identification</h3>
                        <div class="space-y-3 text-xs">
                            <div class="flex justify-between border-b border-[#1f2937]/50 pb-2">
                                <span class="text-gray-500">Material Grade:</span>
                                <span class="text-[#10b981] font-mono font-bold"><?= htmlspecialchars($batch['PRD_material_grade'] ?? 'Standard') ?></span>
                            </div>
                            <div class="flex justify-between border-b border-[#1f2937]/50 pb-2">
                                <span class="text-gray-500">Batch ID:</span>
                                <span class="text-gray-300 font-mono"><?= htmlspecialchars($batch['BCH_batch_id']) ?></span>
                            </div>
                            <div class="flex justify-between border-b border-[#1f2937]/50 pb-2">
                                <span class="text-gray-500">Supplier:</span>
                                <span class="text-gray-300 font-medium"><?= htmlspecialchars($batch['SUP_supplier_name']) ?></span>
                            </div>
                            <div class="flex justify-between border-b border-[#1f2937]/50 pb-2">
                                <span class="text-gray-500">Origin:</span>
                                <span class="text-gray-300"><?= htmlspecialchars($batch['SUP_origin_facility'] ?? 'Inbound Terminal') ?></span>
                            </div>
                            <div class="flex justify-between pb-1">
                                <span class="text-gray-500">Shipment Date:</span>
                                <span class="text-gray-300 font-mono"><?= date('Y-m-d', strtotime($batch['BCH_received_date'])) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-[#0f1722] p-5 rounded-lg border border-[#1f2937]">
                        <h3 class="text-sm font-bold text-white mb-2 uppercase tracking-wider flex justify-between items-center">
                            Visual Record
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </h3>                        
                        <div class="border border-dashed border-[#374151] hover:border-[#10b981] rounded-lg p-1 text-center cursor-pointer transition-colors relative h-48 flex items-center justify-center bg-[#07121a] group">
                            <input type="file" id="imageUpload" name="qc_photo" accept="image/*" capture="environment" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" onchange="previewImage(event)">
                            
                            <div id="uploadPlaceholder" class="text-xs text-gray-500 group-hover:text-[#10b981] transition-colors">
                                <svg class="w-8 h-8 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                <span>Click to upload or snap lot evidence</span>
                            </div>
                            
                            <img id="imagePreview" src="#" alt="Preview" class="hidden absolute inset-0 w-full h-full object-cover rounded-lg z-10" onclick="openLightbox()">
                            
                            <div id="enlargeOverlay" class="hidden absolute inset-0 bg-black/60 backdrop-blur-sm rounded-lg z-30 flex flex-col items-center justify-center cursor-zoom-in pointer-events-none group-hover:flex transition-all opacity-0 group-hover:opacity-100">
                                <svg class="w-8 h-8 text-white mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path></svg>
                                <span class="text-xs font-bold text-white tracking-widest">CLICK TO ENLARGE</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-2 space-y-6">
                    
                    <div class="bg-[#0f1722] p-6 rounded-lg border border-[#1f2937] space-y-6">
                        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 border-b border-[#1f2937] pb-4">
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider">QC Quality Protocol</h3>
                            <h3 class="text-xs text-gray-400 bg-[#1f2937]/50 px-3 py-1.5 rounded font-mono">
                                Protocol: <strong class="text-white">ISO-9001:2025 Standard</strong>
                            </h3>
                            <span class="text-xs text-gray-400 bg-[#1f2937]/50 px-3 py-1.5 rounded font-mono">
                                Phase: Quantitative
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[11px] text-[#10b981] uppercase font-bold mb-2 tracking-wider">Received Quantity</label>
                                <div class="bg-[#07121a] p-3 rounded border border-[#10b981] shadow-[0_0_12px_rgba(16,185,129,0.3)] flex justify-between items-center cursor-not-allowed">
                                    <span class="font-mono text-white text-xl font-bold"><?= number_format($batch['BCH_initial_volume_kg'], 1) ?></span>
                                    <span class="text-xs font-bold text-[#10b981]">KG</span>
                                </div>
                            </div>
                            
                            <div>
                                <label for="rejected_qty" class="block text-[11px] text-[#10b981] uppercase font-bold mb-2 tracking-wider">Enter Rejected Quantity</label>
                                <div class="relative group">
                                    <input type="number" step="0.1" min="0" max="<?= $batch['BCH_initial_volume_kg'] ?>" id="rejected_qty" name="rejected_qty" required oninput="calculateYield()"
                                        class="w-full bg-[#0b121c] border border-[#10b981] shadow-[0_0_12px_rgba(16,185,129,0.3)] text-white text-xl font-mono font-bold p-2.5 rounded text-right pr-12 focus:outline-none focus:shadow-[0_0_20px_rgba(16,185,129,0.6)] transition-all">
                                    <span class="absolute right-4 top-4 text-xs text-[#10b981] font-bold">KG</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="rejection_reason" class="block text-[11px] text-gray-400 uppercase font-semibold mb-2">Select Rejection Reason</label>
                            <select id="rejection_reason" name="rejection_reason" class="w-full bg-[#0b121c] border border-[#1f2937] text-sm text-gray-200 rounded p-3 outline-none focus:border-[#10b981] transition-colors appearance-none cursor-pointer">
                                <option value="None">No Defects</option>
                                <option value="Contaminated">Material Contamination</option>
                                <option value="Rotten">Spoilage / Rotten Degradation</option>
                                <option value="Moisture_Anomaly">Incorrect Moisture Percentage</option>
                                <option value="Other">Other Violation</option>
                            </select>
                        </div>

                        <div>
                            <label for="inspector_comments" class="block text-[11px] text-gray-400 uppercase font-semibold mb-2">Inspector Comments</label>
                            <textarea id="inspector_comments" name="inspector_comments" rows="3" placeholder="Log compliance comments or defect specifics..."
                                      class="w-full bg-[#0b121c] border border-[#1f2937] text-sm text-gray-200 rounded p-3 focus:outline-none focus:border-[#10b981] placeholder-gray-600 transition-colors"></textarea>
                        </div>

                        <div class="mt-8 pt-6 border-t border-[#1f2937]">
                            <div class="p-4 bg-[#07121a] rounded border border-[#1f2937] flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
                                <div>
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wide">Calculated Usable Yield</p>
                                    <p class="text-[11px] text-gray-500 mt-1">Est. pass threshold metric: >= 80%</p>
                                </div>
                                <div class="text-right">
                                    <span id="yieldDisplay" class="text-4xl font-mono font-black text-[#10b981] transition-colors duration-300">100.0%</span>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <button type="button" onclick="window.location.href='qc_inspections.php'" class="flex-1 py-3.5 bg-[#0f1722] border border-[#374151] text-gray-300 rounded font-bold text-xs uppercase tracking-wider hover:bg-[#1f2937] hover:text-white transition-all">
                                    Cancel Inspection
                                </button>
                                <button type="submit" class="flex-1 py-3.5 bg-[#10b981] hover:bg-[#059669] text-gray-900 rounded font-bold text-xs uppercase tracking-wider transition-all shadow-[0_0_15px_rgba(16,185,129,0.2)] hover:shadow-[0_0_20px_rgba(16,185,129,0.4)]">
                                    Submit Report
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </main>

    <div id="lightbox" class="hidden fixed inset-0 bg-black/95 z-[60] flex flex-col items-center justify-center p-4 backdrop-blur-md transition-opacity" onclick="closeLightbox()">
        <button class="absolute top-6 right-6 text-gray-400 hover:text-white bg-[#1f2937] rounded-full p-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <img id="lightboxImg" src="#" alt="Enlarged Evidence" class="max-w-full max-h-[85vh] rounded-lg shadow-2xl border border-[#374151] cursor-zoom-out object-contain">
        <p class="text-gray-400 font-mono text-xs mt-4">Visual Evidence Record - Click anywhere to close</p>
    </div>

    <script>
        function previewImage(event) {
            const input = event.target;
            if (input.files && input.value) {
                const reader = new FileReader();
                reader.onload = function(){
                    const preview = document.getElementById('imagePreview');
                    preview.src = reader.result;
                    preview.classList.remove('hidden');
                    document.getElementById('uploadPlaceholder').classList.add('hidden');
                    document.getElementById('enlargeOverlay').classList.remove('hidden');
                    input.classList.remove('z-20');
                    input.classList.add('z-0');
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function openLightbox() {
            const previewSrc = document.getElementById('imagePreview').src;
            if (previewSrc && previewSrc !== '#') {
                document.getElementById('lightboxImg').src = previewSrc;
                document.getElementById('lightbox').classList.remove('hidden');
            }
        }
        function closeLightbox() {
            document.getElementById('lightbox').classList.add('hidden');
        }

        function calculateYield() {
            const initialStr = document.getElementById('initial_qty').value;
            const initial = parseFloat(initialStr) || 0;
            const rejectedInput = document.getElementById('rejected_qty').value;
            const rejected = parseFloat(rejectedInput) || 0;
            
            let usable = initial - rejected;
            if (usable < 0) usable = 0;
            
            // Tính toán trừ đi 2% hao hụt tự nhiên mặc định của nhà máy
            let finalUsable = usable - (usable * 0.02);
            if (finalUsable < 0) finalUsable = 0;

            const yieldPct = ((finalUsable / initial) * 100).toFixed(1);
            const display = document.getElementById('yieldDisplay');
            display.innerText = yieldPct + "%";
            
            if (yieldPct < 80) {
                display.className = "text-4xl font-mono font-black text-red-500 transition-colors duration-300";
            } else if (yieldPct < 90) {
                display.className = "text-4xl font-mono font-black text-yellow-400 transition-colors duration-300";
            } else {
                display.className = "text-4xl font-mono font-black text-[#10b981] transition-colors duration-300";
            }
        }
    </script>
</body>
</html>