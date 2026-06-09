@extends('whatsapp::layouts.admin')

@section('title', 'System Monitoring')

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-1">System Monitoring</h1>
        <p class="text-muted mb-0">Queue, webhook, API, and rate-limit health</p>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Queue Health</div>
                    <div class="h4 mb-1 {{ ($health['queue']['healthy'] ?? false) ? 'text-success' : 'text-warning' }}">
                        {{ ($health['queue']['healthy'] ?? false) ? 'Healthy' : 'Degraded' }}
                    </div>
                    <div class="small text-muted">
                        Size: {{ $health['queue']['queue_size'] ?? 'N/A' }} &middot;
                        Pending: {{ $health['queue']['pending_messages'] ?? 0 }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Webhook Health</div>
                    <div class="h4 mb-1 text-success">Active</div>
                    <div class="small text-muted">
                        Prefix: /{{ $health['webhook']['prefix'] ?? 'whatsapp' }}/webhook
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">API Health (24h)</div>
                    <div class="h4 mb-1 {{ ($health['api']['healthy'] ?? false) ? 'text-success' : 'text-danger' }}">
                        {{ ($health['api']['healthy'] ?? false) ? 'Healthy' : 'Issues' }}
                    </div>
                    <div class="small text-muted">
                        Sent: {{ $health['api']['sent_last_24h'] ?? 0 }} &middot;
                        Failed: {{ $health['api']['failed_last_24h'] ?? 0 }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Failed Jobs</div>
                    <div class="h4 mb-1 {{ ($health['failed_jobs']['healthy'] ?? false) ? 'text-success' : 'text-danger' }}">
                        {{ $health['failed_jobs']['failed_messages'] ?? 0 }}
                    </div>
                    <div class="small text-muted">
                        Dead lettered: {{ $health['failed_jobs']['dead_lettered_messages'] ?? 0 }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header">Queue Details</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Enabled</span>
                        <span>{{ ($health['queue']['enabled'] ?? false) ? 'Yes' : 'No' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Connection</span>
                        <span><code>{{ $health['queue']['connection'] ?? 'sync' }}</code></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Queue Name</span>
                        <span><code>{{ $health['queue']['queue'] ?? 'default' }}</code></span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header">API Configuration</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>API Version</span>
                        <span><code>{{ $health['api']['api_version'] ?? 'v21.0' }}</code></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Base URL</span>
                        <span><code>{{ $health['api']['base_url'] ?? '' }}</code></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Dead Lettered (7d)</span>
                        <span>{{ $health['api']['dead_lettered_last_7d'] ?? 0 }}</span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">Rate Limit Usage (Latest)</div>
                <div class="card-body">
                    @if (! empty($health['rate_limits']['recorded_at']))
                        <p class="text-muted small mb-3">Recorded: {{ $health['rate_limits']['recorded_at'] }}</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <h6>X-Business-Use-Case-Usage</h6>
                                <pre class="bg-light p-3 rounded small mb-0">{{ json_encode($health['rate_limits']['x_business_use_case_usage'] ?? [], JSON_PRETTY_PRINT) }}</pre>
                            </div>
                            <div class="col-md-6">
                                <h6>X-App-Usage</h6>
                                <pre class="bg-light p-3 rounded small mb-0">{{ json_encode($health['rate_limits']['x_app_usage'] ?? [], JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                    @else
                        <p class="text-muted mb-0">No rate-limit metrics recorded yet. Metrics are logged after Graph API calls.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
