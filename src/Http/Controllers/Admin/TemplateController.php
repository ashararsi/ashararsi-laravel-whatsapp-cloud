<?php

namespace Vendor\LaravelWhatsAppCloud\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppTemplate;
use Vendor\LaravelWhatsAppCloud\Services\TemplateSyncService;

class TemplateController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $category = $request->string('category')->trim()->toString();
        $accountId = $request->integer('account_id') ?: null;

        $templates = WhatsAppTemplate::query()
            ->with('account')
            ->when($accountId, fn ($q) => $q->where('account_id', $accountId))
            ->search($search)
            ->category($category)
            ->orderByDesc('synced_at')
            ->orderBy('template_name')
            ->paginate(20)
            ->withQueryString();

        $accounts = WhatsAppAccount::query()->active()->orderBy('name')->get();

        return view('whatsapp::admin.templates.index', [
            'templates' => $templates,
            'accounts' => $accounts,
            'search' => $search,
            'category' => $category,
            'accountId' => $accountId,
            'categories' => WhatsAppTemplate::CATEGORIES,
        ]);
    }

    public function show(WhatsAppTemplate $template): View
    {
        $template->load('account');

        return view('whatsapp::admin.templates.show', compact('template'));
    }

    public function sync(Request $request, TemplateSyncService $sync): RedirectResponse
    {
        $data = $request->validate([
            'account_id' => ['required', 'exists:whatsapp_accounts,id'],
        ]);

        $account = WhatsAppAccount::query()->findOrFail($data['account_id']);

        try {
            $count = $sync->syncAccount($account);

            return back()->with('success', "Synced {$count} template(s) from Meta for [{$account->name}].");
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Template sync failed. Verify WABA ID, access token, and Meta permissions.');
        }
    }
}
