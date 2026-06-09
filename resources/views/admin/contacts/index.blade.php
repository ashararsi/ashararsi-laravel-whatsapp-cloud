@extends('whatsapp::layouts.admin')

@section('title', 'Contacts')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Contacts</h1>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('whatsapp.admin.contacts.index') }}" class="row g-2">
                <div class="col-md-8">
                    <input type="text" name="search" class="form-control" placeholder="Search by name or phone"
                           value="{{ $search }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-success">Search</button>
                    @if ($search)
                        <a href="{{ route('whatsapp.admin.contacts.index') }}" class="btn btn-outline-secondary">Clear</a>
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
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Account</th>
                        <th>Last Updated</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($contacts as $contact)
                        <tr>
                            <td>{{ $contact->name ?? '—' }}</td>
                            <td><code>{{ $contact->phone }}</code></td>
                            <td>{{ $contact->account?->name }}</td>
                            <td>{{ $contact->updated_at?->format('Y-m-d H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('whatsapp.admin.contacts.show', $contact) }}" class="btn btn-sm btn-outline-primary">View</a>
                                @if ($contact->conversation)
                                    <a href="{{ route('whatsapp.admin.conversations.show', $contact->conversation) }}" class="btn btn-sm btn-outline-success">Conversation</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No contacts yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($contacts->hasPages())
            <div class="card-footer">{{ $contacts->links() }}</div>
        @endif
    </div>
@endsection
