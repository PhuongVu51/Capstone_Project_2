<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF'] ?? '');
$full_name = $_SESSION['full_name'] ?? 'Guest User';
$role = $_SESSION['role'] ?? 'Warehouse_Staff'; // Mặc định nếu không có role

// 1. CẤU HÌNH LABEL & MÀU SẮC THEO ROLE
$role_label = match ($role) {
    'QC' => 'QC Operator',
    'Warehouse_Staff' => 'Warehouse Staff',
    'Production_Manager' => 'Production Manager',
    'Director' => 'Director',
    default => 'Operator',
};

$role_badge = match ($role) {
    'QC' => 'text-[#10b981]',
    'Warehouse_Staff' => 'text-[#60a5fa]',
    'Production_Manager' => 'text-[#f59e0b]',
    'Director' => 'text-[#a78bfa]',
    default => 'text-gray-400',
};

// 2. LOGIC ĐIỀU HƯỚNG MENU THEO ROLE
if ($role === 'QC') {
    $nav_items = [
        ['label' => __('dashboard'), 'href' => 'qc_dashboard.php', 'page' => 'qc_dashboard.php'],
        ['label' => __('inspection_log'), 'href' => 'qc_inspections.php', 'page' => 'qc_inspections.php'],
        ['label' => __('reports'), 'href' => 'qc_reports.php', 'page' => 'qc_reports.php'],
    ];
    $sidebar_title = 'F&G FOOD QC';
    $sidebar_subtitle = 'Quality Control';

} elseif ($role === 'Production_Manager' || $role === 'Director') {
    // Menu chuẩn theo hình ảnh của Production
    $nav_items = [
        ['label' => __('dashboard'), 'href' => 'dashboard_production.php', 'page' => 'dashboard_production.php'],
        ['label' => __('inventory'), 'href' => 'inventory.php', 'page' => 'inventory.php'],
        ['label' => __('fefo_alerts'), 'href' => 'production_FEFO.php', 'page' => 'production_FEFO.php'],
        ['label' => __('analytics'), 'href' => 'production_analytics.php', 'page' => 'production_analytics.php'],
    ];
    $sidebar_title = 'F&G FOOD';
    $sidebar_subtitle = 'Production Unit 04';

} else {
    // Mặc định là Warehouse
    $nav_items = [
        ['label' => __('dashboard'), 'href' => 'dashboard_warehouse.php', 'page' => 'dashboard_warehouse.php'],
        ['label' => __('inventory'), 'href' => 'inventory.php', 'page' => 'inventory.php'],
        //manage request
        ['label' => __('Manage requests'), 'href' => 'manage_requests.php', 'page' => 'manage_requests.php'],
        ['label' => __('log_batch'), 'href' => 'log_batch.php', 'page' => 'log_batch.php'],
        ['label' => __('reports'), 'href' => 'warehouse_reports.php', 'page' => 'warehouse_reports.php'],
    ];
    $sidebar_title = 'F&G FOOD';
    $sidebar_subtitle = 'Warehouse Unit 04';
}

if (!function_exists('__')) {
    function __($key, $default = '') { return $default ?: $key; }
}
?>

<!-- NOTIFICATION BELL (Shared across all pages) -->
<div class="fixed top-4 right-4 md:right-8 z-50">
    <div class="relative">
        <!-- Bell Button -->
        <button id="notification-bell-btn" class="relative p-2 text-gray-400 hover:text-white bg-[#0f1722]/80 backdrop-blur-sm hover:bg-[#1f2937] rounded-full border border-[#1f2937] transition-all shadow-lg focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
            </svg>
            <!-- Badge -->
            <span id="notification-badge" class="hidden absolute top-0 right-0 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white transform translate-x-1/4 -translate-y-1/4 bg-red-500 rounded-full">
                0
            </span>
        </button>

        <!-- Dropdown Panel -->
        <div id="notification-dropdown" class="hidden absolute right-0 mt-2 w-80 sm:w-96 bg-[#0f1722] border border-[#1f2937] rounded-xl shadow-2xl overflow-hidden transform opacity-0 scale-95 transition-all duration-200 origin-top-right">
            <div class="p-4 border-b border-[#1f2937] flex justify-between items-center bg-[#07121a]">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider"><?= __('notifications', 'Thông báo') ?></h3>
                <button id="mark-all-read-btn" class="hidden text-xs text-[#10b981] hover:text-[#059669] transition-colors"><?= __('mark_all_read', 'Đánh dấu đã đọc') ?></button>
            </div>

            <!-- Filter Bar -->
            <div id="notification-filter-bar" class="p-2 border-b border-[#1f2937] bg-[#0f1722] flex gap-2 overflow-x-auto no-scrollbar text-xs">
                <button class="notif-filter-btn px-3 py-1.5 rounded-full bg-[#10b981] text-white font-medium whitespace-nowrap transition-colors" data-filter="all"><?= __('all', 'Tất cả') ?></button>
                <button class="notif-filter-btn px-3 py-1.5 rounded-full bg-[#1f2937] text-gray-400 hover:text-white transition-colors whitespace-nowrap" data-filter="fefo">FEFO</button>
                <button class="notif-filter-btn px-3 py-1.5 rounded-full bg-[#1f2937] text-gray-400 hover:text-white transition-colors whitespace-nowrap" data-filter="qc">QC</button>
                <button class="notif-filter-btn px-3 py-1.5 rounded-full bg-[#1f2937] text-gray-400 hover:text-white transition-colors whitespace-nowrap" data-filter="stock"><?= __('inventory', 'Hàng tồn') ?></button>
                <button class="notif-filter-btn px-3 py-1.5 rounded-full bg-[#1f2937] text-gray-400 hover:text-white transition-colors whitespace-nowrap" data-filter="request"><?= __('materials', 'Vật tư') ?></button>
            </div>
            
            <div id="notification-list-container" class="max-h-[60vh] overflow-y-auto">
                <!-- Data will be injected here via JS AJAX Polling -->
                <div class="p-8 text-center">
                    <svg class="w-6 h-6 text-gray-600 animate-spin mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    <p class="text-sm text-gray-500">Loading...</p>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- MOBILE HEADER -->
<div class="md:hidden w-full bg-[#0f1722] border-b border-[#1f2937] p-4 flex justify-between items-center fixed top-0 left-0 z-50 shadow-md">
    <h1 class="text-sm font-bold text-[#10b981] tracking-wider uppercase flex items-center gap-2">
        <img src="../image/353838036_746744254123717_8058064823033680293_n.jpg" alt="F&G FOOD" class="w-6 h-6 object-contain rounded-md border border-[#1f2937]" />
        <?= htmlspecialchars($sidebar_title) ?>
    </h1>
    <button id="mobile-menu-toggle" class="text-gray-400 hover:text-white focus:outline-none p-1 rounded-md hover:bg-[#1f2937] transition-colors" type="button" aria-label="Toggle navigation">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
    </button>
</div>

<div id="sidebar-overlay" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-30 md:hidden transition-opacity duration-300"></div>

<!-- SIDEBAR MAIN -->
<aside id="main-sidebar" class="fixed inset-y-0 left-0 w-64 bg-[#0f1722] border-r border-[#1f2937] flex flex-col justify-between z-40 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out pt-16 md:pt-0">
    <div>
        <div class="p-6 border-b border-[#1f2937] hidden md:block">
            <h2 class="text-[#10b981] font-bold text-xl tracking-wide flex items-center gap-3">
                <img src="../image/353838036_746744254123717_8058064823033680293_n.jpg" alt="Logo" class="w-8 h-8 object-contain rounded-md shadow-sm border border-[#1f2937]" />
                <?= htmlspecialchars($sidebar_title) ?>
            </h2>
            <p class="text-xs text-gray-500 mt-2"><?= htmlspecialchars($sidebar_subtitle) ?></p>
        </div>

        <nav class="flex-1 p-4 space-y-2 mt-2">
            <?php foreach ($nav_items as $item): ?>
                <?php $is_active = $current_page === $item['page']; ?>
                <a href="<?= htmlspecialchars($item['href']) ?>"
                   class="flex items-center gap-3 px-4 py-3 rounded-md transition-colors <?= $is_active ? 'bg-[#10b981]/10 border-l-4 border-[#10b981] text-[#10b981] font-semibold' : 'text-gray-400 hover:text-white hover:bg-[#1f2937]/50 border-l-4 border-transparent' ?>">
                    <?= htmlspecialchars($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <!-- BOTTOM USER INFO -->
    <div class="p-5 border-t border-[#1f2937] bg-gradient-to-b from-[#0f1722] to-[#0a1118]">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-[#1f2937] flex items-center justify-center border border-[#374151]">
                <span class="text-[#10b981] font-bold text-sm"><?= htmlspecialchars(substr($role, 0, 2) ?: 'U') ?></span>
            </div>
            <div class="overflow-hidden">
                <p class="text-sm font-bold text-gray-200 truncate"><?= htmlspecialchars($full_name) ?></p>
                <p class="text-[11px] <?= $role_badge ?> uppercase tracking-wider font-semibold mt-0.5"><?= htmlspecialchars($role_label) ?></p>
            </div>
        </div>

        <div class="flex items-center justify-center gap-4 mb-4">
            <a href="?lang=vi" class="opacity-70 hover:opacity-100 transition-opacity <?= (isset($_SESSION['lang']) && $_SESSION['lang'] === 'vi') ? 'opacity-100 ring-2 ring-[#10b981] rounded' : '' ?>" title="Tiếng Việt">
                <img src="https://flagcdn.com/w40/vn.png" alt="VN" class="w-7 h-5 object-cover rounded shadow-sm" />
            </a>
            <a href="?lang=en" class="opacity-70 hover:opacity-100 transition-opacity <?= (isset($_SESSION['lang']) && $_SESSION['lang'] === 'en') ? 'opacity-100 ring-2 ring-[#10b981] rounded' : '' ?>" title="English">
                <img src="https://flagcdn.com/w40/gb.png" alt="EN" class="w-7 h-5 object-cover rounded shadow-sm" />
            </a>
        </div>

        <a href="../backend/connection/logout.php" class="flex items-center justify-center gap-2 w-full py-2.5 text-sm text-red-400 font-semibold border border-red-900/30 rounded-lg hover:bg-red-500 hover:text-white hover:border-red-500 transition-all shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            <?= __('logout') ?>
        </a>
    </div>
</aside>




<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('mobile-menu-toggle');
    const sidebar = document.getElementById('main-sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    if (toggleBtn && sidebar && overlay) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        });

        overlay.addEventListener('click', function() {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        });
    }

    // Notification Logic (AJAX Polling)
    const bellBtn = document.getElementById('notification-bell-btn');
    const dropdown = document.getElementById('notification-dropdown');
    const badge = document.getElementById('notification-badge');
    const markReadBtn = document.getElementById('mark-all-read-btn');
    const listContainer = document.getElementById('notification-list-container');
    
    const currentRole = '<?= htmlspecialchars($role) ?>';
    const storageKey = 'fng_read_notif_ids_' + currentRole;
    let currentNotifs = [];
    let currentNotifFilter = 'all';
    
    // Localization for JS
    const textMarkAsRead = '<?= __('mark_as_read', 'Đánh dấu đã đọc') ?>';
    const textRead = '<?= __('read', 'Đã đọc') ?>';

    // Handle filter clicks
    const filterBtns = document.querySelectorAll('.notif-filter-btn');
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Update active state
            filterBtns.forEach(b => {
                b.classList.remove('bg-[#10b981]', 'text-white');
                b.classList.add('bg-[#1f2937]', 'text-gray-400');
            });
            this.classList.remove('bg-[#1f2937]', 'text-gray-400');
            this.classList.add('bg-[#10b981]', 'text-white');
            
            currentNotifFilter = this.getAttribute('data-filter');
            renderNotifications();
        });
    });

    function escapeHtml(unsafe) {
        return (unsafe || '').toString()
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }

    function getReadIds() {
        try {
            const ids = JSON.parse(localStorage.getItem(storageKey));
            return Array.isArray(ids) ? ids : [];
        } catch (e) {
            return [];
        }
    }

    function addReadId(id) {
        const ids = getReadIds();
        if (!ids.includes(id)) {
            ids.push(id);
            if (ids.length > 200) ids.shift(); // Keep max 200 items to avoid bloat
            localStorage.setItem(storageKey, JSON.stringify(ids));
        }
    }

    function renderNotifications() {
        const readIds = getReadIds();
        let unreadCount = 0;
        
        currentNotifs.forEach(n => {
            if (!readIds.includes(n.id)) unreadCount++;
        });

        // Update Badge
        if (unreadCount > 0) {
            badge.textContent = unreadCount;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }

        // Render Dropdown Content
        let displayNotifs = currentNotifs;
        if (currentNotifFilter !== 'all') {
            displayNotifs = currentNotifs.filter(n => {
                if (currentNotifFilter === 'fefo') return n.type === 'fefo';
                if (currentNotifFilter === 'qc') return ['qc_passed', 'qc_failed', 'qc'].includes(n.type);
                if (currentNotifFilter === 'stock') return ['low_stock', 'out_of_stock'].includes(n.type);
                if (currentNotifFilter === 'request') return n.type === 'material_request';
                return true;
            });
        }

        if (displayNotifs.length > 0) {
            markReadBtn.classList.remove('hidden');
            let html = '<ul class="divide-y divide-[#1f2937]">';
            displayNotifs.forEach(notif => {
                const isRead = readIds.includes(notif.id);
                // Create a circular checkmark for "Mark as read"
                const checkBtnHtml = `
                    <button onclick="markSingleRead('${notif.id}', event)" title="${isRead ? textRead : textMarkAsRead}" class="flex-shrink-0 mt-2 transition-colors ${isRead ? 'text-green-500' : 'text-blue-500 hover:text-blue-400'}">
                        ${isRead 
                            ? '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>'
                            : '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'}
                    </button>
                `;

                html += `
                <li class="relative ${isRead ? 'opacity-60' : 'bg-[#1f2937]/30'} hover:bg-[#1f2937]/60 transition-colors">
                    <div class="flex items-start gap-3 p-4">
                        ${checkBtnHtml}
                        <a href="${notif.link}" class="flex-1 min-w-0 block" onclick="markSingleRead('${notif.id}')">
                            <p class="text-sm font-semibold ${isRead ? 'text-gray-400' : 'text-white'} mb-1">${escapeHtml(notif.title)}</p>
                            <p class="text-xs ${isRead ? 'text-gray-500' : 'text-gray-300'} truncate">${escapeHtml(notif.message)}</p>
                            <p class="text-[10px] text-gray-500 mt-1 uppercase tracking-wider">${escapeHtml(notif.time_desc)}</p>
                        </a>
                    </div>
                </li>`;
            });
            html += '</ul>';
            listContainer.innerHTML = html;
        } else {
            markReadBtn.classList.add('hidden');
            listContainer.innerHTML = `
            <div class="p-8 text-center">
                <svg class="w-12 h-12 text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                <p class="text-sm text-gray-500"><?= __('no_new_notif', 'Không có thông báo mới') ?? 'Không có thông báo mới' ?></p>
            </div>`;
        }
    }

    // Expose for inline onclick
    window.markSingleRead = function(id, event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        addReadId(id);
        renderNotifications();
    };

    function fetchNotifications() {
        fetch('../backend/api/notifications.php?t=' + new Date().getTime())
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    currentNotifs = data.data || [];
                    renderNotifications();
                }
            })
            .catch(error => console.error('Error fetching notifications:', error));
    }

    // Initial load & Setup Polling
    fetchNotifications();
    setInterval(fetchNotifications, 30000);

    // Dropdown toggle logic
    if (bellBtn && dropdown) {
        bellBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.toggle('hidden');
            setTimeout(() => {
                dropdown.classList.toggle('opacity-0');
                dropdown.classList.toggle('scale-95');
            }, 10);
        });

        document.addEventListener('click', function(e) {
            if (!bellBtn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('opacity-0', 'scale-95');
                setTimeout(() => dropdown.classList.add('hidden'), 200);
            }
        });
    }

    // Mark all as read logic
    if (markReadBtn) {
        markReadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const ids = getReadIds();
            currentNotifs.forEach(n => {
                if (!ids.includes(n.id)) ids.push(n.id);
            });
            if (ids.length > 200) ids.splice(0, ids.length - 200);
            localStorage.setItem(storageKey, JSON.stringify(ids));
            
            renderNotifications();
            
            // Optionally close dropdown after marking all read
            dropdown.classList.add('opacity-0', 'scale-95');
            setTimeout(() => dropdown.classList.add('hidden'), 200);
        });
    }
});
</script>