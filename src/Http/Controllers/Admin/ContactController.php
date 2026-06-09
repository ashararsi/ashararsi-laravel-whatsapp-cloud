<?php

namespace Vendor\LaravelWhatsAppCloud\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppContact;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppContactNote;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppTag;

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
        $contact->load([
            'account',
            'tags',
            'notes' => fn ($q) => $q->latest('id'),
            'conversation.messages' => fn ($q) => $q->latest('id')->limit(5),
        ]);

        $tags = WhatsAppTag::query()
            ->where('account_id', $contact->account_id)
            ->orderBy('name')
            ->get();

        return view('whatsapp::admin.contacts.show', compact('contact', 'tags'));
    }

    public function storeNote(Request $request, WhatsAppContact $contact): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string'],
            'author' => ['nullable', 'string', 'max:255'],
        ]);

        WhatsAppContactNote::query()->create([
            'contact_id' => $contact->id,
            'body' => $data['body'],
            'author' => $data['author'] ?? 'Admin',
        ]);

        return back()->with('success', 'Note added.');
    }

    public function storeTag(Request $request, WhatsAppContact $contact): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $tag = WhatsAppTag::query()->firstOrCreate(
            [
                'account_id' => $contact->account_id,
                'name' => $data['name'],
            ],
            [
                'color' => $data['color'] ?? '#198754',
            ],
        );

        $contact->tags()->syncWithoutDetaching([$tag->id]);

        return back()->with('success', 'Tag created and assigned.');
    }

    public function syncTags(Request $request, WhatsAppContact $contact): RedirectResponse
    {
        $data = $request->validate([
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:whatsapp_tags,id'],
        ]);

        $tagIds = collect($data['tags'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => WhatsAppTag::query()
                ->where('id', $id)
                ->where('account_id', $contact->account_id)
                ->exists())
            ->all();

        $contact->tags()->sync($tagIds);

        return back()->with('success', 'Tags updated.');
    }

    public function detachTag(WhatsAppContact $contact, WhatsAppTag $tag): RedirectResponse
    {
        if ($tag->account_id !== $contact->account_id) {
            abort(404);
        }

        $contact->tags()->detach($tag->id);

        return back()->with('success', 'Tag removed from contact.');
    }
}
