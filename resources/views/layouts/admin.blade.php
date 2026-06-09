<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'WhatsApp Admin')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .timeline-incoming { background: #e8f5e9; border-left: 4px solid #198754; }
        .timeline-outgoing { background: #e7f1ff; border-left: 4px solid #0d6efd; margin-left: auto; max-width: 85%; }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-success mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ route('whatsapp.admin.dashboard') }}">WhatsApp Cloud</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#whatsappNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="whatsappNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('whatsapp.admin.dashboard') ? 'active' : '' }}"
                           href="{{ route('whatsapp.admin.dashboard') }}">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('whatsapp.admin.contacts.*') ? 'active' : '' }}"
                           href="{{ route('whatsapp.admin.contacts.index') }}">Contacts</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('whatsapp.admin.conversations.*') ? 'active' : '' }}"
                           href="{{ route('whatsapp.admin.conversations.index') }}">Conversations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('whatsapp.admin.accounts.*') ? 'active' : '' }}"
                           href="{{ route('whatsapp.admin.accounts.index') }}">Accounts</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
