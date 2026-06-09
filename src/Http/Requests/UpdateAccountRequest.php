<?php

namespace Vendor\LaravelWhatsAppCloud\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Vendor\LaravelWhatsAppCloud\Http\Requests\Concerns\ValidatesWhatsAppAccount;

class UpdateAccountRequest extends FormRequest
{
    use ValidatesWhatsAppAccount;

    public function authorize(): bool
    {
        if (! config('whatsapp.admin.authorization_enabled', true)) {
            return true;
        }

        $gate = config('whatsapp.admin.gate', 'manage-whatsapp');

        return ! Gate::has($gate) || Gate::allows($gate);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->baseAccountRules($this->route('account')?->id, requireSecrets: false);
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('access_token')) {
            $this->request->remove('access_token');
        }

        if (! $this->filled('twilio_token')) {
            $this->request->remove('twilio_token');
        }

        if (! $this->filled('app_secret')) {
            $this->request->remove('app_secret');
        }

        $this->prepareAccountBooleans();
    }
}
