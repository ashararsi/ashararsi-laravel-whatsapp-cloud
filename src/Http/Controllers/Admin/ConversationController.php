<?php

namespace Vendor\LaravelWhatsAppCloud\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Vendor\LaravelWhatsAppCloud\Events\ConversationReplied;
use Vendor\LaravelWhatsAppCloud\Facades\WhatsApp;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppConversation;

class ConversationController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        $conversations = WhatsAppConversation::query()
            ->with(['account', 'contact'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('contact', function ($contact) use ($search) {
                        $contact->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })->orWhereHas('messages', function ($messages) use ($search) {
                        $messages->where('message', 'like', "%{$search}%");
                    });
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

    public function reply(Request $request, WhatsAppConversation $conversation): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:4096'],
            'queue' => ['sometimes', 'boolean'],
        ]);

        $phone = $conversation->contact?->phone;

        if (! $phone) {
            return back()->with('error', 'Conversation contact phone is missing.');
        }

        try {
            $sender = WhatsApp::account($conversation->account_id);

            if ($request->boolean('queue') && config('whatsapp.queue_enabled', true)) {
                $sender = $sender->queue();
            }

            $sender->sendText($phone, $data['message']);

            event(new ConversationReplied($conversation, $data['message']));

            return back()->with('success', 'Reply sent successfully.');
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'Failed to send reply. Please verify account credentials and try again.');
        }
    }
}
