<?php
// Show chatbot for: students (1), staff (3), unauthenticated visitors, and always on landing page
$_chatbotTypeId = (int)($_SESSION['type_id'] ?? 0);
$_chatbotPage = basename($_SERVER['PHP_SELF'] ?? '');
$_isPublicPage = in_array($_chatbotPage, ['landing.php', 'index.php', 'report.php', 'report_lost.php', 'claim_item.php', 'contact_owner.php', 'inbox.php', 'profile.php', 'view_finder.php']);
$_showChatbot = $_isPublicPage || !isset($_SESSION['type_id']) || in_array($_chatbotTypeId, [1, 3]);
if ($_showChatbot):
?>
<!-- ════════════════════════════════════════════════════════════════════════════
     AI CHATBOT WIDGET — Powered by Gemini
     ════════════════════════════════════════════════════════════════════════════ -->
<style>
/* ===== CHATBOT TOGGLE ===== */
.chatbot-toggle {
    position: fixed; bottom: 28px; right: 90px; z-index: 99999;
    width: 58px; height: 58px; border-radius: 50%; border: none; cursor: pointer;
    background: linear-gradient(135deg, #003366 0%, #0d6efd 50%, #0a4ab2 100%);
    color: white; font-size: 1.5rem;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 6px 24px rgba(13,110,253,0.4), 0 0 0 0 rgba(13,110,253,0.4);
    transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
    animation: chatPulse 3s ease-in-out infinite;
}
.chatbot-toggle:hover {
    transform: scale(1.1) translateY(-2px);
    box-shadow: 0 10px 32px rgba(13,110,253,0.4);
}
.chatbot-toggle.active { animation: none; }
@keyframes chatPulse {
    0%, 100% { box-shadow: 0 6px 24px rgba(13,110,253,0.4), 0 0 0 0 rgba(13,110,253,0.35); }
    50% { box-shadow: 0 6px 24px rgba(13,110,253,0.4), 0 0 0 12px rgba(13,110,253,0); }
}
.chatbot-toggle .badge-dot {
    position: absolute; top: 6px; right: 6px;
    width: 12px; height: 12px; border-radius: 50%;
    background: #0d6efd; border: 2px solid white;
}

/* ===== CHAT PANEL ===== */
.chatbot-panel {
    position: fixed; bottom: 100px; right: 30px; z-index: 99998;
    width: 380px; max-height: 520px;
    border-radius: 22px; overflow: hidden;
    background: rgba(255,255,255,0.97);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    box-shadow: 0 24px 80px rgba(0,0,0,0.18), 0 0 0 1px rgba(0,0,0,0.04);
    display: flex; flex-direction: column;
    transform: scale(0.4) translateY(40px);
    opacity: 0; pointer-events: none;
    transform-origin: bottom right;
    transition: all 0.35s cubic-bezier(0.34,1.56,0.64,1);
}
.chatbot-panel.open {
    transform: scale(1) translateY(0);
    opacity: 1; pointer-events: auto;
}

/* Header */
.chatbot-header {
    background: linear-gradient(135deg, #003366 0%, #0d6efd 50%, #0a4ab2 100%);
    color: white; padding: 18px 20px;
    display: flex; align-items: center; gap: 12px;
    position: relative; overflow: hidden;
}
.chatbot-header::after {
    content: ''; position: absolute; top: -30px; right: -30px;
    width: 100px; height: 100px; border-radius: 50%;
    background: rgba(13,110,253,0.18);
}
.chatbot-avatar {
    width: 42px; height: 42px; border-radius: 50%;
    background: rgba(255,255,255,0.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; flex-shrink: 0;
    border: 2px solid rgba(255,255,255,0.25);
}
.chatbot-header-info { flex: 1; }
.chatbot-header-info h6 { margin: 0; font-weight: 700; font-size: 0.95rem; }
.chatbot-header-info span {
    font-size: 0.72rem; opacity: 0.8;
    display: flex; align-items: center; gap: 4px;
}
.chatbot-header-info span::before {
    content: ''; width: 6px; height: 6px; border-radius: 50%;
    background: #0d6efd; display: inline-block;
}
.chatbot-close {
    background: rgba(255,255,255,0.15); border: none;
    width: 32px; height: 32px; border-radius: 50%;
    color: white; font-size: 1rem; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.2s; z-index: 1;
}
.chatbot-close:hover { background: rgba(255,255,255,0.25); }

/* Messages area */
.chatbot-messages {
    flex: 1; overflow-y: auto; padding: 16px;
    display: flex; flex-direction: column; gap: 10px;
    min-height: 280px; max-height: 340px;
    background: #f8fafc;
    scroll-behavior: smooth;
}
.chatbot-messages::-webkit-scrollbar { width: 4px; }
.chatbot-messages::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

/* Message bubbles */
.chat-msg {
    display: flex; gap: 8px; max-width: 88%;
    animation: msgFadeIn 0.3s ease;
}
.chat-msg.user { flex-direction: row-reverse; align-self: flex-end; }
.chat-msg-avatar {
    width: 28px; height: 28px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.7rem; flex-shrink: 0; font-weight: 700;
}
.chat-msg.bot .chat-msg-avatar {
    background: linear-gradient(135deg, #0d6efd, #0a4ab2); color: white;
}
.chat-msg.user .chat-msg-avatar {
    background: linear-gradient(135deg, #ffcc00, #e8b800); color: #003366;
}
.chat-msg-bubble {
    padding: 10px 14px; border-radius: 16px;
    font-size: 0.85rem; line-height: 1.55;
    word-wrap: break-word;
}
.chat-msg.bot .chat-msg-bubble {
    background: white; color: #1e293b;
    border: 1px solid #e2e8f0;
    border-top-left-radius: 4px;
}
.chat-msg.user .chat-msg-bubble {
    background: linear-gradient(135deg, #0d6efd, #0a4ab2); color: white;
    border-top-right-radius: 4px;
}
.chat-msg-bubble p { margin: 0 0 6px; }
.chat-msg-bubble p:last-child { margin: 0; }
.chat-msg-bubble strong { font-weight: 700; }
.chat-msg-bubble ul, .chat-msg-bubble ol { margin: 4px 0; padding-left: 18px; }
.chat-msg-bubble li { margin: 2px 0; }
.chat-msg-bubble code {
    background: rgba(0,0,0,0.06); padding: 2px 5px;
    border-radius: 4px; font-size: 0.82rem;
}
@keyframes msgFadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Typing indicator */
.typing-indicator {
    display: flex; align-items: center; gap: 4px; padding: 12px 16px;
}
.typing-dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: #94a3b8; animation: typingBounce 1.4s ease-in-out infinite;
}
.typing-dot:nth-child(2) { animation-delay: 0.2s; }
.typing-dot:nth-child(3) { animation-delay: 0.4s; }
@keyframes typingBounce {
    0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
    30% { transform: translateY(-6px); opacity: 1; }
}

/* Input area */
.chatbot-input {
    padding: 12px 16px;
    border-top: 1px solid #e2e8f0;
    display: flex; gap: 8px; align-items: flex-end;
    background: white;
}
.chatbot-input textarea {
    flex: 1; border: 1.5px solid #e2e8f0; border-radius: 14px;
    padding: 10px 14px; font-size: 0.85rem; resize: none;
    font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
    outline: none; transition: border-color 0.2s;
    min-height: 40px; max-height: 80px;
    line-height: 1.4;
}
.chatbot-input textarea:focus { border-color: #0d6efd; }
.chatbot-input textarea::placeholder { color: #94a3b8; }
.chatbot-send {
    width: 40px; height: 40px; border-radius: 50%;
    background: linear-gradient(135deg, #0d6efd, #0a4ab2);
    border: none; color: white; font-size: 0.95rem;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all 0.2s; flex-shrink: 0;
}
.chatbot-send:hover { transform: scale(1.08); box-shadow: 0 4px 12px rgba(13,110,253,0.35); }
.chatbot-send:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

/* Quick action chips */
.chat-chips {
    display: flex; flex-wrap: wrap; gap: 6px;
    padding: 0 16px 12px; background: #f8fafc;
}
.chat-chip {
    padding: 5px 12px; border-radius: 100px;
    font-size: 0.72rem; font-weight: 600;
    border: 1.5px solid #e2e8f0; background: white;
    color: #475569; cursor: pointer; transition: all 0.2s;
    white-space: nowrap;
}
.chat-chip:hover { border-color: #0d6efd; color: #0a4ab2; background: #eff6ff; }

/* Mobile responsive */
@media (max-width: 480px) {
    .chatbot-panel { width: calc(100vw - 24px); right: 12px; bottom: 90px; max-height: 70vh; }
    .chatbot-toggle { bottom: 20px; right: 20px; }
}
</style>

<!-- Chat Toggle Button -->
<button class="chatbot-toggle" id="chatbotToggle" title="Chat with FoundIt! Assistant">
    <i class="bi bi-chat-dots-fill" id="chatbotIcon"></i>
    <span class="badge-dot"></span>
</button>

<!-- Chat Panel -->
<div class="chatbot-panel" id="chatbotPanel">
    <div class="chatbot-header">
        <div class="chatbot-avatar"><i class="bi bi-search" style="font-size:1rem"></i></div>
        <div class="chatbot-header-info">
            <h6>FoundIt! Assistant</h6>
            <span>Online — Powered by AI ✨</span>
        </div>
        <button class="chatbot-close" id="chatbotClose" title="Close chat">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="chatbot-messages" id="chatbotMessages">
        <!-- Messages will be inserted here -->
    </div>

    <div class="chat-chips" id="chatChips">
        <span class="chat-chip" data-q="How do I claim a lost item?">🔍 How to claim</span>
        <span class="chat-chip" data-q="How many items are reported right now?">📊 Stats</span>
        <span class="chat-chip" data-q="Is there any phone reported?">📱 Find phone</span>
        <span class="chat-chip" data-q="What items are available to claim right now?">✅ Available items</span>
        <span class="chat-chip" data-q="How do I report a found item?">📝 Report found</span>
        <span class="chat-chip" data-q="Who developed this website?">👨‍💻 Developer</span>
    </div>

    <div class="chatbot-input">
        <textarea id="chatbotInput" placeholder="Ask me anything about Lost & Found..." rows="1"></textarea>
        <button class="chatbot-send" id="chatbotSend" title="Send message">
            <i class="bi bi-send-fill"></i>
        </button>
    </div>
</div>

<script>
(function() {
    const toggle   = document.getElementById('chatbotToggle');
    const panel    = document.getElementById('chatbotPanel');
    const closeBtn = document.getElementById('chatbotClose');
    const input    = document.getElementById('chatbotInput');
    const sendBtn  = document.getElementById('chatbotSend');
    const messages = document.getElementById('chatbotMessages');
    const chips    = document.getElementById('chatChips');
    const icon     = document.getElementById('chatbotIcon');

    // ── LocalStorage keys ──
    const LS_HISTORY  = 'chatbot_history';      // AI conversation context
    const LS_MESSAGES = 'chatbot_messages_html'; // Rendered message HTML
    const LS_OPEN     = 'chatbot_is_open';       // Panel open state
    const LS_CHIPS    = 'chatbot_chips_hidden';   // Chips visibility

    let conversationHistory = JSON.parse(localStorage.getItem(LS_HISTORY) || '[]');
    let isOpen = false;
    let isSending = false;

    // ── Restore previous messages on page load ──
    const savedHtml = localStorage.getItem(LS_MESSAGES);
    const hadConversation = savedHtml && savedHtml.trim().length > 0;

    if (hadConversation) {
        messages.innerHTML = savedHtml;
        scrollToBottom();
    }

    // Restore chips visibility
    if (localStorage.getItem(LS_CHIPS) === 'hidden') {
        chips.style.display = 'none';
    }

    // Restore open state
    if (localStorage.getItem(LS_OPEN) === 'true') {
        isOpen = true;
        panel.classList.add('open');
        toggle.classList.add('active');
        icon.className = 'bi bi-x-lg';
        scrollToBottom();
    }

    // Show welcome message only if no previous conversation
    if (!hadConversation) {
        // Will show welcome when first opened
    }

    // ── Save state helpers ──
    function saveMessages() {
        localStorage.setItem(LS_MESSAGES, messages.innerHTML);
    }
    function saveHistory() {
        // Keep last 20 messages
        if (conversationHistory.length > 20) {
            conversationHistory = conversationHistory.slice(-20);
        }
        localStorage.setItem(LS_HISTORY, JSON.stringify(conversationHistory));
    }
    function saveOpenState() {
        localStorage.setItem(LS_OPEN, isOpen ? 'true' : 'false');
    }

    // Toggle chat
    toggle.addEventListener('click', () => {
        isOpen = !isOpen;
        panel.classList.toggle('open', isOpen);
        toggle.classList.toggle('active', isOpen);
        icon.className = isOpen ? 'bi bi-x-lg' : 'bi bi-chat-dots-fill';
        saveOpenState();

        // Show welcome only on first ever open (no saved messages)
        if (isOpen && !hadConversation && messages.children.length === 0) {
            addBotMessage("Hi there! 👋 I'm the **FoundIt! Assistant**, your Campus Lost & Found helper. I can help you with:\n\n• **Finding or reporting** lost items\n• **Claiming** found items\n• **Understanding** how the system works\n\nHow can I help you today? ✨");
        }
        if (isOpen) {
            scrollToBottom();
            input.focus();
        }
    });

    closeBtn.addEventListener('click', () => {
        isOpen = false;
        panel.classList.remove('open');
        toggle.classList.remove('active');
        icon.className = 'bi bi-chat-dots-fill';
        saveOpenState();
    });

    // Quick action chips
    chips.addEventListener('click', (e) => {
        const chip = e.target.closest('.chat-chip');
        if (chip && !isSending) {
            sendMessage(chip.dataset.q);
        }
    });

    // Send button
    sendBtn.addEventListener('click', () => {
        if (!isSending) sendMessage(input.value);
    });

    // Enter to send (Shift+Enter for newline)
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            if (!isSending) sendMessage(input.value);
        }
    });

    // Auto-resize textarea
    input.addEventListener('input', () => {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 80) + 'px';
    });

    async function sendMessage(text) {
        text = text.trim();
        if (!text || isSending) return;

        isSending = true;
        sendBtn.disabled = true;
        input.value = '';
        input.style.height = 'auto';

        // Hide chips after first message
        if (chips.style.display !== 'none') {
            chips.style.display = 'none';
            localStorage.setItem(LS_CHIPS, 'hidden');
        }

        // Add user message
        addUserMessage(text);

        // Add typing indicator
        const typingEl = addTypingIndicator();

        // Add to history
        conversationHistory.push({ role: 'user', text: text });

        try {
            const response = await fetch('gemini_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    message: text,
                    history: conversationHistory.slice(0, -1)
                })
            });

            let data;
            try {
                data = await response.json();
            } catch(parseErr) {
                data = { error: 'Server returned invalid response (HTTP ' + response.status + ')' };
            }

            // Remove typing indicator
            typingEl.remove();

            if (data.success && data.reply) {
                addBotMessage(data.reply);
                conversationHistory.push({ role: 'model', text: data.reply });
            } else {
                const errMsg = data.error || "Sorry, I couldn't process that right now.";
                addBotMessage("⚠️ " + errMsg);
            }
        } catch (err) {
            typingEl.remove();
            addBotMessage("Oops! Something went wrong connecting to the AI. Please check your internet connection and try again. 🔄");
        }

        isSending = false;
        sendBtn.disabled = false;
        input.focus();

        // Save everything to localStorage
        saveHistory();
        saveMessages();
    }

    function addUserMessage(text) {
        const initial = document.querySelector('meta[name="user-initial"]')?.content || '👤';
        const div = document.createElement('div');
        div.className = 'chat-msg user';
        div.innerHTML = `
            <div class="chat-msg-avatar">${initial}</div>
            <div class="chat-msg-bubble">${escapeHtml(text)}</div>
        `;
        messages.appendChild(div);
        scrollToBottom();
        saveMessages();
    }

    function addBotMessage(text) {
        const div = document.createElement('div');
        div.className = 'chat-msg bot';
        div.innerHTML = `
            <div class="chat-msg-avatar"><i class="bi bi-search" style="font-size:0.6rem"></i></div>
            <div class="chat-msg-bubble">${formatMarkdown(text)}</div>
        `;
        messages.appendChild(div);
        scrollToBottom();
        saveMessages();
    }

    function addTypingIndicator() {
        const div = document.createElement('div');
        div.className = 'chat-msg bot';
        div.id = 'typing-indicator';
        div.innerHTML = `
            <div class="chat-msg-avatar"><i class="bi bi-search" style="font-size:0.6rem"></i></div>
            <div class="chat-msg-bubble">
                <div class="typing-indicator">
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                </div>
            </div>
        `;
        messages.appendChild(div);
        scrollToBottom();
        return div;
    }

    function scrollToBottom() {
        requestAnimationFrame(() => {
            messages.scrollTop = messages.scrollHeight;
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatMarkdown(text) {
        return text
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.+?)\*/g, '<em>$1</em>')
            .replace(/`(.+?)`/g, '<code>$1</code>')
            .replace(/^• (.+)$/gm, '<li>$1</li>')
            .replace(/^- (.+)$/gm, '<li>$1</li>')
            .replace(/^(\d+)\. (.+)$/gm, '<li>$2</li>')
            .replace(/(<li>.*<\/li>\n?)+/gs, '<ul>$&</ul>')
            .replace(/\n\n/g, '</p><p>')
            .replace(/\n/g, '<br>')
            .replace(/^/, '<p>').replace(/$/, '</p>')
            .replace(/<p><\/p>/g, '')
            .replace(/<p>(<ul>)/g, '$1')
            .replace(/(<\/ul>)<\/p>/g, '$1');
    }

    // ── Expose clear function for logout ──
    window.clearChatbotData = function() {
        localStorage.removeItem(LS_HISTORY);
        localStorage.removeItem(LS_MESSAGES);
        localStorage.removeItem(LS_OPEN);
        localStorage.removeItem(LS_CHIPS);
    };
})();
</script>
<?php endif; // end chatbot ?>
