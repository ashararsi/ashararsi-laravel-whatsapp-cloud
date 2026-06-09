<?php

namespace Vendor\LaravelWhatsAppCloud\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppSetting;
use Vendor\LaravelWhatsAppCloud\Services\WhatsAppSettingsService;

class SettingsController extends Controller
{
    public function edit(WhatsAppSettingsService $settings): View
    {
        return view('whatsapp::admin.settings.edit', [
            'definitions' => $settings->definitions(),
            'values' => $settings->all(),
            'groups' => $settings->groups(),
        ]);
    }

    public function update(Request $request, WhatsAppSettingsService $settings): RedirectResponse
    {
        $definitions = $settings->definitions();
        $rules = [];

        foreach ($definitions as $key => $definition) {
            $field = $this->fieldName($key);

            if ($definition['type'] === WhatsAppSetting::TYPE_BOOLEAN) {
                $rules[$field] = ['required', 'in:0,1'];

                continue;
            }

            if (($definition['nullable'] ?? false) === true) {
                $rules[$field] = ['nullable', 'string', 'max:255'];

                continue;
            }

            $rules[$field] = match ($definition['type']) {
                WhatsAppSetting::TYPE_INTEGER => ['required', 'integer', 'min:'.($definition['min'] ?? 0), 'max:'.($definition['max'] ?? 999999)],
                WhatsAppSetting::TYPE_FLOAT => ['required', 'numeric', 'min:'.($definition['min'] ?? 0), 'max:'.($definition['max'] ?? 999999)],
                default => isset($definition['options'])
                    ? ['required', 'string', 'in:'.implode(',', $definition['options'])]
                    : ['required', 'string', 'max:255'],
            };
        }

        $validated = $request->validate($rules);

        $payload = [];

        foreach ($definitions as $key => $definition) {
            $field = $this->fieldName($key);
            $payload[$key] = $validated[$field] ?? ($definition['nullable'] ?? false ? '' : $definition['default']);
        }

        $settings->updateMany($payload);

        return redirect()
            ->route('whatsapp.admin.settings.edit')
            ->with('success', 'WhatsApp settings updated successfully.');
    }

    protected function fieldName(string $key): string
    {
        return str_replace('.', '_', $key);
    }
}
