<?php
// frontend/includes/chat_widget.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['role'] ?? 'Warehouse_Staff';
$lang = $_SESSION['lang'] ?? 'vi';

if (!function_exists('__')) {
    function __($key, $default = '') { return $default ?: $key; }
}
?>

<!-- AI CHATBOT FLOATING ACTION BUTTON (FAB) -->
<div id="ai-chatbot-widget-container" class="fixed bottom-6 right-6 z-50">
    <!-- Floating Action Button -->
    <button id="ai-chat-fab-btn" 
            class="relative w-14 h-14 rounded-full bg-gradient-to-r from-[#10b981] to-[#059669] text-white shadow-2xl hover:shadow-[#10b981]/30 flex items-center justify-center transition-all duration-300 transform hover:scale-105 active:scale-95 focus:outline-none ring-4 ring-[#10b981]/20"
            title="<?= __('ai_assistant', 'Trợ lý AI F&G Food') ?>"
            aria-label="Toggle AI Chatbot">
        <!-- Sparkles / Chat Icon -->
        <svg id="ai-chat-icon-open" class="w-7 h-7 transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
        </svg>
        <svg id="ai-chat-icon-close" class="w-7 h-7 hidden transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
        <!-- Pulse Status Indicator -->
        <span class="absolute top-0 right-0 flex h-3.5 w-3.5">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500 border-2 border-[#0f1722]"></span>
        </span>
    </button>

    <!-- Chat Popup Window -->
    <div id="ai-chat-window" 
         class="hidden fixed bottom-24 right-4 sm:right-6 w-[calc(100vw-32px)] sm:w-96 h-[520px] max-h-[80vh] bg-[#0f1722] border border-[#1f2937] rounded-2xl shadow-2xl flex flex-col z-50 overflow-hidden transition-all duration-300 transform opacity-0 scale-95 backdrop-blur-md">
        
        <!-- Header -->
        <div class="px-3.5 py-3 bg-gradient-to-r from-[#07121a] via-[#0f1722] to-[#0a1118] border-b border-[#1f2937] flex items-center justify-between gap-2">
            <div class="flex items-center gap-2.5 min-w-0 flex-1">
                <div class="w-8 h-8 rounded-lg bg-[#10b981]/15 border border-[#10b981]/30 flex items-center justify-center text-[#10b981] shrink-0 shadow-inner">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-1.5">
                        <h3 class="text-xs sm:text-sm font-bold text-white truncate leading-tight">
                            <?= __('ai_assistant', 'Trợ lý AI F&G Food') ?>
                        </h3>
                        <span class="px-1.5 py-0.5 text-[10px] font-semibold bg-[#10b981]/20 text-[#10b981] rounded border border-[#10b981]/30 whitespace-nowrap shrink-0">Groq AI</span>
                    </div>
                    <p class="text-[10px] sm:text-[11px] text-gray-400 truncate leading-tight mt-0.5"><?= __('ai_subtitle', 'Hỗ trợ Quyết định Vận hành') ?></p>
                </div>
            </div>
            <div class="flex items-center gap-0.5 shrink-0">
                <button id="ai-chat-expand-btn" class="p-1.5 text-gray-400 hover:text-white rounded-lg hover:bg-[#1f2937] transition-colors" title="Mở rộng / Thu nhỏ">
                    <svg id="ai-chat-expand-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
                    </svg>
                    <svg id="ai-chat-compress-icon" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 15L4 20m0 0h4m-4 0v-4m11 4l5-5m-5 5v-4m0 4h4M9 9L4 4m0 0h4m-4 0v4m11-4l5 5m-5-5v4m0-4h4"></path>
                    </svg>
                </button>
                <button id="ai-chat-clear-btn" class="p-1.5 text-gray-400 hover:text-red-400 rounded-lg hover:bg-[#1f2937] transition-colors" title="<?= __('ai_clear_chat', 'Xóa lịch sử trò chuyện') ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
                <button id="ai-chat-close-btn" class="p-1.5 text-gray-400 hover:text-white rounded-lg hover:bg-[#1f2937] transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>



        <!-- Chat History List -->
        <div id="ai-chat-messages" class="flex-1 p-4 overflow-y-auto space-y-3.5 bg-[#0a1118]/50 text-xs no-scrollbar">
            <!-- Dynamic Messages inserted via JavaScript -->
        </div>

        <!-- Input Form -->
        <form id="ai-chat-form" class="p-3 bg-[#0f1722] border-t border-[#1f2937] flex items-center gap-2">
            <input type="text" 
                   id="ai-chat-input" 
                   class="flex-1 bg-[#0a1118] border border-[#1f2937] text-gray-200 text-xs rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-[#10b981] placeholder-gray-500 transition-colors"
                   placeholder="<?= __('ai_placeholder', 'Hỏi về tồn kho, QC, sản xuất...') ?>"
                   autocomplete="off" required />
            <button type="submit" 
                    id="ai-chat-send-btn"
                    class="p-2.5 bg-[#10b981] hover:bg-[#059669] text-white rounded-xl transition-all shadow-md active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                </svg>
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fabBtn = document.getElementById('ai-chat-fab-btn');
    const chatWindow = document.getElementById('ai-chat-window');
    const closeBtn = document.getElementById('ai-chat-close-btn');
    const clearBtn = document.getElementById('ai-chat-clear-btn');
    const expandBtn = document.getElementById('ai-chat-expand-btn');
    const expandIcon = document.getElementById('ai-chat-expand-icon');
    const compressIcon = document.getElementById('ai-chat-compress-icon');
    const form = document.getElementById('ai-chat-form');
    const input = document.getElementById('ai-chat-input');
    const messagesContainer = document.getElementById('ai-chat-messages');
    const sendBtn = document.getElementById('ai-chat-send-btn');
    const iconOpen = document.getElementById('ai-chat-icon-open');
    const iconClose = document.getElementById('ai-chat-icon-close');

    let isExpanded = false;

    // Toggle Expand / Compress Chat Window
    if (expandBtn) {
        expandBtn.addEventListener('click', function() {
            isExpanded = !isExpanded;
            if (isExpanded) {
                chatWindow.classList.remove('sm:w-96', 'h-[520px]');
                chatWindow.classList.add('sm:w-[660px]', 'h-[680px]', 'max-h-[90vh]');
                expandIcon.classList.add('hidden');
                compressIcon.classList.remove('hidden');
            } else {
                chatWindow.classList.remove('sm:w-[660px]', 'h-[680px]', 'max-h-[90vh]');
                chatWindow.classList.add('sm:w-96', 'h-[520px]');
                expandIcon.classList.remove('hidden');
                compressIcon.classList.add('hidden');
            }
            setTimeout(scrollToBottom, 300);
        });
    }

    const role = '<?= htmlspecialchars($role) ?>';
    const lang = '<?= htmlspecialchars($lang) ?>';
    const storageKey = 'fg_chatbot_history_' + role;


    const defaultWelcomeText = (lang === 'en')
        ? 'Hello! I am your F&G Food AI Assistant. Ask me anything about current stock, QC reports, expiring batches, or production output.'
        : 'Xin chào! Tôi là Trợ lý AI F&G Food. Bạn có thể hỏi tôi về tồn kho, phế phẩm QC, lô hàng sắp hết hạn, sản lượng sản xuất...';

    // Toggle Chat Window
    function toggleChat(show) {
        const isHidden = chatWindow.classList.contains('hidden');
        const shouldShow = (typeof show === 'boolean') ? show : isHidden;

        if (shouldShow) {
            chatWindow.classList.remove('hidden');
            setTimeout(() => {
                chatWindow.classList.remove('opacity-0', 'scale-95');
            }, 10);
            iconOpen.classList.add('hidden');
            iconClose.classList.remove('hidden');
            input.focus();
        } else {
            chatWindow.classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                chatWindow.classList.add('hidden');
            }, 200);
            iconOpen.classList.remove('hidden');
            iconClose.classList.add('hidden');
        }
    }

    if (fabBtn) fabBtn.addEventListener('click', () => toggleChat());
    if (closeBtn) closeBtn.addEventListener('click', () => toggleChat(false));

    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.innerText = text;
        return div.innerHTML;
    }

    // Format Markdown bold/italics/lists simply
    function formatMessageText(text) {
        if (!text) return '';
        let formatted = escapeHtml(text);

        // Convert line breaks
        formatted = formatted.replace(/\n/g, '<br/>');

        // Convert bold **text**
        formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-white">$1</strong>');
        
        // Convert inline code `code`
        formatted = formatted.replace(/`(.*?)`/g, '<code class="bg-[#1f2937] px-1 py-0.5 rounded text-emerald-400 text-[11px]">$1</code>');

        return formatted;
    }

    // Load History
    function getHistory() {
        try {
            const data = sessionStorage.getItem(storageKey);
            return data ? JSON.parse(data) : [];
        } catch (e) {
            return [];
        }
    }

    function saveHistory(history) {
        try {
            sessionStorage.setItem(storageKey, JSON.stringify(history));
        } catch (e) {}
    }

    function renderHistory() {
        const history = getHistory();
        messagesContainer.innerHTML = '';

        // Add Welcome Message if history is empty
        if (history.length === 0) {
            appendMessageUI({
                sender: 'ai',
                answer: defaultWelcomeText,
                in_scope: false
            });
            return;
        }

        history.forEach(msg => appendMessageUI(msg));
        scrollToBottom();
    }

    function scrollToBottom() {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function appendMessageUI(msg) {
        const isUser = msg.sender === 'user';
        const msgDiv = document.createElement('div');

        if (isUser) {
            msgDiv.className = 'flex justify-end';
            msgDiv.innerHTML = `
                <div class="bg-[#10b981]/20 border border-[#10b981]/40 text-emerald-100 rounded-2xl rounded-tr-none px-3.5 py-2.5 max-w-[85%] shadow-sm leading-relaxed">
                    ${escapeHtml(msg.text)}
                </div>
            `;
        } else {
            msgDiv.className = 'flex justify-start';
            let disclaimerHtml = '';
            
            if (msg.in_scope && msg.disclaimer) {
                disclaimerHtml = `
                    <div class="mt-2.5 pt-2 border-t border-[#374151]/60 text-gray-400 text-[11px] italic flex items-start gap-1.5 leading-snug">
                        <svg class="w-3.5 h-3.5 text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <span>${escapeHtml(msg.disclaimer)}</span>
                    </div>
                `;
            }

            msgDiv.innerHTML = `
                <div class="bg-[#1f2937]/90 border border-[#374151] text-gray-200 rounded-2xl rounded-tl-none px-3.5 py-2.5 max-w-[88%] shadow-md leading-relaxed">
                    <div>${formatMessageText(msg.answer)}</div>
                    ${disclaimerHtml}
                </div>
            `;
        }

        messagesContainer.appendChild(msgDiv);
        scrollToBottom();
    }

    function showLoadingIndicator() {
        const loadingDiv = document.createElement('div');
        loadingDiv.id = 'ai-chat-loading';
        loadingDiv.className = 'flex justify-start';
        loadingDiv.innerHTML = `
            <div class="bg-[#1f2937]/80 border border-[#374151] text-gray-400 rounded-2xl rounded-tl-none px-4 py-3 flex items-center gap-1.5">
                <span class="w-2 h-2 bg-[#10b981] rounded-full animate-bounce"></span>
                <span class="w-2 h-2 bg-[#10b981] rounded-full animate-bounce [animation-delay:0.2s]"></span>
                <span class="w-2 h-2 bg-[#10b981] rounded-full animate-bounce [animation-delay:0.4s]"></span>
            </div>
        `;
        messagesContainer.appendChild(loadingDiv);
        scrollToBottom();
    }

    function hideLoadingIndicator() {
        const loader = document.getElementById('ai-chat-loading');
        if (loader) loader.remove();
    }

    // Handle Form Submit
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const userMsg = input.value.trim();
            if (!userMsg) return;

            // Render User Message
            const userObj = { sender: 'user', text: userMsg };
            appendMessageUI(userObj);

            // Save to history
            const history = getHistory();
            history.push(userObj);
            saveHistory(history);

            input.value = '';
            sendBtn.disabled = true;
            showLoadingIndicator();

            // Call API
            fetch('../backend/api/chatbot.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: userMsg })
            })
            .then(res => res.json())
            .then(data => {
                hideLoadingIndicator();
                sendBtn.disabled = false;

                const aiObj = {
                    sender: 'ai',
                    answer: data.answer || ((lang === 'en') ? 'Could not process request.' : 'Không thể xử lý yêu cầu.'),
                    in_scope: !!data.in_scope,
                    disclaimer: data.disclaimer || null
                };

                appendMessageUI(aiObj);
                history.push(aiObj);
                saveHistory(history);
            })
            .catch(err => {
                hideLoadingIndicator();
                sendBtn.disabled = false;

                const errorObj = {
                    sender: 'ai',
                    answer: (lang === 'en') 
                        ? 'Connection error. Please try again.' 
                        : 'Lỗi kết nối. Vui lòng thử lại.',
                    in_scope: false
                };

                appendMessageUI(errorObj);
                history.push(errorObj);
                saveHistory(history);
            });
        });
    }

    // Clear Chat
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            sessionStorage.removeItem(storageKey);
            renderHistory();
        });
    }

    // Initial render on load
    renderHistory();
});
</script>
