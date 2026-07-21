<?php
// Đường dẫn: frontend/includes/qc_sidebar.php.

$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="md:hidden w-full bg-[#0f1722] border-b border-[#1f2937] p-4 flex justify-between items-center fixed top-0 left-0 z-50 shadow-md">
    <h1 class="text-sm font-bold text-[#10b981] tracking-wider uppercase flex items-center gap-2">
        <img src="../image/353838036_746744254123717_8058064823033680293_n.jpg" alt="F&G FOOD" class="w-6 h-6 object-contain rounded-md border border-[#1f2937]" />
        F&G FOOD QC
    </h1>
    <button id="mobile-menu-toggle" class="text-gray-400 hover:text-white focus:outline-none p-1 rounded-md hover:bg-[#1f2937] transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
    </button>
</div>

<div id="sidebar-overlay" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-30 md:hidden transition-opacity duration-300"></div>

<aside id="main-sidebar" class="fixed inset-y-0 left-0 w-64 bg-[#0f1722] border-r border-[#1f2937] flex flex-col justify-between z-40 
    transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out pt-16 md:pt-0">
    
    <div>
        <div class="p-6 border-b border-[#1f2937] hidden md:block">
            <h2 class="text-[#10b981] font-bold text-xl tracking-wide flex items-center gap-3">
                <img src="../image/353838036_746744254123717_8058064823033680293_n.jpg" alt="F&G FOOD logo" class="w-8 h-8 object-contain rounded-md shadow-sm border border-[#1f2937]" />
                F&G FOOD
            </h2>
        </div>
        
        <nav class="flex-1 p-4 space-y-2 mt-2">            
            <a href="qc_dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-md transition-colors 
                <?= ($current_page == 'qc_dashboard.php') ? 'bg-[#10b981]/10 border-l-4 border-[#10b981] text-[#10b981] font-semibold' : 'text-gray-400 hover:text-white hover:bg-[#1f2937]/50 border-l-4 border-transparent' ?>">
                <span class="text-lg group-hover:scale-110 transition-transform"></span>
                Dashboard Overview
            </a>

            <a href="qc_inspections.php" class="flex items-center gap-3 px-4 py-3 rounded-md transition-colors 
                <?= ($current_page == 'qc_inspections.php') ? 'bg-[#10b981]/10 border-l-4 border-[#10b981] text-[#10b981] font-semibold' : 'text-gray-400 hover:text-white hover:bg-[#1f2937]/50 border-l-4 border-transparent' ?>">
                <span class="text-lg group-hover:scale-110 transition-transform"></span>
                Inspection Log
            </a>

            <a href="qc_reports.php" class="flex items-center gap-3 px-4 py-3 rounded-md transition-colors 
                <?= ($current_page == 'qc_reports.php') ? 'bg-[#10b981]/10 border-l-4 border-[#10b981] text-[#10b981] font-semibold' : 'text-gray-400 hover:text-white hover:bg-[#1f2937]/50 border-l-4 border-transparent' ?>">
                <span class="text-lg group-hover:scale-110 transition-transform"></span>
                Reports
            </a>
        </nav>
    </div>

    <div class="p-5 border-t border-[#1f2937] bg-gradient-to-b from-[#0f1722] to-[#0a1118]">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-[#1f2937] flex items-center justify-center border border-[#374151]">
                <span class="text-[#10b981] font-bold text-sm">QC</span>
            </div>
            <div class="overflow-hidden">
                <p class="text-sm font-bold text-gray-200 truncate"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Guest User') ?></p>
                <p class="text-[11px] text-[#10b981] uppercase tracking-wider font-semibold mt-0.5">QC Operator</p>
            </div>
        </div>
        <a href="../backend/connection/logout.php" class="flex items-center justify-center gap-2 w-full py-2.5 text-sm text-red-400 font-semibold border border-red-900/30 rounded-lg hover:bg-red-500 hover:text-white hover:border-red-500 transition-all shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            Logout
        </a>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('mobile-menu-toggle');
    const sidebar = document.getElementById('main-sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    if(toggleBtn && sidebar && overlay) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        });

        overlay.addEventListener('click', function() {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        });
    }
});
</script>