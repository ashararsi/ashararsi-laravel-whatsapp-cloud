@extends('whatsapp::layouts.admin')

@section('title', 'Campaigns')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Broadcast Campaigns</h1>
        <a href="{{ route('whatsapp.admin.campaigns.create') }}" class="btn btn-success">New Campaign</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Account</th>
                        <th>Status</th>
                        <th>Sent</th>
                        <th>Failed</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($campaigns as $campaign)
                        <tr>
                            <td>{{ $campaign->name }}</td>
                            <td>{{ $campaign->account?->name }}</td>
                            <td><span class="badge bg-secondary">{{ $campaign->status }}</span></td>
                            <td>{{ $campaign->sent_count }}</td>
                            <td>{{ $campaign->failed_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No campaigns yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($campaigns->hasPages())
            <div class="card-footer">{{ $campaigns->links() }}</div>
        @endif
    </div>
@endsection
