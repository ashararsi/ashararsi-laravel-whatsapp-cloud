<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'WhatsApp Admin')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --wa-sidebar-width: 260px;
            --wa-green: #128c7e;
            --wa-green-dark: #075e54;
        }

        body { min-height: 100vh; background: #f4f6f9; }

        .wa-sidebar {
            width: var(--wa-sidebar-width);
            min-height: 100vh;
            background: linear-gradient(180deg, var(--wa-green-dark) 0%, var(--wa-green) 100%);
            color: #fff;
            flex-shrink: 0;
        }

        .wa-sidebar .brand {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
            font-weight: 700;
            font-size: 1.1rem;
            color: #fff;
            text-decoration: none;
            display: block;
        }

        .wa-sidebar .nav-link {
            color: rgba(255, 255, 255, 0.85);
            padding: 0.75rem 1.5rem;
            border-radius: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-left: 3px solid transparent;
        }

        .wa-sidebar .nav-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.08);
        }

        .wa-sidebar .nav-link.active {
            color: #fff;
            background: rgba(255, 255, 255, 0.15);
            border-left-color: #fff;
            font-weight: 600;
        }

        .wa-sidebar .nav-link i { font-size: 1.1rem; width: 1.25rem; text-align: center; }

        .wa-main { flex: 1; min-width: 0; }

        .wa-topbar {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 1.5rem;
        }

        .wa-content { padding: 1.5rem; }

        .timeline-incoming { background: #e8f5e9; border-left: 4px solid #198754; }
        .timeline-outgoing { background: #e7f1ff; border-left: 4px solid #0d6efd; margin-left: auto; max-width: 85%; }

        @media (max-width: 991.98px) {
            .wa-sidebar { width: 100%; min-height: auto; }
            .wa-layout { flex-direction: column !important; }
        }
    </style>
</head>
<body>
    <div class="d-flex wa-layout">
        <aside class="wa-sidebar d-none d-lg-block">
            <a class="brand" href="{{ route('whatsapp.admin.dashboard') }}">
                <i class="bi bi-whatsapp me-2"></i>WhatsApp Cloud
            </a>
            <nav class="nav flex-column py-2">
                <a class="nav-link {{ request()->routeIs('whatsapp.admin.dashboard') ? 'active' : '' }}"
                   href="{{ route('whatsapp.admin.dashboard') }}">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a class="nav-link {{ request()->routeIs('whatsapp.admin.contacts.*') ? 'active' : '' }}"
                   href="{{ route('whatsapp.admin.contacts.index') }}">
                    <i class="bi bi-people"></i> Contacts
                </a>
                <a class="nav-link {{ request()->routeIs('whatsapp.admin.conversations.*') ? 'active' : '' }}"
                   href="{{ route('whatsapp.admin.conversations.index') }}">
                    <i class="bi bi-chat-dots"></i> Conversations
                </a>
                <a class="nav-link {{ request()->routeIs('whatsapp.admin.accounts.*') ? 'active' : '' }}"
                   href="{{ route('whatsapp.admin.accounts.index') }}">
                    <i class="bi bi-gear"></i> Accounts
                </a>
            </nav>
        </aside>

        <div class="wa-main">
            <div class="wa-topbar d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-outline-success d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#waMobileNav">
                        <i class="bi bi-list"></i>
                    </button>
                    <div>
                        <div class="fw-semibold">@yield('title', 'WhatsApp Admin')</div>
                        <div class="text-muted small">Laravel WhatsApp Cloud</div>
                    </div>
                </div>
                <a href="{{ route('whatsapp.admin.accounts.create') }}" class="btn btn-success btn-sm d-none d-md-inline-flex align-items-center gap-1">
                    <i class="bi bi-plus-lg"></i> Add Account
                </a>
            </div>

            <div class="wa-content">
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
        </div>
    </div>

    <div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="waMobileNav" style="background: var(--wa-green-dark); color: #fff;">
        <div class="offcanvas-header border-bottom border-light border-opacity-25">
            <h5 class="offcanvas-title"><i class="bi bi-whatsapp me-2"></i>WhatsApp Cloud</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            <nav class="nav flex-column">
                <a class="nav-link text-white {{ request()->routeIs('whatsapp.admin.dashboard') ? 'active' : '' }}"
                   href="{{ route('whatsapp.admin.dashboard') }}">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
                <a class="nav-link text-white {{ request()->routeIs('whatsapp.admin.contacts.*') ? 'active' : '' }}"
                   href="{{ route('whatsapp.admin.contacts.index') }}">
                    <i class="bi bi-people me-2"></i> Contacts
                </a>
                <a class="nav-link text-white {{ request()->routeIs('whatsapp.admin.conversations.*') ? 'active' : '' }}"
                   href="{{ route('whatsapp.admin.conversations.index') }}">
                    <i class="bi bi-chat-dots me-2"></i> Conversations
                </a>
                <a class="nav-link text-white {{ request()->routeIs('whatsapp.admin.accounts.*') ? 'active' : '' }}"
                   href="{{ route('whatsapp.admin.accounts.index') }}">
                    <i class="bi bi-gear me-2"></i> Accounts
                </a>
            </nav>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
