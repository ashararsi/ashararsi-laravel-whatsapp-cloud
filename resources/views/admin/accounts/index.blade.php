@extends('whatsapp::layouts.admin')

@section('title', 'WhatsApp Accounts')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">WhatsApp Accounts</h1>
        <a href="{{ route('whatsapp.admin.accounts.create') }}" class="btn btn-success">Add Account</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Provider</th>
                        <th>Phone</th>
                        <th>Default</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accounts as $account)
                        <tr>
                            <td>
                                <a href="{{ route('whatsapp.admin.accounts.show', $account) }}">{{ $account->name }}</a>
                            </td>
                            <td><span class="badge bg-info text-dark">{{ $account->providerLabel() }}</span></td>
                            <td>{{ $account->phone_number }}</td>
                            <td>
                                @if ($account->is_default)
                                    <span class="badge bg-primary">Default</span>
                                @else
                                    <form action="{{ route('whatsapp.admin.accounts.default', $account) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Set Default</button>
                                    </form>
                                @endif
                            </td>
                            <td>
                                @if ($account->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <form action="{{ route('whatsapp.admin.accounts.toggle', $account) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                        {{ $account->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                                <a href="{{ route('whatsapp.admin.accounts.edit', $account) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form action="{{ route('whatsapp.admin.accounts.destroy', $account) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this account?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No accounts configured yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($accounts->hasPages())
            <div class="card-footer">
                {{ $accounts->links() }}
            </div>
        @endif
    </div>
@endsection
