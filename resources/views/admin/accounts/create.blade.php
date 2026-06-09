@extends('whatsapp::layouts.admin')

@section('title', 'Create WhatsApp Account')

@section('content')
    <div class="mb-4">
        <a href="{{ route('whatsapp.admin.accounts.index') }}" class="text-decoration-none">&larr; Back to accounts</a>
        <h1 class="h3 mt-2">Create WhatsApp Account</h1>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            @include('whatsapp::admin.accounts._form', ['account' => null])
        </div>
    </div>
@endsection
