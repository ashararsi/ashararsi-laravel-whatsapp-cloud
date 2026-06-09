@extends('whatsapp::layouts.admin')

@section('title', 'Conversations')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Conversations</h1>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('whatsapp.admin.conversations.index') }}" class="row g-2">
                <div class="col-md-8">
                    <input type="text" name="search" class="form-control" placeholder="Search by contact name or phone"
                           value="{{ $search }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-success">Search</button>
                    @if ($search)
                        <a href="{{ route('whatsapp.admin.conversations.index') }}" class="btn btn-outline-secondary">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Contact</th>
                        <th>Phone</th>
                        <th>Account</th>
                        <th>Last Message</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($conversations as $conversation)
                        <tr>
                            <td>{{ $conversation->contact?->name ?? '—' }}</td>
                            <td><code>{{ $conversation->contact?->phone }}</code></td>
                            <td>{{ $conversation->account?->name }}</td>
                            <td>{{ $conversation->last_message_at?->diffForHumans() ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('whatsapp.admin.conversations.show', $conversation) }}"
                                   class="btn btn-sm btn-outline-primary">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No conversations yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($conversations->hasPages())
            <div class="card-footer">{{ $conversations->links() }}</div>
        @endif
    </div>
@endsection
