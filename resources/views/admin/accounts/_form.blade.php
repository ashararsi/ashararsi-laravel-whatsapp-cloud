@php
    use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
    $isEdit = isset($account) && $account;
    $action = $isEdit
        ? route('whatsapp.admin.accounts.update', $account)
        : route('whatsapp.admin.accounts.store');
    $selectedProvider = old('provider', $account->provider ?? WhatsAppAccount::PROVIDER_META);
@endphp

<form action="{{ $action }}" method="POST" id="whatsapp-account-form">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', $account->name ?? '') }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Provider</label>
            <select name="provider" id="provider" class="form-select @error('provider') is-invalid @enderror" required>
                <option value="{{ WhatsAppAccount::PROVIDER_META }}" @selected($selectedProvider === WhatsAppAccount::PROVIDER_META)>
                    Meta Cloud API
                </option>
                <option value="{{ WhatsAppAccount::PROVIDER_TWILIO }}" @selected($selectedProvider === WhatsAppAccount::PROVIDER_TWILIO)>
                    Twilio WhatsApp
                </option>
            </select>
            @error('provider')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Phone Number</label>
            <input type="text" name="phone_number" class="form-control @error('phone_number') is-invalid @enderror"
                   value="{{ old('phone_number', $account->phone_number ?? '') }}" required>
            @error('phone_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12">
            <hr class="my-1">
            <h6 class="text-muted provider-section-title" data-provider="{{ WhatsAppAccount::PROVIDER_META }}">Meta Cloud API Settings</h6>
        </div>

        <div class="col-md-6 provider-field" data-provider="{{ WhatsAppAccount::PROVIDER_META }}">
            <label class="form-label">Phone Number ID</label>
            <input type="text" name="phone_number_id" class="form-control @error('phone_number_id') is-invalid @enderror"
                   value="{{ old('phone_number_id', $account->phone_number_id ?? '') }}">
            @error('phone_number_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6 provider-field" data-provider="{{ WhatsAppAccount::PROVIDER_META }}">
            <label class="form-label">Access Token</label>
            <input type="password" name="access_token" class="form-control @error('access_token') is-invalid @enderror"
                   placeholder="{{ $isEdit ? 'Leave blank to keep current token' : '' }}">
            @error('access_token')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6 provider-field" data-provider="{{ WhatsAppAccount::PROVIDER_META }}">
            <label class="form-label">App Secret</label>
            <input type="password" name="app_secret" class="form-control @error('app_secret') is-invalid @enderror"
                   placeholder="{{ $isEdit ? 'Leave blank to keep current secret' : 'Meta app secret for webhook signature' }}">
            @error('app_secret')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6 provider-field" data-provider="{{ WhatsAppAccount::PROVIDER_META }}">
            <label class="form-label">Webhook Verify Token</label>
            <input type="text" name="webhook_verify_token" class="form-control @error('webhook_verify_token') is-invalid @enderror"
                   value="{{ old('webhook_verify_token', $account->webhook_verify_token ?? '') }}">
            @error('webhook_verify_token')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12 provider-field" data-provider="{{ WhatsAppAccount::PROVIDER_TWILIO }}">
            <hr class="my-1">
            <h6 class="text-muted">Twilio WhatsApp Settings</h6>
        </div>

        <div class="col-md-6 provider-field" data-provider="{{ WhatsAppAccount::PROVIDER_TWILIO }}">
            <label class="form-label">Twilio Account SID</label>
            <input type="text" name="twilio_sid" class="form-control @error('twilio_sid') is-invalid @enderror"
                   value="{{ old('twilio_sid', $account->twilio_sid ?? '') }}">
            @error('twilio_sid')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6 provider-field" data-provider="{{ WhatsAppAccount::PROVIDER_TWILIO }}">
            <label class="form-label">Twilio Auth Token</label>
            <input type="password" name="twilio_token" class="form-control @error('twilio_token') is-invalid @enderror"
                   placeholder="{{ $isEdit ? 'Leave blank to keep current token' : '' }}">
            @error('twilio_token')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6 provider-field" data-provider="{{ WhatsAppAccount::PROVIDER_TWILIO }}">
            <label class="form-label">Twilio WhatsApp Number</label>
            <input type="text" name="twilio_whatsapp_number" class="form-control @error('twilio_whatsapp_number') is-invalid @enderror"
                   value="{{ old('twilio_whatsapp_number', $account->twilio_whatsapp_number ?? '') }}"
                   placeholder="14155238886">
            @error('twilio_whatsapp_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-3">
            <div class="form-check mt-4">
                <input type="hidden" name="is_default" value="0">
                <input type="checkbox" name="is_default" value="1" class="form-check-input" id="is_default"
                       {{ old('is_default', $account->is_default ?? false) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_default">Default Account</label>
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-check mt-4">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active"
                       {{ old('is_active', $account->is_active ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Active</label>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-success">{{ $isEdit ? 'Update Account' : 'Create Account' }}</button>
    </div>
</form>

<script>
    (function () {
        const providerSelect = document.getElementById('provider');
        const fields = document.querySelectorAll('.provider-field');
        const titles = document.querySelectorAll('.provider-section-title');

        function toggleProviderFields() {
            const selected = providerSelect.value;
            fields.forEach((field) => {
                field.style.display = field.dataset.provider === selected ? '' : 'none';
            });
            titles.forEach((title) => {
                title.style.display = title.dataset.provider === selected ? '' : 'none';
            });
        }

        providerSelect.addEventListener('change', toggleProviderFields);
        toggleProviderFields();
    })();
</script>
