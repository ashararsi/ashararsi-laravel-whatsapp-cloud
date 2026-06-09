<?php

namespace Vendor\LaravelWhatsAppCloud\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Vendor\LaravelWhatsAppCloud\Services\WhatsAppSettingsService;

class SettingsController extends Controller
{
    public function edit(WhatsAppSettingsService $settings): View
    {
        $definitions = $settings->definitions();
        $values = $settings->all();

        $groups = [
            'graph_api' => 'Graph API',
            'cost' => 'Message Cost Analytics',
            'queue' => 'Queue Resilience',
        ];

        return view('whatsapp::admin.settings.edit', compact('definitions', 'values', 'groups'));
    }

    public function update(Request $request, WhatsAppSettingsService $settings): RedirectResponse
    {
        $definitions = $settings->definitions();
        $rules = [];

        foreach ($definitions as $key => $definition) {
            $field = str_replace('.', '_', $key);

            $rules[$field] = match ($definition['type']) {
                'integer' => ['required', 'integer', 'min:'.($definition['min'] ?? 0), 'max:'.($definition['max'] ?? 999999)],
                'float' => ['required', 'numeric', 'min:'.($definition['min'] ?? 0), 'max:'.($definition['max'] ?? 999999)],
                default => ['required', 'string'],
            };
        }

        $validated = $request->validate($rules);

        $payload = [];

        foreach ($definitions as $key => $definition) {
            $field = str_replace('.', '_', $key);
            $payload[$key] = $validated[$field];
        }

        $settings->updateMany($payload);

        return redirect()
            ->route('whatsapp.admin.settings.edit')
            ->with('success', 'WhatsApp settings updated successfully.');
    }
}
