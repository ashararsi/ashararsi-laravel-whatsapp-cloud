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

Store `app_secret` on each Meta `WhatsAppAccount` record. Webhook verification uses the account secret first, then falls back to the **Global App Secret** in **Admin → Settings** (`/admin/whatsapp/settings`).

Enable **Require Meta Webhook Signature** in the same settings page for production.

### 4. Configure Twilio webhooks

In Twilio Console, set:

| Callback | URL |
|----------|-----|
| Incoming | `https://your-app.com/whatsapp/twilio/webhook` |
| Status | `https://your-app.com/whatsapp/twilio/status` |

Enable **Require Twilio Webhook Signature** in **Admin → Settings** (enabled by default).

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

- Inbox reply queue checkbox (requires **Queue Outgoing Messages** enabled in Admin → Settings)
- Incoming rows in `whatsapp_messages` when **Log Outgoing Messages** is enabled (default)
- Runtime settings UI at `/admin/whatsapp/settings` (database-backed, replaces former `.env` toggles)
