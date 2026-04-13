@extends('layouts.portal')

@section('title', 'Chat with ' . $user->fname . ' – FoundIt!')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 20px;">
                <div class="card-header bg-white border-bottom p-3 d-flex align-items-center gap-3">
                    <a href="{{ route('messages.index') }}" class="btn btn-light rounded-circle d-lg-none">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 48px; height: 48px; flex-shrink: 0;">
                        {{ strtoupper(substr($user->fname, 0, 1)) }}
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold">{{ $user->fname }} {{ $user->lname }}</h6>
                        <span class="small text-muted"><i class="bi bi-circle-fill text-success" style="font-size: .6rem;"></i> Online</span>
                    </div>
                </div>

                <div class="card-body bg-light p-4 overflow-auto" id="chatContainer" style="height: 500px; display: flex; flex-direction: column; gap: 12px;">
                    @forelse($messages as $msg)
                        @php $isMe = $msg->sender_id === auth()->id(); @endphp
                        <div class="d-flex {{ $isMe ? 'justify-content-end' : 'justify-content-start' }}">
                            <div class="p-3 shadow-sm {{ $isMe ? 'bg-primary text-white' : 'bg-white' }}" style="max-width: 75%; border-radius: {{ $isMe ? '18px 18px 0 18px' : '18px 18px 18px 0' }};">
                                <p class="mb-1">{{ $msg->body }}</p>
                                <div class="text-end small opacity-75" style="font-size: .65rem;">
                                    {{ $msg->created_at->format('h:i A') }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 opacity-25">
                            <i class="bi bi-chat-dots fs-1"></i>
                            <p>No messages yet. Say hello!</p>
                        </div>
                    @endforelse
                </div>

                <div class="card-footer bg-white border-top p-3">
                    <form action="{{ route('messages.store') }}" method="POST" class="d-flex gap-2">
                        @csrf
                        <input type="hidden" name="receiver_id" value="{{ $user->id }}">
                        <textarea name="body" class="form-control border-light bg-light" placeholder="Type your message..." rows="1" required style="resize: none; border-radius: 15px;"></textarea>
                        <button type="submit" class="btn btn-primary rounded-circle" style="width: 48px; height: 48px; flex-shrink: 0;">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const chat = document.getElementById('chatContainer');
    chat.scrollTop = chat.scrollHeight;

    document.querySelector('textarea').addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
        if(chat.scrollTop + chat.clientHeight >= chat.scrollHeight - 50) {
            chat.scrollTop = chat.scrollHeight;
        }
    });
</script>
@endpush
@endsection
