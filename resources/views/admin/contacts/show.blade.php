@extends('whatsapp::layouts.admin')

@section('title', $contact->name ?? $contact->phone)

@section('content')
    <div class="mb-4">
        <a href="{{ route('whatsapp.admin.contacts.index') }}" class="text-decoration-none">&larr; Back to contacts</a>
        <h1 class="h3 mt-2">{{ $contact->name ?? 'Unknown Contact' }}</h1>
        <p class="text-muted mb-0">
            <code>{{ $contact->phone }}</code> &middot; {{ $contact->account?->name }}
        </p>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header">Contact Details</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Phone</span><span>{{ $contact->phone }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Account</span><span>{{ $contact->account?->name }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Created</span><span>{{ $contact->created_at?->format('Y-m-d H:i') }}</span>
                    </li>
                </ul>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-header">Tags</div>
                <div class="card-body">
                    @forelse ($contact->tags as $tag)
                        <span class="badge me-1 mb-1 d-inline-flex align-items-center gap-1"
                              style="background-color: {{ $tag->color }};">
                            {{ $tag->name }}
                            <form action="{{ route('whatsapp.admin.contacts.tags.detach', [$contact, $tag]) }}"
                                  method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-close btn-close-white btn-sm"
                                        style="font-size: 0.5rem;" aria-label="Remove tag"></button>
                            </form>
                        </span>
                    @empty
                        <p class="text-muted small mb-3">No tags assigned.</p>
                    @endforelse

                    <form action="{{ route('whatsapp.admin.contacts.tags.create', $contact) }}" method="POST" class="mb-3">
                        @csrf
                        <label class="form-label small">Create &amp; assign tag</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="name" class="form-control" placeholder="Tag name" required>
                            <input type="color" name="color" class="form-control form-control-color" value="#198754" title="Tag color">
                            <button type="submit" class="btn btn-success">Add</button>
                        </div>
                    </form>

                    @if ($tags->isNotEmpty())
                        <form action="{{ route('whatsapp.admin.contacts.tags.sync', $contact) }}" method="POST">
                            @csrf
                            <label class="form-label small">Assign existing tags</label>
                            @foreach ($tags as $tag)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="tags[]"
                                           value="{{ $tag->id }}" id="tag-{{ $tag->id }}"
                                           @checked($contact->tags->contains('id', $tag->id))>
                                    <label class="form-check-label" for="tag-{{ $tag->id }}">
                                        <span class="badge" style="background-color: {{ $tag->color }};">{{ $tag->name }}</span>
                                    </label>
                                </div>
                            @endforeach
                            <button type="submit" class="btn btn-outline-success btn-sm mt-2">Update tags</button>
                        </form>
                    @endif
                </div>
            </div>

            @if ($contact->conversation)
                <a href="{{ route('whatsapp.admin.conversations.show', $contact->conversation) }}"
                   class="btn btn-success w-100 mt-3">Open Conversation</a>
            @endif
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header">Notes</div>
                <div class="card-body">
                    @forelse ($contact->notes as $note)
                        <div class="border rounded p-3 mb-2">
                            <div class="small text-muted mb-1">
                                {{ $note->author ?? 'Admin' }}
                                &middot; {{ $note->created_at?->format('M d, Y H:i') }}
                            </div>
                            <div class="mb-0">{{ $note->body }}</div>
                        </div>
                    @empty
                        <p class="text-muted mb-3">No notes yet.</p>
                    @endforelse

                    <form action="{{ route('whatsapp.admin.contacts.notes.store', $contact) }}" method="POST">
                        @csrf
                        <div class="mb-2">
                            <textarea name="body" class="form-control" rows="3" placeholder="Add a note..." required></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <input type="text" name="author" class="form-control form-control-sm"
                                   placeholder="Author (optional)" style="max-width: 200px;">
                            <button type="submit" class="btn btn-success btn-sm">Add Note</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">Recent Messages</div>
                <div class="card-body">
                    @forelse ($contact->conversation?->messages ?? [] as $message)
                        <div class="p-3 mb-2 rounded {{ $message->isIncoming() ? 'timeline-incoming' : 'timeline-outgoing' }}">
                            <div class="small text-muted mb-1">
                                {{ ucfirst($message->direction) }} &middot; {{ $message->created_at?->format('M d, H:i') }}
                            </div>
                            <div>{{ $message->message }}</div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No messages yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
