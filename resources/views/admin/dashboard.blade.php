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
                    <div class="text-muted small">Messages Today</div>
                    <div class="h2 mb-0">{{ number_format($stats['messages_today'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">Conversations Today</div>
                    <div class="h2 mb-0">{{ number_format($stats['conversations_today'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">Templates Used Today</div>
                    <div class="h2 mb-0">{{ number_format($stats['templates_used_today'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">Delivery Rate</div>
                    <div class="h2 mb-0">{{ number_format($stats['delivery_rate'] ?? 100, 1) }}%</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">Estimated Cost Today</div>
                    <div class="h2 mb-0 text-primary">${{ number_format($stats['estimated_cost_today'] ?? 0, 4) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">Approved Templates</div>
                    <div class="h2 mb-0 text-success">{{ number_format($stats['templates_approved'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">Pending Templates</div>
                    <div class="h2 mb-0 text-warning">{{ number_format($stats['templates_pending'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">Rejected Templates</div>
                    <div class="h2 mb-0 text-danger">{{ number_format($stats['templates_rejected'] ?? 0) }}</div>
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
                <div class="card-header">7-Day Message Volume</div>
                <div class="card-body">
                    <canvas id="volumeChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header">Template Usage (7 days)</div>
                <div class="card-body">
                    <canvas id="templateChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header">Quick Links</div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('whatsapp.admin.conversations.index') }}" class="list-group-item list-group-item-action">Inbox — conversations</a>
                    <a href="{{ route('whatsapp.admin.contacts.index') }}" class="list-group-item list-group-item-action">CRM — contacts</a>
                    <a href="{{ route('whatsapp.admin.campaigns.index') }}" class="list-group-item list-group-item-action">Broadcast campaigns</a>
                    <a href="{{ route('whatsapp.admin.templates.index') }}" class="list-group-item list-group-item-action">Message templates</a>
                    <a href="{{ route('whatsapp.admin.system') }}" class="list-group-item list-group-item-action">System monitoring</a>
                    <a href="{{ route('whatsapp.admin.accounts.index') }}" class="list-group-item list-group-item-action">Manage accounts</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header">Cost Breakdown Today</div>
                <div class="card-body">
                    @php $cost = $stats['cost_breakdown'] ?? []; @endphp
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span>Utility ({{ $cost['utility_count'] ?? 0 }})</span>
                        <span>${{ number_format($cost['utility'] ?? 0, 4) }}</span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span>Marketing ({{ $cost['marketing_count'] ?? 0 }})</span>
                        <span>${{ number_format($cost['marketing'] ?? 0, 4) }}</span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span>Authentication ({{ $cost['authentication_count'] ?? 0 }})</span>
                        <span>${{ number_format($cost['authentication'] ?? 0, 4) }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span>Service ({{ $cost['service_count'] ?? 0 }})</span>
                        <span>${{ number_format($cost['service'] ?? 0, 4) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $chartData = $stats['chart_data'] ?? ['labels' => [], 'incoming' => [], 'outgoing' => [], 'templates' => []];
    @endphp
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const chartData = @json($chartData);

        new Chart(document.getElementById('volumeChart'), {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [
                    { label: 'Incoming', data: chartData.incoming, borderColor: '#198754', tension: 0.3 },
                    { label: 'Outgoing', data: chartData.outgoing, borderColor: '#0d6efd', tension: 0.3 },
                ],
            },
            options: { responsive: true, maintainAspectRatio: false },
        });

        new Chart(document.getElementById('templateChart'), {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [
                    { label: 'Templates', data: chartData.templates, backgroundColor: '#128c7e' },
                ],
            },
            options: { responsive: true, maintainAspectRatio: false },
        });
    </script>
@endsection
