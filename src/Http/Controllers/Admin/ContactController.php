<?php

namespace Vendor\LaravelWhatsAppCloud\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppContact;

class ContactController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        $contacts = WhatsAppContact::query()
            ->with(['account', 'conversation'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('whatsapp::admin.contacts.index', compact('contacts', 'search'));
    }

    public function show(WhatsAppContact $contact): View
    {
        $contact->load(['account', 'conversation.messages' => fn ($q) => $q->latest('id')->limit(5)]);

        return view('whatsapp::admin.contacts.show', compact('contact'));
    }
}
