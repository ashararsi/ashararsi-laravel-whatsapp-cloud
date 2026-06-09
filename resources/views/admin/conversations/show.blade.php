@extends('whatsapp::layouts.admin')

@section('title', 'Conversation')

@section('content')
    <div class="mb-4">
        <a href="{{ route('whatsapp.admin.conversations.index') }}" class="text-decoration-none">&larr; Back to conversations</a>
        <h1 class="h3 mt-2">{{ $conversation->contact?->name ?? $conversation->contact?->phone }}</h1>
        <p class="text-muted mb-0">
            <code>{{ $conversation->contact?->phone }}</code>
            &middot; {{ $conversation->account?->name }}
            &middot; Last activity {{ $conversation->last_message_at?->diffForHumans() }}
        </p>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">Message Timeline</div>
        <div class="card-body">
            @forelse ($messages as $message)
                <div class="d-flex mb-3 {{ $message->isOutgoing() ? 'justify-content-end' : 'justify-content-start' }}">
                    <div class="p-3 rounded {{ $message->isIncoming() ? 'timeline-incoming' : 'timeline-outgoing' }}">
                        <div class="small text-muted mb-1">
                            {{ ucfirst($message->direction) }}
                            &middot; {{ ucfirst($message->type) }}
                            &middot; {{ $message->created_at?->format('M d, Y H:i') }}
                        </div>
                        <div class="mb-0">{{ $message->message ?? '—' }}</div>
                        @if ($message->whatsapp_message_id)
                            <div class="small text-muted mt-1"><code>{{ $message->whatsapp_message_id }}</code></div>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-muted mb-0 text-center py-4">No messages in this conversation yet.</p>
            @endforelse
        </div>
        @if ($messages->hasPages())
            <div class="card-footer">{{ $messages->links() }}</div>
        @endif
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-header">Reply</div>
        <div class="card-body">
            <form action="{{ route('whatsapp.admin.conversations.reply', $conversation) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <textarea name="message" class="form-control" rows="3" placeholder="Type your reply..." required>{{ old('message') }}</textarea>
                </div>
                @if (config('whatsapp.queue_enabled', true))
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="queue" value="1" id="queue_reply" {{ old('queue') ? 'checked' : '' }}>
                        <label class="form-check-label" for="queue_reply">Send via queue</label>
                    </div>
                @endif
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-send"></i> Send Reply
                </button>
            </form>
        </div>
    </div>
@endsection
