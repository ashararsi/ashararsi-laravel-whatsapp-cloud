@extends('whatsapp::layouts.admin')

@section('title', $account->name)

@section('content')
    <div class="mb-4">
        <a href="{{ route('whatsapp.admin.accounts.index') }}" class="text-decoration-none">&larr; Back to accounts</a>
        <h1 class="h3 mt-2">{{ $account->name }}</h1>
        <p class="text-muted mb-0">
            {{ $account->providerLabel() }} &middot; {{ $account->phone_number }}
            @if ($account->isMeta() && $account->phone_number_id)
                &middot; <code>{{ $account->phone_number_id }}</code>
            @endif
        </p>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header">Send Test Message</div>
                <div class="card-body">
                    <form action="{{ route('whatsapp.admin.accounts.test', $account) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Recipient</label>
                            <input type="text" name="to" class="form-control @error('to') is-invalid @enderror"
                                   value="{{ old('to') }}" placeholder="923001234567" required>
                            @error('to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea name="message" class="form-control @error('message') is-invalid @enderror" rows="3" required>{{ old('message', 'Hello from WhatsApp Cloud!') }}</textarea>
                            @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-success w-100">Send Test</button>
                    </form>
                </div>
            </div>

            @if ($account->isMeta())
                <div class="card shadow-sm mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Business Profile</span>
                        <form action="{{ route('whatsapp.admin.accounts.sync-business', $account) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-success">Sync Business</button>
                        </form>
                    </div>
                    <ul class="list-group list-group-flush">
                        @if ($businessProfile)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Business Name</span>
                                <span>{{ $businessProfile->business_name ?: '—' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Display Name</span>
                                <span>{{ $businessProfile->display_name ?: '—' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Verification</span>
                                <span>{{ $businessProfile->verification_status ?: '—' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Quality Rating</span>
                                <span>{{ $businessProfile->quality_rating ?: '—' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Messaging Tier</span>
                                <span>{{ $businessProfile->messaging_tier ?: '—' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Last Synced</span>
                                <span>{{ $businessProfile->synced_at?->diffForHumans() ?? '—' }}</span>
                            </li>
                        @else
                            <li class="list-group-item text-muted">No business profile synced yet.</li>
                        @endif
                    </ul>
                </div>

                <div class="card shadow-sm mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Synced Phone Numbers</span>
                        <form action="{{ route('whatsapp.admin.accounts.sync-numbers', $account) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-success">Sync Numbers</button>
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Display</th>
                                    <th>Verified Name</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($syncedNumbers as $number)
                                    <tr>
                                        <td><code>{{ $number->phone_number_id }}</code></td>
                                        <td>{{ $number->display_phone_number }}</td>
                                        <td>{{ $number->verified_name }}</td>
                                        <td>{{ $number->status }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-muted text-center py-3">No phone numbers synced yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="card shadow-sm mt-4">
                <div class="card-header">Account Details</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Default</span>
                        <span>{{ $account->is_default ? 'Yes' : 'No' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Status</span>
                        <span>{{ $account->is_active ? 'Active' : 'Inactive' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Provider</span>
                        <span>{{ $account->providerLabel() }}</span>
                    </li>
                    @if ($account->isMeta())
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Webhook Token</span>
                            <span>{{ $account->webhook_verify_token ? 'Set' : 'Not set' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>App Secret</span>
                            <span>{{ $account->app_secret ? 'Set' : 'Not set' }}</span>
                        </li>
                    @endif
                    @if ($account->isTwilio())
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Twilio SID</span>
                            <span><code>{{ $account->twilio_sid }}</code></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>WhatsApp Number</span>
                            <span>{{ $account->twilio_whatsapp_number }}</span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">Message Logs</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>To</th>
                                <th>Type</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Sent At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($messages as $message)
                                <tr>
                                    <td>{{ $message->to }}</td>
                                    <td><span class="badge bg-secondary">{{ $message->type }}</span></td>
                                    <td class="text-truncate" style="max-width: 200px;">{{ $message->message }}</td>
                                    <td>
                                        @php
                                            $badge = match ($message->status) {
                                                'sent', 'delivered', 'read' => 'success',
                                                'failed' => 'danger',
                                                default => 'warning',
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $badge }}">{{ $message->status }}</span>
                                    </td>
                                    <td>{{ $message->created_at?->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No messages logged yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($messages->hasPages())
                    <div class="card-footer">
                        {{ $messages->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
