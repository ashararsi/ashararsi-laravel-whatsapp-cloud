# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.0.x   | :white_check_mark: |
| < 1.0   | :x:                |

## Reporting a Vulnerability

If you discover a security vulnerability, please report it via [GitHub Security Advisories](https://github.com/ashararsi/ashararsi-laravel-whatsapp-cloud/security/advisories/new) instead of using the public issue tracker.

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

2. **Enable webhook signature verification** in **Admin → Settings** (`/admin/whatsapp/settings`):
   - Set a **Global App Secret** or store `app_secret` per Meta account (recommended)
   - Enable **Require Meta Webhook Signature** in production
   - Keep **Require Twilio Webhook Signature** enabled (default)

3. **Never commit access tokens** — Use encrypted database storage (enabled by default).

4. **Disable admin panel in production** if not needed:

```env
WHATSAPP_ADMIN_ENABLED=false
```
