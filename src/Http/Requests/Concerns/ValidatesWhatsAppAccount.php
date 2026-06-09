<?php

namespace Vendor\LaravelWhatsAppCloud\Http\Requests\Concerns;

use Illuminate\Validation\Rule;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;

trait ValidatesWhatsAppAccount
{
    /**
     * @return array<string, mixed>
     */
    protected function baseAccountRules(?int $accountId = null, bool $requireSecrets = true): array
    {
        $provider = $this->input('provider', WhatsAppAccount::PROVIDER_META);

        $rules = [
            'provider' => ['required', Rule::in([WhatsAppAccount::PROVIDER_META, WhatsAppAccount::PROVIDER_TWILIO])],
            'name' => [
                'required',
                'string',
                'max:255',
                'alpha_dash:ascii',
                Rule::unique('whatsapp_accounts', 'name')->ignore($accountId),
            ],
            'phone_number' => ['required', 'string', 'max:50', 'regex:/^[0-9+\-\s()]+$/'],
            'webhook_verify_token' => ['nullable', 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        if ($provider === WhatsAppAccount::PROVIDER_META) {
            $rules['phone_number_id'] = [
                'required',
                'string',
                'max:255',
                Rule::unique('whatsapp_accounts', 'phone_number_id')->ignore($accountId),
            ];
            $rules['access_token'] = [$requireSecrets ? 'required' : 'sometimes', 'string', 'min:10'];
            $rules['app_secret'] = ['nullable', 'string', 'min:10'];
        }

        if ($provider === WhatsAppAccount::PROVIDER_TWILIO) {
            $rules['twilio_sid'] = ['required', 'string', 'max:255'];
            $rules['twilio_token'] = [$requireSecrets ? 'required' : 'sometimes', 'string', 'min:10'];
            $rules['twilio_whatsapp_number'] = ['required', 'string', 'max:50', 'regex:/^[0-9+\-\s()]+$/'];
        }

        return $rules;
    }

    protected function prepareAccountBooleans(): void
    {
        $this->merge([
            'is_default' => $this->boolean('is_default'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
