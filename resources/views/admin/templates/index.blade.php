@extends('whatsapp::layouts.admin')

@section('title', 'Templates')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Message Templates</h1>
            <p class="text-muted mb-0">Synced from Meta WhatsApp Business</p>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('whatsapp.admin.templates.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ $search }}"
                           placeholder="Template name, language, status">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" @selected($category === $cat)>{{ ucfirst(strtolower($cat)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Account</label>
                    <select name="account_id" class="form-select">
                        <option value="">All accounts</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}" @selected($accountId === $account->id)>{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-success w-100">Filter</button>
                    <a href="{{ route('whatsapp.admin.templates.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header">Sync from Meta</div>
        <div class="card-body">
            <form method="POST" action="{{ route('whatsapp.admin.templates.sync') }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">Account</label>
                    <select name="account_id" class="form-select" required>
                        @foreach ($accounts->where('provider', 'meta') as $account)
                            <option value="{{ $account->id }}">{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-arrow-repeat"></i> Sync Templates
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Template</th>
                        <th>Account</th>
                        <th>Category</th>
                        <th>Language</th>
                        <th>Status</th>
                        <th>Synced</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($templates as $template)
                        <tr>
                            <td><code>{{ $template->template_name }}</code></td>
                            <td>{{ $template->account?->name }}</td>
                            <td>
                                @if ($template->category)
                                    <span class="badge bg-light text-dark border">{{ $template->category }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $template->language }}</td>
                            <td>
                                <span class="badge bg-{{ $template->statusBadgeClass() }}">
                                    {{ $template->status ?? 'unknown' }}
                                </span>
                            </td>
                            <td>{{ $template->synced_at?->diffForHumans() ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('whatsapp.admin.templates.show', $template) }}"
                                   class="btn btn-sm btn-outline-success">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No templates yet. Sync from a Meta account above.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($templates->hasPages())
            <div class="card-footer">{{ $templates->links() }}</div>
        @endif
    </div>
@endsection
