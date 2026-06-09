<?php

namespace Vendor\LaravelWhatsAppCloud\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppCampaign;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppContact;
use Vendor\LaravelWhatsAppCloud\Services\CampaignService;

class CampaignController extends Controller
{
    public function index(): View
    {
        $campaigns = WhatsAppCampaign::query()->with('account')->latest('id')->paginate(20);

        return view('whatsapp::admin.campaigns.index', compact('campaigns'));
    }

    public function create(): View
    {
        return view('whatsapp::admin.campaigns.create');
    }

    public function store(Request $request, CampaignService $campaigns): RedirectResponse
    {
        $data = $request->validate([
            'account_id' => ['required', 'exists:whatsapp_accounts,id'],
            'name' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        $campaign = WhatsAppCampaign::query()->create([
            ...$data,
            'type' => 'text',
            'status' => WhatsAppCampaign::STATUS_DRAFT,
        ]);

        WhatsAppContact::query()
            ->where('account_id', $data['account_id'])
            ->each(function (WhatsAppContact $contact) use ($campaign) {
                $campaign->recipients()->create([
                    'contact_id' => $contact->id,
                    'phone' => $contact->phone,
                ]);
            });

        if ($request->boolean('send_now')) {
            $campaigns->dispatch($campaign);
        }

        return redirect()
            ->route('whatsapp.admin.campaigns.index')
            ->with('success', 'Campaign created successfully.');
    }
}
