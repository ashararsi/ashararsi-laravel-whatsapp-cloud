# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

## Reporting a Vulnerability

If you discover a security vulnerability, please email **security@example.com** instead of using the public issue tracker.

Include:

- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if available)

We aim to respond within 72 hours.

## Security Recommendations

1. **Protect admin routes** — Define a Gate and add `auth` middleware:

```php
Gate::define('manage-whatsapp', fn ($user) => $user->isAdmin());

// config/whatsapp.php
'admin' => [
    'middleware' => ['web', 'auth', AuthorizeWhatsAppAdmin::class],
],
```

2. **Enable webhook signature verification**:

```env
WHATSAPP_APP_SECRET=your-meta-app-secret
WHATSAPP_WEBHOOK_REQUIRE_SIGNATURE=true
```

3. **Never commit access tokens** — Use encrypted database storage (enabled by default).

4. **Disable admin panel in production** if not needed:

```env
WHATSAPP_ADMIN_ENABLED=false
```
