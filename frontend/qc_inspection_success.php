<?php
// Đường dẫn: frontend/qc_inspection_success.php
require_once '../backend/includes/auth.php';
require_role(['QC', 'Production_Manager', 'Director'], 'login.php');

$batch_id = $_GET['batch_id'] ?? 'Unknown';
$rejected = $_GET['rejected'] ?? '0';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspection Successful | ProSync</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #06121a; color: #d1d5db; font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen overflow-x-hidden flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-[#0f1722] rounded-xl border border-[#1f2937] shadow-2xl relative overflow-hidden transform transition-all animate-[popIn_0.4s_ease-out]">
        
        <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-[#10b981] shadow-[0_0_15px_rgba(16,185,129,0.8)]"></div>

        <div class="p-8 sm:p-10 text-center">
            <div class="w-20 h-20 mx-auto bg-[#10b981]/10 rounded-full flex items-center justify-center border border-[#10b981]/30 mb-6">
                <svg class="w-10 h-10 text-[#10b981]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <h2 class="text-2xl font-bold text-white mb-2 tracking-wide">QC Report Submitted!</h2>
            <p class="text-sm text-gray-400 mb-8 leading-relaxed">
                Inspection protocol for batch <span class="text-[#10b981] font-mono font-bold">#<?= htmlspecialchars($batch_id) ?></span> has been successfully logged and synchronized with the ERP system.
            </p>

            <div class="bg-[#07121a] p-4 rounded border border-[#1f2937] mb-8 text-left">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-xs text-gray-500 font-semibold uppercase">Inspection Status</span>
                    <span class="text-xs text-[#10b981] bg-[#10b981]/10 px-2 py-0.5 rounded font-bold uppercase">Completed</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-500 font-semibold uppercase">Rejected Output</span>
                    <span class="text-xs text-yellow-500 font-mono font-bold"><?= htmlspecialchars($rejected) ?> KG</span>
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <a href="qc_inspections.php" 
                   class="w-full py-3.5 bg-[#10b981] hover:bg-[#059669] text-gray-900 rounded font-bold text-sm uppercase tracking-wider transition-all shadow-[0_0_15px_rgba(16,185,129,0.2)] hover:shadow-[0_0_20px_rgba(16,185,129,0.4)] flex justify-center items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    Inspect Next Batch
                </a>
                <a href="qc_dashboard.php" 
                   class="w-full py-3.5 bg-[#1f2937]/50 hover:bg-[#1f2937] border border-[#374151] text-gray-300 rounded font-bold text-sm uppercase tracking-wider transition-all flex justify-center items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Go to Dashboard
                </a>
            </div>
        </div>
    </div>

    <style>
        @keyframes popIn {
            0% { transform: scale(0.9); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
</body>
</html>