@php
$_chatbotTypeId = (int)(auth()->user()->type_id ?? 0);
$_showChatbot = in_array($_chatbotTypeId, [0, 1, 3]); // Visitors, Students, Staff
@endphp

@if ($_showChatbot)
<div id="chatbot-wrapper">
    <!-- Chat Toggle Button -->
    <button class="chatbot-toggle shadow-lg" id="chatbotToggle" title="Chat with FoundIt! Assistant">
        <i class="bi bi-chat-dots-fill" id="chatbotIcon"></i>
        <span class="badge-dot"></span>
    </button>

    <!-- Chat Panel -->
    <div class="chatbot-panel" id="chatbotPanel">
        <div class="chatbot-header">
            <div class="chatbot-avatar"><i class="bi bi-search" style="font-size:1rem"></i></div>
            <div class="chatbot-header-info">
                <h6 class="mb-0">FoundIt! Assistant</h6>
                <span class="small opacity-75">Online — Powered by AI ✨</span>
            </div>
            <button class="chatbot-close btn-close btn-close-white" id="chatbotClose" aria-label="Close"></button>
        </div>

        <div class="chatbot-messages p-3" id="chatbotMessages" style="height: 350px; overflow-y: auto;">
            <!-- Messages inserted here -->
        </div>

        <div class="chat-chips d-flex flex-wrap gap-2 p-3 bg-light" id="chatChips">
            <span class="chat-chip badge rounded-pill border py-2 px-3 text-dark bg-white" style="cursor:pointer" data-q="How do I claim a lost item?">🔍 How to claim</span>
            <span class="chat-chip badge rounded-pill border py-2 px-3 text-dark bg-white" style="cursor:pointer" data-q="Is there any phone reported?">📱 Find phone</span>
        </div>

        <div class="chatbot-input p-3 border-top bg-white d-flex gap-2">
            <textarea id="chatbotInput" class="form-control form-control-sm" placeholder="Ask me anything..." rows="1" style="resize:none;"></textarea>
            <button class="btn btn-primary btn-sm rounded-circle" id="chatbotSend">
                <i class="bi bi-send-fill"></i>
            </button>
        </div>
    </div>
</div>

<style>
    /* Styling similar to original chatbot.php but adapted for Bootstrap 5 */
    .chatbot-toggle {
        position: fixed; bottom: 30px; right: 30px; z-index: 9999;
        width: 60px; height: 60px; border-radius: 50%;
        background: linear-gradient(135deg, #0d6efd, #0a58ca);
        color: white; border: none; font-size: 1.5rem;
    }
    .chatbot-panel {
        position: fixed; bottom: 100px; right: 30px; z-index: 9999;
        width: 350px; border-radius: 15px; overflow: hidden;
        background: white; border: 1px solid #dee2e6;
        display: none; flex-direction: column;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .chatbot-panel.open { display: flex; }
    .chatbot-header { background: #0d6efd; color: white; padding: 15px; display: flex; align-items: center; justify-content: space-between; }
    .chat-msg { margin-bottom: 10px; display: flex; gap: 10px; }
    .chat-msg.user { flex-direction: row-reverse; }
    .chat-msg-bubble { padding: 8px 12px; border-radius: 15px; font-size: 0.9rem; max-width: 80%; }
    .chat-msg.bot .chat-msg-bubble { background: #f8f9fa; border: 1px solid #dee2e6; }
    .chat-msg.user .chat-msg-bubble { background: #0d6efd; color: white; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('chatbotToggle');
    const panel = document.getElementById('chatbotPanel');
    const input = document.getElementById('chatbotInput');
    const sendBtn = document.getElementById('chatbotSend');
    const messages = document.getElementById('chatbotMessages');
    
    let history = [];

    toggle.onclick = () => {
        panel.classList.toggle('open');
    };

    const addMessage = (text, role) => {
        const div = document.createElement('div');
        div.className = `chat-msg ${role}`;
        div.innerHTML = `<div class="chat-msg-bubble">${text}</div>`;
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    };

    sendBtn.onclick = async () => {
        const text = input.value.trim();
        if (!text) return;
        
        addMessage(text, 'user');
        input.value = '';
        
        try {
            const res = await fetch('{{ route("chatbot.ask") }}', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ message: text, history: history })
            });
            const data = await res.json();
            if (data.reply) {
                addMessage(data.reply, 'bot');
                history.push({ role: 'user', text: text });
                history.push({ role: 'model', text: data.reply });
            }
        } catch (e) {
            addMessage('Error connecting to AI', 'bot');
        }
    };
    
    document.querySelectorAll('.chat-chip').forEach(chip => {
        chip.onclick = () => {
            input.value = chip.dataset.q;
            sendBtn.click();
        };
    });
});
</script>
@endif
