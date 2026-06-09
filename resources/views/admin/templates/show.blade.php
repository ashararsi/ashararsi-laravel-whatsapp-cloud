@extends('whatsapp::layouts.admin')

@section('title', $template->template_name)

@section('content')
    <div class="mb-4">
        <a href="{{ route('whatsapp.admin.templates.index') }}" class="text-decoration-none">&larr; Back to templates</a>
        <h1 class="h3 mt-2"><code>{{ $template->template_name }}</code></h1>
        <p class="text-muted mb-0">
            {{ $template->account?->name }} &middot; {{ $template->language }}
            &middot; <span class="badge bg-{{ $template->statusBadgeClass() }}">{{ $template->status }}</span>
        </p>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header">Template Details</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Name</span><code>{{ $template->template_name }}</code>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Provider</span><span>{{ $template->provider }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Category</span><span>{{ $template->category ?? '—' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Language</span><span>{{ $template->language }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Status</span>
                        <span class="badge bg-{{ $template->statusBadgeClass() }}">{{ $template->status }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Meta Template ID</span>
                        <span><code>{{ $template->meta_template_id ?? '—' }}</code></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Last synced</span>
                        <span>{{ $template->synced_at?->format('Y-m-d H:i') ?? '—' }}</span>
                    </li>
                </ul>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-header">Send via API</div>
                <div class="card-body">
                    <pre class="bg-light p-3 rounded small mb-0"><code>WhatsApp::template(
    '{{ $template->account?->phone_number ?? '923001234567' }}',
    '{{ $template->template_name }}',
    ['value1', 'value2']
);</code></pre>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">Components</div>
                <div class="card-body">
                    @if (! empty($template->components_json))
                        <pre class="bg-light p-3 rounded small mb-0">{{ json_encode($template->components_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    @else
                        <p class="text-muted mb-0">No component data stored.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
