# Laravel WhatsApp Cloud

[![Tests](https://github.com/vendor/laravel-whatsapp-cloud/actions/workflows/test.yml/badge.svg)](https://github.com/vendor/laravel-whatsapp-cloud/actions/workflows/test.yml)

A production-ready **WhatsApp conversation platform** for Laravel. Send messages via **Meta Cloud API** or **Twilio WhatsApp** using one unified fluent API, with contacts, conversations, message timelines, multi-account support, webhooks, queues, notifications, and an admin panel.

## Requirements

- PHP 8.3+
- Laravel 13.x

## Installation

```bash
composer require vendor/laravel-whatsapp-cloud
php artisan whatsapp:install
php artisan migrate
```

## Provider Comparison

| Feature | Meta Cloud API | Twilio WhatsApp |
|---------|----------------|-----------------|
| Setup | Meta Business + Graph API token | Twilio Account SID + Auth Token |
| Templates | Native WhatsApp templates | Twilio Content SID |
| Media | URL-based (`link`) | URL-based (`MediaUrl`) |
| Location | Native location message | Sent as formatted text |
| Webhooks | Built-in (`/whatsapp/webhook`) | Use Twilio status callbacks separately |
| Message ID | `wamid.*` | `SM*` (Twilio SID) |
| Best for | Direct Meta integration | Teams already on Twilio |

## Quick Start (API unchanged)

```php
use Vendor\LaravelWhatsAppCloud\Facades\WhatsApp;

WhatsApp::send('923001234567', 'Hello World');
WhatsApp::account(1)->sendText('923001234567', 'Hello');
WhatsApp::using('marketing')->send('923001234567', 'Sale Started');
WhatsApp::queue()->send('923001234567', 'Queued message');
```

### Supported Methods

```php
WhatsApp::sendText($to, $message, $previewUrl = false);
WhatsApp::sendTemplate($to, 'hello_world', 'en_US', $components);
WhatsApp::sendImage($to, 'https://example.com/image.jpg', $caption);
WhatsApp::sendDocument($to, 'https://example.com/doc.pdf', $filename, $caption);
WhatsApp::sendAudio($to, 'https://example.com/audio.mp3');
WhatsApp::sendVideo($to, 'https://example.com/video.mp4', $caption);
WhatsApp::sendLocation($to, 24.86, 67.00, 'Office', 'Address');
```

## Meta Cloud API Setup

### 1. Create a Meta App

1. Go to [Meta for Developers](https://developers.facebook.com/)
2. Create an app with **WhatsApp** product enabled
3. Add a phone number and generate a **permanent access token**
4. Copy **Phone Number ID** and **Access Token**

### 2. Create Account (code or admin)

```php
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;

WhatsAppAccount::create([
    'name' => 'primary',
    'provider' => WhatsAppAccount::PROVIDER_META,
    'phone_number' => '923001234567',
    'phone_number_id' => 'YOUR_PHONE_NUMBER_ID',
    'access_token' => 'YOUR_ACCESS_TOKEN',
    'app_secret' => 'YOUR_META_APP_SECRET',
    'webhook_verify_token' => 'my-secret-token',
    'is_default' => true,
    'is_active' => true,
]);
```

### 3. Configure Webhook

```
GET/POST  /whatsapp/webhook
```

```env
WHATSAPP_APP_SECRET=your-meta-app-secret
WHATSAPP_WEBHOOK_REQUIRE_SIGNATURE=true
```

## Twilio WhatsApp Setup

### 1. Enable WhatsApp in Twilio

1. Sign up at [Twilio](https://www.twilio.com/)
2. Enable WhatsApp Sandbox or register a WhatsApp sender
3. Copy **Account SID**, **Auth Token**, and **WhatsApp number**

### 2. Create Twilio Account

```php
WhatsAppAccount::create([
    'name' => 'twilio-support',
    'provider' => WhatsAppAccount::PROVIDER_TWILIO,
    'phone_number' => '923001234567',
    'twilio_sid' => 'ACxxxxxxxx',
    'twilio_token' => 'your_auth_token',
    'twilio_whatsapp_number' => '14155238886',
    'is_default' => false,
    'is_active' => true,
]);
```

### 3. Send via Twilio provider

```php
WhatsApp::using('twilio-support')->sendText('923001234567', 'Hello from Twilio');
```

## Provider Architecture

```
WhatsApp::send()
    └── WhatsAppManager
            └── ProviderFactory::make($account)
                    ├── MetaProvider
                    └── TwilioProvider
```

### Extend with custom providers

Implement `WhatsAppProviderInterface` and register in `config/whatsapp.php`:

```php
'providers' => [
    'meta' => \Vendor\LaravelWhatsAppCloud\Providers\MetaProvider::class,
    'twilio' => \Vendor\LaravelWhatsAppCloud\Providers\TwilioProvider::class,
],
```

## Conversation Platform

The package automatically tracks conversations:

| Table | Purpose |
|-------|---------|
| `whatsapp_contacts` | Per-account contact records |
| `whatsapp_conversations` | One thread per contact |
| `whatsapp_conversation_messages` | Incoming & outgoing timeline |

- **Incoming**: stored automatically from webhooks
- **Outgoing**: stored when `WhatsApp::send()` succeeds

Disable with `WHATSAPP_CONVERSATIONS_ENABLED=false`.

### Admin URLs

| URL | Feature |
|-----|---------|
| `/admin/whatsapp` | Dashboard (contacts, conversations, today's messages) |
| `/admin/whatsapp/contacts` | Contact list + search |
| `/admin/whatsapp/conversations` | Conversation list + search |
| `/admin/whatsapp/conversations/{id}` | Message timeline |
| `/admin/whatsapp/accounts` | Account management |

## Admin Panel

`/admin/whatsapp/accounts` — create accounts with provider-specific fields:

- **Meta**: Phone Number ID, Access Token, App Secret, Webhook Verify Token
- **Twilio**: Account SID, Auth Token, WhatsApp Number

## Environment Variables

```env
WHATSAPP_DEFAULT_ACCOUNT=
WHATSAPP_DEFAULT_PROVIDER=meta
WHATSAPP_API_VERSION=v21.0
WHATSAPP_APP_SECRET=
WHATSAPP_WEBHOOK_REQUIRE_SIGNATURE=false
WHATSAPP_QUEUE_ENABLED=true
WHATSAPP_LOG_MESSAGES=true
WHATSAPP_ADMIN_AUTHORIZATION_ENABLED=true
```

## Notifications

```php
use Vendor\LaravelWhatsAppCloud\Notifications\WhatsAppChannel;

public function via($notifiable): array
{
    return [WhatsAppChannel::class];
}

public function toWhatsApp($notifiable): array
{
    return [
        'using' => 'twilio-support', // or account ID
        'text' => 'Your order shipped!',
        'queue' => true,
    ];
}
```

## Webhooks (Meta)

```php
use Vendor\LaravelWhatsAppCloud\Events\MessageReceived;

Event::listen(MessageReceived::class, function (MessageReceived $event) {
    // Handle incoming Meta message
});
```

## Testing

```bash
composer test
```

## Security

See [SECURITY.md](SECURITY.md).

## License

MIT — see [LICENSE](LICENSE).
