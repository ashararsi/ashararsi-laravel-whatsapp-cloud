# Release Notes — v1.2.0

**ashararsi/laravel-whatsapp-cloud** — Production-ready WhatsApp platform for Laravel 13.

## Highlights

### Security hardened
- Meta webhook signatures verified per account using `app_secret`
- Twilio `X-Twilio-Signature` validation enabled by default
- Idempotent webhook processing prevents duplicate message handling

### Twilio fully supported
- Inbound webhook for text, media, and location messages
- Status callbacks for delivery lifecycle tracking
- Contacts, conversations, and message log integration

### Better inbox workflow
- Reply directly from conversation detail page
- Optional queue dispatch for replies
- Full timeline with incoming and outgoing directions in `whatsapp_messages`

### Better developer experience
- `php artisan whatsapp:doctor` — one-command health report
- PHPStan + Laravel Pint in CI
- Expanded README with setup, security, and webhook guides

## Install / Upgrade

```bash
composer require ashararsi/laravel-whatsapp-cloud:^1.2
php artisan migrate
php artisan whatsapp:doctor
```

See [UPGRADE.md](UPGRADE.md) for migration notes.

## Test coverage

60+ automated tests including Meta security, idempotency, Twilio webhooks, inbox replies, and doctor command.
