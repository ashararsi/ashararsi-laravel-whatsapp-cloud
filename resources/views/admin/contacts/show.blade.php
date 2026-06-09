@extends('whatsapp::layouts.admin')

@section('title', $contact->name ?? $contact->phone)

@section('content')
    <div class="mb-4">
        <a href="{{ route('whatsapp.admin.contacts.index') }}" class="text-decoration-none">&larr; Back to contacts</a>
        <h1 class="h3 mt-2">{{ $contact->name ?? 'Unknown Contact' }}</h1>
        <p class="text-muted mb-0">
            <code>{{ $contact->phone }}</code> &middot; {{ $contact->account?->name }}
        </p>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header">Contact Details</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Phone</span><span>{{ $contact->phone }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Account</span><span>{{ $contact->account?->name }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Created</span><span>{{ $contact->created_at?->format('Y-m-d H:i') }}</span>
                    </li>
                </ul>
            </div>
            @if ($contact->conversation)
                <a href="{{ route('whatsapp.admin.conversations.show', $contact->conversation) }}"
                   class="btn btn-success w-100 mt-3">Open Conversation</a>
            @endif
        </div>
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">Recent Messages</div>
                <div class="card-body">
                    @forelse ($contact->conversation?->messages ?? [] as $message)
                        <div class="p-3 mb-2 rounded {{ $message->isIncoming() ? 'timeline-incoming' : 'timeline-outgoing' }}">
                            <div class="small text-muted mb-1">
                                {{ ucfirst($message->direction) }} &middot; {{ $message->created_at?->format('M d, H:i') }}
                            </div>
                            <div>{{ $message->message }}</div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No messages yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
