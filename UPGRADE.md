# Upgrade Guide

## Upgrading to v1.2.0

### 1. Update the package

```bash
composer update ashararsi/laravel-whatsapp-cloud
php artisan migrate
```

### 2. Run migrations

v1.2.0 adds migration `2024_01_01_000008_add_direction_to_whatsapp_messages`:

- `direction` column (default `outgoing`)
- `from` column for inbound sender phone
- Unique index on `whatsapp_message_id`

**Note:** If you have duplicate `whatsapp_message_id` values in existing data, deduplicate before migrating.

### 3. Configure per-account Meta secrets (recommended)

Store `app_secret` on each Meta `WhatsAppAccount` record. Webhook verification uses the account secret first, then falls back to `WHATSAPP_APP_SECRET`.

```env
WHATSAPP_WEBHOOK_REQUIRE_SIGNATURE=true
WHATSAPP_APP_SECRET=your-fallback-secret
```

### 4. Configure Twilio webhooks

In Twilio Console, set:

| Callback | URL |
|----------|-----|
| Incoming | `https://your-app.com/whatsapp/twilio/webhook` |
| Status | `https://your-app.com/whatsapp/twilio/status` |

```env
WHATSAPP_TWILIO_REQUIRE_SIGNATURE=true
```

### 5. Verify deployment

```bash
php artisan whatsapp:doctor
```

Resolve any **ERROR** items before going live.

### Backward compatibility

- `WhatsApp::send()` and all existing facade methods are unchanged
- Meta webhook URL remains `/whatsapp/webhook`
- Existing events (`MessageReceived`, `MessageDelivered`, `MessageRead`) unchanged
- `meta_json` column name preserved (maps to payload storage)
- Conversation tables and admin routes unchanged

### New optional features

- Inbox reply queue checkbox (requires `WHATSAPP_QUEUE_ENABLED=true`)
- Incoming rows in `whatsapp_messages` when `WHATSAPP_LOG_MESSAGES=true` (default)
