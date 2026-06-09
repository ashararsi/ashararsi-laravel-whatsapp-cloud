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

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header">Quick Links</div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('whatsapp.admin.conversations.index') }}" class="list-group-item list-group-item-action">
                        View all conversations
                    </a>
                    <a href="{{ route('whatsapp.admin.contacts.index') }}" class="list-group-item list-group-item-action">
                        Browse contacts
                    </a>
                    <a href="{{ route('whatsapp.admin.accounts.index') }}" class="list-group-item list-group-item-action">
                        Manage accounts
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
