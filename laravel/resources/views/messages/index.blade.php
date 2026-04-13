@extends('layouts.portal')

@section('title', 'Inbox – FoundIt!')

@push('styles')
<style>
    .inbox-card {
        border: none; border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.07);
        overflow: hidden; min-height: 600px; display: flex;
    }

    /* Sidebar */
    .conv-sidebar { width: 300px; flex-shrink: 0; border-right: 1px solid #f1f5f9; background: #fff; overflow-y: auto; }
    .conv-sidebar-header { padding: 20px; border-bottom: 1px solid #f1f5f9; font-weight: 800; font-size: 1.1rem; display: flex; align-items: center; gap: 10px; }

    .conv-search { padding: 10px 16px; border-bottom: 1px solid #f1f5f9; }
    .conv-search input {
        width: 100%; border: 1.5px solid #e2e8f0; border-radius: 10px;
        padding: 8px 12px 8px 34px; font-size: .82rem; outline: none;
        background: #f8fafc url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0'/%3E%3C/svg%3E") 10px center no-repeat;
        transition: all 0.2s;
    }
    .conv-search input:focus { border-color: #0d6efd; background-color: #fff; }

    .conv-item { padding: 16px 20px; border-bottom: 1px solid #f8fafc; cursor: pointer; transition: all 0.2s; text-decoration: none; display: block; color: inherit; }
    .conv-item:hover { background: #f8fafc; color: inherit; }
    .conv-item.active { background: #eff6ff; border-left: 3px solid #0d6efd; }
    .conv-item .conv-name { font-weight: 700; font-size: .93rem; }
    .conv-item .conv-subject { font-size: .78rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .conv-item .conv-time { font-size: .72rem; color: #94a3b8; }

    .conv-avatar { width: 42px; height: 42px; border-radius: 50%; background: linear-gradient(135deg, #0d6efd, #0a4ab2); color: white; font-weight: 700; font-size: 1rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }

    .conv-sidebar .sidebar-section-label {
        padding: 8px 16px; font-size: .68rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: .08em;
        color: #94a3b8; background: #f8fafc;
        border-bottom: 1px solid #e2e8f0; border-top: 1px solid #e2e8f0;
    }

    .conv-item.pinned-admin { border-left: 3px solid #0d6efd; }
    .conv-item.pinned-admin:hover { background: #eff6ff; }
    .conv-item.pinned-organizer { border-left: 3px solid #0d9488; }
    .conv-item.pinned-organizer:hover { background: #f0fdfa; }
    .conv-item.pinned-admin.active { background: #eff6ff; }
    .conv-item.pinned-organizer.active { background: #f0fdfa; border-left-color: #0d9488; }

    /* Chat panel */
    .chat-panel { flex: 1; display: flex; flex-direction: column; background: #fff; }
    .chat-panel-header { padding: 18px 24px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 14px; }
    .chat-panel-empty { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #94a3b8; gap: 16px; padding: 40px; }

    .chat-messages { flex: 1; overflow-y: auto; padding: 20px 24px; display: flex; flex-direction: column; gap: 10px; max-height: 440px; }
    .chat-bubble { max-width: 70%; padding: 10px 14px; border-radius: 18px; font-size: .88rem; line-height: 1.5; opacity: 0; animation: bubbleIn 0.3s ease forwards; }
    @keyframes bubbleIn {
        from { opacity: 0; transform: translateY(8px) scale(0.97); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .chat-bubble.sent { background: #0d6efd; color: white; align-self: flex-end; border-bottom-right-radius: 4px; }
    .chat-bubble.received { background: #f1f5f9; color: #1e293b; align-self: flex-start; border-bottom-left-radius: 4px; }
    .bubble-name { font-size: .7rem; font-weight: 700; opacity: .7; margin-bottom: 3px; }
    .bubble-time { font-size: .68rem; opacity: .6; margin-top: 4px; text-align: right; }
    .chat-bubble.sent .bubble-time { color: rgba(255,255,255,0.7); }

    .chat-input-area { padding: 16px 24px; border-top: 1px solid #f1f5f9; display: flex; gap: 10px; align-items: flex-end; }
    .chat-input-area textarea { flex: 1; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 12px 16px; font-size: .9rem; resize: none; font-family: inherit; max-height: 100px; transition: border-color 0.2s; }
    .chat-input-area textarea:focus { outline: none; border-color: #0d6efd; box-shadow: 0 0 0 3px rgba(13,110,253,0.1); }
    .send-btn { width: 46px; height: 46px; border-radius: 50%; background: linear-gradient(135deg, #0d6efd, #0a4ab2); border: none; color: white; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; cursor: pointer; transition: all 0.2s; box-shadow: 0 3px 12px rgba(13,110,253,0.25); }
    .send-btn:hover { transform: scale(1.08); box-shadow: 0 6px 20px rgba(13,110,253,0.35); }

    @media (max-width: 640px) {
        .conv-sidebar { width: 100%; border-right: none; }
        .inbox-card { flex-direction: column; }
        .chat-panel { display: {{ $activeConv ? 'flex' : 'none' }}; }
    }
</style>
@endpush

@section('content')
<div class="container py-4" style="max-width: 1000px;">
    <div class="d-flex align-items-center gap-3 mb-4">
        <h4 class="fw-800 mb-0"><i class="bi bi-inbox-fill me-2 text-primary"></i>Inbox</h4>
        @if($totalUnread > 0)
        <span class="badge bg-primary rounded-pill">{{ $totalUnread }} unread</span>
        @endif
    </div>

    <div class="inbox-card">

        <!-- Conversation Sidebar -->
        <div class="conv-sidebar">
            <div class="conv-sidebar-header"><i class="bi bi-chat-dots text-primary"></i> Messages</div>
            <div class="conv-search">
                <input type="text" id="convSearch" placeholder="Search conversations..." autocomplete="off">
            </div>

            <!-- Pinned Contacts -->
            @if($pinnedContacts->isNotEmpty())
            <div class="sidebar-section-label"><i class="bi bi-pin-angle me-1"></i> Support</div>
            @foreach($pinnedContacts as $pin)
                @php
                    $isAdmin = (int)$pin->type_id === 4;
                    $isPresident = (!$isAdmin && ($pin->organizer_role ?? '') === 'president');
                    if ($isAdmin) {
                        $pinClass = 'pinned-admin'; $pinColor = '#0d6efd'; $pinLabel = 'Admin'; $pinIcon = 'bi-shield-fill';
                        $pinBg = 'linear-gradient(135deg,#0d6efd,#0a4ab2)';
                    } elseif ($isPresident) {
                        $pinClass = 'pinned-organizer'; $pinColor = '#7c3aed'; $pinLabel = 'SSG President'; $pinIcon = 'bi-star-fill';
                        $pinBg = 'linear-gradient(135deg,#7c3aed,#6d28d9)';
                    } else {
                        $pinClass = 'pinned-organizer'; $pinColor = '#0d9488'; $pinLabel = 'SSG Organizer'; $pinIcon = 'bi-people-fill';
                        $pinBg = 'linear-gradient(135deg,#0d9488,#0f766e)';
                    }
                @endphp
                <a href="{{ route('messages.index', ['with' => $pin->id]) }}"
                   class="conv-item {{ $pinClass }} {{ $activeConv == $pin->id ? 'active' : '' }}">
                    <div class="d-flex align-items-center gap-3">
                        <div class="conv-avatar" style="background:{{ $pinBg }}">{{ strtoupper(substr($pin->fname, 0, 1)) }}</div>
                        <div style="min-width:0; flex:1">
                            <div class="conv-name">{{ $pin->fname }} {{ $pin->lname }}</div>
                            <div class="conv-subject" style="color:{{ $pinColor }}; font-weight:600;">
                                <i class="bi {{ $pinIcon }} me-1"></i>{{ $pinLabel }}
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
            @endif

            <!-- Recent Conversations -->
            @php
                $filteredConvs = array_filter($conversations, fn($c) => !in_array($c->other_user_id, $pinnedIds));
            @endphp

            @if(!empty($filteredConvs))
            <div class="sidebar-section-label"><i class="bi bi-chat-left-dots me-1"></i> Recent</div>
            @foreach($filteredConvs as $conv)
            <a href="{{ route('messages.index', ['with' => $conv->other_user_id]) }}"
               class="conv-item {{ $activeConv == $conv->other_user_id ? 'active' : '' }}">
                <div class="d-flex align-items-center gap-3">
                    <div class="conv-avatar">{{ strtoupper(substr($conv->fname, 0, 1)) }}</div>
                    <div style="min-width:0; flex:1">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="conv-name">{{ $conv->fname }} {{ $conv->lname }}</div>
                            @if($conv->unread_count > 0)
                            <span class="badge bg-primary rounded-pill" style="font-size:.65rem">{{ $conv->unread_count }}</span>
                            @endif
                        </div>
                        <div class="conv-subject">{{ $conv->subject ?? '' }}</div>
                        <div class="conv-time">{{ \Carbon\Carbon::parse($conv->last_message_time)->format('M d, h:i A') }}</div>
                    </div>
                </div>
            </a>
            @endforeach
            @elseif($pinnedContacts->isEmpty())
            <div class="text-center text-muted py-5 px-3" style="font-size:.88rem">
                <i class="bi bi-chat-dots display-5 d-block mb-2 opacity-30"></i>No conversations yet.
            </div>
            @endif
        </div>

        <!-- Chat Panel -->
        <div class="chat-panel">
            @if(!$activeConv || !$otherUser)
            <div class="chat-panel-empty">
                <div style="width:80px;height:80px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center">
                    <i class="bi bi-chat-square-dots" style="font-size:2.2rem; color:#cbd5e1"></i>
                </div>
                <div style="font-size:1rem; font-weight:700; color:#64748b">No conversation selected</div>
                <div style="font-size:.85rem; color:#94a3b8">Pick a conversation from the left to start chatting</div>
            </div>
            @else
            <!-- Chat Header -->
            @php
                $headerBg = 'linear-gradient(135deg,#0d6efd,#0a4ab2)';
                $roleLabel = '';
                if ((int)$otherUser->type_id === 4) { $roleLabel = 'Admin'; }
                elseif ((int)$otherUser->type_id === 2) { $headerBg = 'linear-gradient(135deg,#0d9488,#0f766e)'; $roleLabel = 'Guard'; }
                elseif ((int)$otherUser->type_id === 6) { $headerBg = 'linear-gradient(135deg,#0d9488,#0f766e)'; $roleLabel = 'Organizer'; }
            @endphp
            <div class="chat-panel-header">
                <div class="conv-avatar" style="background:{{ $headerBg }}">{{ strtoupper(substr($otherUser->fname, 0, 1)) }}</div>
                <div>
                    <div class="fw-bold">
                        {{ $otherUser->fname }} {{ $otherUser->lname }}
                        @if($roleLabel)
                        <span class="badge ms-1" style="background:{{ (int)$otherUser->type_id===4?'#0d6efd':'#0d9488' }};font-size:.65rem">{{ $roleLabel }}</span>
                        @endif
                    </div>
                    <div class="text-muted small">{{ $convSubject }}</div>
                </div>
            </div>

            <!-- Messages -->
            <div class="chat-messages" id="chatMessages">
                @forelse($convMessages as $i => $msg)
                    @php $isSent = ((int)$msg->sender_id === (int)auth()->id()); @endphp
                    <div class="chat-bubble {{ $isSent ? 'sent' : 'received' }}" style="animation-delay:{{ $i * 0.04 }}s">
                        @if(!$isSent)
                        <div class="bubble-name">{{ $msg->sender->fname ?? '' }} {{ $msg->sender->lname ?? '' }}</div>
                        @endif
                        {!! nl2br(e($msg->body)) !!}
                        <div class="bubble-time">{{ $msg->created_at->format('M d, h:i A') }}</div>
                    </div>
                @empty
                    <div class="text-center text-muted py-5" style="margin:auto">
                        <i class="bi bi-chat-dots display-6 d-block mb-2 opacity-50"></i>No messages yet. Say hello!
                    </div>
                @endforelse
            </div>

            <!-- Reply -->
            @if(session('success'))
            <div class="px-4 pt-3">
                <div class="alert alert-success border-0 rounded-3 py-2 px-3 mb-0" style="font-size:.85rem">
                    <i class="bi bi-check-circle-fill me-1"></i> Sent!
                </div>
            </div>
            @endif

            <form action="{{ route('messages.store') }}" method="POST">
                @csrf
                <input type="hidden" name="receiver_id" value="{{ $activeConv }}">
                <input type="hidden" name="subject" value="{{ $convSubject }}">
                <div class="chat-input-area">
                    <textarea name="body" rows="2" placeholder="Type a reply... (Enter to send, Shift+Enter for new line)" required></textarea>
                    <button type="submit" class="send-btn"><i class="bi bi-send-fill"></i></button>
                </div>
            </form>
            @endif
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    const chat = document.getElementById('chatMessages');
    if (chat) chat.scrollTop = chat.scrollHeight;

    // Auto-resize textarea + Enter to send
    const textarea = document.querySelector('.chat-input-area textarea');
    if (textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        });
        textarea.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.closest('form').submit();
            }
        });
    }

    // Conversation search
    const convSearch = document.getElementById('convSearch');
    if (convSearch) {
        convSearch.addEventListener('input', function() {
            const term = this.value.toLowerCase().trim();
            document.querySelectorAll('.conv-item').forEach(item => {
                const name = item.querySelector('.conv-name');
                if (!name) return;
                item.style.display = name.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        });
    }
</script>
@endpush
