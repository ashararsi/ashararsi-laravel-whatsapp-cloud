@extends('whatsapp::layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-1">WhatsApp Dashboard</h1>
        <p class="text-muted mb-0">Conversation platform overview</p>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">Total Contacts</div>
                    <div class="display-6">{{ number_format($stats['total_contacts']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">Total Conversations</div>
                    <div class="display-6">{{ number_format($stats['total_conversations']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">Incoming Today</div>
                    <div class="display-6 text-success">{{ number_format($stats['incoming_today']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">Outgoing Today</div>
                    <div class="display-6 text-primary">{{ number_format($stats['outgoing_today']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">Open Conversations</div>
                    <div class="h2 mb-0">{{ number_format($stats['open_conversations'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">Campaigns</div>
                    <div class="h2 mb-0">{{ number_format($stats['campaigns_total'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">Messages Sent</div>
                    <div class="h2 mb-0">{{ number_format($stats['messages_sent_total'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">Failed Messages</div>
                    <div class="h2 mb-0 text-danger">{{ number_format($stats['messages_failed_total'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header">Quick Links</div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('whatsapp.admin.conversations.index') }}" class="list-group-item list-group-item-action">Inbox — conversations</a>
                    <a href="{{ route('whatsapp.admin.contacts.index') }}" class="list-group-item list-group-item-action">CRM — contacts</a>
                    <a href="{{ route('whatsapp.admin.campaigns.index') }}" class="list-group-item list-group-item-action">Broadcast campaigns</a>
                    <a href="{{ route('whatsapp.admin.accounts.index') }}" class="list-group-item list-group-item-action">Manage accounts</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header">7-Day Message Volume</div>
                <div class="card-body">
                    @forelse ($stats['daily_messages'] ?? [] as $day)
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span>{{ $day['date'] }}</span>
                            <span>
                                <span class="text-success">↓ {{ $day['incoming'] }}</span>
                                <span class="text-muted mx-1">|</span>
                                <span class="text-primary">↑ {{ $day['outgoing'] }}</span>
                            </span>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No message data yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
