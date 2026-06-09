@extends('whatsapp::layouts.admin')

@section('title', 'Create Campaign')

@section('content')
    <h1 class="h3 mb-4">Create Broadcast Campaign</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('whatsapp.admin.campaigns.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Account</label>
                    <select name="account_id" class="form-select" required>
                        @foreach (\Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount::query()->active()->get() as $account)
                            <option value="{{ $account->id }}">{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Campaign Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Message</label>
                    <textarea name="message" class="form-control" rows="4" required></textarea>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="send_now" value="1" id="send_now">
                    <label class="form-check-label" for="send_now">Send immediately to all contacts</label>
                </div>
                <button type="submit" class="btn btn-success">Create Campaign</button>
            </form>
        </div>
    </div>
@endsection
