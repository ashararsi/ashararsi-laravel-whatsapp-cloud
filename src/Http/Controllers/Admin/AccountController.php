<?php

namespace Vendor\LaravelWhatsAppCloud\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Vendor\LaravelWhatsAppCloud\Facades\WhatsApp;
use Vendor\LaravelWhatsAppCloud\Http\Requests\StoreAccountRequest;
use Vendor\LaravelWhatsAppCloud\Http\Requests\TestMessageRequest;
use Vendor\LaravelWhatsAppCloud\Http\Requests\UpdateAccountRequest;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage;

class AccountController extends Controller
{
    public function index(): View
    {
        $accounts = WhatsAppAccount::query()->latest('id')->paginate(15);

        return view('whatsapp::admin.accounts.index', compact('accounts'));
    }

    public function create(): View
    {
        return view('whatsapp::admin.accounts.create');
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($data['is_default'] ?? false) {
            WhatsAppAccount::query()->update(['is_default' => false]);
        }

        if (! WhatsAppAccount::query()->exists()) {
            $data['is_default'] = true;
        }

        WhatsAppAccount::query()->create($data);

        return redirect()
            ->route('whatsapp.admin.accounts.index')
            ->with('success', 'WhatsApp account created successfully.');
    }

    public function show(WhatsAppAccount $account): View
    {
        $messages = WhatsAppMessage::query()
            ->where('account_id', $account->id)
            ->latest('id')
            ->paginate(20);

        return view('whatsapp::admin.accounts.show', compact('account', 'messages'));
    }

    public function edit(WhatsAppAccount $account): View
    {
        return view('whatsapp::admin.accounts.edit', compact('account'));
    }

    public function update(UpdateAccountRequest $request, WhatsAppAccount $account): RedirectResponse
    {
        $data = $request->validated();

        if ($data['is_default'] ?? false) {
            WhatsAppAccount::query()->where('id', '!=', $account->id)->update(['is_default' => false]);
        }

        $account->update($data);

        return redirect()
            ->route('whatsapp.admin.accounts.index')
            ->with('success', 'WhatsApp account updated successfully.');
    }

    public function destroy(WhatsAppAccount $account): RedirectResponse
    {
        $account->delete();

        return redirect()
            ->route('whatsapp.admin.accounts.index')
            ->with('success', 'WhatsApp account deleted successfully.');
    }

    public function setDefault(WhatsAppAccount $account): RedirectResponse
    {
        WhatsAppAccount::setDefault($account);

        return redirect()
            ->route('whatsapp.admin.accounts.index')
            ->with('success', 'Default account updated successfully.');
    }

    public function toggleActive(WhatsAppAccount $account): RedirectResponse
    {
        $account->update(['is_active' => ! $account->is_active]);

        return redirect()
            ->route('whatsapp.admin.accounts.index')
            ->with('success', 'Account status updated successfully.');
    }

    public function sendTest(TestMessageRequest $request, WhatsAppAccount $account): RedirectResponse
    {
        try {
            WhatsApp::account($account->id)->sendText(
                $request->validated('to'),
                $request->validated('message'),
            );

            return back()->with('success', 'Test message sent successfully.');
        } catch (\Throwable $e) {
            Log::error('WhatsApp admin test message failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to send test message. Check application logs for details.');
        }
    }
}
