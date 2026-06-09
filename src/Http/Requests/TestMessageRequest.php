<?php

namespace Vendor\LaravelWhatsAppCloud\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class TestMessageRequest extends FormRequest
{
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
        return [
            'to' => ['required', 'string', 'max:50', 'regex:/^[0-9+\-\s()]+$/'],
            'message' => ['required', 'string', 'max:4096'],
        ];
    }
}
