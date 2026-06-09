<?php

namespace Vendor\LaravelWhatsAppCloud\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Vendor\LaravelWhatsAppCloud\Http\Requests\Concerns\ValidatesWhatsAppAccount;

class StoreAccountRequest extends FormRequest
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
        return $this->baseAccountRules(requireSecrets: true);
    }

    protected function prepareForValidation(): void
    {
        $this->prepareAccountBooleans();
    }
}
