<?php

namespace Vendor\LaravelWhatsAppCloud\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppConversation;

class ConversationController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        $conversations = WhatsAppConversation::query()
            ->with(['account', 'contact'])
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('contact', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('last_message_at')
            ->paginate(20)
            ->withQueryString();

        return view('whatsapp::admin.conversations.index', compact('conversations', 'search'));
    }

    public function show(WhatsAppConversation $conversation): View
    {
        $conversation->load(['account', 'contact']);

        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->paginate(50);

        return view('whatsapp::admin.conversations.show', compact('conversation', 'messages'));
    }
}
